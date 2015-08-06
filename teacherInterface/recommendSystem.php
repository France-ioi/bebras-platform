<?php 
/* Copyright (c) 2012 Association France-ioi, MIT License http://opensource.org/licenses/MIT */

// error_reporting(E_ALL);

   // teachers login:
   // 104: beatrice.gaspalou@ac-nantes.fr
   // 92: anne-marie.cagnon@ac-lille.fr
   // 2398: marc.lobel@ac-lille.fr


require_once("../shared/common.php");
require_once("commonAdmin.php");

if (!isset($_SESSION["userID"])) {
   echo "Votre session a expiré, veuillez vous reconnecter.";
   exit;
}

function REQUEST_or_default($key, $default) {
   if (isset($_REQUEST[$key]))
      return $_REQUEST[$key];
   return $default;
}

//-------------------------------------------------------------------
// Shared functions

function getConstraintColleges() {
   return "nature_uai = 340";
}

function getConstraintLycees() {
   return "(nature_uai = 300 OR nature_uai = 302)";
}

function getConstraintCollegesLycees() {
   return "(" . getConstraintLycees() . " OR " . getConstraintColleges() . ")";
}

function getConstraintForLevel($level) {
   if ($level == "Lycées" || $level == "Lycée" || $level == "Lycee")
      return getConstraintLycees();
   else if ($level == "Collèges" || $level == "Collège" || $level == "College") 
      return getConstraintColleges();
   else 
      return getConstraintCollegesLycees();
}


//-------------------------------------------------------------------
// Recommendation functions

function getUserName($userID) {
   global $db;
   $query = "
     SELECT firstName, lastName
     FROM `user` 
     WHERE ID = :userID
     ";
   $stmt = $db->prepare($query);
   $stmt->execute(array(':userID' => $userID));
   $results = $stmt->fetch(PDO::FETCH_OBJ);
   if ($results == FALSE) 
      return "";
   return $results->firstName . " " . $results->lastName;
}

function emailAlreadyInDatabase($email) {
   global $db;
   $query = "
         (SELECT 1 as emailFound
         FROM `recommend_user`
         WHERE email LIKE :email COLLATE utf8_general_ci)
       UNION
         (SELECT 1 as emailFound
         FROM `user`
         WHERE officialEmail LIKE :email COLLATE utf8_general_ci)
     ";
   $stmt = $db->prepare($query);
   $stmt->execute(array(':email' => $email));
   $results = $stmt->fetch(PDO::FETCH_OBJ);
   if ($results == FALSE) 
      return 0;
   return $results->emailFound;
}

function getUserNbRecommendations($userID) {
   global $db;
   $query = "
     SELECT COUNT(*) as nb
     FROM `recommend_user`
     WHERE userID = :userID
     ";
   $stmt = $db->prepare($query);
   $stmt->execute(array(':userID' => $userID));
   $results = $stmt->fetch(PDO::FETCH_OBJ);
   if ($results == FALSE) 
      return 0;
   return $results->nb;
}

function saveRecommendation($userID, $email) {
   global $db;
   $query = "
      INSERT INTO `recommend_user` (userID, email, date)
      VALUES (:userID, :email, NOW())
     ";
   $stmt = $db->prepare($query);
   $stmt->execute(array(':userID' => $userID, ':email' => $email));
}


//-------------------------------------------------------------------
// Nearby schools functions

function getTeacherAcademie($userID) {
   global $db;
   $query = "SELECT `recommend_academy`.ID as academieID
      FROM `user` 
      LEFT JOIN `recommend_academy` 
      ON `recommend_academy`.domain = SUBSTRING_INDEX(`user`.officialEmail, '@', -1)
      WHERE `user`.ID = :userID
      ";
   $stmt = $db->prepare($query);
   $stmt->execute(array(':userID' => $userID));
   $results = $stmt->fetch(PDO::FETCH_OBJ);
   if ($results == FALSE) 
      return -1;
   return $results->academieID;
}

function getTeacherOneSchool($userID) {
      global $db;
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
   $stmt = $db->prepare($query);
   $stmt->execute(array(':userID' => $userID));
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
         ), array('', '', '', '', '',  '', '','', '', '', '', '', '', '', ''), 
         $schoolName));
}

function getNearbySchools($academyID, $level, $X, $Y, $limit) {
   global $db;
   $filterType = getConstraintForLevel($level);
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
     ORDER BY SQRT(POW(:X - `recommend_listedschool`.X, 2) + POW(:Y - `recommend_listedschool`.Y, 2))
     LIMIT $limit";
   $stmt = $db->prepare($query);
   $stmt->execute(array(':academyID' => $academyID, ':X' => $X, ':Y' => $Y));
   $results = $stmt->fetchAll(PDO::FETCH_OBJ);
   if ($results === FALSE) 
      $results = array();
   return $results; // array of {name,city,distance} objects, ordered by distance.
}

function getNearbySchoolResults($userID) {
   $academyID = getTeacherAcademie($userID);
   $school = getTeacherOneSchool($userID);
   $schoolLevel = $school->type;
   $schoolID = $school->ID;
   $schoolX = $school->X;
   $schoolY = $school->Y;
   $nbSchools = 9;
   $schools = getNearbySchools($academyID, $schoolLevel, $schoolX, $schoolY, $nbSchools);
   $schoolNames = array_map(function($s) { 
      return getSchoolShortName($s->name); }, $schools );
   $schoolNames = array_filter($schoolNames, function($x) {
      return trim($x) != ""; });
   $level = '';
   if ($schoolLevel == "Collège")
      $level = "College";
   else if ($schoolLevel == "Lycée")
      $level = "Lycee";
   return array('schoolNames' => $schoolNames, 'academyID' => $academyID, 'level' => $level);
}

//-------------------------------------------------------------------
// Full list of schools functions

function tablePartipEtablissements($academyID, $filterType) {
   global $db;

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

   $stmt = $db->prepare($query);
   // $stmt->debugDumpParams();
   $stmt->execute(array(':academyID' => $academyID));
   $results = $stmt->fetchAll(PDO::FETCH_OBJ);
   if ($results === FALSE) 
     die("no results");
   $s = '';
   $s .= "<table id='participating'>\n";
   foreach ($results as $id => $result) {
      $color = ($result->participating) ? '#CCFFCC' : '#FFFFFF';
      $srow = "<tr style='background: $color'>\n";
      $srow .= "<td>" . $result->participating ." </td>\n";
      $srow .= "<td>" . $result->name ." </td>\n";
      $srow .= "<td>" . $result->city ." </td>\n";
      $srow .= "</tr>\n";
      $s .= $srow;
   }
   $s .= "</table>\n";
   return $s;
}

function getAcademieItems() {
   global $db;
   $query = "SELECT ID, name FROM `recommend_academy`";
   $stmt = $db->prepare($query);
   $stmt->execute();
   $results = $stmt->fetchAll(PDO::FETCH_OBJ);
   if ($results === FALSE) 
     die("no results");
   $items = array();
   foreach ($results as $result) 
      $items[$result->ID] = $result->name;
   return $items;
}


function getLevelItems() {
   return array("College" => "Collèges", "Lycee" => "Lycées", "Tous" => "Tous");
}

function optionsForSelect($items, $selected = FALSE) {
   $s = '';
   foreach ($items as $key => $value) {
      $sSelected = ((string) $key === (string) $selected) ? "selected" : "";
      $key = addslashes($key);
      $s .= "<option $sSelected value='$key'>$value</option>";
   }
   return $s;
}


//--------------------------------------------------------------
// parameters

$userID = $_SESSION["userID"];
$recommendTo = REQUEST_or_default('recommendTo', '');
$nearby = REQUEST_or_default('nearby', 0);
$academyID = REQUEST_or_default('academyID', 0);
$level = REQUEST_or_default('level', "Lycées");

//--------------------------------------------------------------
// recommend to a email

if (! empty($recommendTo)) {
   $nbMaxNbRecommendations = 20;
   $message = '';
   if (! filter_var($recommendTo, FILTER_VALIDATE_EMAIL)) {
      $message = "invalid_email";
   } else if (emailAlreadyInDatabase($recommendTo)) {
      $message = "already_known_email";
   } else if (getUserNbRecommendations($userID) >= $nbMaxNbRecommendations) {
      $message = "exceeded_max_email";
   } else {
      saveRecommendation($userID, $recommendTo);
      $sEmail = $recommendTo;
      $userName = getUserName($userID);
      $sTitle = "$userName souhaite vous recommander le Concours Castor Informatique";
      $sBody = <<<EOF
Bonjour,

Votre collègue $userName souhaite vous recommander le Concours Castor Informatique.

Ce concours, entièrement gratuit, vise à faire découvrir à vos élèves l'informatique et les sciences du numérique. Il dure 45 minutes et ne requiert aucune connaissance préalable en informatique. 

Le concours se déroule dans une salle informatique de votre établissement, à n'importe quel moment au cours de la semaine du 12 au 19 Novembre 2014. Pour faire participer vos élèves, il suffit de vous inscrire dès maintenant comme coordinateur et de créer les groupes de passage pour vos classe. 

Pour en savoir plus : http://castor-informatique.fr/

Bien Cordialement,

L'équipe du Castor
info@castor-informatique.fr
EOF;
      // $sBody = str_replace("\n", "\r\n", $sBody);
      global $config;
      // TODO: sendMail($sEmail, $sTitle, $sBody, $config->email->sEmailSender);
      $message = "successful_email";
   }
      
   echo json_encode(array(
      'success' => 1,
      'message' => $message
   ));
   exit;
}

//--------------------------------------------------------------
// get short list of nearbySchools

if ($nearby) {
   $result = getNearbySchoolResults($userID);
   $userAcademieID = $result['academyID'];
   $userLevel = $result['level'];
   echo json_encode(array(
      'success' => 1,
      'nearbySchools' => $result['schoolNames'],
      'fullListURL' => "participatingSchools.php?academyID=$userAcademieID&level=$userLevel"
   ));
   exit;
}

//--------------------------------------------------------------
// display full list of nearbySchools

$optionsLevel = optionsForSelect(getLevelItems(), $level);
$optionsAcademy = optionsForSelect(getAcademieItems(), $academyID);
$tableData = '';

if ($academyID != 0) {
   $constraintLevel = getConstraintForLevel($level);
   $tableData = tablePartipEtablissements($academyID, $constraintLevel);
}

if (! empty($tableData))
   $tableData = "
   <div>Les établissements participants sont affichés sur fond vert. <br>
   Seuls les collèges, et lycées généraux et/ou technologiques sont listés ci-dessous. <br> 
   Note : certains établissements participants peuvent manquer si leur nom a été mal saisi.</div>
   <br>" . $tableData;

echo "
<!DOCTYPE html>
<html>
<head>
   <meta http-equiv='Content-Type' content='text/html; charset=utf-8' />
   <link rel='stylesheet' href='admin.css' />

   <style>
   #participating {
      border-collapse: collapse;
   }
   #participating td {
     border: 1px solid black;
     padding: 0.2em;
   }
   </style>
</head>
<body>
<div id='divHeader'>
     <table style='width:100%'><tr>
         <td style='width:20%'><img src='images/castor_small.png'/></td>
         <td><p class='headerH1'>Castor Informatique France</p>
         <p class='headerH2'> Plate-forme du concours Castor - <span style='color:red;font-weight:bold'>ACCÈS COORDINATEUR</span></p>
         </td>
         <td></td>
      </tr></table>
</div>

<br>
<form action='participatingSchools.php' method='get'>
<div>
<select name='academyID' onchange='this.form.submit()'>
   <option value='0'>Choisir une académie</option> 
   $optionsAcademy
   </select>
<select name='level' onchange='this.form.submit()'>
   $optionsLevel
   </select>
</div>

</form>



<br>
$tableData



</body>
</html>

";



//  
?>
