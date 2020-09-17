<?php 
/* Copyright (c) 2012 Association France-ioi, MIT License http://opensource.org/licenses/MIT */

require_once("../shared/common.php");
require_once("commonAdmin.php");

include('./config.php');
header('Content-type: text/html');

$errorMessage = str_replace('__contactEmail__', $config->email->sInfoAddress, translate('validate_email_error'));

function validateEmail($type, $email, $salt) {
   global $db, $config, $errorMessage;
   $query = "SELECT * FROM `user` WHERE (`".$type."` = ? AND `salt` = ?)";
   $stmt = $db->prepare($query);
   $stmt->execute(array($email, $salt));
   if ($row = $stmt->fetchObject()) {
      $validate = "";
      $message = translate('validate_email_ok');
      if (($type === "officialEmail") && ($config->teacherInterface->forceOfficialEmailDomain || $config->teacherInterface->autoValidateOfficialEmail)) {
         $validate = ", `validated` = 1 ";
      } else {
         $message = translate('validate_email_unofficial');         
      }
      $query = "UPDATE `user` SET `".$type."Validated` = 1 ".$validate." WHERE (`ID` = ?)";
      $stmt = $db->prepare($query);
      $stmt->execute(array($row->ID));
      $message = str_replace('__email__', $email, $message);
      echo str_replace('__email_info__', $config->email->sInfoAddress, $message);
   } else {
      echo $errorMessage;
   }
   echo "<br/><br/>";
}

echo "
<!DOCTYPE html>
<html>
<head>
   <meta http-equiv='Content-Type' content='text/html; charset=utf-8' />
   <link rel='stylesheet' href='admin.css' />
   <title data-i18n=\"page_title\"></title>";
   script_tag('/bower_components/jquery/jquery.min.js');
   script_tag('/bower_components/i18next/i18next.min.js');
echo "
</head>
<body>
<div id='divHeader'>
     <table style='width:100%'><tr>
         <td style='width:20%' data-i18n=\"[html]main_logo\"></td>
         <td><p class='headerH1' data-i18n=\"title\"></p>
         <p class='headerH2' data-i18n=\"[html]subtitle\"></p>
         </td>
         <td></td>
      </tr></table>
</div>
<div class='dialog'>
";


if (!isset($_REQUEST["check"])) {
   echo $errorMessage;
} else if (isset($_REQUEST["altEmail"])) {
   validateEmail("alternativeEmail", $_REQUEST["altEmail"], $_REQUEST["check"]);
} else if (isset($_REQUEST["acEmail"])) {
   validateEmail("officialEmail", $_REQUEST["acEmail"], $_REQUEST["check"]);
} else {
   echo $errorMessage;
}
echo '<p data-i18n="[html]go_to_index"></p>';
echo "</div>
<script>
    i18n.init(";
      echo json_encode([
      'lng' => $config->defaultLanguage,
      'fallbackLng' => [$config->defaultLanguage],
      'fallbackNS' => 'translation',
      'ns' => [
        'namespaces' => $config->customStringsName ? [$config->customStringsName, 'translation'] : ['translation'],
        'defaultNs' => $config->customStringsName ? $config->customStringsName : 'translation',
      ],
      'getAsync' => true,
      'resGetPath' => static_asset('/i18n/__lng__/__ns__.json')
    ]);
    echo ", function () {
      $(\"title\").i18n();
      $(\"body\").i18n();
    });
</script>
</body>
</html>"

?>
