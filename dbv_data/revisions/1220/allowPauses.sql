ALTER TABLE `contest` ADD `allowPauses` TINYINT NOT NULL DEFAULT '0' AFTER `newInterface`;
ALTER TABLE `history_contest` ADD `allowPauses` TINYINT NOT NULL DEFAULT '0' AFTER `newInterface`;

ALTER TABLE `team` ADD `extraMinutes` INT NULL DEFAULT NULL AFTER `nbMinutes`, ADD `nbPauses` INT NOT NULL DEFAULT '0' AFTER `extraMinutes`;
