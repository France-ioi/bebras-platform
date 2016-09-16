ALTER TABLE `contest` ADD `startDate` DATETIME DEFAULT NULL  AFTER `printCodes`;
ALTER TABLE `contest` ADD `endDate` DATETIME DEFAULT NULL  AFTER `startDate`;
ALTER TABLE `history_contest` ADD `startDate` DATETIME DEFAULT NULL  AFTER `printCodes`;
ALTER TABLE `history_contest` ADD `endDate` DATETIME DEFAULT NULL  AFTER `startDate`;