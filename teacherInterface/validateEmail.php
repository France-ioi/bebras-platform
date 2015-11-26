<?php 
/* Copyright (c) 2012 Association France-ioi, MIT License http://opensource.org/licenses/MIT */

require_once("../shared/common.php");
require_once("commonAdmin.php");

include('./config.php');
header('Content-type: text/html');

$errorMessage = "<p><b style='color:red'>Erreur. L'url est invalide.</b></p><p>Réessayez en vous assurant d'inclure l'adresse complète fournie dans l'email que vous avez reçu. Si cela ne fonctionne toujours pas, contactez-nous à ".$config->email->sInfoAddress."</p>";

function validateEmail($type, $email, $salt) {
   global $db;
   global $config;
   global $errorMessage;
   $query = "SELECT * FROM `user` WHERE (`".$type."` = ? AND `salt` = ?)";
   $stmt = $db->prepare($query);
   $stmt->execute(array($email, $salt));
   if ($row = $stmt->fetchObject()) {
      $validate = "";
      if ($type === "officialEmail") {
         $validate = ", `validated` = 1 ";
      }
      $query = "UPDATE `user` SET `".$type."Validated` = 1 ".$validate." WHERE (`ID` = ?)";
      $stmt = $db->prepare($query);
      $stmt->execute(array($row->ID));
      echo "Votre adresse ".$email." a maintenant été confirmée. Vous pouvez désormais vous <a href='".$config->teacherInterface->sCoordinatorFolder."/'>connecter sur l'interface coordinateur</a>";
   } else {
      echo $errorMessage;
   }
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
echo "<p>Retourner à la la <a href='index.php'>page d'accueil coordinateur</a></p>";
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
