ALTER TABLE  `contest` ADD  `closedToOfficialGroups` TINYINT(1) NOT NULL DEFAULT 1 AFTER `status`;
ALTER TABLE  `history_contest` ADD  `closedToOfficialGroups` TINYINT(1) NOT NULL DEFAULT 1 AFTER `status`;
