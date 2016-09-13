ALTER TABLE `contest` ADD `startDate` DATETIME NOT NULL DEFAULT 0  AFTER `printCodes`;
ALTER TABLE `contest` ADD `endDate` DATETIME NOT NULL DEFAULT 0  AFTER `startDate`;

