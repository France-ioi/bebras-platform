ALTER TABLE `contest` ADD `qualificationCategory` VARCHAR(30) NULL DEFAULT NULL AFTER `categoryColor`, ADD `qualificationScore` INT NULL DEFAULT NULL AFTER `qualificationCategory`;
ALTER TABLE `contest` CHANGE `name` `name` VARCHAR(100) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;

ALTER TABLE `history_contest` ADD `qualificationCategory` VARCHAR(30) NULL DEFAULT NULL AFTER `categoryColor`, ADD `qualificationScore` INT NULL DEFAULT NULL AFTER `qualificationCategory`;
ALTER TABLE `history_contest` CHANGE `name` `name` VARCHAR(100) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;
