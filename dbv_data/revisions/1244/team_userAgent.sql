ALTER TABLE `team` ADD `userAgent` VARCHAR(300) NOT NULL AFTER `participationType`;
ALTER TABLE `history_team` ADD `userAgent` VARCHAR(300) NOT NULL AFTER `participationType`;
