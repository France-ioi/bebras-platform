ALTER TABLE  `contest` ADD  `visibility`  ENUM( 'Visible',  'Hidden' ) NOT NULL DEFAULT  'Hidden' AFTER `status`;
ALTER TABLE  `contest` ADD  `open` ENUM( 'Open',  'Closed' ) NOT NULL DEFAULT  'Open';
ALTER TABLE  `contest` ADD  `showSolutions` TINYINT(1) NOT NULL DEFAULT 0;

ALTER TABLE  `history_contest` ADD  `visibility`  ENUM( 'Visible',  'Hidden' ) NOT NULL DEFAULT  'Hidden' AFTER `status`;
ALTER TABLE  `history_contest` ADD  `open` ENUM( 'Open',  'Closed' ) NOT NULL DEFAULT  'Open';
ALTER TABLE  `history_contest` ADD  `showSolutions` TINYINT(1) NOT NULL DEFAULT 0;

UPDATE `contest` SET `startDate` = NOW(), `endDate` = NOW() + INTERVAL 3 YEAR WHERE `status` = 'RunningContest';
UPDATE `contest` SET `startDate` = NOW() + INTERVAL 2 YEAR, `endDate` = NOW() + INTERVAL 3 YEAR WHERE `status` = 'FutureContest';
UPDATE `contest` SET `startDate` = NOW() - INTERVAL 2 YEAR, `endDate` = NOW() WHERE `status` = 'PastContest';
UPDATE `contest` SET `visibility` = 'Visible' WHERE `status` != 'Hidden';