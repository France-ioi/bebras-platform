ALTER TABLE `contest` ADD `logActivity` TINYINT(1) NOT NULL AFTER `headerHTML`;
ALTER TABLE `history_contest` ADD `logActivity` TINYINT(1) NOT NULL AFTER `headerHTML`;
