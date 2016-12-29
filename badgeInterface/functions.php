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
  $stmt = $db->prepare('select contestant.lastName as sLastName, contestant.firstName as sFirstName, contestant.genre as genre, contestant.email as sEmail, contestant.zipcode as sZipcode from contestant
    join team on team.ID = contestant.teamID
    join `group` on `group`.ID = team.groupID
    join contest on contest.ID = `group`.contestID
    where algoreaCode = :code and contest.badgeName = :badgeName;');
  $stmt->execute(['code' => $code, 'badgeName' => $badgeName]);

  $contestant = $stmt->fetch(PDO::FETCH_ASSOC);

  if (!$contestant) {
    return null;
  }

  $contestant['sSex'] = ($contestant['genre'] == 2 ? 'Male' : 'Female');
  unset($contestant['genre']);

  return $contestant;
}

/* Associate the user with ID idUser to the given (badgeName, code) pair. */
function updateAlgoreaRegistration($badgeName, $code, $idUser) {
  global $db;
  $stmt = $db->prepare('select algorea_registration.* from algorea_registration
    join contestant on contestant.ID = algorea_registration.contestantID
    join team on team.ID = contestant.teamID
    join `group` on `group`.ID = team.groupID
    join contest on contest.ID = `group`.contestID
    where algoreaCode = :code and contest.badgeName = :badgeName;');
  $stmt->execute(['code' => $code, 'badgeName' => $badgeName]);

  $infos = $stmt->fetch(PDO::FETCH_ASSOC);

  if ($infos) {
    if ($infos['franceioiID'] != $idUser) {
      return ['success' => false, 'error' => 'code is already registered by someone else'];
    }
    return ['success' => true];
  }

  $stmt = $db->prepare('select contestant.ID from contestant
    join team on team.ID = contestant.teamID
    join `group` on `group`.ID = team.groupID
    join contest on contest.ID = `group`.contestID
    where algoreaCode = :code and contest.badgeName = :badgeName;');
  $stmt->execute(['code' => $code, 'badgeName' => $badgeName]);

  $contestantID = $stmt->fetchColumn();

  if (!$contestantID) {
    return ['success' => false, 'error' => 'code is not valid'];
  }

  $stmt = $db->prepare('insert into algorea_registration (code, contestantID, franceioiID) values (:code, :contestantID, :franceioiID);');
  $stmt->execute(['code' => $code, 'contestantID' => $contestantID, 'franceioiID' => $idUser]);
  return ['success' => true];
}

/* Remove the association of a code with a franceioiID in algorea_registration */
function removeByCode($badgeName, $code) {
  global $db;
  $stmt = $db->prepare('select contestant.ID from contestant 
    join team on team.ID = contestant.teamID
    join `group` on `group`.ID = team.groupID
    join contest on contest.ID = `group`.contestID
    where algoreaCode = :code and contest.badgeName = :badgeName;');
  $stmt->execute(['code' => $code, 'badgeName' => $badgeName]);

  $contestantID = $stmt->fetchColumn();

  if (!$contestantID) {
    return ['success' => false, 'error' => 'code is not valid'];
  }

  $stmt = $db->prepare('remove algorea_registration where contestantID = :contestantID and code = :code;');
  $stmt->execute(['contestantID' => $contestantID, 'code' => $code]);
  return ['success' => true];
}