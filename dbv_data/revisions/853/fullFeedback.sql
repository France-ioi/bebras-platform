ALTER TABLE  `contest` ADD  `fullFeedback` BOOLEAN NOT NULL DEFAULT FALSE AFTER  `allowTeamsOfTwo`;
ALTER TABLE  `history_contest` ADD  `fullFeedback` BOOLEAN NOT NULL DEFAULT FALSE AFTER  `allowTeamsOfTwo`;

INSERT INTO `translations` (`languageID`, `category`, `key`, `translation`) VALUES
(1, 'admin_js', 'contest_fullFeedback_label', 'Score immédiat'),
(2, 'admin_js', 'contest_fullFeedback_label', 'Full feedback');

