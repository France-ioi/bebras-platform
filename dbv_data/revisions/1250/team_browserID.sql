ALTER TABLE `team` ADD `browserID` INT NULL DEFAULT NULL AFTER `finalAnswerTime`;
ALTER TABLE `history_team` ADD `browserID` INT NULL DEFAULT NULL AFTER `finalAnswerTime`;