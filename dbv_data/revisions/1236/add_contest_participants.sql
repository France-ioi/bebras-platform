CREATE TABLE IF NOT EXISTS `contest_participants` (
`ID` bigint(20) NOT NULL,
  `contestID` bigint(20) DEFAULT NULL,
  `grade` int(11) DEFAULT NULL,
  `nbContestants` int(11) DEFAULT NULL,
  `number` int(11) NOT NULL DEFAULT '0'
) ENGINE=InnoDB AUTO_INCREMENT=127 DEFAULT CHARSET=utf8;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `contest_participants`
--
ALTER TABLE `contest_participants`
 ADD PRIMARY KEY (`ID`), ADD KEY `contestID` (`contestID`);

ALTER TABLE `contest_participants` CHANGE `ID` `ID` BIGINT(20) NOT NULL AUTO_INCREMENT;
