ALTER TABLE `contest` ADD `sendPings` TINYINT(1) NOT NULL DEFAULT '0' AFTER `logActivity`;
ALTER TABLE `history_contest` ADD `sendPings` TINYINT(1) NOT NULL DEFAULT '0' AFTER `logActivity`;