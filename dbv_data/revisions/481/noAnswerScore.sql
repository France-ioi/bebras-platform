ALTER TABLE  `contest_question` ADD  `noAnswerScore` INT NOT NULL DEFAULT  '0' AFTER  `minScore`;
ALTER TABLE  `history_contest_question` ADD  `noAnswerScore` INT NOT NULL DEFAULT  '0' AFTER  `minScore`;
