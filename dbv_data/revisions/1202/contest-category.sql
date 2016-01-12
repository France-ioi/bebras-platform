ALTER TABLE `contest` ADD `category` VARCHAR(50) NOT NULL AFTER `year`;
ALTER TABLE `history_contest` ADD `category` VARCHAR(50) NOT NULL AFTER `year`;