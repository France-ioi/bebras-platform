-- phpMyAdmin SQL Dump
-- version 2.11.11
-- http://www.phpmyadmin.net
--
-- Serveur: sql5-1.aquaray.com
-- Généré le : Mar 08 Janvier 2013 à 20:57
-- Version du serveur: 5.1.60
-- Version de PHP: 4.4.9

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";

--
-- Base de données: `france_ioi_0`
--

-- --------------------------------------------------------

--
-- Structure de la table `archive_contest`
--

CREATE TABLE IF NOT EXISTS `archive_contest` (
  `archiveID` int(11) NOT NULL AUTO_INCREMENT,
  `archiveDate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `ID` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `level` int(11) NOT NULL,
  `year` int(11) NOT NULL,
  `status` int(11) NOT NULL,
  `nbMinutes` int(11) NOT NULL,
  `folder` varchar(50) NOT NULL,
  PRIMARY KEY (`archiveID`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=114 ;

-- --------------------------------------------------------

--
-- Structure de la table `archive_contestant`
--

CREATE TABLE IF NOT EXISTS `archive_contestant` (
  `archiveID` int(11) NOT NULL AUTO_INCREMENT,
  `archiveDate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `ID` int(11) NOT NULL,
  `lastName` varchar(50) NOT NULL,
  `firstName` varchar(50) NOT NULL,
  `genre` int(11) NOT NULL,
  `teamID` int(11) NOT NULL,
  `rank` int(11) NOT NULL,
  `schoolRank` int(11) NOT NULL,
  `saniValid` tinyint(4) NOT NULL DEFAULT '0',
  PRIMARY KEY (`archiveID`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=2482 ;

-- --------------------------------------------------------

--
-- Structure de la table `archive_contest_question`
--

CREATE TABLE IF NOT EXISTS `archive_contest_question` (
  `archiveID` int(11) NOT NULL AUTO_INCREMENT,
  `archiveDate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `ID` int(11) NOT NULL,
  `contestID` int(11) NOT NULL,
  `questionID` int(11) NOT NULL,
  `minScore` int(11) DEFAULT NULL,
  `maxScore` int(11) DEFAULT NULL,
  `order` int(11) NOT NULL,
  PRIMARY KEY (`archiveID`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=478 ;

-- --------------------------------------------------------

--
-- Structure de la table `archive_group`
--

CREATE TABLE IF NOT EXISTS `archive_group` (
  `archiveID` int(11) NOT NULL AUTO_INCREMENT,
  `archiveDate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
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
  `startTime` datetime DEFAULT NULL,
  `noticePrinted` tinyint(4) NOT NULL,
  `expectedStartTime` datetime DEFAULT NULL,
  `isPublic` tinyint(4) NOT NULL,
  `isUnofficial` tinyint(4) NOT NULL DEFAULT '0',
  PRIMARY KEY (`archiveID`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=10354 ;

-- --------------------------------------------------------

--
-- Structure de la table `archive_question`
--

CREATE TABLE IF NOT EXISTS `archive_question` (
  `archiveID` int(11) NOT NULL AUTO_INCREMENT,
  `archiveDate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `ID` int(11) NOT NULL,
  `key` varchar(30) NOT NULL,
  `folder` varchar(30) NOT NULL,
  `name` text NOT NULL,
  `answerType` tinyint(4) NOT NULL,
  `expectedAnswer` text,
  PRIMARY KEY (`archiveID`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=119 ;

-- --------------------------------------------------------

--
-- Structure de la table `archive_school`
--

CREATE TABLE IF NOT EXISTS `archive_school` (
  `archiveID` int(11) NOT NULL AUTO_INCREMENT,
  `archiveDate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
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
  PRIMARY KEY (`archiveID`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1775 ;

-- --------------------------------------------------------

--
-- Structure de la table `archive_team`
--

CREATE TABLE IF NOT EXISTS `archive_team` (
  `archiveID` int(11) NOT NULL AUTO_INCREMENT,
  `archiveDate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `ID` int(11) NOT NULL,
  `groupID` int(11) NOT NULL,
  `password` varchar(30) NOT NULL,
  `startTime` datetime DEFAULT NULL,
  `endTime` datetime DEFAULT NULL,
  `score` int(11) DEFAULT NULL,
  PRIMARY KEY (`archiveID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Structure de la table `archive_team_connection`
--

CREATE TABLE IF NOT EXISTS `archive_team_connection` (
  `archiveID` int(11) NOT NULL AUTO_INCREMENT,
  `archiveDate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `ID` int(11) NOT NULL,
  `teamID` int(11) NOT NULL,
  `date` datetime DEFAULT NULL,
  PRIMARY KEY (`archiveID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Structure de la table `archive_team_question`
--

CREATE TABLE IF NOT EXISTS `archive_team_question` (
  `archiveID` int(11) NOT NULL AUTO_INCREMENT,
  `archiveDate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `ID` int(11) NOT NULL,
  `teamID` int(11) NOT NULL,
  `questionID` int(11) NOT NULL,
  `answer` text NOT NULL,
  `score` int(11) DEFAULT NULL,
  `date` datetime NOT NULL,
  PRIMARY KEY (`archiveID`)
) ENGINE=InnoDB   DEFAULT CHARSET=utf8 AUTO_INCREMENT=5 ;

-- --------------------------------------------------------

--
-- Structure de la table `archive_user`
--

CREATE TABLE IF NOT EXISTS `archive_user` (
  `archiveID` int(11) NOT NULL AUTO_INCREMENT,
  `archiveDate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
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
  PRIMARY KEY (`archiveID`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1711 ;

-- --------------------------------------------------------

--
-- Structure de la table `certi_queue`
--

CREATE TABLE IF NOT EXISTS `certi_queue` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `schoolID` int(11) NOT NULL,
  `nbStudents` int(11) NOT NULL,
  `requestDate` datetime NOT NULL,
  `startDate` datetime DEFAULT NULL,
  `endDate` datetime DEFAULT NULL,
  `state` enum('WAITING','RUNNING','CANCELED','STOPPED','FINISHED') NOT NULL DEFAULT 'WAITING',
  PRIMARY KEY (`ID`),
  KEY `schoolID` (`schoolID`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=62 ;

-- --------------------------------------------------------

--
-- Structure de la table `contest`
--

CREATE TABLE IF NOT EXISTS `contest` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `level` int(11) NOT NULL,
  `year` int(11) NOT NULL,
  `status` int(11) NOT NULL,
  `nbMinutes` int(11) NOT NULL,
  `folder` varchar(50) NOT NULL,
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=22 ;

-- --------------------------------------------------------

--
-- Structure de la table `contestant`
--

CREATE TABLE IF NOT EXISTS `contestant` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `lastName` varchar(50) NOT NULL,
  `firstName` varchar(50) NOT NULL,
  `genre` int(11) NOT NULL,
  `teamID` int(11) NOT NULL,
  `cached_schoolID` int(11) NOT NULL,
  `rank` int(11) DEFAULT NULL,
  `schoolRank` int(11) DEFAULT NULL,
  `saniValid` tinyint(4) NOT NULL DEFAULT '0',
   `orig_firstName` VARCHAR( 50 ) NOT NULL,
   `orig_lastName` VARCHAR( 50 ) NOT NULL,
  PRIMARY KEY (`ID`),
  KEY `teamID` (`teamID`),
  KEY `cached_schoolID` (`cached_schoolID`),
  KEY `rank` (`rank`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=100008412 ;

-- --------------------------------------------------------

--
-- Structure de la table `contest_question`
--

CREATE TABLE IF NOT EXISTS `contest_question` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `contestID` int(11) NOT NULL,
  `questionID` int(11) NOT NULL,
  `minScore` int(11) DEFAULT NULL,
  `maxScore` int(11) DEFAULT NULL,
  `order` int(11) NOT NULL,
  PRIMARY KEY (`ID`),
  KEY `contestID` (`contestID`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=331 ;

-- --------------------------------------------------------

--
-- Structure de la table `group`
--

CREATE TABLE IF NOT EXISTS `group` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
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
  `isUnofficial` tinyint(4) NOT NULL DEFAULT '0',
  PRIMARY KEY (`ID`),
  UNIQUE KEY `code` (`code`),
  UNIQUE KEY `password` (`password`),
  KEY `userID` (`userID`),
  KEY `schoolID` (`schoolID`),
  KEY `contestID` (`contestID`),
  KEY `name` (`name`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=8499 ;

-- --------------------------------------------------------

--
-- Structure de la table `question`
--

CREATE TABLE IF NOT EXISTS `question` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `key` varchar(30) NOT NULL,
  `folder` varchar(50) NOT NULL,
  `name` text NOT NULL,
  `answerType` tinyint(4) NOT NULL,
  `expectedAnswer` text,
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=140 ;

-- --------------------------------------------------------

--
-- Structure de la table `school`
--

CREATE TABLE IF NOT EXISTS `school` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
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
  `orig_name` VARCHAR( 50 ) NOT NULL,
  `orig_city` VARCHAR( 50 ) NOT NULL,
  `orig_country` VARCHAR( 50 ) NOT NULL,
  `saniMsg` text NOT NULL,
  PRIMARY KEY (`ID`),
  KEY `userID` (`userID`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=927 ;

-- --------------------------------------------------------

--
-- Structure de la table `school_user`
--

CREATE TABLE IF NOT EXISTS `school_user` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `schoolID` int(11) NOT NULL,
  `userID` int(11) NOT NULL,
  PRIMARY KEY (`ID`),
  KEY `schoolID` (`schoolID`,`userID`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1024 ;

-- --------------------------------------------------------

--
-- Structure de la table `team`
--

CREATE TABLE IF NOT EXISTS `team` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `groupID` int(11) NOT NULL,
  `password` varchar(30) NOT NULL,
  `startTime` datetime NOT NULL,
  `endTime` datetime DEFAULT NULL,
  `score` int(11) DEFAULT NULL,
  `cached_contestants` varchar(300) NOT NULL,
  `isUnofficial` tinyint(4) NOT NULL DEFAULT '0',
  PRIMARY KEY (`ID`),
  UNIQUE KEY `password` (`password`),
  KEY `score` (`score`),
  KEY `groupID` (`groupID`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=100007027 ;

-- --------------------------------------------------------

--
-- Structure de la table `team_connection`
--

CREATE TABLE IF NOT EXISTS `team_connection` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `teamID` int(11) NOT NULL,
  `date` datetime DEFAULT NULL,
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Structure de la table `team_question`
--

CREATE TABLE IF NOT EXISTS `team_question` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `teamID` int(11) NOT NULL,
  `questionID` int(11) NOT NULL,
  `answer` text NOT NULL,
  `score` int(11) DEFAULT NULL,
  `date` datetime NOT NULL,
  PRIMARY KEY (`ID`),
  UNIQUE KEY `teamID` (`teamID`,`questionID`),
  KEY `score` (`score`),
  KEY `teamID_2` (`teamID`),
  KEY `questionID` (`questionID`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=100069154 ;

-- --------------------------------------------------------

--
-- Structure de la table `user`
--

CREATE TABLE IF NOT EXISTS `user` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
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
  `orig_firstName` VARCHAR( 50 ) NOT NULL,
  `orig_lastName` VARCHAR( 50 ) NOT NULL,
  PRIMARY KEY (`ID`),
  KEY `officialEmail` (`officialEmail`),
  KEY `alternativeEmail` (`alternativeEmail`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1185 ;

-- --------------------------------------------------------

--
-- Structure de la table `school_year`
--

CREATE TABLE IF NOT EXISTS `school_year` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `schoolID` int(11) NOT NULL,
  `year` int(11) NOT NULL,
  `nbOfficialContestants` int(11) NOT NULL,
  PRIMARY KEY (`ID`),
  KEY `schoolID` (`schoolID`,`year`),
  KEY `nbOfficialContestants` (`nbOfficialContestants`),
  KEY `awarded` (`awarded`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=4 ;

-- --------------------------------------------------------

