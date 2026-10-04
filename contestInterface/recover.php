<?php
/* Copyright (c) 2012 Association France-ioi, MIT License http://opensource.org/licenses/MIT */

require_once(__DIR__ . '/config.php');
require_once(__DIR__ . '/../shared/connect.php');

function decodeRecoveryData($raw) {
   $raw = preg_replace('/\s+/', '', $raw);
   if (strlen($raw) >= 2 && $raw[0] === '"' && $raw[strlen($raw) - 1] === '"') {
      $raw = substr($raw, 1, -1);
   }
   $cleaned = preg_replace('/[^A-Za-z0-9+\/=]/', '', $raw);
   for ($trimStart = 0; $trimStart <= 10; $trimStart++) {
      for ($trimEnd = 0; $trimEnd <= 10; $trimEnd++) {
         $candidate = $cleaned;
         if ($trimStart > 0) {
            $candidate = substr($candidate, $trimStart);
         }
         if ($trimEnd > 0) {
            $candidate = substr($candidate, 0, -$trimEnd);
         }
         if (strlen($candidate) < 4) {
            continue;
         }
         $padded = $candidate . '==';
         $decoded = @base64_decode($padded, true);
         if ($decoded === false || $decoded === '') {
            continue;
         }
         $json = json_decode($decoded, true);
         if (is_array($json) && isset($json['pwd']) && isset($json['ans'])) {
            return $json;
         }
      }
   }
   return false;
}

$results = array();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['encodedData'])) {
   $rawData = trim($_POST['encodedData']);
   if ($rawData === '' && isset($_FILES['encodedFile']) && $_FILES['encodedFile']['error'] === UPLOAD_ERR_OK) {
      $rawData = file_get_contents($_FILES['encodedFile']['tmp_name']);
   }
   if ($rawData === '') {
      $results[] = array('success' => false, 'messageKey' => 'recover_error_no_data');
   } else {
      $lines = explode("\n", $rawData);
      foreach ($lines as $lineNum => $line) {
         $line = trim($line);
         if ($line === '') {
            continue;
         }
         $json = decodeRecoveryData($line);
         if ($json === false) {
            $results[] = array('success' => false, 'line' => $lineNum + 1, 'messageKey' => 'recover_error_invalid_format');
            continue;
         }
         $pwd = $json['pwd'];
         $ans = $json['ans'];

         $stmt = $db->prepare("SELECT `team`.`ID`, `group`.`name` as `groupName`, `contest`.`name` as contestName " .
            "FROM `team` " .
            "JOIN `group` ON team.groupID = `group`.ID " .
            "JOIN `contest` ON `group`.contestID = `contest`.ID " .
            "WHERE `team`.`password` = ?");
         $stmt->execute(array($pwd));
         if ($row = $stmt->fetchObject()) {
            $stmtInsert = $db->prepare("INSERT IGNORE INTO `team_question_recover` (`teamID`, `questionID`, `answer`) VALUES (?, ?, ?)");
            $stmtUpdate = $db->prepare("UPDATE `team_question_recover` SET `answer` = ? WHERE `teamID` = ? AND `questionID` = ?");
            $stmtReset = $db->prepare("UPDATE `team` SET `score` = NULL WHERE ID = ?");
            $teamID = $row->ID;
            $count = 0;
            foreach ($ans as $answer) {
               $stmtInsert->execute(array($teamID, $answer[0], $answer[1]));
               $stmtUpdate->execute(array($answer[1], $teamID, $answer[0]));
               $stmtReset->execute(array($teamID));
               $count++;
            }
            $results[] = array('success' => true, 'line' => $lineNum + 1, 'messageKey' => 'recover_result_team_saved', 'messageParams' => array('count' => $count));
         } else {
            $results[] = array('success' => false, 'line' => $lineNum + 1, 'messageKey' => 'recover_error_team_not_found', 'messageParams' => array('password' => $pwd));
         }
      }
   }
}
?>
<!DOCTYPE html>
<html>
<head>
<meta charset='utf-8'>
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<link rel="shortcut icon" href="<?= $config->faviconfile ?>" />
<title data-i18n="recover_page_title"></title>
<?php
   stylesheet_tag('/style.css');
   if ($config->defaultLanguage == "ar") {
      stylesheet_tag('/style_rtl.css');
   }
   script_tag('/bower_components/jquery/jquery.min.js');
   script_tag('/bower_components/i18next/i18next.min.js');
?>
</head>
<body>
<div id="divHeader">
  <div id="leftTitle"></div>
  <div id="rightTitle"></div>
  <div id="headerGroup">
    <h1 id="headerH1" data-i18n="recover_page_title"></h1>
    <h2 id="headerH2" data-i18n="recover_subtitle"></h2>
  </div>
</div>

<div id="mainContent">
  <div class="dialog">
    <p data-i18n="recover_intro"></p>
    <p data-i18n="[html]recover_reminder"></p>

    <form method="post" action="recover.php" enctype="multipart/form-data">
      <p>
        <label for="encodedData"><b data-i18n="recover_label_paste"></b></label><br>
        <textarea name="encodedData" id="encodedData" cols="80" rows="10" style="width:100%; max-width:700px; box-sizing:border-box;"></textarea>
      </p>
      <p>
        <label for="encodedFile"><b data-i18n="recover_label_upload"></b></label><br>
        <input type="file" name="encodedFile" id="encodedFile" accept=".txt">
      </p>
      <p>
        <button type="submit" class="btn btn-primary" data-i18n="recover_button_submit"></button>
      </p>
    </form>

<?php if (!empty($results)): ?>
    <div id="results" style="margin-top: 20px;">
      <h3 data-i18n="recover_results_title"></h3>
<?php foreach ($results as $result): ?>
      <p style="color: <?= $result['success'] ? 'green' : 'red' ?>;">
        <span class="recoverResult" data-i18n-key="<?= htmlspecialchars($result['messageKey']) ?>"<?php if (!empty($result['messageParams'])): ?> data-params="<?= htmlspecialchars(json_encode($result['messageParams']), ENT_QUOTES) ?>"<?php endif; ?>></span>
      </p>
<?php endforeach; ?>
    </div>
<?php endif; ?>

    <p style="margin-top:30px;"><a href="index.php" data-i18n="recover_back_to_contest"></a></p>
  </div>
</div>

<script>
  function updateQueryStringParameter(uri, key, value) {
    var re = new RegExp("([?&])" + key + "=.*?(&|$)", "i");
    var separator = uri.indexOf('?') !== -1 ? "&" : "?";
    if (uri.match(re)) {
      return uri.replace(re, '$1' + key + "=" + value + '$2');
    }
    else {
      return uri + separator + key + "=" + value;
    }
  }

  try {
    i18n.init(<?= json_encode([
      'lng' => $config->defaultLanguage,
      'fallbackLng' => [$config->defaultLanguage],
      'fallbackNS' => 'translation',
      'ns' => [
        'namespaces' => $config->customStringsName ? [$config->customStringsName, 'translation'] : ['translation'],
        'defaultNs' => $config->customStringsName ? $config->customStringsName : 'translation',
      ],
      'getAsync' => true,
      'resGetPath' => static_asset('/i18n/__lng__/__ns__.json')
    ]); ?>, function () {
      $("title").i18n();
      $("body").i18n();
      $(".recoverResult").each(function() {
         var $result = $(this);
         var params = $result.attr("data-params");
         $result.text(i18n.t($result.attr("data-i18n-key"), params ? JSON.parse(params) : {}));
      });
    });
  } catch(e) {
    // assuming s3 was blocked, so add ?p=1 to url, see contestInterface/config.php
    var newLocation = updateQueryStringParameter(window.location.toString(), 'p', '1');
    if (newLocation != window.location.toString()) {
      window.location = newLocation;
    }
  }
</script>
</body>
</html>
