DROP TABLE `algorea_registration`;

CREATE TABLE IF NOT EXISTS `algorea_registration` (
`ID` bigint(20) NOT NULL,
  `code` varchar(20) NOT NULL,
  `contestantID` bigint(20) DEFAULT NULL,
  `franceioiID` bigint(20) DEFAULT NULL
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=latin1;

ALTER TABLE `algorea_registration`
 ADD PRIMARY KEY (`ID`), ADD UNIQUE KEY `code` (`code`), ADD UNIQUE KEY `franceioiID` (`franceioiID`), ADD UNIQUE KEY `contestantID` (`contestantID`);

ALTER TABLE `algorea_registration`
MODIFY `ID` bigint(20) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=1;