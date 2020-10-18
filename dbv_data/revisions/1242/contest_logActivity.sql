ALTER TABLE `contest` ADD `logActivity` TEXT NOT NULL AFTER `headerHTML`;
ALTER TABLE `history_contest` ADD `logActivity` TEXT NOT NULL AFTER `headerHTML`;
