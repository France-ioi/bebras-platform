ALTER TABLE `contestant` ADD `phoneNumber` TEXT NULL AFTER `studentID`;
ALTER TABLE `history_contestant` ADD `phoneNumber` BOOLEAN NOT NULL DEFAULT FALSE AFTER `studentID`;
ALTER TABLE `contest` ADD `askPhoneNumber` BOOLEAN NOT NULL DEFAULT FALSE AFTER `askStudentId`;
ALTER TABLE `algorea_registration` ADD `phoneNumber` BOOLEAN NOT NULL DEFAULT FALSE AFTER `studentID`;
ALTER TABLE `history_contest` ADD `askPhoneNumber` BOOLEAN NOT NULL DEFAULT FALSE AFTER `askStudentID`;
