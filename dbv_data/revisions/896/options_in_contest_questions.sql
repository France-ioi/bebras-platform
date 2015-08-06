ALTER TABLE `contest_question` ADD `options` VARCHAR(200) NOT NULL DEFAULT '{}' AFTER `maxScore`;
INSERT INTO `translations` (`ID`, `languageID`, `category`, `key`, `translation`, `iVersion`) VALUES (NULL, '1', 'admin_js', 'contest_question_options_label', 'Options', ''), (NULL, '2', 'admin_js', 'contest_question_options_label', 'Options', '');
