<?php
require_once("commonAdmin.php");
require_once("./config.php");

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

function makeGradesArray() {
   global $config;
   $defaultLanguage = 'en';
   $translations = json_decode(file_get_contents(__DIR__ . "/../contestInterface/i18n/$defaultLanguage/translation.json"), true);
   $grades = [];
   foreach($config->grades as $grade) {
      if(isset($translations["grade_$grade"])) {
         $grades[$grade] = $translations["grade_$grade"];
      } else {
         $grades[$grade] = $grade;
      }
   }
   return $grades;
}

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

// Tells whether the row of the CSV file carries exactly the data already
// stored in the table, so that its update can be skipped
function registrationDataMatches($current, $infos) {
   if($current['firstName'] !== $infos['firstname'] || $current['lastName'] !== $infos['lastname']) {
      return false;
   }
   if(is_numeric($infos['grade']) && is_numeric($current['grade'])) {
      if((float) $infos['grade'] != (float) $current['grade']) {
         return false;
      }
   } elseif($current['grade'] !== $infos['grade']) {
      return false;
   }
   if(isset($infos['category']) && $current['category'] !== $infos['category']) {
      return false;
   }
   return true;
}

// Download CSV
if (isset($_GET['download']) && $_GET['download'] == '1') {
   header('Content-Type: text/csv; charset=utf-8');
   header('Content-Disposition: attachment; filename="participants.csv"');
   
   $gradesArray = makeGradesArray();
   
   $stmt = $db->prepare("SELECT `code`, `firstName`, `lastName`, `grade`, `category` FROM `algorea_registration` ORDER BY `code`");
   $stmt->execute();
   
   $output = fopen('php://output', 'w');
   fputcsv($output, ['code', 'firstname', 'lastname', 'grade', 'category']);
   while ($row = $stmt->fetch()) {
      $grade = isset($gradesArray[$row['grade']]) ? $gradesArray[$row['grade']] : $row['grade'];
      fputcsv($output, [
         $row['code'],
         $row['firstName'],
         $row['lastName'],
         $grade,
         $row['category']
      ]);
   }

   fclose($output);
   exit;
}

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
<h1 data-i18n="manual_participants_title"></h1>
<?php
function handleDataFile($dataFilePath) {
   global $config, $db;

   // Large imports may exceed the default maximum execution time
   @set_time_limit(0);

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

      if($infos['grade'] == '' || ($infos['firstname'] == '' && $infos['lastname'] == '')) {
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

   // Perform the whole import in a single transaction so that all statements
   // are committed together instead of once per row
   if(!$db->inTransaction()) {
      $db->beginTransaction();
   }

   try {
      // Remove former codes
      $stmt = $db->prepare("DELETE FROM `algorea_registration` WHERE `code` = :code;");
      foreach($removedCodes as $code) {
         $stmt->execute(['code' => $code]);
         echo "<p>Removed code " . $code . "</p>";
      }

      // Process data in batches of 500 codes max
      $nbAdded = 0;
      $existingCodes = [];       // codes already in the table, as reported in the summary
      $existingCodesSet = [];    // lookup table for $existingCodes
      $codesInDb = [];           // codes known to be in the table, found or added
      $existingData = [];        // data currently stored for the codes found in the table
      $batchSize = 16000;

      // Only two update statement shapes are possible, prepare them once
      $updateStmts = [
         false => $db->prepare("UPDATE `algorea_registration` SET `firstName` = :firstname, `lastName` = :lastname, `grade` = :grade, `lastGradeUpdate` = NOW() WHERE `code` = :code;"),
         true => $db->prepare("UPDATE `algorea_registration` SET `firstName` = :firstname, `lastName` = :lastname, `grade` = :grade, `lastGradeUpdate` = NOW(), `category` = :category WHERE `code` = :code;"),
      ];

      foreach(array_chunk($data, $batchSize) as $batch) {
         // Check for existing codes in this batch, skipping the already known ones
         $codesToCheck = [];
         foreach($batch as $infos) {
            if(!isset($codesInDb[$infos['code']])) {
               $codesToCheck[] = $infos['code'];
            }
         }
         if(count($codesToCheck) > 0) {
            $stmt = $db->prepare("SELECT `code`, `firstName`, `lastName`, `grade`, `category` FROM `algorea_registration` WHERE `code` IN (" . implode(',', array_fill(0, count($codesToCheck), '?')) . ")");
            $stmt->execute($codesToCheck);
            while($row = $stmt->fetch()) {
               $codesInDb[$row['code']] = true;
               $existingData[$row['code']] = $row;
               if(!isset($existingCodesSet[$row['code']])) {
                  $existingCodes[] = $row['code'];
                  $existingCodesSet[$row['code']] = true;
               }
            }
         }

         // Update existing codes in this batch, collect the new ones
         $newRows = [];
         foreach($batch as $infos) {
            if(!isset($codesInDb[$infos['code']])) {
               $newRows[] = $infos;
               continue;
            }
            if(!isset($existingCodesSet[$infos['code']])) {
               $existingCodes[] = $infos['code'];
               $existingCodesSet[$infos['code']] = true;
            }
            if(isset($existingData[$infos['code']]) && registrationDataMatches($existingData[$infos['code']], $infos)) {
               // The stored data already matches the file, no update needed
               continue;
            }
            $updateStmts[isset($infos['category'])]->execute($infos);
            $existingData[$infos['code']] = [
               'firstName' => $infos['firstname'],
               'lastName' => $infos['lastname'],
               'grade' => $infos['grade'],
               'category' => isset($infos['category']) ? $infos['category'] : null,
            ];
            echo "<p>Updated code " . $infos['code'] . "</p>";
         }

         // A duplicated code would make the whole multi-row insert fail:
         // keep the first occurrence and report the others
         $rowsToInsert = [];
         $seenCodes = [];
         foreach($newRows as $infos) {
            if(isset($seenCodes[$infos['code']])) {
               echo "<p>Error while adding code " . $infos['code'] . " (possible duplicate in the list).</p>";
               continue;
            }
            $seenCodes[$infos['code']] = true;
            $rowsToInsert[] = $infos;
         }

         // Rows with and without a category are inserted in separate statements
         $rowGroups = [[], []];
         foreach($rowsToInsert as $infos) {
            if(isset($infos['category']) && $infos['category'] != '') {
               $rowGroups[0][] = $infos;
            } else {
               $rowGroups[1][] = $infos;
            }
         }

         foreach($rowGroups as $groupIdx => $groupRows) {
            if(count($groupRows) == 0) {
               continue;
            }
            $withCategory = ($groupIdx == 0);

            $query = "INSERT INTO `algorea_registration` (`ID`, `firstName`, `lastName`, `grade`, `lastGradeUpdate`, `code`";
            $query .= $withCategory ? ", `category`) VALUES " : ") VALUES ";
            $rowPlaceholders = [];
            $params = [];
            foreach($groupRows as $infos) {
               $rowPlaceholders[] = $withCategory ? "(?, ?, ?, ?, NOW(), ?, ?)" : "(?, ?, ?, ?, NOW(), ?)";
               array_push($params, getRandomID(), $infos['firstname'], $infos['lastname'], $infos['grade'], $infos['code']);
               if($withCategory) {
                  $params[] = $infos['category'];
               }
            }
            $query .= implode(', ', $rowPlaceholders) . ";";

            try {
               $stmt = $db->prepare($query);
               $stmt->execute($params);
               $nbAdded += count($groupRows);
               foreach($groupRows as $infos) {
                  $codesInDb[$infos['code']] = true;
               }
            } catch (PDOException $e) {
               // The whole batch was rejected: retry row by row so that only
               // the faulty rows are reported and skipped
               foreach($groupRows as $infos) {
                  $infos['ID'] = getRandomID();
                  $query = "INSERT INTO `algorea_registration` (`ID`, `firstName`, `lastName`, `grade`, `lastGradeUpdate`, `code`";
                  $queryValues = " VALUES(:ID, :firstname, :lastname, :grade, NOW(), :code";

                  if($withCategory) {
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
                        $codesInDb[$infos['code']] = true;
                     }
                  } catch (PDOException $e) {
                     echo $query;
                     print_r($infos);
                     echo $e->getMessage();
                     echo "<p>Error while adding code " . $infos['code'] . " (possible duplicate in the list).</p>";
                  }
               }
            }
         }
      }
   } catch (Exception $e) {
      if($db->inTransaction()) {
         $db->rollBack();
      }
      throw $e;
   }

   if($db->inTransaction()) {
      $db->commit();
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
   <p><a href="addManualParticipants.php?download=1" data-i18n="manual_participants_download"></a></p>
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
