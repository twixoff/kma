CREATE TABLE IF NOT EXISTS `results`
(
 `id`               INT(10) AUTO_INCREMENT,
 `url`              VARCHAR(255) NOT NULL,
 `code`             INT NOT NULL,
 `header`           TEXT NOT NULL,
 `body`             LONGTEXT NOT NULL,
 `created_at`       Datetime DEFAULT CURRENT_TIMESTAMP(),
    PRIMARY KEY (`id`)
) DEFAULT CHARSET=utf8 COLLATE=utf8_bin;