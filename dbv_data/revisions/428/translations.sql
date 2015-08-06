CREATE TABLE `translations` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `languageID` int(11) NOT NULL,
  `category` varchar(50) CHARACTER SET utf8 NOT NULL,
  `key` varchar(50) CHARACTER SET utf8 NOT NULL,
  `translation` text CHARACTER SET utf8 NOT NULL,
  PRIMARY KEY (`ID`),
  UNIQUE KEY `id_in_template` (`category`,`key`),
  KEY `languageID` (`languageID`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1