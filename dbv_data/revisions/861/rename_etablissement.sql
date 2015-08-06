RENAME TABLE  `etablissement` TO  `recommend_listedschool` ;
RENAME TABLE  `etablissement_normalized` TO `recommend_listedschool_normalized` ;
RENAME TABLE  `recommendation` TO `recommend_user` ;
RENAME TABLE  `school_etablissement` TO `recommend_school_listedschool` ;
RENAME TABLE  `academy` TO `recommend_academy` ;
RENAME TABLE  `academies_dpt` TO `recommend_academy_department` ;

ALTER TABLE  `recommend_academy_department` CHANGE  `academieID`  `academyID` INT( 11 ) NOT NULL AUTO_INCREMENT;
ALTER TABLE  `recommend_listedschool` CHANGE  `academieID`  `academyID` INT( 3 ) UNSIGNED NOT NULL;

ALTER TABLE  `recommend_listedschool_normalized` CHANGE  `etablissementID`  `listedschoolID` INT( 11 ) NOT NULL;
ALTER TABLE  `recommend_school_listedschool` CHANGE  `etablissementID`  `listedschoolID` INT( 11 ) NOT NULL;