<?php
/** Extension:NewDuplicateUserMessage
 *
 * @file
 * @ingroup Extensions
 *
 * @license GPL-2.0-or-later
 */

/**
 * Job to create the initial message on a user's talk page
 *
 * Required parameters:
 *   - userId: the user ID
 */
class NewDuplicateUserMessageJob extends Job implements GenericParameterJob {
    /**
     * @param array $params
     */
    public function __construct(array $params) {
        parent::__construct('NewDuplicateUserMessageJob', $params);
    }

    public function run() {
        $user = User::newFromId($this->params['userId']);
        $user->load($user::READ_LATEST);
        if (!$user->getId()) {
            return false;
        }

        NewDuplicateUserMessage::createNewDuplicateUserMessage($user);

        return true;
    }
}
