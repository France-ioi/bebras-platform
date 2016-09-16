ALTER TABLE `contest` ADD `subsetsSize` INT NOT NULL DEFAULT 0 AFTER `nbUnlockedTasksInitial`;
ALTER TABLE `history_contest` ADD `subsetsSize` INT NOT NULL DEFAULT 0 AFTER `nbUnlockedTasksInitial`;