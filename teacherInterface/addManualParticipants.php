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
   $firstLine = true;
   $data = [];
   while(($dataLine = fgetcsv($handle)) !== FALSE) {
      if($firstLine) {
         $firstLine = false;
         continue;
      }
      if(count($dataLine) < 3) {
         echo "<p>Invalid data line: " . implode(', ', $dataLine) . "</p>";
         continue;
      }
      $dataLine = array_map('trim', $dataLine);
      $nameParts = explode(' ', $dataLine[1]);
      if(!isset($gradesReverse[$dataLine[2]])) {
         echo "<p>Invalid grade: " . $dataLine[2] . "</p>";
         continue;
      }
      $data[] = [strtolower($dataLine[0]), $nameParts[0], $nameParts[1], $gradesReverse[$dataLine[2]]];
   }
   fclose($handle);

   // Check for existing codes
   $newCodes = array_map(function($line) { return $line[0]; }, $data);
   if(count($newCodes) == 0) {
      return [0, 0, []];
   }
   $stmt = $db->prepare("SELECT `code` FROM `algorea_registration` WHERE `code` IN (" . implode(',', array_fill(0, count($newCodes), '?')) . ")");
   $stmt->execute($newCodes);
   $existingCodes = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
   $newCodes = array_diff($newCodes, $existingCodes);
   if(count($newCodes) == 0) {
      return [0, count($existingCodes), $existingCodes];
   }
   
   // Add new codes
   $nbAdded = 0;
   $query = "INSERT INTO `algorea_registration` (`ID`, `firstName`, `lastName`, `grade`, `lastGradeUpdate`, `code`) VALUES(:ID, :firstName, :lastName, :grade, NOW(), :code);";
   $stmt = $db->prepare($query);
   foreach($data as $dataLine) {
      if(in_array($dataLine[0], $existingCodes)) {
         echo "<p>Skipping existing code " . $dataLine[0] . "</p>";
         continue;
      }

      // Add to algorea_registration
      $codeData = [
         'ID' => getRandomID(),
         'firstName' => $dataLine[1],
         'lastName' => $dataLine[2],
         'grade' => $dataLine[3],
         'code' => $dataLine[0]
      ];
      try {
         $stmt->execute($codeData);
         if($stmt->rowCount() == 0) {
            echo "<p>Error while adding code " . $dataLine[0] . ".</p>";
         } else {
            $nbAdded++;
         }
      } catch (PDOException $e) {
         echo "<p>Error while adding code " . $dataLine[0] . " (possible duplicate in the list).</p>";
      }
   }

   return [$nbAdded, count($existingCodes), $existingCodes];
}

if (isset($_FILES['dataFile'])) {
   $dataFilePath = $_FILES['dataFile']['tmp_name'];
   $nbCodes = handleDataFile($dataFilePath);
   if($nbCodes[0] + $nbCodes[1] > 0) {
      echo "<p><ul><li>" . $nbCodes[0] . " " . translate("manual_participants_added") . "</li>";
      echo "<li>" . $nbCodes[1] . " " . translate("manual_participants_existing") . ($nbCodes[1] > 0 ? " : " . implode(', ', $nbCodes[2]) : '') . "</li></ul></p>";
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
