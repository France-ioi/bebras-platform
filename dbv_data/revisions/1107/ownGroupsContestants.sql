ALTER TABLE  `school_user` ADD  `ownGroupsContestants` INT NOT NULL DEFAULT  '0' AFTER  `confirmed`;
ALTER TABLE  `history_school_user` ADD  `ownGroupsContestants` INT NOT NULL DEFAULT  '0' AFTER  `confirmed`;
