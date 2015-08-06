ALTER TABLE  `contest` ADD  `bonusScore` INT NOT NULL DEFAULT  '0' AFTER  `nbMinutes`;
ALTER TABLE  `history_contest` ADD  `bonusScore` INT NOT NULL DEFAULT  '0' AFTER  `nbMinutes`;
