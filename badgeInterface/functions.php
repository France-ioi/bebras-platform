<?php

global $db;
require_once '../shared/connect.php';

function exitWithJson($json) {
  header("Content-Type: application/json");
  header("Connection: close");
  echo json_encode($json);
  exit;
}

function exitWithJsonFailure($message) {
  $result = array("success" => false, "error" => $message);
  exitWithJson($result);
}

function getRequiredParam($key) {
  if (!array_key_exists($key, $_POST)) {
    exitWithJsonFailure($key.' param is required');
  }
  return $_POST[$key];
}

/* Return the user details for the matching (badgeName, code) pair, or null if
   no match is found. */
function verifyCode($badgeName, $code) {
  global $db;
  $stmt = $db->prepare('SELECT algorea_registration.lastName as sLastName, algorea_registration.firstName as sFirstName, algorea_registration.genre as genre, algorea_registration.email as sEmail, algorea_registration.zipcode as sZipcode, algorea_registration.franceioiID, algorea_registration.category FROM algorea_registration, contest WHERE code = :code and contest.badgeName = :badgeName;');
  $stmt->execute(['code' => $code, 'badgeName' => $badgeName]);

  $contestant = $stmt->fetch(PDO::FETCH_ASSOC);

  if (!$contestant) {
    return null;
  }

  $contestant['sSex'] = ($contestant['genre'] == 2 ? 'Male' : 'Female');
  unset($contestant['genre']);

  // Data is transmitted everywhere along the badge
  $contestant['data'] = ['category' => $contestant['category']];
  unset($contestant['category']);

  return $contestant;
}

/* Associate the user with ID idUser to the given (badgeName, code) pair. */
function updateAlgoreaRegistration($badgeName, $code, $idUser) {
  global $db;
  $stmt = $db->prepare('select algorea_registration.* from algorea_registration, contest
    where code = :code and contest.badgeName = :badgeName;');
  $stmt->execute(['code' => $code, 'badgeName' => $badgeName]);

  $infos = $stmt->fetch(PDO::FETCH_ASSOC);

  if (!$infos) {
    return ['success' => false, 'error' => 'code is invalid'];
  }
  if (($infos['franceioiID'] != $idUser) && ($infos['franceioiID' != null)) {
    return ['success' => false, 'error' => 'code is already registered by someone else'];
  }
  $stmt = $db->prepare('UPDATE algorea_registration SET franceioiID = :franceioiID WHERE code = :code');
  $stmt->execute(['code' => $code, 'franceioiID' => $idUser]);
  return ['success' => true];
}

/* Remove the association of a code with a franceioiID in algorea_registration */
function removeByCode($badgeName, $code) {
  global $db;
  $stmt = $db->prepare('SELECT algorea_registration.ID FROM algorea_registration, contest
    WHERE algoreaCode = :code AND contest.badgeName = :badgeName');
  $stmt->execute(['code' => $code, 'badgeName' => $badgeName]);

  $registrationID = $stmt->fetchColumn();

  if (!$registrationID) {
    return ['success' => false, 'error' => 'code is not valid'];
  }
  $stmt = $db->prepare('UPDATE algorea_registration SET franceioiID = NULL WHERE code = :code;');
  $stmt->execute('code' => $code]);
  return ['success' => true];
}
