<?php
require(__DIR__.'/../config.php');

if (preg_match('/(?i)msie [5-7]/',$_SERVER['HTTP_USER_AGENT'])) {
    // if IE<=7 or ?p=1
    $config->teacherInterface->sAssetsStaticPath = $config->teacherInterface->sAbsoluteStaticPathOldIE;
}

if (isset($_GET['p']) && $_GET['p'] == '1') {
	$config->teacherInterface->sAssetsStaticPath = $config->contestInterface->sAssetsStaticPathNoS3;
	$config->teacherInterface->sAbsoluteStaticPath = $config->contestInterface->sAbsoluteStaticPathNoS3;
}

function escape_js($str) {
	return str_replace('"', '\\"', $str);
}

function array_get($array, $key, $default) {
	if (array_key_exists($key, $array)) {
		return $array[$key];
	} else {
		return $default;
	}
}

function upgrade_url($url) {
	if (array_get($_SERVER, "HTTPS", "") == "on" ||
			array_get($_SERVER, "HTTP_X_FORWARDED_PROTO", "") == "https") {
		$url = preg_replace("/^http:/", "https:", $url, 1);
	}
	return $url;
}

function static_asset($path) {
	global $config;
	$qs = '';
	if ($config->timestamp !== false) {
		$qs = '?v=' . $config->timestamp;
	}
	return upgrade_url($config->teacherInterface->sAssetsStaticPath . $path . $qs);
}

function script_tag($path) {
	echo "<script type=\"text/javascript\" src=\"" . static_asset($path) . "\"></script>\n";
}

function stylesheet_tag($path) {
	echo "<link rel=\"stylesheet\" href=\"" . static_asset($path) . "\" />\n";
}
