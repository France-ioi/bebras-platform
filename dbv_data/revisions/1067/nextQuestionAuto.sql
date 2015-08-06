ALTER TABLE  `contest` ADD  `nextQuestionAuto` BOOLEAN NOT NULL DEFAULT TRUE AFTER  `fullFeedback`;
ALTER TABLE  `history_contest` ADD  `nextQuestionAuto` BOOLEAN NOT NULL DEFAULT TRUE AFTER  `fullFeedback`;

INSERT INTO `translations` (`languageID`, `category`, `key`, `translation`) VALUES
(1, 'admin_js', 'contest_nextQuestionAuto_label', 'Question suivante auto'),
(2, 'admin_js', 'contest_nextQuestionAuto_label', 'Auto-move to next question');

