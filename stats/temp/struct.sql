-- phpMyAdmin SQL Dump
-- version 4.0.4
-- http://www.phpmyadmin.net
--
-- Client: localhost
-- Généré le: Jeu 21 Novembre 2013 à 10:26
-- Version du serveur: 5.6.12-log
-- Version de PHP: 5.4.16

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Base de données: `beaver`
--
CREATE DATABASE IF NOT EXISTS `beaver` DEFAULT CHARACTER SET latin1 COLLATE latin1_swedish_ci;
USE `beaver`;

-- --------------------------------------------------------

--
-- Structure de la table `contest`
--

CREATE TABLE IF NOT EXISTS `contest` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `level` int(11) NOT NULL,
  `year` int(11) NOT NULL,
  `status` enum('FutureContest','RunningContest','PastContest','Other','Hidden','Closed') NOT NULL DEFAULT 'Hidden',
  `nbMinutes` int(11) NOT NULL,
  `bonusScore` int(11) NOT NULL DEFAULT '0',
  `allowTeamsOfTwo` int(11) NOT NULL DEFAULT '0',
  `folder` varchar(50) NOT NULL,
  `iVersion` int(11) NOT NULL,
  PRIMARY KEY (`ID`),
  KEY `status` (`status`),
  KEY `iVersion` (`iVersion`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=32 ;

-- --------------------------------------------------------

--
-- Structure de la table `contest_question`
--

CREATE TABLE IF NOT EXISTS `contest_question` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `contestID` int(11) NOT NULL,
  `questionID` int(11) NOT NULL,
  `minScore` int(11) DEFAULT NULL,
  `noAnswerScore` int(11) NOT NULL DEFAULT '0',
  `maxScore` int(11) DEFAULT NULL,
  `order` int(11) NOT NULL,
  `iVersion` int(11) NOT NULL,
  PRIMARY KEY (`ID`),
  KEY `contestID` (`contestID`),
  KEY `iVersion` (`iVersion`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=550 ;

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
  `participationType` enum('Official','Unofficial') DEFAULT NULL,
  `iVersion` int(11) NOT NULL,
  PRIMARY KEY (`ID`),
  UNIQUE KEY `code` (`code`),
  UNIQUE KEY `password` (`password`),
  KEY `userID` (`userID`),
  KEY `schoolID` (`schoolID`),
  KEY `contestID` (`contestID`),
  KEY `name` (`name`),
  KEY `participationType` (`participationType`),
  KEY `iVersion` (`iVersion`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=21605 ;

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
  `iVersion` int(11) NOT NULL,
  PRIMARY KEY (`ID`),
  KEY `iVersion` (`iVersion`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=209 ;

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
  `cached_officialForContestID` int(11) NOT NULL,
  `participationType` enum('Official','Unofficial','Undefined') DEFAULT NULL,
  `iVersion` int(11) NOT NULL,
  PRIMARY KEY (`ID`),
  UNIQUE KEY `password` (`password`),
  KEY `score` (`score`),
  KEY `groupID` (`groupID`),
  KEY `participationType` (`participationType`),
  KEY `iVersion` (`iVersion`),
  KEY `cached_officialForContestID` (`cached_officialForContestID`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=100285398 ;

-- --------------------------------------------------------

--
-- Structure de la table `team_question`
--

CREATE TABLE IF NOT EXISTS `team_question` (
  `teamID` int(11) NOT NULL,
  `questionID` int(11) NOT NULL,
  `answer` varchar(2047) NOT NULL,
  `score` int(11) DEFAULT NULL,
  `date` datetime NOT NULL,
  PRIMARY KEY (`teamID`,`questionID`),
  KEY `questionID` (`questionID`),
  KEY `questionID_2` (`questionID`,`answer`(255))
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
