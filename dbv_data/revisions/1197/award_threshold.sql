CREATE TABLE IF NOT EXISTS `award_threshold` (
  `ID` bigint(20) NOT NULL,
  `contestID` bigint(20) NOT NULL,
  `gradeID` bigint(20) NOT NULL,
  `awardID` bigint(20) NOT NULL,
  `minimalScore` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

ALTER TABLE `award_threshold`
 ADD PRIMARY KEY (`ID`), ADD UNIQUE KEY `contestGradeAward` (`contestID`,`gradeID`,`awardID`);
