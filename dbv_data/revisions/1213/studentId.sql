ALTER TABLE `contest` ADD `askStudentId` BOOLEAN NOT NULL DEFAULT FALSE AFTER `askGrade`;
ALTER TABLE `history_contest` ADD `askStudentId` BOOLEAN NOT NULL DEFAULT FALSE AFTER `askGrade`;
ALTER TABLE `contestant` ADD `studentID` VARCHAR(30) NOT NULL AFTER `grade`;
ALTER TABLE `history_contestant` ADD `studentID` VARCHAR(30) NOT NULL AFTER `grade`;
