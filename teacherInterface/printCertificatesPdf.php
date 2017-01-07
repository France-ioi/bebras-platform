<?php
  include('./config.php');
  header('Content-type: text/html');
?><!DOCTYPE html>
<html><head>
<meta http-equiv="Content-Type" content="text/html" charset="UTF-8">
<title data-i18n="page_title"></title>
<link href="https://fonts.googleapis.com/css?family=Lato|Sorts+Mill+Goudy|Varela+Round" rel="stylesheet">
<!-- Varela Round needed for Alkindi -->
<?php
function imageToBase64($path) {
   return 'data:image/' . pathinfo($path, PATHINFO_EXTENSION) . ';base64,' .
          base64_encode(file_get_contents($path));
}

script_tag('/bower_components/pdfmake/build/pdfmake.js');
script_tag('/bower_components/pdfmake/build/vfs_fonts.js');
script_tag('/bower_components/jquery/jquery.min.js');
script_tag('/bower_components/jquery-ui/jquery-ui.min.js'); // for $.datepicker.formatDate
script_tag('/bower_components/i18next/i18next.min.js');
script_tag('/printCertificatesPdf.js');

?>

<script type="text/javascript">

 window.i18nconfig = <?= json_encode([
   'lng' => $config->defaultLanguage,
   'fallbackLng' => [$config->defaultLanguage],
   'fallbackNS' => 'translation',
   'ns' => [
    'namespaces' => $config->customStringsName ? [$config->customStringsName, 'translation'] : ['translation'],
    'defaultNs' => $config->customStringsName ? $config->customStringsName : 'translation',
   ],
   'getAsync' => true,
   'resGetPath' => static_asset('/i18n/__lng__/__ns__.json')
 ]); ?>;



var allImages = {
   background: "<?= imageToBase64($config->certificates->background) ?>",
   logo: "<?= imageToBase64($config->certificates->logo) ?>",
   yearBackground: "<?= imageToBase64($config->certificates->yearBackground) ?>"
}
<?php

$partnerImagesInfos = array();
$maxLogoHeight = 0;
$finalWidth = 70;
$logoStartX = 165 - (count($config->certificates->partnerLogos) * 80 - 10) / 2;
foreach ($config->certificates->partnerLogos as $fileName) {
   $imageInfo = getimagesize($fileName);
   $width = intVal($imageInfo[0]);
   $height = intVal($imageInfo[1]) * $finalWidth / $width;
   if ($height > $maxLogoHeight) {
      $maxLogoHeight = $height;
   }
   $partnerImagesInfos[] = array($fileName, $height);
}
$strJS = "var partnerLogos = [\n";
foreach ($partnerImagesInfos as $iLogo => $logoInfo) {
   $strJS .= "{ stack:[{image: '" . imageToBase64($logoInfo[0]) .
              "', width:70}], absolutePosition: {x:" . ($logoStartX + ($iLogo * 80)) . ", y:" .
              (510 + ($maxLogoHeight - $logoInfo[1]) / 2) . " } },\n";
}
$strJS .= "];";

echo $strJS;

?>
var contestName = '<?=$config->certificates->title?>';
var qualificationText = '<?=$config->certificates->qualificationText?>';
var contestUrl = '<?=$config->certificates->url?>';
var mainColor = '<?=$config->certificates->mainColor?>';
var accentColor = '<?=$config->certificates->accentColor?>';
var showYear = <?=$config->certificates->showYear?>;
var titleFontSize = <?=$config->certificates->titleFontSize?>;
</script>
<style>
   /*
font-family: 'Sorts Mill Goudy', serif;
font-family: 'Varela Round', sans-serif;
   */
   body {
      font-family: Arial, sans-serif;
      font-size: 16px;
      color: #4A5785;
      line-height: 1;
   }
   .bigmessage {
      text-align: center;
      font-size: 32px;
      margin-bottom: 30px;
      margin-top: 30px;
   }
</style>
</head>
<body>
<div style="text-align: center">
   <p class="bigmessage">Génération des diplômes</p>

   <div id="preload">
      <p>Téléchargement des données en cours.</p>
      <p>Veuillez patienter quelques instants.</p>
   </div>

   <div id="loaded" style="display:none;text-align:center">
      <div style="width:600px;background:#EEE;border:solid black 1px;margin:auto">
         <p>La création d'un pdf peut prendre plusieurs secondes.</p>
         <p><b>Attention :</b> il est possible que le navigateur affiche une popup vous disant<br/>que la page ne répond plus et vous demandant si vous voulez continuer.<br/>Répondez oui car la préparation des diplômes peut prendre du temps.</p>
         <p>Assurez-vous d'utiliser un navigateur récent.</p>
      </div>
      <br/>
      <div style="border:solid black 1px;margin:auto;padding:5px;text-align:left;width:600px;">
         <p><b>Option</b> : n'imprimer les diplômes que pour :</p>
         <p><input type="checkbox" id="qualifiedOnly" onchange="updateNbDiplomas()"></input>Les élèves ayant obtenu <span id="qualificationText"></span></p>
         <p><input type="checkbox" id="topRankedOnly" onchange="updateNbDiplomas()"></input>Les élèves étant dans les <input type="number" id="minRankPercentile" style="width:40px;text-align:center" value="50" onchange="updateNbDiplomas()"/></input>% mieux classés de leur catégorie</p>
         <p>Diplômes à imprimer : <span id="printedCertificates"></span> sur <span id="totalCertificates"></span>
      </div>
      <br/>
      <p>Créer un pdf pour chaque paquet de <input type="number" id="diplomasPerPart" value="100" style="width:40px" onchange="updateNbDiplomas()"></input> diplômes.<br/>(Un petit nombre réduit les chances que votre navigateur crashe)</p>
      <div id="buttons">
      </div>
   </div>

</div>

</body>
</html>