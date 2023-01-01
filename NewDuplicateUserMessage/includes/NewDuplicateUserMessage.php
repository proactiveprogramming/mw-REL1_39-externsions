<?php
/** Extension:NewDuplicateUserMessage
 *
 * @file
 * @ingroup Extensions
 *
 * @author [http://www.organicdesign.co.nz/nad User:Nad]
 * @license GPL-2.0-or-later
 * @copyright 2007-10-15 [http://www.organicdesign.co.nz/nad User:Nad]
 * @copyright 2009 Siebrand Mazeland
 * @copyright 2019 David Wong
 */

class NewDuplicateUserMessage {
    /**
     * Produce the editor for new user messages.
     * @return User|bool
     */
    private static function fetchEditor() {
        // Create a user object for the editing user and add it to the
        // database if it is not there already.
        $editor = User::newFromName(self::getMsg('newduplicateusermessage-editor')->text());

        if (!$editor) {
            return false; // Invalid user name.
        }

        if (!$editor->isLoggedIn()) {
            $editor->addToDatabase();
        }

        return $editor;
    }

    /**
     * Produce a (possibly random) signature.
     * @return String
     */
    private static function fetchSignature() {
        $signatures = self::getMsg('newduplicateusermessage-signatures')->text();
        $signature = '';

        if (!self::getMsg('newduplicateusermessage-signatures')->isDisabled()) {
            $pattern = '/^\* ?(.*?)$/m';
            $signatureList = [];
            preg_match_all($pattern, $signatures, $signatureList, PREG_SET_ORDER);
            if (count($signatureList) > 0) {
                $rand = rand(0, count($signatureList) - 1);
                $signature = $signatureList[$rand][1];
            }
        }

        return $signature;
    }

    /**
     * Return the template name if it exists, or '' otherwise.
     * @param string $template string with page name of user message template.
     * @return string
     */
    private static function fetchTemplateIfExists($template) {
        $text = Title::newFromText($template);

        if (!$text) {
            wfDebug(__METHOD__ . ": '$template' is not a valid title.\n");
            return '';
        }

        if ($text->getNamespace() !== NS_TEMPLATE) {
            wfDebug(__METHOD__ . ": '$template' is not a valid Template.\n");
            return '';
        }

        if (!$text->exists()) {
            return '';
        }

        return $text->getText();
    }

    /**
     * Produce a subject for the message.
     * @return String
     */
    private static function fetchSubject() {
        return self::fetchTemplateIfExists(
            self::getMsg('newduplicateusermessage-template-subject')->text()
        );
    }

    /**
     * Produce the template that contains the text of the message.
     * @return String
     */
    private static function fetchText() {
        $template = self::getMsg('newduplicateusermessage-template-body')->text();

        $title = Title::newFromText($template);
        if ($title && $title->exists() && $title->getLength()) {
            return $template;
        }

        // Fall back if necessary to the old template.
        return self::getMsg('newduplicateusermessage-template')->text();
    }

    /**
     * Produce the flags to set on Article::doEditContent.
     * @param $page
     * @return Int
     */
    private static function fetchFlags($page) {
        global $wgNewDuplicateUserMinorEdit, $wgNewDuplicateUserSuppressRC;

        $flags = ($page->exists() ? EDIT_UPDATE : EDIT_NEW);

        if ($wgNewDuplicateUserMinorEdit) {
            $flags |= EDIT_MINOR;
        }

        if ($wgNewDuplicateUserSuppressRC) {
            $flags |= EDIT_SUPPRESS_RC;
        }

        return $flags;
    }

    /**
     * Take care of substitution on the string in a uniform manner.
     * @param string $str
     * @param User $user
     * @param User $editor
     * @param Title $talk
     * @param bool $preparse If provided, then preparse the string using a Parser.
     * @return string
     */
    private static function substString($str, $user, $duplicateUsers, $editor, $talk, $preparse = null) {
        $realName = $user->getRealName();
        $name = $user->getName();

        // Add (any) content to [[MediaWiki:Newduplicateusermessage-substitute]] to substitute the
        // welcome template.
        $substDisabled = self::getMsg('newduplicateusermessage-substitute')->isDisabled();

        $duplicateUserText = join(", ", array_map(function ($duplicateUser) {
            return "[[User:$duplicateUser|$duplicateUser]]";
        }, $duplicateUsers));

        $str = '{{' . (!$substDisabled ? 'subst:' : '') . "$str|realName=$realName|name=$name|duplicateUsers=$duplicateUserText}}";

        if ($preparse) {
            global $wgParser;

            $str = $wgParser->preSaveTransform($str, $talk, $editor, new ParserOptions);
        }

        return $str;
    }

    private static function getDuplicateUsers($user) {
        $dbr = wfGetDB(DB_REPLICA);
        $userTable = $dbr->tableName('user');
        $name = $dbr->addQuotes($user->getName());
        $email = $dbr->addQuotes($user->getEmail());
        $result = $dbr->query("
                select user_name, user_email
                from $userTable
                where user_email = $email and user_name != $name
            ",
            __METHOD__
        );

        $users = [];
        foreach ($result as $row) {
            $users[] = $row->user_name;
        }

        return $users;
    }

    /**
     * Add the message to the user's talk page.
     * @param User $user User object.
     * @return bool
     */
    public static function createNewDuplicateUserMessage($user) {
        $email = $user->getEmail();
        if (strlen($email) <= 0) {
            return true;
        }

        $users = self::getDuplicateUsers($user);
        if (count($users) <= 0) {
            return true;
        }

        $talk = $user->getTalkPage();

        $wikiPage = WikiPage::factory($talk);
        $subject = self::fetchSubject();
        $text = self::fetchText();
        $signature = self::fetchSignature();
        $editSummary = self::getMsg('newduplicateusermessage-edit-summary')->text();
        $editor = self::fetchEditor();
        $flags = self::fetchFlags($talk);

        // Do not add a message if the username is invalid or if the account that adds it,
        // is blocked.
        if (!$editor || $editor->isBlocked()) {
            return true;
        }

        if ($subject) {
            $subject = self::substString($subject, $user, $users, $editor, $talk, "preparse");
        }

        if ($text) {
            $text = self::substString($text, $user, $users, $editor, $talk);
        }

        self::leaveUserMessage(
            $user,
            $wikiPage,
            $subject,
            $text,
            $signature,
            $editSummary,
            $editor,
            $flags
        );

        return true;
    }

    /**
     * Hook function to create new user pages when an account is created or autocreated.
     * @param User $user object of the user.
     * @param bool $autocreated
     * @return bool
     */
    public static function onLocalUserCreated(User $user, $autocreated) {
        global $wgNewDuplicateUserMessageOnAutoCreate;

        if (!$autocreated) {
            DeferredUpdates::addCallableUpdate(
                function () use ($user) {
                    // Not a human.
                    if ($user->isBot()) {
                        return;
                    }

                    NewDuplicateUserMessage::createNewDuplicateUserMessage($user);
                },
                ($autocreated ? DeferredUpdates::POSTSEND : DeferredUpdates::PRESEND)
            );
        } elseif ($wgNewDuplicateUserMessageOnAutoCreate) {
            JobQueueGroup::singleton()->lazyPush(
                new NewDuplicateUserMessageJob(['userId' => $user->getId()])
            );
        }

        return true;
    }

    /**
     * Hook function to provide a reserved name.
     * @param array &$names
     * @return bool
     */
    public static function onUserGetReservedNames(&$names) {
        $names[] = 'msg:newduplicateusermessage-editor';
        return true;
    }

    /**
     * Leave a user a message.
     * @param User $user User to message.
     * @param WikiPage $wikiPage user talk page.
     * @param string $subject string with the subject of the message.
     * @param string $text string with the message to leave.
     * @param string $signature string to leave in the signature.
     * @param string $summary string with the summary for this change, defaults to
     *                        "Leave system message."
     * @param User $editor User leaving the message, defaults to
     *                        "{{MediaWiki:newduplicateusermessage-editor}}".
     * @param int $flags default edit flags.
     *
     * @return bool true if it was successful.
     */
    public static function leaveUserMessage(
        $user,
        $wikiPage,
        $subject,
        $text,
        $signature,
        $summary,
        $editor,
        $flags
    ) {
        $text = self::formatUserMessage($subject, $text, $signature);
        $flags = $wikiPage->checkFlags($flags);

        if ($flags & EDIT_UPDATE) {
            $text = ContentHandler::getContentText($wikiPage->getContent(Revision::RAW)) . "\n\n" . $text;
        }

        $content = ContentHandler::makeContent($text, $wikiPage->getTitle());

        $status = $wikiPage->doEditContent(
            $content,
            $summary,
            $flags,
            false,
            $editor
        );

        return $status->isGood();
    }

    /**
     * Format the user message using a hook, a template, or, failing these, a static format.
     * @param string $subject the subject of the message
     * @param string $text the content of the message
     * @param string $signature the signature, if provided.
     * @return string in wiki text with complete user message
     */
    protected static function formatUserMessage($subject, $text, $signature) {
        $contents = '';
        $signature = (empty($signature) ? '~~~~' : "{$signature} ~~~~~");

        if ($subject) {
            $contents .= "== $subject ==\n\n";
        }
        $contents .= "$text\n\n-- $signature\n";

        return $contents;
    }

    /**
     * @param string $name
     * @return Message
     */
    protected static function getMsg($name) {
        return wfMessage($name)->inContentLanguage();
    }
}
