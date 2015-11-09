ALTER TABLE `contest` ADD `newInterface` TINYINT NOT NULL AFTER `nextQuestionAuto`;
ALTER TABLE `history_contest` ADD `newInterface` TINYINT NOT NULL AFTER `nextQuestionAuto`;
