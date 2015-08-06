<?php

/*

getting ready:
(1) in phpmyadmin, import schoolData/etablissement_avril_2014.sql.zip
(2) in a brower, open teacherInterface/nearbySchools.php
(3) click on all "execute" links, one by one

preview:
(4) click on each of the "view page for teacher" examples
(5) follow the link "voir la liste complète"

remaining to implement:
- generation of the select box for academy
- action of the "validate" button [see "TODO : VALIDATE" down in the file]
- integration of the box in the index page, with the correct userID

for checking/debugging:
- click on "all" to view all schools, or all participating schools
- open the examples "View details of page for teacher" 
- open the "nonmatching" page to view unmatched schools 
  => principalement lycées à l'étranger, noms communs (StMarie,StJoseph,..), 
  => ainsi que données incorrectes dans le code postal de la table school
     (à corriger avec un 
      SELECT * FROM schools WHERE NOT (1000 <= CAST(code_postal as SIGNED) <= 99999)
      à faire sur la base principale directement).

*/


error_reporting(E_ALL);


// DROP TABLE `recommend_academy_department`, `recommend_academy`, `recommend_listedschool`, `recommend_listedschool_normalized`, `recommend_school_listedschool`, `recommandation`;

//*****************************************************************
// tools

include_once '../shared/connect.php';

function getDb() {
   global $db;
   return $db;
}

function reportErrors($stmt) {
   $errs = $stmt->errorInfo();
   if (empty($errs) || intval($errs[0]) == 0)
      return;
   print_r($errs);
}

function viewQuery($stmt) {
   ob_start(); 
   $stmt->debugDumpParams();
   return pre(ob_get_clean());
}

//*****************************************************************

function createAcademyDptQuery() {
   $academies = <<<EOF
      4 5 13 84 
      2 60 80 
      25 39 70 90 
      24 33 40 47 64
      14 50 61
      3 15 43 63 
      20
      77 93 94
      21 58 71 89 
      7 26 38 73 74 
      971 977 978
      973 
      59 62 
      19 23 87
      1 42 69
      972
      11 30 34 48 66 
      54 55 57 88 
      44 49 53 72 85 
      6 83 
      18 28 36 37 41 45
      75 
      16 17 79 86
      08 10 51 52
      22 29 35 56
      974 
      27 76 
      67 68 
      9 12 31 32 46 65 81 82
      78 91 92 95 
EOF;
   $queryInsert = "INSERT INTO academies_dpt (academyID, departement) VALUES";
   $academiesGroups = explode("\n", $academies);
   foreach($academiesGroups as $idAcademieMinusOne => $academiesRow) {
      $departements = explode(" ", $academiesRow); 
      $departements = array_filter($departements, function($v) { return intval($v) != 0; });
      // echo "<br>".$idAcademieMinusOne. " "; print_r($departements);
      foreach ($departements as $departement) {
         $queryInsert .= "(" . ($idAcademieMinusOne+1) . "," . $departement . "),";
      }
   }
   $queryInsert = substr($queryInsert, 0, strlen($queryInsert)-1);
   $query = "DROP TABLE IF EXISTS `recommend_academy_department`;
   CREATE TABLE `recommend_academy_department` (
     `academyID` int(11) NOT NULL AUTO_INCREMENT,
     `departement` int(11) NOT NULL,
     PRIMARY KEY (departement),
     KEY (academyID)
    );";
   $query .= $queryInsert.";";
   // echo $queryInsert;
   return $query;
}


//*****************************************************************

function createAcademyQuery() {
   $query = "DROP TABLE IF EXISTS `recommend_academy`;
   CREATE TABLE `recommend_academy` (
     `ID` int(11) NOT NULL AUTO_INCREMENT,
     `name` varchar(50) NOT NULL,
     `domain` varchar(25) NOT NULL,
     PRIMARY KEY (ID),
     KEY (domain)
    );
    INSERT INTO `recommend_academy` (`ID`, `name`, `domain`) VALUES
      (1, 'Académie d\'Aix-Marseille', 'ac-aix-marseille.fr'),
      (2, 'Académie d\'Amiens', 'ac-amiens.fr'),
      (3, 'Académie de Besançon', 'ac-besancon.fr'),
      (4, 'Académie de Bordeaux', 'ac-bordeaux.fr'),
      (5, 'Académie de Caen', 'ac-caen.fr'),
      (6, 'Académie de Clermont-Ferrand', 'ac-clermont.fr'),
      (7, 'Académie de Corse', 'ac-corse.fr'),
      (8, 'Académie de Créteil', 'ac-creteil.fr'),
      (9, 'Académie de Dijon', 'ac-dijon.fr'),
      (10, 'Académie de Grenoble', 'ac-grenoble.fr'),
      (11, 'Académie de la Guadeloupe', 'ac-guadeloupe.fr'),
      (12, 'Académie de Guyane', 'ac-guyane.fr'),
      (13, 'Académie de Lille', 'ac-lille.fr'),
      (14, 'Académie de Limoges', 'ac-limoges.fr'),
      (15, 'Académie de Lyon', 'ac-lyon.fr'),
      (16, 'Académie de la Martinique', 'ac-martinique.fr'),
      (17, 'Académie de Montpellier', 'ac-montpellier.fr'),
      (18, 'Académie de Nancy-Metz', 'ac-nancy-metz.fr'),
      (19, 'Académie de Nantes', 'ac-nantes.fr'),
      (20, 'Académie de Nice', 'ac-nice.fr'),
      (21, 'Académie d\'Orléans-Tours', 'ac-orleans-tours.fr'),
      (22, 'Académie de Paris', 'ac-paris.fr'),
      (23, 'Académie de Poitiers', 'ac-poitiers.fr'),
      (24, 'Académie de Reims', 'ac-reims.fr'),
      (25, 'Académie de Rennes', 'ac-rennes.fr'),
      (26, 'Académie de La Réunion', 'ac-reunion.fr'),
      (27, 'Académie de Rouen', 'ac-rouen.fr'),
      (28, 'Académie de Strasbourg', 'ac-strasbourg.fr'),
      (29, 'Académie de Toulouse', 'ac-toulouse.fr'),
      (30, 'Académie de Versailles', 'ac-versailles.fr');
      ";
   // echo $queryInsert;
   return $query;
}




//*****************************************************************

function createRecommendationQuery() {
   $query = "DROP TABLE IF EXISTS `recommend_user`;
   CREATE TABLE `recommend_user` (
     `ID` int(11) NOT NULL AUTO_INCREMENT,
     `userID` int(11) NOT NULL,
     `email` varchar(50) NOT NULL,
     `date` datetime NOT NULL,
     PRIMARY KEY (ID),
     KEY (userID),
     KEY (email)
    );";
   return $query;
}




//*****************************************************************

function prepareEtablissementsQuery() {
   $query = "
      ALTER TABLE  `recommend_listedschool` ADD  `ID` INT( 11 ) NOT NULL AUTO_INCREMENT PRIMARY KEY FIRST ;
      ALTER TABLE  `recommend_listedschool` ADD  `academyID` INT( 3 ) UNSIGNED NOT NULL AFTER  `ID` , ADD INDEX (  `academyID` ) ;
      ALTER TABLE  `recommend_listedschool` ADD INDEX (  `nature_uai` ) ;

      UPDATE `recommend_listedschool` ta SET ta.academyID = 
         (SELECT tb.academyID
            FROM `recommend_academy_department` tb
            WHERE tb.departement = (CAST(ta.code_postal_uai AS SIGNED) DIV 1000) )
      ;
   ";
   // TODO: ajouter que 
   return $query;
}


//*****************************************************************

function prepareNormalizedNamesQuery() {
   $query = "DROP TABLE IF EXISTS `recommend_listedschool_normalized`;
   CREATE TABLE `recommend_listedschool_normalized` (
     `listedschoolID` int(11) NOT NULL,
     `codePostal` varchar(5) NOT NULL,
     `lastWord` varchar(200) NOT NULL,
     `normalizedName` varchar(200) NOT NULL,
     PRIMARY KEY (listedschoolID),
     KEY (normalizedName)
    );
    INSERT INTO `recommend_listedschool_normalized` (listedschoolID, codePostal, lastWord, normalizedName) 
    SELECT 
      ID, 
      `recommend_listedschool`.code_postal_uai,
      LOWER(SUBSTRING_INDEX(`recommend_listedschool`.appellation_officielle_uai, ' ', -1)),
      REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(LOWER(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(`recommend_listedschool`.appellation_officielle_uai, 
         'd\'enseignement', ''),
         'général', ''),
         'et technologique', ''),
         'des métiers', ''),
         'privé', ''), 
         'Etablissement régional d\'enseignement adapté', ''), 
         'Section d\'enseignement général et professionnel adapté du', '')), 
         'polyvalent', ''),
         '-', ''),
         'é', 'e'),
         'è', 'e'),
         'de ', ''),
         'du ', ''),
         ' ', '')
    FROM `recommend_listedschool` 
    WHERE `recommend_listedschool`.nature_uai >= 300
    AND (`recommend_listedschool`.appellation_officielle_uai NOT LIKE 'Section%')
    ;
    ";   /**/
   return $query;
}


//*****************************************************************

function getTableSchools() { 
   $query = "SELECT 
     name, 
     zipcode, 
     @zipcode2 := CAST(REPLACE(zipcode, ' ', '') AS SIGNED) as zipcode2,
     IF(@zipcode2 > 1000, CAST(@zipcode2 / 1000 AS SIGNED), @zipcode2) as dpt
     FROM `school` WHERE 1
     ";
     // pour voir ceux qui ont pas un bon nom: AND NOT (name LIKE 'Lycée%' OR name LIKE 'Collège%')
   $stmt = getDb()->prepare($query);
   $stmt->execute();
   reportErrors($stmt);
   $results = $stmt->fetchAll(PDO::FETCH_OBJ);
   if ($results === FALSE) 
     die("no results");
   $data = array();
   foreach ($results as  $id => $result) {
      $row = array();
      $row[] = $id;
      $row[] = $result->name;
      $row[] = $result->zipcode;
      $row[] = $result->zipcode2;
      $row[] = $result->dpt;
      $data[] = $row;
   }
   return $data;
}

//*****************************************************************

function getTableSchoolsMatchingView() { 

  $query = "
     SELECT 
     `school`.ID, 
     `school`.name, 
     `school`.zipcode, 
     `recommend_listedschool_normalized`.listedschoolID,
     `recommend_listedschool_normalized`.codePostal,
     `recommend_listedschool`.appellation_officielle_uai
     FROM `school` LEFT JOIN `recommend_listedschool_normalized` ON  
           REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(LOWER(`school`.name),
            'é', 'e'),
            'è', 'e'),
            '-', ''),
            'de ', ''),
            'du ', ''),
            ' ', '') 
         = `recommend_listedschool_normalized`.normalizedName
       AND
           (CAST(`recommend_listedschool_normalized`.codePostal AS SIGNED) DIV 1000) 
         = (CAST(`school`.zipcode AS SIGNED) DIV 1000)
     LEFT JOIN `recommend_listedschool` ON
         `recommend_listedschool`.ID = `recommend_listedschool_normalized`.listedschoolID
       WHERE
        `school`.id >= 0  AND `school`.id <= 100
      ";

   $stmt = getDb()->prepare($query);
   //$stmt->debugDumpParams();
   $stmt->execute();
   reportErrors($stmt);
   $results = $stmt->fetchAll(PDO::FETCH_OBJ);
   if ($results === FALSE) 
     die("no results");
   $data = array();
   $nbfound = 0;
   foreach ($results as $id => $result) {
      $row = array();
      // $row[] = $id;
      $row[] = $result->ID;
      $row[] = $result->listedschoolID;
      $row[] = $result->name;
      $row[] = $result->appellation_officielle_uai;
      $row[] = $result->zipcode;
      $row[] = $result->codePostal;
      $data[] = $row;
      if (! empty($result->appellation_officielle_uai))
         $nbfound++;
   }
   // echo ($nbfound / count($results) * 100) ."% match<br>";
   return $data;
}


//*****************************************************************
/* query to view action of matching 2

  $query = "
     SELECT 
     `school`.ID, 
     `school`.name, 
     `school`.zipcode, 
     `recommend_listedschool_normalized`.listedschoolID,
     `recommend_listedschool_normalized`.codePostal,
     `recommend_listedschool`.appellation_officielle_uai
     FROM `school` LEFT JOIN `recommend_listedschool_normalized` ON  
            (CAST(`recommend_listedschool_normalized`.codePostal AS SIGNED) DIV 1000) 
         = (CAST(`school`.zipcode AS SIGNED) DIV 1000)
         AND 
           `recommend_listedschool_normalized`.lastWord 
         = LOWER(SUBSTRING_INDEX(`school`.name, ' ', -1))
     LEFT JOIN `recommend_listedschool` ON
         `recommend_listedschool`.ID = `recommend_listedschool_normalized`.listedschoolID
     WHERE
        0 = (SELECT COUNT(*) as r FROM `recommend_school_listedschool`
                     WHERE `recommend_school_listedschool`.schoolID = `school`.ID)
      AND 
         1 = (SELECT COUNT(*) 
        FROM `recommend_listedschool_normalized` 
        WHERE
            (CAST(`recommend_listedschool_normalized`.codePostal AS SIGNED) DIV 1000) 
         = (CAST(`school`.zipcode AS SIGNED) DIV 1000)
         AND 
           `recommend_listedschool_normalized`.lastWord 
         = LOWER(SUBSTRING_INDEX(`school`.name, ' ', -1))
        ) 
      ";
*/

//*****************************************************************

function getSchoolsMatchingManualQuery() { 
   $query = "REPLACE INTO `recommend_school_listedschool` (schoolId, listedschoolID) VALUES
(57, 33812),
(57, 35795),
(77, 9419),
(77, 9654),
(92, 34865),
(98, 56665),
(117, 27621),
(117, 28396),
(120, 67078),
(173, 54350),
(215, 60862),
(246, 32056),
(264, 36931),
(264, 37230),
(264, 37278),
(301, 55110),
(301, 55113),
(302, 35796),
(307, 32174),
(307, 32188),
(307, 32760),
(307, 33034),
(307, 33053),
(318, 34795),
(319, 15213),
(319, 15870),
(319, 15705),
(392, 27894),
(397, 65523),
(411, 48809),
(411, 48930),
(455, 35502),
(468, 48118),
(468, 48696),
(474, 61300),
(523, 33737),
(523, 33822),
(544, 58695),
(553, 37713),
(581, 25077),
(628, 17906),
(628, 18353),
(654, 48090),
(654, 48683),
(718, 34852),
(727, 66747),
(727, 66840),
(779, 53976),
(779, 54499),
(793, 28519),
(793, 28941),
(802, 66141),
(802, 66736),
(845, 30732),
(852, 33041),
(876, 7605),
(878, 52387),
(878, 53074),
(894, 33727),
(894, 35800),
(938, 14409),
(938, 15066),
(942, 6981),
(942, 7396),
(946, 2218),
(946, 2734),
(953, 43721),
(953, 44522),
(953, 44697),
(954, 18623),
(954, 18651),
(967, 30323),
(967, 30897),
(976, 37693),
(977, 20432),
(977, 20695),
(992, 649),
(992, 660),
(992, 666),
(992, 1165),
(993, 9),
(993, 10),
(1024, 14452),
(1024, 15058),
(1033, 2189),
(1033, 2190),
(1035, 14451),
(1035, 15060),
(1035, 15149),
(1053, 31420),
(1053, 31452),
(1053, 32016),
(1053, 32113),
(1072, 66023),
(1075, 14961),
(1075, 14367),
(1105, 27411),
(1116, 10479),
(1116, 11093),
(1141, 19530),
(1144, 7874),
(1144, 7894),
(1158, 32194),
(1158, 33042),
(1158, 33052),
(1161, 62192),
(1182, 40054),
(1182, 39765),
(1192, 12477),
(1192, 12531),
(1211, 27872),
(1211, 28347),
(1228, 25046),
(1228, 25929),
(1236, 648),
(1237, 44062),
(1237, 44605),
(1248, 58197),
(1248, 58605),
(1260, 10447),
(1260, 11041),
(1276, 40156),
(1276, 40780),
(1277, 25041),
(1277, 25924),
(1277, 26057),
(1290, 49838),
(1290, 49995),
(1291, 48132),
(1291, 48706),
(1292, 8370),
(1292, 8371),
(1339, 41690),
(1389, 35902),
(1463, 35911),
(1471, 52962),
(1471, 53375),
(1478, 13138),
(1484, 15186),
(1502, 23853),
(1502, 24401),
(1510, 49999),
(1536, 19774),
(1536, 19779),
(1536, 20188),
(1544, 31457),
(1547, 13133),
(1576, 36271),
(1576, 36289),
(1601, 14411),
(1601, 14421),
(1610, 36927),
(1610, 37172),
(1610, 37297),
(1612, 60507),
(1612, 61029),
(1612, 61123),
(1625, 48808),
(1625, 48966),
(1633, 49269),
(1633, 49282),
(1633, 49284),
(1643, 46958),
(1643, 47249),
(1648, 38502),
(1650, 21369),
(1650, 22232),
(1668, 9724),
(1668, 9736),
(1668, 10175),
(1669, 30318),
(1669, 30738),
(1675, 60510),
(1675, 60738),
(1675, 61127),
(1720, 32147),
(1720, 32636),
(1728, 48052),
(1728, 48105),
(1728, 48690),
(1739, 31159),
(1739, 31173),
(1741, 33768),
(1741, 33824),
(1743, 6962),
(1760, 37694),
(1760, 38598),
(1781, 32192),
(1795, 45316),
(1806, 21369),
(1830, 35383),
(1838, 47710),
(1873, 19734),
(1873, 19748),
(1875, 24200),
(1875, 24614),
(1878, 60845),
(1878, 61183),
(1878, 61189),
(1882, 12547),
(1882, 12975),
(1899, 63053),
(1899, 63416),
(1966, 10486),
(1966, 11098),
(1972, 52961),
(1972, 53363),
(1990, 38529),
(1999, 47254),
(2035, 34828),
(2035, 34868),
(2042, 59206),
(2042, 59341),
(2052, 61883),
(2066, 10447),
(2066, 11041)
;
      ";
   return $query;
}
/*
(585, 27615),
(592, 36259),
(615, 46486),
(625, 37662),
(628, 18353),
(641, 35439),
(643, 41280),
(654, 48683),
(654, 48090),
*/

//*****************************************************************

function getTableSchoolsNonMatchingView() { 

  $query = "
     SELECT 
     `school`.ID, 
     `school`.name, 
     `school`.zipcode, 
     `recommend_listedschool_normalized`.listedschoolID,
     `recommend_listedschool_normalized`.codePostal,
     `recommend_listedschool`.appellation_officielle_uai
     FROM `school` LEFT JOIN `recommend_listedschool_normalized` ON  
            (CAST(`recommend_listedschool_normalized`.codePostal AS SIGNED) DIV 1000) 
         = (CAST(`school`.zipcode AS SIGNED) DIV 1000)
         AND 
           `recommend_listedschool_normalized`.lastWord 
         = LOWER(SUBSTRING_INDEX(`school`.name, ' ', -1))
     LEFT JOIN `recommend_listedschool` ON
         `recommend_listedschool`.ID = `recommend_listedschool_normalized`.listedschoolID
     WHERE
        0 = (SELECT COUNT(*) as r FROM `recommend_school_listedschool`
                     WHERE `recommend_school_listedschool`.schoolID = `school`.ID)
  
      ";

//echo "WARNING: limited display";     AND `school`.ID <= 100
   $stmt = getDb()->prepare($query);
   //$stmt->debugDumpParams();
   $stmt->execute();
   reportErrors($stmt);
   $results = $stmt->fetchAll(PDO::FETCH_OBJ);
   if ($results === FALSE) 
     die("no results");
   $data = array();
   $nbfound = 0;
   foreach ($results as $id => $result) {
      $row = array();
      // $row[] = $id;
      $row[] = $result->ID;
      $row[] = $result->listedschoolID;
      $row[] = $result->name;
      $row[] = $result->appellation_officielle_uai;
      $row[] = $result->zipcode;
      $row[] = $result->codePostal;
      $data[] = $row;
      if (! empty($result->appellation_officielle_uai))
         $nbfound++;
   }
   return $data;
}


//*****************************************************************

function getSchoolsMatchingQuery() { 

  $query = "
   DROP TABLE IF EXISTS `recommend_school_listedschool`;

   CREATE TABLE `recommend_school_listedschool` (
     `ID` INT(11) NOT NULL AUTO_INCREMENT,
     `schoolID` int(11) NOT NULL,
     `listedschoolID` int(11) NOT NULL,
     PRIMARY KEY(ID),
     KEY (schoolID),
     KEY (listedschoolID)
    );

    INSERT INTO `recommend_school_listedschool` (schoolID, listedschoolID) 
     (SELECT 
     `school`.ID, 
     `recommend_listedschool_normalized`.listedschoolID
     FROM `school` JOIN `recommend_listedschool_normalized` ON  
           REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(LOWER(`school`.name),
            'é', 'e'),
            'è', 'e'),
            '-', ''),
            'de ', ''),
            'du ', ''),
            ' ', '') 
         = `recommend_listedschool_normalized`.normalizedName
       AND
           (CAST(`recommend_listedschool_normalized`.codePostal AS SIGNED) DIV 1000) 
         = (CAST(`school`.zipcode AS SIGNED) DIV 1000)
       );

    INSERT INTO `recommend_school_listedschool` (schoolID, listedschoolID) 
     (SELECT 
     `school`.ID, 
     `recommend_listedschool_normalized`.listedschoolID
     FROM `school` LEFT JOIN `recommend_listedschool_normalized` ON  
            (CAST(`recommend_listedschool_normalized`.codePostal AS SIGNED) DIV 1000) 
         = (CAST(`school`.zipcode AS SIGNED) DIV 1000)
         AND 
           `recommend_listedschool_normalized`.lastWord 
         = LOWER(SUBSTRING_INDEX(`school`.name, ' ', -1))
     LEFT JOIN `recommend_listedschool` ON
         `recommend_listedschool`.ID = `recommend_listedschool_normalized`.listedschoolID
     WHERE
        0 = (SELECT COUNT(*) as r FROM `recommend_school_listedschool`
                     WHERE `recommend_school_listedschool`.schoolID = `school`.ID)
      AND 
         1 = (SELECT COUNT(*) 
        FROM `recommend_listedschool_normalized` 
        WHERE
            (CAST(`recommend_listedschool_normalized`.codePostal AS SIGNED) DIV 1000) 
         = (CAST(`school`.zipcode AS SIGNED) DIV 1000)
         AND 
           `recommend_listedschool_normalized`.lastWord 
         = LOWER(SUBSTRING_INDEX(`school`.name, ' ', -1))
        ) 
      );
      ";
   return $query; 
}



//*****************************************************************

function tableEtablissements($academyID, $filterType, $details = true) {
   $filterAcademie = "1";
   if ($academyID > 0)
      $filterAcademie = "academyID = :academyID";
   $query = "SELECT 
     numero_uai,
     nature_uai,
     appellation_officielle_uai, 
     patronyme_uai,
     code_postal_uai
     FROM `recommend_listedschool` 
     WHERE $filterType 
     AND $filterAcademie
     AND (patronyme_uai NOT LIKE '' OR appellation_officielle_uai NOT LIKE 'Collège')
     ORDER BY code_postal_uai ";
   $stmt = getDb()->prepare($query);
   // $stmt->debugDumpParams();
   $stmt->execute(array(':academyID' => $academyID));
   reportErrors($stmt);
   $results = $stmt->fetchAll(PDO::FETCH_OBJ);
   if ($results === FALSE) 
     die("no results");
   $data = array();
   foreach ($results as $id => $result) {
      $row = array();
      if ($details) {
         $row[] = $id;
         $row[] = $result->numero_uai;
         $row[] = $result->nature_uai;
         $row[] = $result->appellation_officielle_uai;
         $row[] = $result->patronyme_uai;
         $row[] = $result->code_postal_uai;
      } else {
         $row[] = $result->appellation_officielle_uai;
      }
      $data[] = $row;
   }
   return $data;
}


//*****************************************************************

function tablePartipEtablissements($academyID, $filterType) {
   $filterAcademie = "1";
   if ($academyID > 0)
      $filterAcademie = "academyID = :academyID";
  
    // pour avoir aussi le userID d'un gars de l'établissement:
    $query = "SELECT     
     `recommend_listedschool`.appellation_officielle_uai as name,
     SUBSTRING_INDEX(`recommend_listedschool`.localite_acheminement_uai, 'CEDEX', 1) as city,
     (IF ((SELECT COUNT(*)
           FROM `recommend_school_listedschool` 
           JOIN `school_year` 
           ON `school_year`.schoolID = `recommend_school_listedschool`.schoolID 
           AND `school_year`.year >= 2013
           WHERE `recommend_school_listedschool`.listedschoolID = `recommend_listedschool`.ID) = 1, 'CASTOR', ''))
        as participating,
       `school`.userID as userID
     FROM `recommend_listedschool` 
     LEFT JOIN `recommend_school_listedschool` 
       ON `recommend_school_listedschool`.listedschoolID = `recommend_listedschool`.ID
     LEFT JOIN `school` 
       ON `school`.ID = `recommend_school_listedschool`.schoolID 
     WHERE $filterType 
      AND $filterAcademie
      AND (patronyme_uai NOT LIKE '' OR appellation_officielle_uai NOT LIKE 'Collège')
     ORDER BY city";

  $query = "SELECT     
     `recommend_listedschool`.appellation_officielle_uai as name,
     SUBSTRING_INDEX(`recommend_listedschool`.localite_acheminement_uai, 'CEDEX', 1) as city,
     (IF ((SELECT COUNT(*)
           FROM `recommend_school_listedschool` 
           JOIN `school_year` 
           ON `school_year`.schoolID = `recommend_school_listedschool`.schoolID 
           AND `school_year`.year >= 2013
           WHERE `recommend_school_listedschool`.listedschoolID = `recommend_listedschool`.ID) = 1, 'CASTOR', ''))
        as participating
     FROM `recommend_listedschool` 
     WHERE $filterType 
      AND $filterAcademie
      AND (patronyme_uai NOT LIKE '' OR appellation_officielle_uai NOT LIKE 'Collège')
     ORDER BY city";
   $stmt = getDb()->prepare($query);
   // $stmt->debugDumpParams();
   $stmt->execute(array(':academyID' => $academyID));
   reportErrors($stmt);
   $results = $stmt->fetchAll(PDO::FETCH_OBJ);
   if ($results === FALSE) 
     die("no results");
   $data = array();
   foreach ($results as $id => $result) {
      $row = array();
      $row[] = $result->participating;
      $row[] = $result->name;
      $row[] = $result->city;
      // $row[] = $result->userID;
      $data[] = $row;
   }
   return $data;
}

//*****************************************************************
// Shared functions for below

function getTeacherAcademie($userID) {
   $query = "SELECT `recommend_academy`.ID as academyID
      FROM `user` 
      LEFT JOIN `recommend_academy` 
      ON `recommend_academy`.domain = SUBSTRING_INDEX(`user`.officialEmail, '@', -1)
      WHERE `user`.ID = :userID
      ";
   $stmt = getDb()->prepare($query);
   $results = $stmt->execute(array(':userID' => $userID));
   $results = $stmt->fetch(PDO::FETCH_OBJ);
   if ($results == FALSE) 
      return -1;
   return $results->academyID;
}

function getTeacherOneSchool($userID) {
   $query = "SELECT `school_user`.schoolID, 
                     SUBSTRING_INDEX(`school`.name, ' ', 1) as type,
                     `recommend_listedschool`.X, `recommend_listedschool`.Y 
         FROM `school_user` 
         LEFT JOIN `school` 
           ON `school`.ID = `school_user`.schoolID
         LEFT JOIN `recommend_school_listedschool` 
           ON `recommend_school_listedschool`.schoolID = `school`.ID
         LEFT JOIN `recommend_listedschool` 
           ON `recommend_listedschool`.ID = `recommend_school_listedschool`.listedschoolID
         WHERE `school_user`.userID = :userID";
   $stmt = getDb()->prepare($query);
   $results = $stmt->execute(array(':userID' => $userID));
   $results = $stmt->fetch(PDO::FETCH_OBJ);
   $obj = new stdClass;
   if ($results !== FALSE) {
     $obj->ID = $results->schoolID;
     $obj->type = $results->type;
     $obj->X = $results->X;
     $obj->Y = $results->Y;
   } else {
     $obj->ID = -1;
     $obj->type = '';
   }
   if (empty($obj->X) || empty($obj->Y)) {
     $obj->X = 0;
     $obj->Y = 0;
   }
   return $obj;
}

function getNearbySchools($academyID, $schoolType, $X, $Y) {
     // TODO: on pourrait ajouter une limite à la query, mais ça ne semble pas utile.

   if ($schoolType == "Lycée")
      $filterType = getConstraintLycees();
   else if ($schoolType == "Collège")
      $filterType = getConstraintColleges();
   else 
      $filterType = getConstraintCollegesLycees();

  $query = "SELECT 
     `recommend_listedschool`.appellation_officielle_uai as name,
     SUBSTRING_INDEX(`recommend_listedschool`.localite_acheminement_uai, 'CEDEX', 1) as city,
     SQRT(POW(:X - `recommend_listedschool`.X, 2) + POW(:Y - `recommend_listedschool`.Y, 2)) as distance
     FROM `recommend_listedschool` 
     WHERE $filterType 
     AND academyID = :academyID
     AND (patronyme_uai NOT LIKE '' OR appellation_officielle_uai NOT LIKE 'Collège')
     AND (appellation_officielle_uai NOT LIKE '%privé%' COLLATE utf8_general_ci)
     AND 
          NOT EXISTS (SELECT * FROM `recommend_school_listedschool` 
           JOIN `school_year` 
           ON `school_year`.schoolID = `recommend_school_listedschool`.schoolID 
           AND `school_year`.year >= 2013
           WHERE `recommend_school_listedschool`.listedschoolID = `recommend_listedschool`.ID)
     ORDER BY SQRT(POW(:X - `recommend_listedschool`.X, 2) + POW(:Y - `recommend_listedschool`.Y, 2)) ";
     // LIMIT 10
   $stmt = getDb()->prepare($query);
   // $stmt->debugDumpParams();
   $stmt->execute(array(':academyID' => $academyID, ':X' => $X, ':Y' => $Y));
   reportErrors($stmt);
   $results = $stmt->fetchAll(PDO::FETCH_OBJ);
   if ($results === FALSE) 
      $results = array();
   return $results; // array of {name,city,distance} objects, ordered by distance.
}


//*****************************************************************

function getViewDetailsForTeacher($userID) {
   $s = '';

   $sdata = '';

   $academyID = getTeacherAcademie($userID);
   $school = getTeacherOneSchool($userID);
   $schoolType = $school->type;
   $schoolID = $school->ID;
   $schoolX = $school->X;
   $schoolY = $school->Y;
 
   $schools = getNearbySchools($academyID, $schoolType, $schoolX, $schoolY);
   $data = array();
   foreach ($schools as $id => $school) {
      $row = array();
      $row[] = getSchoolShortName($school->name);
      $row[] = $school->name;
      $row[] = $school->city;
      $row[] = $school->distance;
      $data[] = $row;
   }
   $sdata = displayTable($data);

   $s .= "academyID = $academyID<br>";
   $s .= "schoolID = $schoolID<br>";
   $s .= "schoolType = $schoolType<br>";
   $s .= "schoolX = $schoolX<br>";
   $s .= "schoolY = $schoolY<br>";
   $s .= "===========================<br>";
   if ($academyID != -1) {
      if ($schoolType == "Lycée")
        $s .= "Voir la participation de tous les " .linkAction("viewParticipLycees", "lycees", "&academie=" . $academyID). " de votre académie";
      else if ($schoolType == "Collège")
        $s .= "Voir la participation de tous les " .linkAction("viewParticipColleges", "colleges", "&academie=" . $academyID). " de votre académie";
      else
         $s .= "Voir la participation des établissements de votre académie : " .linkAction("viewParticipColleges", "colleges", "&academie=" . $academyID). " " .linkAction("viewParticipLycees", "lycees", "&academie=" . $academyID);
   } else
      $s .= "Academie not deduced from email.";

   $s .= "<br>Etblissements non participants les plus proches : <br> ";
   $s .= $sdata;

   return $s;
}


//*****************************************************************

function getSchoolShortName($schoolName) {
   return str_replace(array('  '), array(' '), str_replace(array(
         'd\'enseignement',
         'générale',
         'général',
         'et technologique',
         'des métiers',
         'privée', 
         'Privée', 
         'Privé', 
         'privé', 
         '(', 
         ')', 
         'Ecole', 
         'Secondaire', 
         'secondaire', 
         'catholique', 
         'Catholique', 
         'Etablissement régional d\'enseignement adapté', 
         'Section d\'enseignement général et professionnel adapté du', 
         'polyvalent',
         'Collège',
         'Lycée',
         ), array('', '', '', '', '',  '', '','', '', '', '', '', '', '', ''), $schoolName));
}



//*****************************************************************

function getViewForTeacher($userID) {
   $academyID = getTeacherAcademie($userID);
   $school = getTeacherOneSchool($userID);
   $schoolType = $school->type;
   $schoolID = $school->ID;
   $schoolX = $school->X;
   $schoolY = $school->Y;
 
   $displaysCols = 2;
   $nbSchoolsDisplay = 9;
   $schools = getNearbySchools($academyID, $schoolType, $schoolX, $schoolY);
   $schools = array_slice($schools, 0, $nbSchoolsDisplay);
   $schoolNames = array_map(function($s) { 
      return getSchoolShortName($s->name); }, $schools );
   $schoolNames = array_filter($schoolNames, function($x) {
      return trim($x) != ""; });

   // todo: fixe link
   if ($schoolType == "Lycée")
      $sUrlFullAction = "viewParticipLycees";
   else if ($schoolType == "Collège")
      $sUrlFullAction = "viewParticipColleges";
   else 
      $sUrlFullAction = "viewParticipAll";
   $sUrlFull = "nearbySchools.php?action=$sUrlFullAction&academie=$academyID";

   $sRows1 = '';
   $sRows2 = '';
   $nb1 = ($nbSchoolsDisplay+1)/$displaysCols;
   $schoolNames1 = array_slice($schoolNames, 0, $nb1);
   $schoolNames2 = array_slice($schoolNames, $nb1);
   foreach ($schoolNames1 as $schoolName) 
      $sRows1 .= "<li> $schoolName </li>";
   foreach ($schoolNames2 as $schoolName) 
      $sRows2 .= "<li> $schoolName </li>";

   $sFullList = "<li>... <span style='font-size: small'><a href='$sUrlFull'>voir la liste complète</a></span></li>";
   if ($displaysCols == 1)
      $sRows1 .= $sFullList;
   else
      $sRows2 .= $sFullList;

   $s = '';
   // TODO: fixer ça une fois qu'on a choisit
   $width = 100 / $displaysCols;
   $s .= "<style>
   #spread-castor {
      background: #FFCCCC;
      border: 1px solid black;
      width: 21em;
      padding-left: 0.5em;
      padding-right: 0.2em;
      padding-bottom: 0.5em;
   }
   #spread-castor h2 {
      margin: 0.2em;
      margin-bottom: 0.4em;
      padding: 0em;
      font-size: 1.5em;
      font-weight: bold;
      text-align: center;
   }
   #spread-castor ul {
      margin-top: 0.2em;
      margin-bottom: 1em;
      padding-left: 1.5em;
   }
   #spread-castor-table td {
      vertical-align: top;
      width: $width%;
   }
   #spread-castor-email {
      width: 8em;
   }
   </style>";
   $s .= "<div id='spread-castor'>
   <h2>Faites connaître le Castor<br> autour de vous !</h2>";
   if ($academyID != -1) 
     $s .= "<div>Établissements n'ayant pas de coordinateur :</div>
      <table class='spread-castor-table'><tr>
         <td><ul>$sRows1</ul></td>
         <td><ul>$sRows2</ul></td>
      </tr></table>
      ";
   $s .= "
   <div style='padding-bottom:0.2em'>Recommander le Castor de ma part à :</div>
   <div><i>e-mail</i> 
   <input id='spread-castor-email' type='text'>
   <input id='spread-castor-send' type='button' value='Envoyer'></div>
   </div>
   ";

   return $s;
}



//*****************************************************************
// TODO : VALIDATE

function hasAlreadyRecommendation($email) {
   return "
         SELECT COUNT(*)
         FROM ̀recommendatioǹ
         WHERE email LIKE :email
     ";
}

function isAlreadyCoordinateur($email) {
   return "
         SELECT COUNT(*)
         FROM ̀user̀
         WHERE officialEmail LIKE :email
     ";
}

function addRecommendation($userID, $email) {
   return "INSERT INTO ̀recommendatioǹ (userID, email, date)
      VALUES (:userID, :email, NOW())
     ";
}

function userIsSpammingRecommendation($userID) {
   return "
     SELECT COUNT(*) 
     FROM ̀recommendatioǹ 
     WHERE userID = :userID
     ";
}

/* Messages pour le email :

   Bonjour,

   Votre collègue XXX YYY souhaite vous recommander le Concours Castor.

   Ce concours, entièrement gratuit, vise à faire découvrir à vos élèves l'informatique et les sciences du numérique. Il dure 45 minutes et ne requiert aucune connaissance préalable en informatique. 
   
   Le concours se déroule dans une salle informatique de votre établissement, à n'importe quel moment au cours de la semaine du 12 au 19 Novembre 2014. Pour faire participer vos élèves, il suffit de vous inscrire dès maintenant comme coordinateur et de créer les groupes de passage pour vos classe. 
   
   Pour en savoir plus : http://castor-informatique.fr/

   Bien Cordialement,

   L'équipe du Castor
   info@castor-informatique.fr




   
   Messages pour l'interface :

   - Le message de recommandation a bien été envoyé. Merci !

   - Ce email correspond à un enseignant qui a déjà reçu une recommandation.
   
   - Cet email correspond à un enseignant qui a déjà été coordinateur.

   - Vous avez dépassé le nombre maximum de recommandations que le site peut envoyer pour vous. Pour continuer, merci d'envoyer vous-même les email de recommandation.

*/





//*****************************************************************

function displayTable($data) {
   $s = count($data) . " rows</br>";
   $s .= "<table class='table'>\n";
   foreach ($data as $row) {
      $srow = "<tr>\n";
      foreach ($row as $col) {
         $srow .= "<td>" . $col . "</td>\n";
      }
      $srow .= "</tr>\n";
      $s .= $srow;
   }
   $s .= "</table>\n";
   return $s;
}


function displayInteractiveTable($data) {
   $s = count($data) . " rows</br>";
   $s .= "<form name='myform'><textarea name='myselection' rows=10 cols=80></textarea></form>";
   $s .= "<table class='table'>\n";
   foreach ($data as $row) {
      $srow = "<tr>\n ";
      if (!empty($row[1])) {
         $sitem = trim(addslashes('('.$row[0] . ', '. $row[1] . '), '));
         $srow .= "<td> <span style='text-decoration: underline' onclick=\"document.myform.myselection.value += '$sitem\\n';\">GET</span> </td>\n";
      } else {
         $srow .= "<td></td>";
      }
      foreach ($row as $col) {
         $srow .= "<td>" . $col . "</td>\n";
      }
      $srow .= "</tr>\n";
      $s .= $srow;
   }
   $s .= "</table>\n";
   return $s;
}



function displayPage($body) {
echo <<<EOF
   <!DOCTYPE html>
   <html>
   <head>
   <meta charset='utf-8'>
   <title>Castor - NearbySchools</title>
   <style>
   .table {
      border-collapse: collapse;
   }
   .table td {
     border: 1px solid black;
     padding: 0.2em;
   }
   </style>
   </head><body>
   $body
   </body></html>
EOF;
   }
//    <script src='../ext/jqueryUI/js/jquery-1.7.2.min.js'></script>




//*****************************************************************
// Helper functions

function linkAction($action, $label, $extraArgs = "") {
   return "<a href='nearbySchools.php?action=" . $action . $extraArgs . "'>" . $label . "</a>";
}

function pre($s) {
   return "<pre>".$s."</pre>";
}

function getAcademieArg() {
   $academie = "1";
   if (isset($_GET['academie']))
      $academie = $_GET['academie'];
   return $academie;
}

function getConstraintColleges() {
   return "nature_uai = 340";
}

function getConstraintLycees() {
   return "(nature_uai = 300 OR nature_uai = 302)";
}

function getConstraintCollegesLycees() {
   return "(" . getConstraintLycees() . " OR " . getConstraintColleges() . ")";
}

function getConstraintAll() {
   return "nature_uai >= 300";
}

//*****************************************************************
// Menu

$menu =
   "utilisation : charger la base 'recommend_listedschool' à partir du fichier sql, puis cliquer sur tous les boutons 'execute' dans ci-dessous l'ordre. Enfin, cliquer sur les example en bas. Pour voir les établissements non associés, cliquer sur 'nonmatching'.<br>
   <ul>
   <li>Generate all execution query in 'nearby.sql': " .linkAction("generateAllExecutionQuery", "generate") . "</li>
   <li>Create recommendation : " .linkAction("createRecommendationQuery", "query"). " " .linkAction("createRecommendationExecute", "execute") . "</li>
   <li>Create academy : " .linkAction("createAcademyQuery", "query"). " " .linkAction("createAcademyExecute", "execute") . "</li>
   <li>Create academy_dpt : " .linkAction("createAcademyDptQuery", "query"). " " .linkAction("createAcademyDptExecute", "execute") . "</li>
   <li>Prepare recommend_listedschool : " .linkAction("prepareEtablissementsQuery", "query"). " " .linkAction("prepareEtablissementsExecute", "execute") . " (takes a dozen seconds)</li>
   <li>View by academie (modify url to filter by academie in range 1-30) : " .linkAction("viewColleges", "colleges", "&academie=0"). " " .linkAction("viewLycees", "lycees", "&academie=0") . " " .linkAction("viewBoth", "both", "&academie=0") . " " .linkAction("viewAll", "all", "&academie=0") . "</li>
   <li>View participating schools " .linkAction("viewSchools", "all") . "</li>
   <li>Prepare table normalized names " .linkAction("prepareNormalizedNamesQuery", "query") ." " . linkAction("prepareNormalizedNamesExecute", "execute") . "</li>
   <li>Matching schools " .linkAction("viewSchoolsMatchingView", "matching 1") . "(preview for 100 first schools)  " . linkAction("viewSchoolsNonMatchingView", "nonmatching") . "(view all), matching 1 and 2 : " .linkAction("viewSchoolsMatchingExecute", "execute") . " (takes a minute or so), manual matching : ". linkAction("schoolsMatchingManualExecute", "execute"). "   </li>
   <li>Particip by academie (modify url to select an academie in the range 1-30) : " .linkAction("viewParticipColleges", "colleges", "&academie=1"). " " .linkAction("viewParticipLycees", "lycees", "&academie=1") ." " .linkAction("viewParticipBoth", "both", "&academie=1") ." "
   .linkAction("viewParticipAll", "all", "&academie=1") .  "</li>
   <li>View details of page for teacher (modify url to change teacher id) : " .linkAction("viewDetailsForTeacher", "example collège", "&userID=104"). " "  .linkAction("viewDetailsForTeacher", "example lycée", "&userID=2398"). " "  .linkAction("viewDetailsForTeacher", "example unknown", "&userID=92"). "</li>
   <li>View page for teacher (modify url to change teacher id) : " .linkAction("viewForTeacher", "example collège", "&userID=104"). " "  .linkAction("viewForTeacher", "example lycée", "&userID=2398"). " "  .linkAction("viewForTeacher", "example unknown", "&userID=92"). "</li>
   </ul>";

   // teachers login:
   // 104: beatrice.gaspalou@ac-nantes.fr
   // 92: anne-marie.cagnon@ac-lille.fr
   // 2398: marc.lobel@ac-lille.fr


$body = "";



//*****************************************************************
// Action

if (isset($_GET['action']))
   $action = $_GET['action'];
else
   $action = "";

if ($action =="generateAllExecutionQuery") {
   $s = '';
   $s .= createRecommendationQuery()."\n\n";
   $s .= createAcademyQuery()."\n\n";
   $s .= createAcademyDptQuery()."\n\n";
   $s .= prepareEtablissementsQuery()."\n\n";
   $s .= prepareNormalizedNamesQuery()."\n\n";
   $s .= getSchoolsMatchingQuery()."\n\n";
   $s .= getSchoolsMatchingManualQuery()."\n\n";
   file_put_contents("nearby.sql", $s);
   $body = "Generated nearby.sql";

} else if ($action == "createRecommendationQuery") {
   $body = viewQuery(createRecommendationQuery());   
} else if ($action == "createRecommendationExecute") {
   $stmt = getDb()->prepare(createRecommendationQuery());
   $stmt->execute();
   reportErrors($stmt);
   $body = "Created table recommendation.";

} else if ($action == "createAcademyQuery") {
   $body = viewQuery(createAcademyQuery());   
} else if ($action == "createAcademyExecute") {
   $stmt = getDb()->prepare(createAcademyQuery());
   $stmt->execute();
   reportErrors($stmt);
   $body = "Created table academy.";

} else if ($action == "createAcademyDptQuery") {
   $body = viewQuery(createAcademyDptQuery());   
} else if ($action == "createAcademyDptExecute") {
   $stmt = getDb()->prepare(createAcademyDptQuery());
   $stmt->execute();
   reportErrors($stmt);
   $body = "Created table academy_dpt.";

} else if ($action == "prepareEtablissementsQuery") {
   $body = viewQuery(prepareEtablissementsQuery());   
} else if ($action == "prepareEtablissementsExecute") {
   $stmt = getDb()->prepare(prepareEtablissementsQuery());
   $stmt->execute();
   reportErrors($stmt);
   $body = "Processed table etablissements.";

} else if ($action == "viewColleges") {
   $body = displayTable(tableEtablissements(getAcademieArg(), getConstraintColleges()));

} else if ($action == "viewLycees") {
   $body = displayTable(tableEtablissements(getAcademieArg(), getConstraintLycees()));

} else if ($action == "viewBoth") {
   $body = displayTable(tableEtablissements(getAcademieArg(), getConstraintCollegesLycees()));

} else if ($action == "viewAll") {
   $body = displayTable(tableEtablissements(getAcademieArg(), getConstraintAll()));


} else if ($action == "viewParticipColleges") {
   $body = displayTable(tablePartipEtablissements(getAcademieArg(), getConstraintColleges()));

} else if ($action == "viewParticipLycees") {
   $body = displayTable(tablePartipEtablissements(getAcademieArg(), getConstraintLycees()));

} else if ($action == "viewParticipBoth") {
   $body = displayTable(tablePartipEtablissements(getAcademieArg(), getConstraintCollegesLycees()));

} else if ($action == "viewParticipAll") {
   $body = displayTable(tablePartipEtablissements(getAcademieArg(), getConstraintAll()));


} else if ($action == "viewSchools") {
   $body = displayTable(getTableSchools());


} else if ($action == "prepareNormalizedNamesQuery") {
   $body = viewQuery(prepareNormalizedNamesQuery());   

} else if ($action == "prepareNormalizedNamesExecute") {
   $stmt = getDb()->prepare(prepareNormalizedNamesQuery());
   $stmt->execute();
   reportErrors($stmt);
   $body = "Normalized etablissements names.";

} else if ($action == "viewSchoolsMatchingView") {
   $body = displayTable(getTableSchoolsMatchingView());

} else if ($action == "viewSchoolsNonMatchingView") {
   $body = displayInteractiveTable(getTableSchoolsNonMatchingView());

} else if ($action == "viewSchoolsMatchingExecute") {
   $stmt = getDb()->prepare(getSchoolsMatchingQuery());
   $stmt->execute();
   reportErrors($stmt);
   $body = "Matching executed.";

} else if ($action == "schoolsMatchingManualExecute") {
   $stmt = getDb()->prepare(getSchoolsMatchingManualQuery());
   $stmt->execute();
   reportErrors($stmt);
   $body = "Manual Matching executed.";

} else if ($action == "viewDetailsForTeacher") {
   $userID = "1";
   if (isset($_GET['userID']))
      $userID = $_GET['userID'];
   $body = getViewDetailsForTeacher($userID);

} else if ($action == "viewForTeacher") {
   $userID = "1";
   if (isset($_GET['userID']))
      $userID = $_GET['userID'];
   $body = getViewForTeacher($userID);


} else if ($action == "") {
   $body = "Select action from menu above."; 

} else {
   die("unrecognized action");
}

displayPage($menu . "<br><br>" . $body);


//*****************************************************************
// Documentations of recommend_listedschool table

/*


ETALAB
Géolocalisation des établissements des premier et second degré 
sous tutelle du ministère de l’éducation nationale


Le fichier (MEN_Etalab.csv) comporte les champs suivants :

numero_uai
numéro de l'unité administrative immatriculée (uai)

etat_etablissement
état de l’établissement  (1 = ouvert ; 2 = à fermer ; 3 = à ouvrir)

lib_nature
libellé de la nature de l'uai

sous_fic
code du sous-fichier (1 = premier degré ; 3 = second degré)

Pour la géolocalisation, les données avaient été extraites de la base centrale des établissements (BCE) en octobre 2011 et la géolocalisation a été effectuée par l’IGN en novembre 2011. 

Les coordonnées X et Y sont fournies par l’IGN selon les référentiels suivants (système de référence - projection utilisée) :

France métropolitaine : 	 RGF93 - Lambert 93
Guadeloupe : 	                        WGS84 - UTM Nord fuseau 20
Martinique :	                        WGS84 - UTM Nord fuseau 20
Guyane : 	                        RGFG95 - UTM Nord fuseau 22
Réunion : 	                        RGR92 - UTM Sud fuseau 40


Les codes nature suivants correspondent au premier degré :

101
ECOLE MATERNELLE
102
ECOLE MATERNELLE ANNEXE D IUFM
103
ECOLE MATERNELLE D APPLICATION
111
ECOLE MATERNELLE SPECIALISEE
151
ECOLE DE NIVEAU ELEMENTAIRE
152
ECOLE ELEMENTAIRE ANNEXE D IUFM
153
ECOLE ELEMENTAIRE D APPLICATION
160
ECOLE DE PLEIN AIR
161
ECOLE AUTONOME DE PERFECTIONNEMENT
162
ECOLE DE NIVEAU ELEMENTAIRE SPECIALISEE
169
ECOLE REGIONALE DU PREMIER DEGRE
170
ECOLE SANS EFFECTIFS PERMANENTS


Les codes nature suivants correspondent au second degré : 

300
LYCEE ENSEIGNT GENERAL ET TECHNOLOGIQUE
301
LYCEE D ENSEIGNEMENT TECHNOLOGIQUE
302
LYCEE D ENSEIGNEMENT GENERAL
306
LYCEE POLYVALENT
310
LYCEE CLIMATIQUE
312
ECOLE SECONDAIRE SPECIALISEE (2 D CYCLE)
315
ETABLISSEMENT EXPERIMENTAL
320
LYCEE PROFESSIONNEL
332
ECOLE PROFESSIONNELLE SPECIALISEE
334
SECTION D ENSEIGNEMENT PROFESSIONNEL
335
SECTION ENSEIGT GENERAL ET TECHNOLOGIQUE
336
SECTION ENSEIGNT TECHNO (1ER CYCLE)
340
COLLEGE
349
ETABLISSEMENT DE REINSERTION SCOLAIRE
350
COLLEGE CLIMATIQUE
352
COLLEGE SPECIALISE
370
ETABLISSEMENT REGIONAL D'ENSEIGNT ADAPTE
390
SECTION ENSEIGNT GEN. ET PROF. ADAPTE


   // todo: pre-select the academie id; warning: it could be -1;
   $sAcademieSelector = "<select id=\"user_create_officialEmail_domain\"><option value=\"ac-aix-marseille.fr\">ac-aix-marseille.fr</option><option value=\"ac-amiens.fr\">ac-amiens.fr</option><option value=\"ac-besancon.fr\" selected>ac-besancon.fr</option><option value=\"ac-bordeaux.fr\">ac-bordeaux.fr</option></select>";

*/


?>