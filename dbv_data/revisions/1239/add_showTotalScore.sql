ALTER TABLE `contest` ADD `showTotalScore` BOOLEAN NOT NULL DEFAULT TRUE AFTER `fullFeedback`;
ALTER TABLE `history_contest` ADD `showTotalScore` BOOLEAN NOT NULL DEFAULT TRUE AFTER `fullFeedback`;