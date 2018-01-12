ALTER TABLE `algorea_registration` ADD `validatedCategory` VARCHAR(30) NOT NULL AFTER `category`;
ALTER TABLE `contest` ADD `validationCategory` VARCHAR(30) NOT NULL AFTER `qualificationScore`;
ALTER TABLE `history_contest` ADD `validationCategory` VARCHAR(30) NOT NULL AFTER `qualificationScore`;