ALTER TABLE `contest` ADD `rankTimes` TINYINT(1) NOT NULL DEFAULT '0' AFTER `rankNbContestants`;
ALTER TABLE `history_contest` ADD `rankTimes` TINYINT(1) NOT NULL DEFAULT '0' AFTER `rankNbContestants`;
