CREATE TABLE IF NOT EXISTS `activity` (
`ID` int(11) NOT NULL,
  `teamID` bigint(20) NOT NULL,
  `questionID` bigint(20) NOT NULL,
  `type` enum('load', 'attempt','submission') NOT NULL,
  `answer` mediumtext DEFAULT NULL,
  `score` int(11) DEFAULT NULL,
  `date` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


ALTER TABLE `activity`
 ADD PRIMARY KEY (`ID`),
 ADD KEY `teamID` (`teamID`),
 ADD KEY `questionID` (`questionID`);

ALTER TABLE `activity`
MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT;
