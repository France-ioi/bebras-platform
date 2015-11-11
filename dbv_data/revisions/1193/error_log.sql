CREATE TABLE IF NOT EXISTS `error_log` (
  `ID` bigint(20) NOT NULL,
  `date` datetime NOT NULL,
  `message` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `teamID` bigint(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

ALTER TABLE `error_log`
 ADD PRIMARY KEY (`ID`), ADD KEY `teamID` (`teamID`);

ALTER TABLE `error_log`
MODIFY `ID` bigint(20) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=2;