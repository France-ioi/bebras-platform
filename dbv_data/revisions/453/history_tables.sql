CREATE TABLE IF NOT EXISTS `history_contest` (
  `historyID` int(11) NOT NULL AUTO_INCREMENT,
  `ID` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `level` int(11) NOT NULL,
  `year` int(11) NOT NULL,
  `status` enum('FutureContest','RunningContest','PastContest','Other','Hidden') NOT NULL DEFAULT 'Hidden',
  `nbMinutes` int(11) NOT NULL,
  `folder` varchar(50) NOT NULL,
  `iVersion` int(11),
  `iNextVersion` int(11),
  `bDeleted` tinyint(4),
  PRIMARY KEY (`historyID`),
  KEY `status` (`status`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=23 ;

-- --------------------------------------------------------

--
-- Table structure for table `history_contestant`
--

CREATE TABLE IF NOT EXISTS `history_contestant` (
  `historyID` int(11) NOT NULL AUTO_INCREMENT,
  `ID` int(11) NOT NULL,
  `lastName` varchar(50) NOT NULL,
  `firstName` varchar(50) NOT NULL,
  `genre` int(11) NOT NULL,
  `teamID` int(11) NOT NULL,
  `cached_schoolID` int(11) NOT NULL,
  `rank` int(11) DEFAULT NULL,
  `schoolRank` int(11) DEFAULT NULL,
  `saniValid` tinyint(4) NOT NULL DEFAULT '0',
  `iVersion` int(11),
  `iNextVersion` int(11),
  `bDeleted` tinyint(4),
  PRIMARY KEY (`historyID`),
  KEY `teamID` (`teamID`),
  KEY `cached_schoolID` (`cached_schoolID`),
  KEY `rank` (`rank`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=100008424 ;

-- --------------------------------------------------------

--
-- Table structure for table `history_contest_question`
--

CREATE TABLE IF NOT EXISTS `history_contest_question` (
  `historyID` int(11) NOT NULL AUTO_INCREMENT,
  `ID` int(11) NOT NULL,
  `contestID` int(11) NOT NULL,
  `questionID` int(11) NOT NULL,
  `minScore` int(11) DEFAULT NULL,
  `maxScore` int(11) DEFAULT NULL,
  `order` int(11) NOT NULL,
  `iVersion` int(11),
  `iNextVersion` int(11),
  `bDeleted` tinyint(4),
  PRIMARY KEY (`historyID`),
  KEY `contestID` (`contestID`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=333 ;

-- --------------------------------------------------------

--
-- Table structure for table `history_group`
--

CREATE TABLE IF NOT EXISTS `history_group` (
  `historyID` int(11) NOT NULL AUTO_INCREMENT,
  `ID` int(11) NOT NULL,
  `schoolID` int(11) NOT NULL,
  `grade` int(11) NOT NULL,
  `gradeDetail` varchar(50) NOT NULL,
  `userID` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `nbStudents` int(11) NOT NULL,
  `nbTeamsEffective` int(11) NOT NULL DEFAULT '0',
  `nbStudentsEffective` int(11) NOT NULL DEFAULT '0',
  `contestID` int(11) DEFAULT NULL,
  `code` varchar(10) NOT NULL,
  `password` varchar(30) NOT NULL,
  `expectedStartTime` datetime DEFAULT NULL,
  `startTime` datetime DEFAULT NULL,
  `noticePrinted` tinyint(4) NOT NULL,
  `isPublic` tinyint(4) NOT NULL,
  `participationType` enum('Official','Unofficial') DEFAULT NULL,
  `iVersion` int(11),
  `iNextVersion` int(11),
  `bDeleted` tinyint(4),
  PRIMARY KEY (`historyID`),
  KEY `code` (`code`),
  KEY `password` (`password`),
  KEY `userID` (`userID`),
  KEY `schoolID` (`schoolID`),
  KEY `contestID` (`contestID`),
  KEY `name` (`name`),
  KEY `participationType` (`participationType`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=8500 ;

-- --------------------------------------------------------

--
-- Table structure for table `history_languages`
--

CREATE TABLE IF NOT EXISTS `history_languages` (
  `historyID` int(11) NOT NULL AUTO_INCREMENT,
  `ID` int(11) NOT NULL,
  `name` varchar(50) CHARACTER SET utf8 NOT NULL,
  `suffix` varchar(50) CHARACTER SET utf8 NOT NULL,
  `iVersion` int(11),
  `iNextVersion` int(11),
  `bDeleted` tinyint(4),
  PRIMARY KEY (`historyID`),
  KEY `name` (`name`),
  KEY `suffix` (`suffix`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=3 ;

-- --------------------------------------------------------

--
-- Table structure for table `history_question`
--

CREATE TABLE IF NOT EXISTS `history_question` (
  `historyID` int(11) NOT NULL AUTO_INCREMENT,
  `ID` int(11) NOT NULL,
  `key` varchar(30) NOT NULL,
  `folder` varchar(50) NOT NULL,
  `name` text NOT NULL,
  `answerType` tinyint(4) NOT NULL,
  `expectedAnswer` text,
  `iVersion` int(11),
  `iNextVersion` int(11),
  `bDeleted` tinyint(4),
  PRIMARY KEY (`historyID`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=142 ;

-- --------------------------------------------------------

--
-- Table structure for table `school`
--

CREATE TABLE IF NOT EXISTS `history_school` (
  `historyID` int(11) NOT NULL AUTO_INCREMENT,
  `ID` int(11) NOT NULL,
  `userID` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `region` varchar(50) NOT NULL,
  `address` varchar(50) NOT NULL,
  `zipcode` varchar(10) NOT NULL,
  `city` varchar(50) NOT NULL,
  `country` varchar(50) NOT NULL,
  `coords` varchar(100) NOT NULL DEFAULT '0,0,0',
  `nbStudents` int(11) NOT NULL,
  `validated` tinyint(4) NOT NULL,
  `saniValid` tinyint(4) NOT NULL DEFAULT '0',
  `saniMsg` text NOT NULL,
  `iVersion` int(11),
  `iNextVersion` int(11),
  `bDeleted` tinyint(4),
  PRIMARY KEY (`historyID`),
  KEY `userID` (`userID`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=928 ;

-- --------------------------------------------------------

--
-- Table structure for table `history_school_user`
--

CREATE TABLE IF NOT EXISTS `history_school_user` (
  `historyID` int(11) NOT NULL AUTO_INCREMENT,
  `ID` int(11) NOT NULL,
  `schoolID` int(11) NOT NULL,
  `userID` int(11) NOT NULL,
  `iVersion` int(11),
  `iNextVersion` int(11),
  `bDeleted` tinyint(4),
  PRIMARY KEY (`historyID`),
  KEY `schoolID` (`schoolID`,`userID`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1025 ;

-- --------------------------------------------------------

--
-- Table structure for table `history_team`
--

CREATE TABLE IF NOT EXISTS `history_team` (
  `historyID` int(11) NOT NULL AUTO_INCREMENT,
  `ID` int(11) NOT NULL,
  `groupID` int(11) NOT NULL,
  `password` varchar(30) NOT NULL,
  `startTime` datetime NOT NULL,
  `endTime` datetime DEFAULT NULL,
  `score` int(11) DEFAULT NULL,
  `cached_contestants` varchar(300) NOT NULL,
  `participationType` enum('Official','Unofficial') DEFAULT NULL,
  `iVersion` int(11),
  `iNextVersion` int(11),
  `bDeleted` tinyint(4),
  PRIMARY KEY (`historyID`),
  KEY `password` (`password`),
  KEY `score` (`score`),
  KEY `groupID` (`groupID`),
  KEY `participationType` (`participationType`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=100007039 ;

-- --------------------------------------------------------

--
-- Table structure for table `history_team_connection`
--

CREATE TABLE IF NOT EXISTS `history_team_connection` (
  `historyID` int(11) NOT NULL AUTO_INCREMENT,
  `ID` int(11) NOT NULL,
  `teamID` int(11) NOT NULL,
  `date` datetime DEFAULT NULL,
  `iVersion` int(11),
  `iNextVersion` int(11),
  `bDeleted` tinyint(4),
  PRIMARY KEY (`historyID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `history_translations`
--

CREATE TABLE IF NOT EXISTS `history_translations` (
  `historyID` int(11) NOT NULL AUTO_INCREMENT,
  `ID` int(11) NOT NULL,
  `languageID` int(11) NOT NULL,
  `category` varchar(50) CHARACTER SET utf8 NOT NULL,
  `key` varchar(50) CHARACTER SET utf8 NOT NULL,
  `translation` text CHARACTER SET utf8 NOT NULL,
  `iVersion` int(11),
  `iNextVersion` int(11),
  `bDeleted` tinyint(4),
  PRIMARY KEY (`historyID`),
  KEY `languageID` (`languageID`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=782 ;

-- --------------------------------------------------------

--
-- Table structure for table `history_user`
--

CREATE TABLE IF NOT EXISTS `history_user` (
  `historyID` int(11) NOT NULL AUTO_INCREMENT,
  `ID` int(11) NOT NULL,
  `gender` enum('F','M') DEFAULT NULL,
  `firstName` varchar(30) NOT NULL,
  `lastName` varchar(30) NOT NULL,
  `officialEmail` varchar(50) DEFAULT NULL COMMENT 'validates that the user is a teacher',
  `officialEmailValidated` tinyint(4) NOT NULL,
  `alternativeEmail` varchar(50) DEFAULT NULL,
  `alternativeEmailValidated` tinyint(4) NOT NULL,
  `salt` varchar(40) NOT NULL,
  `passwordMd5` varchar(40) NOT NULL,
  `recoverCode` varchar(50) NOT NULL,
  `validated` tinyint(11) NOT NULL COMMENT 'account validated as an admin for the given school',
  `allowMultipleSchools` tinyint(4) NOT NULL,
  `isAdmin` tinyint(4) NOT NULL,
  `registrationDate` datetime NOT NULL,
  `lastLoginDate` datetime NOT NULL,
  `comment` text NOT NULL,
  `saniValid` tinyint(4) NOT NULL DEFAULT '0',
  `iVersion` int(11),
  `iNextVersion` int(11),
  `bDeleted` tinyint(4),
  PRIMARY KEY (`historyID`),
  KEY `officialEmail` (`officialEmail`),
  KEY `alternativeEmail` (`alternativeEmail`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1191 ;


CREATE TABLE `history_school_year` (
  `historyID` int(11) NOT NULL AUTO_INCREMENT,
  `ID` int(11) NOT NULL,
  `schoolID` int(11) NOT NULL,
  `year` int(11) NOT NULL,
  `nbOfficialContestants` int(11) NOT NULL,
  `awarded` tinyint(4) NOT NULL,
  `iVersion` int(11),
  `iNextVersion` int(11),
  `bDeleted` tinyint(4),
  PRIMARY KEY (`historyID`),
  UNIQUE KEY `schoolID_2` (`schoolID`,`year`),
  KEY `schoolID` (`schoolID`,`year`),
  KEY `nbOfficialContestants` (`nbOfficialContestants`),
  KEY `awarded` (`awarded`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=887 ;

