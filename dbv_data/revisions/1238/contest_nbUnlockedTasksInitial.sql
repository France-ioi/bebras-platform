ALTER TABLE `contest` CHANGE `nbUnlockedTasksInitial` `nbUnlockedTasksInitial` INT(11) NOT NULL DEFAULT '0';
UPDATE `contest` SET `nbUnlockedTasksInitial` = 0;
