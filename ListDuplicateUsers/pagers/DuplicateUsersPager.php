<?php
/**
 * Copyright © 2004 Brion Vibber, lcrocker, Tim Starling,
 * Domas Mituzas, Antoine Musso, Jens Frank, Zhengzhu,
 * 2006 Rob Church <robchur@gmail.com>
 * 2019 David Wong
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @file
 * @ingroup Pager
 */

use Wikimedia\Rdbms\Subquery;

/**
 * This class is used to get a list of user. The ones with specials
 * rights (sysop, bureaucrat, developer) will have them displayed
 * next to their names.
 *
 * @ingroup Pager
 */
class DuplicateUsersPager extends AlphabeticPager {
    /**
     * @var array[] A array with user ids as key and a array of groups as value
     */
    protected $userGroupCache;

    /**
     * @param IContextSource $context
     * @param array $par (Default null)
     * @param bool $including Whether this page is being transcluded in.
     * another page
     */
    function __construct(IContextSource $context = null, $par = null, $including = null) {
        if ($context) {
            $this->setContext($context);
        }

        $request = $this->getRequest();
        $par = ($par !== null ? $par : '');
        $parms = explode('/', $par);
        $symsForAll = ['*', 'user'];

        if ($parms[0] != '' &&
            (in_array($par, User::getAllGroups()) || in_array($par, $symsForAll))
        ) {
            $this->requestedGroup = $par;
            $un = $request->getText('username');
        } elseif (count($parms) == 2) {
            $this->requestedGroup = $parms[0];
            $un = $parms[1];
        } else {
            $this->requestedGroup = $request->getVal('group');
            $un = ($par != '' ? $par : $request->getText('username'));
        }

        if (in_array($this->requestedGroup, $symsForAll)) {
            $this->requestedGroup = '';
        }

        $this->showBlocked = ($request->getVal('showBlocked') === null && $request->getVal('wpsubmit') === null ? true : $request->getBool('showBlocked'));
        $this->editsOnly = $request->getBool('editsOnly');
        $this->creationSort = $request->getBool('creationSort');
        $this->including = $including;
        $this->mDefaultDirection = ($request->getBool('desc')
            ? IndexPager::DIR_DESCENDING
            : IndexPager::DIR_ASCENDING);

        $this->requestedUser = '';

        if ($un != '') {
            $username = Title::makeTitleSafe(NS_USER, $un);

            if (!is_null($username)) {
                $this->requestedUser = $username->getText();
            }
        }

        parent::__construct();
    }

    /**
     * @return string|string[]|array[]
     */
    function getIndexField() {
        return ($this->creationSort ? 'user_id' : 'id');
    }

    /**
     * @return array
     */
    function getQueryInfo() {
        $dbr = wfGetDB(DB_REPLICA);
        $conds = [];

        // Don't show hidden names.
        if (!$this->getUser()->isAllowed('hideuser')) {
            $conds[] = 'ipb_deleted IS NULL OR ipb_deleted = 0';
        }

        $options = [];

        if ($this->requestedGroup != '') {
            $conds['ug_group'] = $this->requestedGroup;
            $conds[] = 'ug_expiry IS NULL OR ug_expiry >= ' . $dbr->addQuotes($dbr->timestamp());
        }

        if ($this->requestedUser != '') {
            // Sorted either by account creation or name.
            if ($this->creationSort) {
                $conds[] = 'user_id >= ' . intval(User::idFromName($this->requestedUser));
            } else {
                $conds[] = 'user_name >= ' . $dbr->addQuotes($this->requestedUser);
            }
        }

//        $output = $this->getOutput();
//        $output->addHTML($this->showBlocked);

        if (!$this->showBlocked) {
            $conds[] = 'ipb_deleted IS NULL';
        }

        if ($this->editsOnly) {
            $conds[] = 'user_editcount > 0';
        }

        $options['GROUP BY'] = ($this->creationSort ? 'user_id' : 'user_name');

        // B query.
        $bQuery = [
            'tables' => ['user'],
            'fields' => [
                'min_user_id' => 'min(user_id)',
                'user_email'
            ],
            'conds' => ['char_length(user_email) > 0'],
            'options' => [
                'GROUP BY' => 'user_email',
                'HAVING' => 'count(*) > 1'
            ],
            'join_conds' => []
        ];
        $bSqlText = $dbr->selectSQLText($bQuery['tables'], $bQuery['fields'], $bQuery['conds'], __METHOD__, $bQuery['options'], $bQuery['join_conds']);

        // A query.
        $aQuery = [
            'tables' => [
                'a' => 'user',
                'b' => new Subquery($bSqlText),
                'user_groups',
                'ipblocks'
            ],
            'fields' => [
                'user_id' => ($this->creationSort ? 'user_id' : 'max(user_id)'),
                'b.min_user_id',
                'user_name' => ($this->creationSort ? 'max(user_name)' : 'user_name'),
                'user_email' => ($this->creationSort ? 'max(a.user_email)' : 'a.user_email'),
                'edits' => 'max(user_editcount)',
                'creation' => 'min(user_registration)',
                'ipb_deleted' => 'max(ipb_deleted)' // Block/hide status.
            ],
            'options' => $options,
            'join_conds' => [
                'b' => ['JOIN', 'a.user_email = b.user_email'],
                'user_groups' => ['LEFT JOIN', 'user_id = ug_user'],
                'ipblocks' => ['LEFT JOIN', [
                        'user_id = ipb_user',
                        'ipb_auto' => 0
                    ]
                ]
            ],
            'conds' => $conds
        ];
        $aSqlText = $dbr->selectSQLText($aQuery['tables'], $aQuery['fields'], $aQuery['conds'], __METHOD__, $aQuery['options'], $aQuery['join_conds']);

        // Order query.
        $orderQuery = [
            'tables' => [
                'r' => new Subquery('SELECT @row_number := 0'),
                't' => new Subquery($aSqlText)
            ],
            'fields' => [
                'id' => new Subquery('@row_number := @row_number + 1'),
                't.user_id',
                't.min_user_id',
                't.user_name',
                't.user_email',
                't.edits',
                't.creation',
                't.ipb_deleted'
            ],
            'conds' => [],
            'options' => ['ORDER BY' => ['min_user_id', 'user_id']],
            'join_conds' => []
        ];
        $orderSqlText = $dbr->selectSQLText($orderQuery['tables'], $orderQuery['fields'], $orderQuery['conds'], __METHOD__, $orderQuery['options'], $orderQuery['join_conds']);

        // Wrapper query.
        $query = [
            'tables' => [
                'o' => new Subquery($orderSqlText)
            ],
            'fields' => [
                'o.id',
                'o.user_id',
                'o.user_name',
                'o.user_email',
                'o.edits',
                'o.creation',
                'o.ipb_deleted'
            ],
            'conds' => [],
            'options' => [],
            'join_conds' => []
        ];
//        $sqlText = $dbr->selectSQLText($query['tables'], $query['fields'], $query['conds'], __METHOD__, $query['options'], $query['join_conds']);
//        $output = $this->getOutput();
//        $output->addHTML("\r\n" . $sqlText . "\r\n\r\n\r\n<br/><br/>");

        Hooks::run('SpecialListusersQueryInfo', [$this, &$query]);

        return $query;
    }

    /**
     * @param stdClass $row
     * @return string
     */
    function formatRow($row) {
        if ($row->user_id == 0) { # T18487
            return '';
        }

        $userName = $row->user_name;

        $ulinks = Linker::userLink($row->user_id, $userName);
        $ulinks .= Linker::userToolLinksRedContribs(
            $row->user_id,
            $userName,
            (int) $row->edits
        );

        $lang = $this->getLanguage();

        $groups = '';
        $ugms = self::getGroupMemberships(intval($row->user_id), $this->userGroupCache);

        if (!$this->including && count($ugms) > 0) {
            $list = [];
            foreach ($ugms as $ugm) {
                $list[] = $this->buildGroupLink($ugm, $userName);
            }
            $groups = $lang->commaList($list);
        }

        $item = $lang->specialList($ulinks, $groups);

        $wordSeparator = $this->msg('word-separator')->escaped();

        $userEmail = $row->user_email;
        $email = $wordSeparator . Html::rawElement('a', [
            'class' => 'email mw-useremail',
            'href' => "mailto:$userEmail",
            'data-email' => $userEmail
        ], $userEmail);

        if ($row->ipb_deleted) {
            $item = "<span class=\"deleted\">$item</span>";
        }

        $edits = '';
        if (!$this->including && $this->getConfig()->get('Edititis')) {
            $count = $this->msg('usereditcount')->numParams($row->edits)->escaped();
            $edits = $wordSeparator . $this->msg('brackets', $count)->escaped();
        }

        $created = '';
        // Some rows may be null.
        if (!$this->including && $row->creation) {
            $user = $this->getUser();
            $d = $lang->userDate($row->creation, $user);
            $t = $lang->userTime($row->creation, $user);
            $created = $this->msg('usercreated', $d, $t, $row->user_name)->escaped();
            $created = $wordSeparator . $this->msg('parentheses')->rawParams($created)->escaped();
        }
        $blocked = (!is_null($row->ipb_deleted) ?
            $wordSeparator . $this->msg('listusers-blocked', $userName)->escaped() :
            '');

        Hooks::run('SpecialListusersFormatRow', [&$item, $row]);

        return Html::rawElement('li', [], "{$item}{$email}{$edits}{$created}{$blocked}");
    }

    function doBatchLookups() {
        $batch = new LinkBatch();
        $userIds = [];
        // Give some pointers to make user links.
        foreach ($this->mResult as $row) {
            $batch->add(NS_USER, $row->user_name);
            $batch->add(NS_USER_TALK, $row->user_name);
            $userIds[] = $row->user_id;
        }

        // Lookup groups for all the users.
        $dbr = wfGetDB(DB_REPLICA);
        $groupRes = $dbr->select(
            'user_groups',
            UserGroupMembership::selectFields(),
            ['ug_user' => $userIds],
            __METHOD__
        );
        $cache = [];
        $groups = [];
        foreach ($groupRes as $row) {
            $ugm = UserGroupMembership::newFromRow($row);
            if (!$ugm->isExpired()) {
                $cache[$row->ug_user][$row->ug_group] = $ugm;
                $groups[$row->ug_group] = true;
            }
        }

        // Give extensions a chance to add things like global user group data
        // into the cache array to ensure proper output later on.
        Hooks::run('UsersPagerDoBatchLookups', [$dbr, $userIds, &$cache, &$groups]);

        $this->userGroupCache = $cache;

        // Add page of groups to link batch.
        foreach ($groups as $group => $unused) {
            $groupPage = UserGroupMembership::getGroupPage($group);
            if ($groupPage) {
                $batch->addObj($groupPage);
            }
        }

        $batch->execute();
        $this->mResult->rewind();
    }

    /**
     * @return string
     */
    function getPageHeader() {
        list($self) = explode('/', $this->getTitle()->getPrefixedDBkey());

        $groupOptions = [$this->msg('group-all')->text() => ''];
        foreach ($this->getAllGroups() as $group => $groupText) {
            $groupOptions[$groupText] = $group;
        }

        $formDescriptor = [
            'user' => [
                'class' => HTMLUserTextField::class,
                'label' => $this->msg('listusersfrom')->text(),
                'name' => 'username',
                'default' => $this->requestedUser,
            ],
            'dropdown' => [
                'label' => $this->msg('group')->text(),
                'name' => 'group',
                'default' => $this->requestedGroup,
                'class' => HTMLSelectField::class,
                'options' => $groupOptions,
            ],
            'showBlocked' => [
                'type' => 'check',
                'label' => $this->msg('listduplicateusers-showblocked')->text(),
                'name' => 'showBlocked',
                'id' => 'showBlocked',
                'default' => $this->showBlocked
            ],
            'editsOnly' => [
                'type' => 'check',
                'label' => $this->msg('listusers-editsonly')->text(),
                'name' => 'editsOnly',
                'id' => 'editsOnly',
                'default' => $this->editsOnly
            ],
            'creationSort' => [
                'type' => 'check',
                'label' => $this->msg('listusers-creationsort')->text(),
                'name' => 'creationSort',
                'id' => 'creationSort',
                'default' => $this->creationSort
            ],
            'desc' => [
                'type' => 'check',
                'label' => $this->msg('listusers-desc')->text(),
                'name' => 'desc',
                'id' => 'desc',
                'default' => $this->mDefaultDirection
            ],
            'limithiddenfield' => [
                'class' => HTMLHiddenField::class,
                'name' => 'limit',
                'default' => $this->mLimit
            ]
        ];

        $beforeSubmitButtonHookOut = '';
        Hooks::run('SpecialListusersHeaderForm', [$this, &$beforeSubmitButtonHookOut]);

        if ($beforeSubmitButtonHookOut !== '') {
            $formDescriptor['beforeSubmitButtonHookOut'] = [
                'class' => HTMLInfoField::class,
                'raw' => true,
                'default' => $beforeSubmitButtonHookOut
            ];
        }

        $formDescriptor['submit'] = [
            'class' => HTMLSubmitField::class,
            'buttonlabel-message' => 'listusers-submit',
        ];

        $beforeClosingFieldsetHookOut = '';
        Hooks::run('SpecialListusersHeader', [$this, &$beforeClosingFieldsetHookOut]);

        if ($beforeClosingFieldsetHookOut !== '') {
            $formDescriptor['beforeClosingFieldsetHookOut'] = [
                'class' => HTMLInfoField::class,
                'raw' => true,
                'default' => $beforeClosingFieldsetHookOut
            ];
        }

        $htmlForm = HTMLForm::factory('ooui', $formDescriptor, $this->getContext());
        $htmlForm
            ->setMethod('get')
            ->setAction(Title::newFromText($self)->getLocalURL())
            ->setId('mw-listusers-form')
            ->setFormIdentifier('mw-listusers-form')
            ->suppressDefaultSubmit()
            ->setWrapperLegendMsg('listusers');
        return $htmlForm->prepareForm()->getHTML(true);
    }

    /**
     * Get a list of all explicit groups.
     * @return array
     */
    function getAllGroups() {
        $result = [];
        foreach (User::getAllGroups() as $group) {
            $result[$group] = UserGroupMembership::getGroupName($group);
        }
        asort($result);

        return $result;
    }

    /**
     * Preserve group and username offset parameters when paging.
     * @return array
     */
    function getDefaultQuery() {
        $query = parent::getDefaultQuery();
        if ($this->requestedGroup != '') {
            $query['group'] = $this->requestedGroup;
        }
        if ($this->requestedUser != '') {
            $query['username'] = $this->requestedUser;
        }
        Hooks::run('SpecialListusersDefaultQuery', [$this, &$query]);

        return $query;
    }

    /**
     * Get an associative array containing groups the specified user belongs to,
     * and the relevant UserGroupMembership objects.
     *
     * @param int $uid User id
     * @param array[]|null $cache
     * @return UserGroupMembership[] (group name => UserGroupMembership object)
     */
    protected static function getGroupMemberships($uid, $cache = null) {
        if ($cache === null) {
            $user = User::newFromId($uid);
            return $user->getGroupMemberships();
        } else {
            return isset($cache[$uid]) ? $cache[$uid] : [];
        }
    }

    /**
     * Format a link to a group description page.
     *
     * @param string|UserGroupMembership $group Group name or UserGroupMembership object.
     * @param string $username
     * @return string
     */
    protected function buildGroupLink($group, $username) {
        return UserGroupMembership::getLink($group, $this->getContext(), 'html', $username);
    }
}
