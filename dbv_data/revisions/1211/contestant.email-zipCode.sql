ALTER TABLE `contestant` ADD `email` VARCHAR(70) NULL DEFAULT NULL AFTER `genre`, ADD `zipCode` VARCHAR(10) NULL DEFAULT NULL AFTER `email`;
ALTER TABLE `history_contestant` ADD `email` VARCHAR(70) NULL DEFAULT NULL AFTER `genre`, ADD `zipCode` VARCHAR(10) NULL DEFAULT NULL AFTER `email`;

ALTER TABLE `contestant` CHANGE `genre` `genre` INT(11) NULL DEFAULT NULL;
ALTER TABLE `history_contestant` CHANGE `genre` `genre` INT(11) NULL DEFAULT NULL;