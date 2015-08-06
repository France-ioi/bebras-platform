ALTER TABLE  `contest` ADD  `allowTeamsOfTwo` INT NOT NULL DEFAULT  '0' AFTER  `bonusScore`;
ALTER TABLE  `history_contest` ADD  `allowTeamsOfTwo` INT NOT NULL DEFAULT  '0' AFTER  `bonusScore`;
