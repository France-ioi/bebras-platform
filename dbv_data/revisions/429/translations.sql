-- phpMyAdmin SQL Dump
-- version 3.5.2.2
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Apr 26, 2013 at 09:23 PM
-- Server version: 5.5.27
-- PHP Version: 5.4.7

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `bebras`
--

-- --------------------------------------------------------

--
-- Table structure for table `translations`
--

CREATE TABLE IF NOT EXISTS `translations` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `languageID` int(11) NOT NULL,
  `category` varchar(50) CHARACTER SET utf8 NOT NULL,
  `key` varchar(50) CHARACTER SET utf8 NOT NULL,
  `translation` text CHARACTER SET utf8 NOT NULL,
  PRIMARY KEY (`ID`),
  UNIQUE KEY `id_in_template` (`category`,`key`),
  KEY `languageID` (`languageID`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=20 ;

--
-- Dumping data for table `translations`
--

INSERT INTO `translations` (`ID`, `languageID`, `category`, `key`, `translation`) VALUES
(1, 1, 'contest', 'general_page_title', 'Castor - la plateforme'),
(4, 1, 'contest', 'general_instructions', 'Pour accéder à une présentation générale du concours Castor Informatique, allez sur &lt;a href=&quot;http://castor-informatique.fr&quot;&gt;la page d''accueil&lt;/a&gt;.'),
(5, 1, 'contest', 'general_choice', 'Choisissez ce que vous souhaitez :'),
(6, 1, 'contest', 'general_start_contest', 'Commencer un concours&lt;br/&gt;en classe'),
(7, 1, 'contest', 'general_public_contests', 'S''entraîner à la maison&lt;br /&gt;Consulter les annales'),
(8, 1, 'contest', 'general_view_results', 'Reprendre un concours ou&lt;br/&gt;voir ses résultats'),
(9, 1, 'contest', 'tab_start_contest_enter_code', 'Entrez le code de votre groupe, fourni par votre enseignant&amp;nbsp;: '),
(10, 1, 'contest', 'tab_start_contest_start_button', 'Je commence le concours'),
(11, 1, 'contest', 'tab_public_contests_info', 'Testez-vous sur l''un des concours publics ci-dessous.'),
(12, 1, 'contest', 'tab_public_contests_score_explanation', 'Votre score et une correction détaillée des questions vous seront fournis immédiatement après avoir validé vos réponses.'),
(13, 1, 'contest', 'tab_public_contests_organization', 'Le Castor est organisé en France depuis 2011. Les sujets 2010 ci-dessus sont ceux de l''édition Suisse. Ils sont également disponibles en version &lt;a target=&quot;blank&quot; href=&quot;http://castor-informatique.fr/documents/plaquette_2010.pdf&quot;&gt;pdf&lt;/a&gt;.'),
(14, 1, 'contest', 'tab_view_results_access_code', 'Entrez le code d''accès qui vous a été fourni au moment de démarrer le concours :'),
(15, 1, 'contest', 'tab_view_results_view_results_button', 'Accéder à ma participation'),
(16, 1, 'contest', 'tab_view_results_info_1', 'Vous pourrez revoir vos réponses, et si le concours est terminé, votre score et une correction de chaque question.'),
(17, 1, 'contest', 'tab_view_results_info_2', 'Vous n''avez pas de code d''accès?'),
(18, 1, 'contest', 'tab_view_results_info_3', 'Si vous êtes en classe, entrez ci-dessus le code de groupe fourni par votre enseignant.'),
(19, 1, 'contest', 'tab_view_results_info_4', 'Notez aussi que votre enseignant a accès à votre score depuis son interface coordinateur.');

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
