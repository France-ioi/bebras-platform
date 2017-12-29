ALTER TABLE `group` ADD `minCategory` VARCHAR(30) NOT NULL AFTER `contestID`, ADD `maxCategory` VARCHAR(30) NOT NULL AFTER `minCategory`, ADD `language` VARCHAR(30) NOT NULL AFTER `maxCategory`;
