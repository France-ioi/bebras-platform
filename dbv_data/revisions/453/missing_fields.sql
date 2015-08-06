ALTER TABLE  `history_contestant` ADD  `orig_firstName` VARCHAR( 50 ) NOT NULL AFTER  `saniValid` , ADD  `orig_lastName` VARCHAR( 50 ) NOT NULL AFTER  `orig_firstName`;
ALTER TABLE  `history_school` ADD  `orig_name` VARCHAR( 50 ) NOT NULL AFTER  `saniMsg` , ADD  `orig_city` VARCHAR( 50 ) NOT NULL AFTER  `orig_name`, ADD  `orig_country` VARCHAR( 50 ) NOT NULL AFTER  `orig_city`;
ALTER TABLE  `history_user` ADD  `orig_firstName` VARCHAR( 50 ) NOT NULL AFTER  `saniValid` , ADD  `orig_lastName` VARCHAR( 50 ) NOT NULL AFTER  `orig_firstName`;



CREATE TABLE IF NOT EXISTS `synchro_version` (
  `iVersion` int(11) NOT NULL,
  KEY `iVersion` (`iVersion`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `synchro_version`
--

INSERT INTO `synchro_version` (`iVersion`) VALUES
(1);

-- --------------------------------------------------------

--
-- Table structure for table `school_year`
--

CREATE TABLE IF NOT EXISTS `school_year` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `schoolID` int(11) NOT NULL,
  `year` int(11) NOT NULL,
  `nbOfficialContestants` int(11) NOT NULL,
  `awarded` tinyint(4) NOT NULL,
  `iVersion` int(11),
  PRIMARY KEY (`ID`),
  UNIQUE KEY `schoolID_2` (`schoolID`,`year`),
  KEY `schoolID` (`schoolID`,`year`),
  KEY `nbOfficialContestants` (`nbOfficialContestants`),
  KEY `awarded` (`awarded`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=887 ;

