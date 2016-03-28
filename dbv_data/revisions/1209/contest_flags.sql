ALTER TABLE `contest` ADD `bHidden` TINYINT(1) NOT NULL DEFAULT '0' COMMENT 'hidden to teachers' AFTER `status`;
ALTER TABLE `contest` ADD `bContestMode` TINYINT(1) NOT NULL DEFAULT '0' COMMENT 'has official participations' AFTER `bHidden`;
ALTER TABLE `contest` ADD `contestTime` ENUM('past','present','future') NOT NULL DEFAULT 'future'  AFTER `bContestMode`;
ALTER TABLE `contest` ADD `bOpen` TINYINT(1) NOT NULL DEFAULT '0' COMMENT 'open to contestants' AFTER `contestTime`;

ALTER TABLE `history_contest` ADD `bHidden` TINYINT(1) NOT NULL DEFAULT '0' AFTER `status`;
ALTER TABLE `history_contest` ADD `bContestMode` TINYINT(1) NOT NULL DEFAULT '0' AFTER `bHidden`;
ALTER TABLE `history_contest` ADD `contestTime` ENUM('past','present','future') NOT NULL DEFAULT 'future'  AFTER `bContestMode`;
ALTER TABLE `history_contest` ADD `bOpen` TINYINT(1) NOT NULL DEFAULT '0' AFTER `contestTime`;

UPDATE `contest` SET bHidden=1, bContestMode=0, contestTime='past', bOpen=1 WHERE status = 'Hidden';
UPDATE `contest` SET bHidden=1, bContestMode=0, contestTime='past', bOpen=0 WHERE status = 'Closed';
UPDATE `contest` SET bHidden=0, bContestMode=0, contestTime='past', bOpen=1 WHERE status = 'Other';
UPDATE `contest` SET bHidden=0, bContestMode=0, contestTime='past', bOpen=1 WHERE status = 'PastContest';
UPDATE `contest` SET bHidden=0, bContestMode=1, contestTime='past', bOpen=0 WHERE status = 'PreRanking';
UPDATE `contest` SET bHidden=0, bContestMode=1, contestTime='present', bOpen=1 WHERE status = 'RunningContest';
UPDATE `contest` SET bHidden=0, bContestMode=1, contestTime='future', bOpen=0 WHERE status = 'FutureContest';