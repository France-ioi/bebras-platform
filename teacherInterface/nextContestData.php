<?php

require_once("../shared/common.php");
require_once("commonAdmin.php");


if (!isset($_SESSION["userID"])) {
   echo "Votre session a expiré, veuillez vous reconnecter.";
   exit;
}

if (function_exists('getNextContestData')) {
  	$data = getNextContestData();
  	echo json_encode(['success' => true, 'data' => $data]);
} else {
	echo json_encode(['success' => false]);
}

