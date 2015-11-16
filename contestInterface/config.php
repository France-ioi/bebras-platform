<?php
require('../config.php');

if(preg_match('/(?i)msie [5-7]/',$_SERVER['HTTP_USER_AGENT']) || (isset($_GET['p']) && $_GET['p'] == '1')) {
    // if IE<=7 or ?p=1
    $config->teacherInterface->sAssetsStaticPath = $config->teacherInterface->sAbsoluteStaticPathOldIE;
}

function escape_js($str) {
	return str_replace('"', '\\"', $str);
}

function static_asset($path) {
	global $config;
	return $config->teacherInterface->sAssetsStaticPath . $path;
}

function script_tag($path) {
	echo "<script type=\"text/javascript\" src=\"" . static_asset($path) . "\"></script>\n";
}

function stylesheet_tag($path) {
	echo "<link rel=\"stylesheet\" href=\"" . static_asset($path) . "\" />\n";
}
