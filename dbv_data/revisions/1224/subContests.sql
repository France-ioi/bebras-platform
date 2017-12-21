ALTER TABLE `team` ADD `contestID` BIGINT NULL DEFAULT NULL AFTER `nbContestants`, ADD INDEX `contestID` (`contestID`);
ALTER TABLE `contest` ADD `parentContestID` BIGINT NULL DEFAULT NULL AFTER `showSolutions`, ADD INDEX `parentContestID` (`parentContestID`);
ALTER TABLE `history_contest` ADD `parentContestID` BIGINT NULL DEFAULT NULL AFTER `showSolutions`, ADD INDEX `parentContestID` (`parentContestID`);

ALTER TABLE `contest` ADD `language` VARCHAR(30) NULL DEFAULT NULL AFTER `parentContestID`;
ALTER TABLE `contest` ADD `categoryColor` VARCHAR(30) NULL DEFAULT NULL AFTER `language`;

ALTER TABLE `history_contest` ADD `language` VARCHAR(30) NULL DEFAULT NULL AFTER `parentContestID`;
ALTER TABLE `history_contest` ADD `categoryColor` VARCHAR(30) NULL DEFAULT NULL AFTER `language`;
