CREATE TABLE `languages` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) CHARACTER SET utf8 NOT NULL,
  `suffix` varchar(50) CHARACTER SET utf8 NOT NULL,
  PRIMARY KEY (`ID`),
  UNIQUE KEY `name` (`name`),
  UNIQUE KEY `suffix` (`suffix`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1