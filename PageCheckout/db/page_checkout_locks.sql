CREATE TABLE IF NOT EXISTS /*$wgDBprefix*/page_checkout_locks (
    `pcl_id` int unsigned NOT NULL PRIMARY KEY AUTO_INCREMENT,
    `pcl_page_id` INT NOT NULL,
    `pcl_user_id` INT NOT NULL,
    `pcl_payload` BLOB NULL
) /*$wgDBTableOptions*/;
