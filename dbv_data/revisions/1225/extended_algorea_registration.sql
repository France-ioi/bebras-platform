ALTER TABLE `algorea_registration` ADD `firstName` VARCHAR(50) NOT NULL AFTER `ID`, ADD `lastName` VARCHAR(50) NOT NULL AFTER `firstName`, ADD `genre` TINYINT NOT NULL AFTER `lastName`, ADD `email` VARCHAR(70) NOT NULL AFTER `genre`, ADD `zipCode` VARCHAR(10) NOT NULL AFTER `email`, ADD `grade` INT NOT NULL AFTER `zipCode`, ADD `studentID` VARCHAR(30) NOT NULL AFTER `grade`, ADD `category` VARCHAR(30) NOT NULL AFTER `studentID`, ADD `schoolID` BIGINT NOT NULL AFTER `category`;
ALTER TABLE `algorea_registration` CHANGE `schoolID` `schoolID` BIGINT(20) NULL DEFAULT NULL;
ALTER TABLE `contestant` ADD `registrationID` BIGINT NULL DEFAULT NULL AFTER `algoreaCode`, ADD INDEX `registrationID` (`registrationID`);

ALTER TABLE `group` ADD `isGenerated` TINYINT NOT NULL DEFAULT '0' AFTER `isPublic`;
ALTER TABLE `algorea_registration` ADD `userID` BIGINT NULL DEFAULT NULL AFTER `schoolID`;
ALTER TABLE `algorea_registration` ADD INDEX(`userID`);
ALTER TABLE `group` CHANGE `gradeDetail` `gradeDetail` VARCHAR(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '';
ALTER TABLE `group` CHANGE `noticePrinted` `noticePrinted` TINYINT(4) NOT NULL DEFAULT '0';
ALTER TABLE `group` CHANGE `isPublic` `isPublic` TINYINT(4) NOT NULL DEFAULT '0';
ALTER TABLE `group` CHANGE `iVersion` `iVersion` INT(11) NULL DEFAULT NULL;
ALTER TABLE `group` ADD `parentGroupID` BIGINT NULL DEFAULT NULL AFTER `contestID`, ADD INDEX `parentGroupID` (`parentGroupID`);
ALTER TABLE `contestant` CHANGE `algoreaCategory` `algoreaCategory` VARCHAR(30) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL;