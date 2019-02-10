<?php
  include('./config.php');
  require_once("../shared/common.php");
  require_once("commonAdmin.php");

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
script_tag('/certificates_vfs_fonts.js');
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
$totalWidth = 0;
foreach ($config->certificates->partnerLogos as $numLogo => $fileName) {
   $imageInfo = getimagesize($fileName);
   $width = intVal($imageInfo[0]);
   $finalWidth = 70;
   if ($config->certificates->partnerLogosWidths != null) {
      $finalWidth = intVal($config->certificates->partnerLogosWidths[$numLogo]);
   }
   $height = intVal($imageInfo[1]) * $finalWidth / $width;
   if ($height > $maxLogoHeight) {
      $maxLogoHeight = $height;
   }
   $partnerImagesInfos[] = array($fileName, $height, $finalWidth);
   $totalWidth += $finalWidth;
}
$nbLogos = count($config->certificates->partnerLogos);
$logoStartX = 165 - ($totalWidth + (10 * ($nbLogos - 1))) / 2;
$xPos = $logoStartX;
$partnersStartY = 510;
if (isset($config->certificates->partnerLogosY)) {
   $partnersStartY = intVal($config->certificates->partnerLogosY);
}
$strJS = "var partnersStartY = ".$partnersStartY.";\nvar partnerLogos = [\n";
foreach ($partnerImagesInfos as $iLogo => $logoInfo) {
   $width = $logoInfo[2];

   $height = $logoInfo[1];
   $strJS .= "{ stack:[{image: '" . imageToBase64($logoInfo[0]) .
              "', width:".$width."}], absolutePosition: {x:" . $xPos . ", y:" .
              ($partnersStartY  + ($maxLogoHeight - $height) / 2) . " } },\n"; 
   $xPos += $width + 10;
}
$strJS .= "];";

echo $strJS;

if (isset($config->certificates->footer)) {
   echo "var footer = '".$config->certificates->footer."';\n";
} else {
   echo "var footer = '';\n";
}
if (isset($config->certificates->defaultFont)) {
   echo "var defaultFont = '".$config->certificates->defaultFont."';\n";
} else {
   echo "var defaultFont = 'Roboto';\n";
}

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
   <p class="bigmessage"><?php echo translate("certificates_generation"); ?></p>

   <div id="preload">
      <p><?php echo translate("download_in_progress"); ?></p>
      <p><?php echo translate("please_wait"); ?></p>
   </div>

   <div id="loaded" style="display:none;text-align:center">
      <div style="width:600px;background:#EEE;border:solid black 1px;margin:auto">
         <p><?php echo translate("certificates_generation_may_take_time"); ?></p>
         <p><?php echo translate("warning_possible_popup"); ?></p>
         <p><?php echo translate("use_recent_browser"); ?></p>
      </div>
      <br/>
      <div style="border:solid black 1px;margin:auto;padding:5px;text-align:left;width:600px;">
         <p><?php echo translate("certificates_option_filter"); ?></p>
         <!--<p><input type="checkbox" id="qualifiedOnly" onchange="updateNbDiplomas()"></input>Les élèves ayant obtenu <span id="qualificationText"></span></p>-->
         <p><input type="checkbox" id="topRankedOnly" onchange="updateNbDiplomas()"></input>
         <?php
            echo sprintf(translate("certificates_filter_students_percentile"), "<input type='number' id='minRankPercentile' style='width:40px;text-align:center' value='50' onchange='updateNbDiplomas()'/></input>");
         ?> </p>
         <p><?php echo sprintf(translate("certificate_number_to_print"), "<span id='printedCertificates'></span>", "<span id='totalCertificates'></span>");  ?></p>
         <p><?php echo "<b>".translate("certificates_option_display")."</b>"; ?></p>
         
         <?php
            echo "<p>".sprintf(translate("certificates_min_score_displayed"), "<input type='number' id='minScoreDisplayed' style='width:40px;text-align:center' value='0'/></input>")."</p>";
            echo "<p>".sprintf(translate("certificates_max_rank_percentile_displayed"), "<input type='number' id='maxRankPercentileDisplayed' style='width:40px;text-align:center' value='50'/></input>")."</p>";
            echo "<p>".sprintf(translate("certificates_max_school_rank_percentile_displayed"), "<input type='number' id='maxSchoolRankPercentileDisplayed' style='width:40px;text-align:center' value='50'/></input>")."</p>";
         ?>
      </div>
      <br/>
      <p><?php echo sprintf(translate("certificates_number_per_pdf"), "<input type='number' id='diplomasPerPart' value='100' style='width:40px' onchange='updateNbDiplomas()'></input>"); ?> </p>
      <div id="buttons">
      </div>
   </div>

</div>

</body>
</html>