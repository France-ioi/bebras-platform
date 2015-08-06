ALTER TABLE  `contest` CHANGE  `status`  `status_old` INT( 11 ) NOT NULL;
ALTER TABLE  `contest` ADD  `status` ENUM(  'FutureContest',  'RunningContest',  'PastContest',  'Other',  'Hidden' ) NOT NULL DEFAULT  'Hidden' AFTER  `year` ,
ADD INDEX (  `status` );

ALTER TABLE  `group` ADD  `participationType` ENUM(  'Official',  'Unofficial' ) DEFAULT NULL AFTER  `isUnofficial` ,
ADD INDEX (  `participationType` );
UPDATE `group` SET `participationType` = 'Official' WHERE `isUnofficial` = 2;
UPDATE `group` SET `participationType` = 'Unofficial' WHERE `isUnofficial` = 1;
ALTER TABLE  `group` DROP  `isUnofficial`;

ALTER TABLE  `team` ADD  `participationType` ENUM(  'Official',  'Unofficial' ) DEFAULT NULL AFTER  `isUnofficial` ,
ADD INDEX (  `participationType` );
UPDATE `team` SET `participationType` = 'Official' WHERE `isUnofficial` = 2;
UPDATE `team` SET `participationType` = 'Unofficial' WHERE `isUnofficial` = 1;
ALTER TABLE  `team` DROP  `isUnofficial`;

UPDATE `group` LEFT JOIN `contest` ON (`group`.`contestID` = `contest`.`ID`) SET `group`.`participationType` = 'Unofficial' WHERE `contest`.`year` <> 2012;
UPDATE `team` LEFT JOIN `group` ON (`team`.`groupID` = `group`.`ID`) SET `team`.`participationType` = 'Unofficial' WHERE `group`.`participationType` = 'Unofficial';

# This is specific to the sample data (and french database). Noone else is using the platform with their own contests yet.
UPDATE `group` SET `contestID` = `contestID` - 10 WHERE `contestID` >= 16 AND `contestID` <= 19;
DELETE FROM `contest` WHERE `ID` >= 16 AND `ID` <= 19;
UPDATE `contest` SET `year` = `year` - 100000 WHERE `year` > 100000;
UPDATE `contest` SET `status` = 'PastContest';
ALTER TABLE `contest` DROP `status_old`;


