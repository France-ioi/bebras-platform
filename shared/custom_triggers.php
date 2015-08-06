<?php

function addCustomTriggers(&$triggers) {
   $triggerQuery =
            "INSERT IGNORE INTO `user_user` (`userID`, `targetUserID`, `accessType`) ".
            "SELECT NEW.`userID`, `school_user_other`.`userID`, 'none' ".
            "FROM `school_user` as `school_user_other` WHERE `school_user_other`.`schoolID` = NEW.`schoolID` AND NEW.`userID` <> `school_user_other`.`userID`; ".
            "INSERT IGNORE INTO `user_user` (`targetUserID`, `userID`, `accessType`) ".
            "SELECT NEW.`userID`, `school_user_other`.`userID`, 'none' ".
            "FROM `school_user` as `school_user_other` WHERE `school_user_other`.`schoolID` = NEW.`schoolID` AND NEW.`userID` <> `school_user_other`.`userID`";
   $triggers["school_user"]["AFTER UPDATE"][] = $triggerQuery;
   $triggers["school_user"]["AFTER INSERT"][] = $triggerQuery;

   $triggers["school_user"]["AFTER DELETE"][] = "DELETE FROM `user_user` WHERE `user_user`.`userID` = OLD.`userID` AND NOT EXISTS ".
      "(SELECT `su1`.`ID` FROM `school_user` as `su1` JOIN `school_user` as `su2` ON (`su1`.`schoolID` = `su2`.`schoolID`) ".
      "WHERE `su1`.`userID` = OLD.`userID` AND `su2`.`userID` = `user_user`.`targetUserID`)";
   $triggers["school_user"]["AFTER DELETE"][] = "DELETE FROM `user_user` WHERE `user_user`.`targetUserID` = OLD.`userID` AND NOT EXISTS ".
      "(SELECT `su1`.`ID` FROM `school_user` as `su1` JOIN `school_user` as `su2` ON (`su1`.`schoolID` = `su2`.`schoolID`) ".
      "WHERE `su1`.`userID` = OLD.`userID` AND `su2`.`userID` = `user_user`.`userID`)";
}

?>