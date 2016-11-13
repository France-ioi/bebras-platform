<?php

require_once '../config.php';

function escape_js($str) {
	return str_replace('"', '\\"', $str);
}

function static_asset($path) {
	global $config;
	$qs = '';
	if ($config->timestamp !== false) {
		$qs = '?v=' . $config->timestamp;
	}
	return $config->teacherInterface->baseUrl . $path . $qs;
}

function script_tag($path) {
	echo "<script type=\"text/javascript\" src=\"" . static_asset($path) . "\"></script>\n";
}

function stylesheet_tag($path) {
	echo "<link rel=\"stylesheet\" href=\"" . static_asset($path) . "\" />\n";
}
