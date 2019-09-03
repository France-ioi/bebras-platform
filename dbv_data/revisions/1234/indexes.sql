ALTER TABLE `group` ADD INDEX `isPublic` (`isPublic`);
ALTER TABLE `contest` ADD INDEX `startDate_endDate` (`startDate`, `endDate`);