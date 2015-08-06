<?php
/* Copyright (c) 2012 Association France-ioi, MIT License http://opensource.org/licenses/MIT */

require_once("connect.php");

$rows = $_REQUEST["data"];

error_log("start : ".time()." ".count($rows)." rows");

$query = "INSERT INTO `tracking_rawdata` (`data`) VALUES (?)";
$stmt = $db->prepare($query);
$stmt->execute(array(json_encode($rows)));

error_log("end : ".time());

unset($db);

?>