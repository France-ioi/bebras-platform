<?php

include('./config.php');
require_once("../shared/common.php");
require_once("commonAdmin.php");

$grades = array(-1 => "Enseignant",
   4 => "CM1",
   5 => "CM2",
   6 => "6e",
   7 => "5e",
   8 => "4e",
   9 => "3e",
   10 => "2de",
   11 => "1re",
   12 => "Terminale",
   13 => "2de pro",
   14 => "1re pro",
   15 => "Terminale pro",
   16 => "6e SEGPA",
   17 => "5e SEGPA",
   18 => "4e SEGPA",
   19 => "3e SEGPA",
   20 => "Post-Bac"
   );

      if (!isset($_SESSION['userID'])) {
   die(json_encode(array('success' => false, 'error' => "Votre session a expiré, veuillez vous reconnecter.")));
   exit();
}
?>
<!DOCTYPE html>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<style>
#participationCodes tr td {
   padding:5px;
   border: solid black 1px;
}
#participationCodes tr:first-child td {
   background-color: #e0e0ff;
   font-weight: bold;
}
</style>
</head>
<body>
<?php

function showError($message) {
   echo "<script>alert('".$message."')</script><div style='background-color:#F88;font-weight:bold;padding:10px'>".$message."</div>";
}

if (isset($_POST["schoolID"])) {
   if ($_POST["lastName"] == "" || $_POST["firstName"] == "") {
      showError("<b>Vous n'avez pas rempli tous les champs !</b>");
   } else {
      $query = "SELECT `code` FROM algorea_registration WHERE schoolID = :schoolID AND userID = :userID AND firstName = :firstName AND lastName = :lastName AND grade = :grade";
      $stmt = $db->prepare($query);
      $stmt->execute(['userID' => $_SESSION['userID'],
         'schoolID' => $_POST["schoolID"],
         'firstName' => $_POST["firstName"],
         'lastName' => $_POST["lastName"],
         'grade' => $_POST["grade"]
         ]);
      if ($row = $stmt->fetchObject()) {
         showError("Vous avez déjà un participant nommé ".$_POST["firstName"]." ".$_POST["lastName"]." dans cet établissement, qui a pour code : ".$row->code);
      }
      else {
         $code = generateRandomCode();
         $query = "INSERT INTO algorea_registration (`firstName`, `lastName`, `genre`, `email`, `studentID`, `zipCode`, `code`, `grade`, `schoolID`, `userID`, `category`) ".
            "VALUES (:firstName, :lastName, 0, '', '', '', :code, :grade, :schoolID, :userID, 'blanche') ";
         $stmt = $db->prepare($query);
         $stmt->execute(['userID' => $_SESSION['userID'],
            'schoolID' => $_POST["schoolID"],
            'firstName' => $_POST["firstName"],
            'lastName' => $_POST["lastName"],
            'grade' => $_POST["grade"],
            'code' => $code         
            ]);
         showError("Le code ".$code." a été généré pour ".$_POST["firstName"]." ".$_POST["lastName"]);
      }
   }
}

?>
<h1>Codes de participants supplémentaires</h1>
<p>
Voici la liste des codes de participants que vous avez créé manuellement :
</p>
<table id="participationCodes" cellspacing=0>
<tr><td>Établissement</td><td>Nom</td><td>Prénom</td><td>Classe</td><td>Code de participant</td><td>Catégorie</td></tr>
<?php

$query = "select `school`.`name`, `firstName`, `lastName`, `grade`, `code`, `category` ".
   "FROM `algorea_registration` JOIN `school` ON `schoolID` = `school`.`ID` ".
   "WHERE `algorea_registration`.`userID` = :userID AND contestantID IS NULL";
$stmt = $db->prepare($query);
$stmt->execute(['userID' => $_SESSION['userID']]);
while ($row = $stmt->fetchObject()) {
   echo "<tr><td>".$row->name."</td>".
      "<td>".$row->lastName."</td>".
      "<td>".$row->firstName."</td>".
      "<td>".$grades[$row->grade]."</td>".
      "<td>".$row->code."</td>".
      "<td>".$row->category."</td>".
      "</tr>";
}

?>
</table>

<h2>Créer un nouveau code :</h2>
<p>
Vous pouvez ici créer un code de participant pour un élève qui n'a pas pu en obtenir lors d'une étape précédente, ou bien pour vous-même, pour tester le fonctionnement.
<p>
Assurez-vous de ne pas créer un code pour une personne qui en dispose déjà.
</p>
<form action="extraQualificationCode.php" method="post">
   <table>
      <tr><td>Établissement :</td><td><select name="schoolID">
<?php
$query = "select CONCAT(CONCAT(`school`.`name`, ', '), `school`.`city`) as name, `school`.`ID` FROM `school` JOIN `school_user` ON `school`.`ID` = `school_user`.`schoolID` WHERE `school_user`.`userID` = :userID";
$stmt = $db->prepare($query);
$stmt->execute(['userID' => $_SESSION['userID']]);
while ($row = $stmt->fetchObject()) {
   echo "<option value='".$row->ID."'
   >".htmlentities($row->name)."</option>";
}
?>
</select>    
      </td></tr>
      <tr><td>Nom :</td><td><input type="text" name="lastName" /></td></tr>
      <tr><td>Prénom :</td><td><input type="text" name="firstName" /></td></tr>
      <tr><td>Classe :</td><td><select name="grade">
<?php
   foreach ($grades as $iGrade => $strGrade) {
      echo "<option value='".$iGrade."'>".$strGrade."</option>";
   }
?>    
      </select>
      </td></tr>
   </table>
   <input type="submit" value="Valider" />
</form>
<?php
   script_tag('/bower_components/i18next/i18next.min.js');
?>
</html>
<?php

function generateRandomCode() {
   global $db;
   srand(time() + rand());
   $charsAllowed = "0123456789";
   $base = 'g';
   $query = "SELECT ID as nb FROM algorea_registration WHERE code = :code;";
   $stmt = $db->prepare($query);
   while(true) {
      $code = $base;
      for ($pos = 0; $pos < 14; $pos++) {
         $iChar = rand(0, strlen($charsAllowed) - 1);
         $code .= substr($charsAllowed, $iChar, 1);
      }
      $stmt->execute(array('code' => $code));
      $row = $stmt->fetchObject();
      if (!$row) {
         return $code;
      }
      error_log("Error, code ".$code." is already used");
   }
}



?>