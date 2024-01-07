ALTER TABLE `algorea_registration` ADD `lastGradeUpdate` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER `grade`;
UPDATE `algorea_registration` SET `lastGradeUpdate` = '2000-01-01 00:00:00';