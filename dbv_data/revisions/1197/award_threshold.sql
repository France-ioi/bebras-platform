
CREATE TABLE IF NOT EXISTS `award_threshold` (
`ID` bigint(20) NOT NULL,
  `contestID` bigint(20) NOT NULL,
  `gradeID` bigint(20) NOT NULL,
  `awardID` bigint(20) NOT NULL,
  `nbContestants` int(11) NOT NULL,
  `minScore` int(11) NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

ALTER TABLE `award_threshold`
 ADD PRIMARY KEY (`ID`), ADD UNIQUE KEY `all` (`contestID`,`gradeID`,`awardID`,`nbContestants`);

ALTER TABLE `award_threshold`
MODIFY `ID` bigint(20) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=1;
