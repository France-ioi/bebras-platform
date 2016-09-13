ALTER TABLE  `contest` ADD  `visibility`  ENUM( 'Visible',  'Hidden' ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT  'Hidden' AFTER `status`;

UPDATE `contest` SET `startDate` = NOW(), `endDate` = NOW() + INTERVAL 3 YEAR WHERE `status` = 'RunningContest';
UPDATE `contest` SET `startDate` = NOW() + INTERVAL 2 YEAR, `endDate` = NOW() + INTERVAL 3 YEAR WHERE `status` = 'FutureContest';
UPDATE `contest` SET `startDate` = NOW() - INTERVAL 2 YEAR, `endDate` = NOW() WHERE `status` = 'PastContest';
UPDATE `contest` SET `visibilty` = 'Visible' WHERE `status` != 'Hidden';

ALTER TABLE  `contest` CHANGE  `status`  `status` ENUM(  'Open',  'Closed' ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT  'Open';

UPDATE `contest` SET `status` = 'Open';
