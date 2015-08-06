ALTER TABLE  `school_user` ADD  `confirmed` BOOLEAN NOT NULL DEFAULT FALSE AFTER  `userID`;
ALTER TABLE  `history_school_user` ADD  `confirmed` BOOLEAN NOT NULL DEFAULT FALSE AFTER  `userID`;
