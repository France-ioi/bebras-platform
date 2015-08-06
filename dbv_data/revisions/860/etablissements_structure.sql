CREATE TABLE IF NOT EXISTS `etablissement` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `academieID` int(3) unsigned NOT NULL,
  `numero_uai` varchar(8) DEFAULT NULL,
  `appellation_officielle_uai` varchar(155) DEFAULT NULL,
  `denomination_principale_uai` varchar(32) DEFAULT NULL,
  `patronyme_uai` varchar(33) DEFAULT NULL,
  `secteur_public_prive` varchar(2) DEFAULT NULL,
  `adresse_uai` varchar(36) DEFAULT NULL,
  `lieu_dit_uai` varchar(26) DEFAULT NULL,
  `boite_postale_uai` varchar(7) DEFAULT NULL,
  `code_postal_uai` varchar(5) DEFAULT NULL,
  `localite_acheminement_uai` varchar(26) DEFAULT NULL,
  `X` varchar(8) DEFAULT NULL,
  `Y` varchar(8) DEFAULT NULL,
  `app` varchar(9) DEFAULT NULL,
  `loc` varchar(23) DEFAULT NULL,
  `etat_etablissement` int(1) DEFAULT NULL,
  `nature_uai` int(3) DEFAULT NULL,
  `lib_nature` varchar(40) DEFAULT NULL,
  `sous_fic` int(1) DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `academieID` (`academieID`),
  KEY `nature_uai` (`nature_uai`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

CREATE TABLE `recommendation` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `userID` int(11) NOT NULL,
  `email` varchar(50) NOT NULL,
  `date` datetime NOT NULL,
  PRIMARY KEY (ID),
  KEY (userID),
  KEY (email)
 );

CREATE TABLE `academy` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `domain` varchar(25) NOT NULL,
  PRIMARY KEY (ID),
  KEY (domain)
 );

CREATE TABLE `academies_dpt` (
     `academieID` int(11) NOT NULL AUTO_INCREMENT,
     `departement` int(11) NOT NULL,
     PRIMARY KEY (departement),
     KEY (academieID)
);

CREATE TABLE `etablissement_normalized` (
  `etablissementID` int(11) NOT NULL,
  `codePostal` varchar(5) NOT NULL,
  `lastWord` varchar(200) NOT NULL,
  `normalizedName` varchar(200) NOT NULL,
  PRIMARY KEY (etablissementID),
  KEY (normalizedName)
 );

CREATE TABLE `school_etablissement` (
  `ID` INT(11) NOT NULL AUTO_INCREMENT,
  `schoolID` int(11) NOT NULL,
  `etablissementID` int(11) NOT NULL,
  PRIMARY KEY(ID),
  KEY (schoolID),
  KEY (etablissementID)
 );
