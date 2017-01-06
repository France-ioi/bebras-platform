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
      <p>Téléchargement des donnéees en cours.</p>
      <p>Veuillez patienter quelques instants.</p>
   </div>

   <div id="loaded" style="display:none">
      <button onclick="getStrings(params)" style="display: block;margin: 0 auto">Générer le PDF</button>

      <p>La création du fichier peut prendre plusieurs secondes.</p>
   </div>
   <br/><br/>
   <p>Assurez-vous d'utiliser un navigateur récent</p>

</div>

</body>
</html>