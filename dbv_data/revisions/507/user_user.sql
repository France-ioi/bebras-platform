
CREATE TABLE IF NOT EXISTS `user_user` (
  `ID` bigint(20) NOT NULL AUTO_INCREMENT,
  `userID` bigint(20) NOT NULL,
  `targetUserID` bigint(20) NOT NULL,
  `accessType` enum('none','read','write') NOT NULL DEFAULT 'none',
  `iVersion` int(11) NOT NULL,
  PRIMARY KEY (`ID`),
  KEY `userID` (`userID`,`targetUserID`,`iVersion`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `history_user_user` (
  `historyID` bigint(20) NOT NULL AUTO_INCREMENT,
  `ID` bigint(20) NOT NULL,
  `userID` bigint(20) NOT NULL,
  `targetUserID` bigint(20) NOT NULL,
  `accessType` enum('none','read','write') NOT NULL DEFAULT 'none',
  `iVersion` int(11) NOT NULL,
  `iNextVersion` int(11),
  `bDeleted` tinyint(4),
  PRIMARY KEY (`historyID`),
  KEY (`ID`),
  KEY (`iVersion`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

ALTER TABLE  `user_user` ADD UNIQUE (`userID` , `targetUserID`);

INSERT IGNORE INTO `user_user` (`userID`, `targetUserID`, `accessType`) 
            SELECT `school_user_main`.`userID`, `school_user_other`.`userID`, 'none' 
            FROM `school_user` as `school_user_main`, `school_user` as `school_user_other`
WHERE `school_user_other`.`schoolID` = `school_user_main`.`schoolID`
AND `school_user_other`.`userID` <> `school_user_main`.`userID`;
