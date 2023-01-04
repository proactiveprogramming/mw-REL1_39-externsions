CREATE TABLE dp (
    `dp_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
    `dp_title` varchar(255) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
    `dp_timestamp` binary(14) NOT NULL DEFAULT '\0\0\0\0\0\0\0\0\0\0\0\0\0\0',
    PRIMARY KEY (`dp_id`),
    UNIQUE KEY `dp_title` (`dp_title`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
