<?php
   require_once("commonAdmin.php");
   require_once("./config.php");
   header('Content-type: text/html');
?><!DOCTYPE html>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<link rel="shortcut icon" href="<?= $config->faviconfile ?>" />
<title data-i18n="manual_participants_title"></title>
<?php stylesheet_tag('/admin.css'); ?>
</head>
<body class="body-margin">
<?php
if (!isset($_SESSION["userID"])) {
   echo "<p>" . translate("session_expired") . "</p>";
   echo "<p>" . translate("go_to_index") . "</p>";
   echo "</body>";
   exit;
}

if (!isset($_SESSION["isAdmin"]) || !$_SESSION["isAdmin"]) {
   echo "<p>" . translate("admin_restricted") . "</p>";
   echo "<p>" . translate("go_to_index") . "</p>";
   echo "</body>";
   exit;
}
?> 
<h1 data-i18n="manual_participants_title"></h1>
<?php
function makeGradesReverseArray() {
   global $config;
   $languages = ['ar', 'en'];
   $gradesReverse = [];
   foreach($languages as $lang) {
      $translations = json_decode(file_get_contents(__DIR__ . "/../contestInterface/i18n/$lang/translation.json"), true);
      foreach($config->grades as $grade) {
         if(isset($translations["grade_$grade"])) {
            $gradesReverse[$translations["grade_$grade"]] = $grade;
         }
      }
   }
   return $gradesReverse;
}

function handleDataFile($dataFilePath) {
   global $config, $db;

   $gradesReverse = makeGradesReverseArray();

   // Read CSV
   $handle = fopen($dataFilePath, "r");

   $columns = ['code', 'name', 'grade', 'firstname', 'lastname', 'category'];
   $columnsIdx = [];
   $firstLine = true;
   $data = [];
   $invalid = 0;
   $removedCodes = [];

   while(($dataLine = fgetcsv($handle)) !== FALSE) {
      if($firstLine) {
         foreach($dataLine as $idx => $column) {
            $column = strtolower($column);
            if(in_array($column, $columns)) {
               $columnsIdx[$column] = $idx;
            } else {
               echo "<p>Invalid column ignored: " . $column . "</p>";
            }
         }
         if(!isset($columnsIdx['code'])) {
            die("<p>Missing code column in the CSV file.</p></body>");
         }
         if(!isset($columnsIdx['grade'])) {
            die("<p>Missing grade column in the CSV file.</p></body>");
         }
         if(!isset($columnsIdx['name']) && (!isset($columnsIdx['firstname']) || !isset($columnsIdx['lastname']))) {
            die("<p>Missing name column in the CSV file. You must provide either the name column, of the firstname and lastname columns.</p></body>");
         }
         $firstLine = false;
         continue;
      }

      $dataLine = array_map('trim', $dataLine);
      $infos = ['code' => '', 'grade' => '', 'firstname' => '', 'lastname' => ''];
      foreach($columnsIdx as $column => $idx) {
         $infos[$column] = isset($dataLine[$idx]) ? $dataLine[$idx] : '';
      }

      if($infos['code'] == '') {
         echo "<p>Invalid data line: " . implode(', ', $dataLine) . "</p>";
         continue;
      }
      if(isset($infos['name']) && $infos['name'] != '') {
         // name has precedence
         $nameParts = explode(' ', $infos['name']);
         $infos['firstname'] = $nameParts[0];
         $infos['lastname'] = implode(' ', array_slice($nameParts, 1));
      }
      unset($infos['name']);

      if($infos['grade'] == '' || $infos['firstname'] == '' || $infos['lastname'] == '') {
         // No information, so it is a code to be removed
         $removedCodes[] = $infos['code'];
         continue;
      }

      if(isset($gradesReverse[$infos['grade']])) {
         $infos['grade'] = $gradesReverse[$infos['grade']];
      } elseif(!is_numeric($infos['grade'])) {      
         echo "<p>Invalid grade: " . $infos['grade'] . "</p>";
         continue;
      }

      $data[] = $infos;
   }
   fclose($handle);

   // Check for existing codes
   $newCodes = array_map(function($infos) { return $infos['code']; }, $data);
   if(count($newCodes) > 0) {
      $stmt = $db->prepare("SELECT `code`, `firstName`, `lastName`, `grade` FROM `algorea_registration` WHERE `code` IN (" . implode(',', array_fill(0, count($newCodes), '?')) . ")");
      $stmt->execute($newCodes);
      $existingCodes = [];
      $existingCodesData = [];
      while($row = $stmt->fetch()) {
         $existingCodes[] = $row['code'];
         $existingCodesData[$row['code']] = [$row['firstName'], $row['lastName'], $row['grade']];
      }
      $newCodes = array_diff($newCodes, $existingCodes);
   }

   // Remove former codes
   $stmt = $db->prepare("DELETE FROM `algorea_registration` WHERE `code` = :code;");
   foreach($removedCodes as $code) {
      $stmt->execute(['code' => $code]);
      echo "<p>Removed code " . $code . "</p>";
   }

   // Update existing codes
   foreach($data as $infos) {
      if(!in_array($infos['code'], $existingCodes)) {
         continue;
      }
      $query = "UPDATE `algorea_registration` SET `firstName` = :firstname, `lastName` = :lastname, `grade` = :grade, `lastGradeUpdate` = NOW()";
      if(isset($infos['category'])) {
         $query .= ", `category` = :category";
      }
      $query .= " WHERE `code` = :code;";
      $stmt = $db->prepare($query);
      $stmt->execute($infos);
      echo "<p>Updated code " . $infos['code'] . "</p>";
   }

   // Add new codes
   $nbAdded = 0;
   foreach($data as $infos) {
      if(in_array($infos['code'], $existingCodes)) {
         continue;
      }

      // Add to algorea_registration
      $infos['ID'] = getRandomID();
      $query = "INSERT INTO `algorea_registration` (`ID`, `firstName`, `lastName`, `grade`, `lastGradeUpdate`, `code`";
      $queryValues = " VALUES(:ID, :firstname, :lastname, :grade, NOW(), :code";

      if(isset($infos['category']) && $infos['category'] != '') {
         $query .= ", `category`";
         $queryValues .= ", :category";
      } else {
         unset($infos['category']);
      }
      $query .= ")" . $queryValues . ");";

      try {
         $stmt = $db->prepare($query);
         $stmt->execute($infos);
         if($stmt->rowCount() == 0) {
            echo "<p>Error while adding code " . $infos['code'] . ".</p>";
         } else {
            $nbAdded++;
         }
      } catch (PDOException $e) {
         echo $query;
         print_r($infos);
         echo $e->getMessage();
         echo "<p>Error while adding code " . $infos['code'] . " (possible duplicate in the list).</p>";
      }
   }

   return [$nbAdded, count($existingCodes), $existingCodes];
}

if (isset($_FILES['dataFile'])) {
   if ($_FILES['dataFile']['error'] !== 0) {
      echo "<p><b>" . translate("upload_error") . "</b></p>";
   } else {
      $dataFilePath = $_FILES['dataFile']['tmp_name'];
      $nbCodes = handleDataFile($dataFilePath);
      if($nbCodes[0] + $nbCodes[1] > 0) {
         echo "<p><ul><li>" . $nbCodes[0] . " " . translate("manual_participants_added") . "</li>";
         echo "<li>" . $nbCodes[1] . " " . translate("manual_participants_existing") . ($nbCodes[1] > 0 ? " : " . implode(', ', $nbCodes[2]) : '') . "</li></ul></p>";
      }
   }
}
?>
<div>
   <p data-i18n="[html]manual_participants_instructions"></p>
   <form action="addManualParticipants.php" method="post" enctype="multipart/form-data">
      <span data-i18n="manual_participants_file"></span>
      <input type="file" name="dataFile" accept=".csv" required>
      <br>
      <input type="submit" value="Submit" data-i18n="[value]manual_participants_title">
   </form>
   <br>
   <p data-i18n="[html]go_to_index"></p>
</div>

<?php
   script_tag('/bower_components/jquery/jquery.min.js');
   script_tag('/bower_components/i18next/i18next.min.js');
?>
<script>
   i18n.init(<?= json_encode([
      'lng' => $config->defaultLanguage,
      'fallbackLng' => [$config->defaultLanguage],
      'getAsync' => true,
      'resGetPath' => static_asset('/i18n/__lng__/__ns__.json'),
      'fallbackNS' => 'translation',
      'ns' => [
         'namespaces' => $config->customStringsName ? [$config->customStringsName, 'translation', 'country' . $config->teacherInterface->countryCode] : ['translation', 'country' . $config->teacherInterface->countryCode],
         'defaultNs' => $config->customStringsName ? $config->customStringsName : 'translation',
      ],
      'useDataAttrOptions' => true
   ]) ?>, function() {
      $("title").i18n();
      $("body").i18n();
   });
</script>
</body>
</html>
