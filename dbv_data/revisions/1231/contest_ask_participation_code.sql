ALTER TABLE `contest` ADD `askParticipationCode` TINYINT NOT NULL DEFAULT '0' AFTER `askStudentId`;
ALTER TABLE `history_contest` ADD `askParticipationCode` TINYINT NOT NULL DEFAULT '0' AFTER `askStudentId`;
