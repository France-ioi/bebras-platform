<?php

function loadSchoolUsers($db) {
  $query = "SELECT DISTINCT `user`.`firstName`, `user`.`lastName` FROM `user_user` ".
     " INNER JOIN `user` ON `user`.`ID` = `user_user`.`userID`".
     " WHERE `user_user`.`targetUserID` = :userID";
  $stmt = $db->prepare($query);
  $stmt->execute(array("userID" => $_SESSION["userID"]));
  $users = array();
  while ($row = $stmt->fetchObject()) {
     $users[] = $row;
  }
  return $users;
}


function jsonUser($db, $row, $options) {
   return array_merge($options, [
      "user" => array(
         "ID" => $row->ID,
         "isAdmin" => $row->isAdmin,
         "allowMultipleSchools" => $row->allowMultipleSchools,
         "gender" => $row->gender,
         "firstName" => $row->firstName,
         "lastName" => $row->lastName,
         "officialEmail" => $row->officialEmail,
         "alternativeEmail" => $row->alternativeEmail,
         "officialEmailValidated" => $row->officialEmailValidated,
         "alternativeEmailValidated" => $row->alternativeEmailValidated,
         "awardPrintingDate" => $row->awardPrintingDate,
         ),
      "alternativeEmailValidated" => $row->alternativeEmailValidated,
      "schoolUsers" => loadSchoolUsers($db)
   ]);
}


function setUserSession($row) {
    $_SESSION["userID"] = $row->ID;
    $_SESSION["isAdmin"] = $row->isAdmin;
    if($row->isAdmin) {
        $_SESSION["userType"] = "admin";
    } else {
        $_SESSION["userType"] = "user";
    }
}


function createUpdateUser($db, $user) {
    $obj = makeUserObject($user);
    $query = "SELECT * FROM `user` WHERE `externalID` = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$obj->externalID]);
    if($row = $stmt->fetchObject()) {
        validateUserAccess($user, $row);
        $obj->ID = $row->ID;
        updateUser($db, $obj);
    } else {
        validateUserAccess($user);
        $obj->ID = createUser($db, $obj);
    }
    return $obj;
}


function makeUserObject($user) {
    $res = [
        'externalID' => $user['id'],
        'firstName' => $user['first_name'],
        'lastName' => $user['last_name'],
        'isOwnOfficialEmail' => 1,
        'officialEmail' => $user['primary_email'],
        'officialEmailValidated' => empty($user['primary_email_verified']) ? 0 : 1,
        'alternativeEmail' => $user['secondary_email'],
        'alternativeEmailValidated' => empty($user['secondary_email_verified']) ? 0 : 1,
        'comment' => $user['presentation'],
        'gender' => null,
        'isAdmin' => false
    ];
    if($user['gender'] == 'm') {
        $res['gender'] = 'M';
    } else if($user['gender'] == 'f') {
        $res['gender'] = 'F';
    }
    return (object) $res;
}



function createUser($db, $row) {
    $stmt = $db->prepare("
        INSERT INTO
            `user`
            (`externalID`, `lastLoginDate`, `firstName`, `lastName`, `officialEmail`, `officialEmailValidated`, `alternativeEmail`, `alternativeEmailValidated`, `comment`, `gender`)
        VALUES
            (:externalID, UTC_TIMESTAMP(), :firstName, :lastName, :officialEmail, :officialEmailValidated, :alternativeEmail, :alternativeEmailValidated, :comment, :gender)
    ");
    $stmt->execute([
        'externalID' => $row->externalID,
        'firstName' => $row->firstName,
        'lastName' => $row->lastName,
        'officialEmail' => $row->officialEmail,
        'officialEmailValidated' => $row->officialEmailValidated,
        'alternativeEmail' => $row->alternativeEmail,
        'alternativeEmailValidated' => $row->alternativeEmailValidated,
        'comment' => $row->comment,
        'gender' => $row->gender
    ]);
    return $db->lastInsertId();
}


function updateUser($db, $row) {
    $stmt = $db->prepare("
        UPDATE
            `user`
        SET
            `lastLoginDate` = UTC_TIMESTAMP(),
            `firstName` = :firstName,
            `lastName` = :lastName,
            `officialEmail` = :officialEmail,
            `officialEmailValidated` = :officialEmailValidated,
            `alternativeEmail` = :alternativeEmail,
            `alternativeEmailValidated` = :alternativeEmailValidated,
            `comment` = :comment,
            `gender` = :gender
        WHERE
            `externalID` = :externalID
    ");
    $stmt->execute([
        'externalID' => $row->externalID,
        'firstName' => $row->firstName,
        'lastName' => $row->lastName,
        'officialEmail' => $row->officialEmail,
        'officialEmailValidated' => $row->officialEmailValidated,
        'alternativeEmail' => $row->alternativeEmail,
        'alternativeEmailValidated' => $row->alternativeEmailValidated,
        'comment' => $row->comment,
        'gender' => $row->gender
   ]);
}


function validateUserAccess($login_module_user, $bebras_user = null) {
    global $config;
    $manual_access = $bebras_user && $bebras_user->manualAccess > 0;
    $is_teacher = strtoupper($login_module_user['country_code']) == strtoupper($config->teacherInterface->countryCode) && $login_module_user['role'] == 'teacher';
    if(!$is_teacher && !$manual_access) {
        throw new Exception('login_module_access_denied');
    }
}