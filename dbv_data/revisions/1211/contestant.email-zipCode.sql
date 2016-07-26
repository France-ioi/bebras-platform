ALTER TABLE `contestant` ADD `email` VARCHAR(70) NULL DEFAULT NULL AFTER `genre`, ADD `zipCode` VARCHAR(10) NULL DEFAULT NULL AFTER `email`;
ALTER TABLE `history_contestant` ADD `email` VARCHAR(70) NULL DEFAULT NULL AFTER `genre`, ADD `zipCode` VARCHAR(10) NULL DEFAULT NULL AFTER `email`;

ALTER TABLE `contestant` CHANGE `genre` `genre` INT(11) NULL DEFAULT NULL;
ALTER TABLE `history_contestant` CHANGE `genre` `genre` INT(11) NULL DEFAULT NULL;

ALTER TABLE `contest` ADD `askEmail` TINYINT(1) NOT NULL DEFAULT '0' AFTER `showResults`, ADD `askZip` TINYINT(1) NOT NULL DEFAULT '0' AFTER `askEmail`, ADD `askGenre` TINYINT(1) NOT NULL DEFAULT '1' AFTER `askZip`, ADD `askGrade` TINYINT(1) NOT NULL DEFAULT '1' AFTER `askGenre`;
ALTER TABLE `history_contest` ADD `askEmail` TINYINT(1) NOT NULL DEFAULT '0' AFTER `showResults`, ADD `askZip` TINYINT(1) NOT NULL DEFAULT '0' AFTER `askEmail`, ADD `askGenre` TINYINT(1) NOT NULL DEFAULT '1' AFTER `askZip`, ADD `askGrade` TINYINT(1) NOT NULL DEFAULT '1' AFTER `askGenre`;