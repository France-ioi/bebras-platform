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
$request["filters"] = array('awarded' => true, 'printable' => true);
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
      font-size: 12px;
      width: 16cm;
      margin-left: 1.5cm;
    }
    p {
      margin-bottom:5px;
      margin-top: 0px;
    }
    .title{
      font-size: 15px;
      font-weight:bold;
    }
    .small{
      font-size: 11px;
    }
    .code{
      font-size: 15px;
      font-weight:bold;
    }
    .label{
       width: 8cm;
       height: 6cm;
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

   $firstLine = $strings['award_print_first_line'];
   $secondLine = $strings['award_print_second_line'];
   $thirdLine = $strings['award_print_third_line'];
   $contests3rdRound = array("124236500942177376", "151709596466921552", "175448842785562190", "424393866218438188", "195702159164266914", "452484155216195876");
   if (in_array($currentContestID , $contests3rdRound)) {
      $firstLine = "Qualification en 1/2 finale du concours Algoréa";
      $secondLine = "Utilisable sur concours.algorea.org";
      $thirdLine = "Date du concours : du 28 mai au 12 juin 2017";
   }
   echo '<div class="label"><div class="labelContent">';
   echo '<p class="title">'.$firstLine.'</p>';
   echo '<p class="name">'.$contestant->firstName.' '.$contestant->lastName.'</p>';
   echo '<p class="schoolName">'.$contestant->name.'</p>'; // name of the school
   echo '<p>code confidentiel: <span class="code">'.$contestant->algoreaCode.'</span></p>';
   echo '<p class="small">'.$secondLine.'</p>';
   echo '<p class="small">'.$thirdLine.'</p>';
   echo '</div></div>';
   $nbLabelsOnPage += 1;
   if ($nbLabelsOnPage >= 8) {
      $nbLabelsOnPage = 0;
      echo '<div class="page-break"></div>';
   }
}

?>

</body>
</html>
