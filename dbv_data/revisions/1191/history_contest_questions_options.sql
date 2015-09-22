ALTER TABLE `history_contest_question` ADD `options` VARCHAR(200) NOT NULL DEFAULT '{}' AFTER `order`;
ALTER TABLE `history_user` ADD `awardPrintingDate` DATE NULL DEFAULT NULL AFTER `validated`;

ALTER TABLE `user` CHANGE `ID` `ID` BIGINT(20) NOT NULL;
ALTER TABLE `history_user` CHANGE `ID` `ID` BIGINT(20) NOT NULL;
