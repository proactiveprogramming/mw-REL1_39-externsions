CREATE TABLE IF NOT EXISTS /*$wgDBprefix*/page_checkout_event (
    `pce_action` VARCHAR(255) NOT NULL,
    `pce_actor_id` INT NOT NULL,
    `pce_page_id` INT NOT NULL,
    `pce_revision_id` INT NOT NULL,
    `pce_timestamp` VARBINARY(14) NULL,
    `pce_comment` TEXT NULL,
    `pce_lock` BLOB NULL
) /*$wgDBTableOptions*/;
