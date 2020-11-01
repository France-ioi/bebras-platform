CREATE TABLE IF NOT EXISTS `registration_category` (
`ID` bigint(20) NOT NULL,
  `registrationID` bigint(20) NOT NULL,
  `category` text NOT NULL,
  `bestScoreIndividual` int(11) DEFAULT NULL,
  `dateBestScoreIndividual` datetime DEFAULT NULL,
  `bestScoreTeam` int(11) DEFAULT NULL,
  `dateBestScoreTeam` datetime DEFAULT NULL
) ENGINE=InnoDB AUTO_INCREMENT=2110169 DEFAULT CHARSET=utf8;

ALTER TABLE `registration_category`
 ADD PRIMARY KEY (`ID`), ADD UNIQUE KEY `registration_category` (`registrationID`,`category`(100));

ALTER TABLE `registration_category`
MODIFY `ID` bigint(20) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=2110169;