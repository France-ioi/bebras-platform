ALTER TABLE  `user` ADD  `isOwnOfficialEmail` BOOLEAN NULL DEFAULT NULL AFTER  `lastName`;
ALTER TABLE  `history_user` ADD  `isOwnOfficialEmail` BOOLEAN NULL DEFAULT NULL AFTER  `lastName`;
