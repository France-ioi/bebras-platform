<?php

include('./config.php');
require_once("../shared/common.php");
require_once("commonAdmin.php");

if (!isset($_SESSION['userID'])) {
   die(json_encode(array('success' => false, 'error' => translate("session_expired"))));
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
      showError("<b>".translate("codes_fields_missing")."</b>");
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
         showError(sprintf(translate("codes_participant_exists"), $_POST["firstName"], $_POST["lastName"], $row->code));
      }
      else {
         $category = "";
         if (isset($config->defaultCategory)) {
            $category = $config->defaultCategory;
         }
         if (($_POST["grade"] == -1) && isset($config->defaultTeacherCategory)) {
            $category = $config->defaultTeacherCategory;
         }
         $code = generateRandomCode();
         $query = "INSERT INTO algorea_registration (`firstName`, `lastName`, `genre`, `email`, `studentID`, `zipCode`, `code`, `grade`, `schoolID`, `userID`, `category`) ".
            "VALUES (:firstName, :lastName, 0, '', '', '', :code, :grade, :schoolID, :userID, '".$category."') ";
         $stmt = $db->prepare($query);
         $stmt->execute(['userID' => $_SESSION['userID'],
            'schoolID' => $_POST["schoolID"],
            'firstName' => $_POST["firstName"],
            'lastName' => $_POST["lastName"],
            'grade' => $_POST["grade"],
            'code' => $code         
            ]);
         showError(sprintf(translate("codes_generated"), $code, $_POST["firstName"], $_POST["lastName"]));
      }
   }
}

echo "<h1>".translate("codes_extra_title")."</h1>".
     "<p>".translate("codes_list_existing")."</p>".
     "<table id='participationCodes' cellspacing=0><tr>".
     "<td>".translate("schools_title")."</td>".
     "<td>".translate("contestant_lastName_label")."</td>".
     "<td>".translate("contestant_firstName_label")."</td>".
     "<td>".translate("contestant_grade_label")."</td>".
     "<td>".translate("participation_code")."</td>".
     "<td>".translate("codes_category")."</td>".
     "</tr>";

$query = "select `school`.`name`, `firstName`, `lastName`, `grade`, `code`, `category` ".
   "FROM `algorea_registration` JOIN `school` ON `schoolID` = `school`.`ID` ".
   "WHERE `algorea_registration`.`userID` = :userID AND contestantID IS NULL";
$stmt = $db->prepare($query);
$stmt->execute(['userID' => $_SESSION['userID']]);
while ($row = $stmt->fetchObject()) {
   echo "<tr><td>".$row->name."</td>".
      "<td>".$row->lastName."</td>".
      "<td>".$row->firstName."</td>".
      "<td>".translate("grade_short_".$row->grade)."</td>".
      "<td>".$row->code."</td>".
      "<td>".$row->category."</td>".
      "</tr>";
}

echo "</table>".
   "<h2>".translate("codes_generate_new_title")."</h2>".
   translate("codes_generate_new_explanation").
   
   "<form action='extraQualificationCode.php' method='post'>".
   "<table>".
   "<tr><td>".translate("codes_school")."</td><td><select name='schoolID'>";

$query = "select CONCAT(CONCAT(`school`.`name`, ', '), `school`.`city`) as name, `school`.`ID` FROM `school` JOIN `school_user` ON `school`.`ID` = `school_user`.`schoolID` WHERE `school_user`.`userID` = :userID";
$stmt = $db->prepare($query);
$stmt->execute(['userID' => $_SESSION['userID']]);
while ($row = $stmt->fetchObject()) {
   echo "<option value='".$row->ID."'>".htmlentities($row->name)."</option>";
}
echo "</select>".
     "</td></tr>".
     "<tr><td>".translate("codes_lastName")."</td><td><input type='text' name='lastName' /></td></tr>".
     "<tr><td>".translate("codes_firstName")."</td><td><input type='text' name='firstName' /></td></tr>".
     "<tr><td>".translate("codes_grade")."</td><td><select name='grade'>";

   echo "<option value=''>".translate("codes_grade")."</option>";
   foreach ($config->grades as $iGrade) {
      echo "<option value='".$iGrade."'>".translate("grade_short_".$iGrade)."</option>";
   }

echo "</select>".
     "</td></tr>".
   "</table>".
   "<input type='submit' value='".translate("codes_validate")."' />".
   "</form>";

   script_tag('/bower_components/i18next/i18next.min.js');

echo "</html>";

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