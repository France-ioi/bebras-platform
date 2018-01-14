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
if (isset($_GET["groupID"])) {
   $request["filters"]["groupID"] = $_GET["groupID"];
}
if (isset($_GET["schoolID"])) {
   $request["filters"]["schoolID"] = $_GET["schoolID"];
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
       width: 45%;
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
       clear: both;
       display:block;
       page-break-after:always;
       outline: 0px;
    }
    .awardsTable {
       page-break-before:always;
       page-break-after:always;
       padding-top: 10px;
       padding-bottom: 10px;
       clear: both;
    }
    .awardsTable tr td {
       border: solid black 1px;
       padding: 3px;
    }
    .awardsTable tr:first-child td {
       font-weight: bold;
    }
    </style>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
  </head>
  <body onload="window.print()">
  
<?php

$currentYear = date("Y");
$currentSchoolID = null;
$currentContestID = null;
$currentGroupField = null;
$nbLabelsOnPage = 0;
$strCertificates = '<br/>';
$strTableHeader = '<table cellpadding=0 cellspacing=0 class="awardsTable"><tr><td>Groupe</td><td>Prénom</td><td>Nom</td><td>Code de participant Algoréa</td><td>Qualification</td></tr>';
$strTable = $strTableHeader;
foreach($awarded as $contestant) {
   if ($currentSchoolID == null) {
      $currentSchoolID = $contestant->schoolID;
      $currentContestID = $contestant->contestID;
      $currentGroupField = $contestant->groupField;
   }
   if ($contestant->schoolID != $currentSchoolID || $contestant->contestID != $currentContestID || $contestant->groupField != $currentGroupField) {
      $currentSchoolID = $contestant->schoolID;
      $currentContestID = $contestant->contestID;
      $currentGroupField = $contestant->groupField;
      $nbLabelsOnPage = 0;
      $strTable .= "</table>";
      echo $strTable;
      echo $strCertificates;
      $strCertificates = '<br/>';
      $strTable = $strTableHeader;
   }
   $strTable .= '<tr>';
   $firstLine = $strings['award_print_first_line'];
   $secondLine = $strings['award_print_second_line'];
   $thirdLine = $strings['award_print_third_line'];
   $contests3rdRound = array("124236500942177376", "151709596466921552", "175448842785562190", "424393866218438188", "195702159164266914", "452484155216195876");
   if (in_array($currentContestID , $contests3rdRound)) {
      $firstLine = "Qualification en 1/2 finale du concours Algoréa";
      $secondLine = "Utilisable sur concours.algorea.org";
      $thirdLine = "Date du concours : du 28 mai au 12 juin 2017";
   }
   $strCertificates .= '<div class="label"><div class="labelContent">';
   $strCertificates .= '<p class="title">'.$firstLine.'</p>';
   $strCertificates .= '<p class="name">'.$contestant->firstName.' '.$contestant->lastName.'</p>';
   if (isset($_GET["showGroup"])) {
      $strCertificates .= '<p class="small">'.$contestant->groupName.'</p>';
   }
   $strTable .= '<td>'.$contestant->groupName.'</td>';
   $strTable .= '<td>'.$contestant->firstName.'</td>';
   $strTable .= '<td>'.$contestant->lastName.'</td>';
   $strTable .= '<td>'.$contestant->algoreaCode.'</td>';
   $strTable .= '<td>'.$contestant->algoreaCategory.'</td>';
   $strCertificates .= '<p class="schoolName">'.$contestant->name.'</p>'; // name of the school
   $strCertificates .= '<p>code confidentiel: <span class="code">'.$contestant->algoreaCode.'</span></p>';
   if ($contestant->algoreaCategory) {
      $strCertificates .= '<p>qualification : <span class="code">'.$contestant->algoreaCategory.'</span></p>';
   }
   $strCertificates .= '<p class="small">'.$secondLine.'</p>';
   $strCertificates .= '<p class="small">'.$thirdLine.'</p>';
   $strCertificates .= '</div></div>';
   $nbLabelsOnPage += 1;
   $strTable .= '</tr>';
   if ($nbLabelsOnPage >= 8) {
      $nbLabelsOnPage = 0;
      $strCertificates .= '<div class="page-break">&nbsp;</div><br/>';
   }
}
$strTable .= '</table>';
echo $strTable;
echo $strCertificates;

?>

</body>
</html>
