<?php

require_once("../shared/common.php");
require_once("commonAdmin.php");

if (!isset($_SESSION['userID'])) {
   echo "Votre session a expiré, veuillez vous reconnecter.";
   exit();
}

$strings = json_decode(file_get_contents(__DIR__.'/i18n/'.$config->defaultLanguage.'/translation.json'), true);
if ($config->customStringsName) {
  $customStrings = json_decode(file_get_contents(__DIR__.'/i18n/'.$config->defaultLanguage.'/'.$config->customStringsName.'.json'), true);
  $strings = array_merge($strings, $customStrings);
}

$model = getViewModel('award1');
$request = array(
   "modelName" => 'award1',
   "model" => $model,
   "filters" => array()
);
foreach($model["fields"] as $fieldName => $field) {
   $request["fields"][] = $fieldName;
}
$request["filters"] = array('awarded' => true);
if (!$_SESSION["isAdmin"]) {
   $request["filters"]["userID"] = $_SESSION["userID"];
}
$request['orders'] = $model['orders'];
$result = selectRows($db, $request);
$awarded = $result['items'];
if (!count($awarded)) {
   echo 'Rien à imprimer !';
   exit();
}

?>
<!doctype html>
<html lang="fr">
  <head>
    <style type="text/css">
    body, td {
      font-family: Arial, sans-serif;
      font-size: 9px;
      width: 18cm;
      margin: 0 0;
    }
    p {
      margin-bottom:5px;
      margin-top: 0px;
    }
    .title{
      font-size: 11px;
      font-weight:bold;
    }
    .small{
      font-size: 7px;
    }
    .code{
      font-size: 11px;
      font-weight:bold;
    }
    .label{
       width: 6cm;
       height: 3.75cm;
       padding: 0 0 0 0;
       margin-right: 0;
       float: left;
       overflow: hidden;
       outline: 1px solid;
    }
    .labelContent{
       margin: 0.5cm;
       text-align: center;
    }
    .page-break  {
       clear: left;
       display:block;
       page-break-after:always;
       outline: 0px;
    }
    </style>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
  </head>
  <body onload="window.print()">
  
<?php

$currentYear = date("Y");
$currentSchoolID = null;
$currentContestID = null;
$nbLabelsOnPage = 0;
foreach($awarded as $contestant) {
   if ($currentSchoolID == null) {
      $currentSchoolID = $contestant->schoolID;
      $currentContestID = $contestant->contestID;
   }
   if ($contestant->schoolID != $currentSchoolID || $contestant->contestID != $currentContestID) {
      $currentSchoolID = $contestant->schoolID;
      $currentContestID = $contestant->contestID;
      $nbLabelsOnPage = 0;
      echo '<div class="page-break"></div>';
   }
   echo '<div class="label"><div class="labelContent">';
   echo '<p class="title">'.$strings['award_print_first_line'].'</p>';
   echo '<p class="name">'.$contestant->firstName.' '.$contestant->lastName.'</p>';
   echo '<p class="schoolName">'.$contestant->name.'</p>'; // name of the school
   echo '<p>code confidentiel: <span class="code">'.$contestant->algoreaCode.'</span></p>';
   echo '<p class="small">'.$strings['award_print_second_line'].'</p>';
   echo '<p class="small">'.$strings['award_print_third_line'].'</p>';
   echo '</div></div>';
   $nbLabelsOnPage += 1;
   if ($nbLabelsOnPage >= 18) {
      $nbLabelsOnPage = 0;
      echo '<div class="page-break"></div>';
   }
}

?>

</body>
</html>
