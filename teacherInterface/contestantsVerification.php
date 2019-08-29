<?php
require_once("../shared/common.php");
require_once("commonAdmin.php");


function getUserData($user_id, $table) {
    global $db;
    $q = "
        SELECT
            ID,
            firstName,
            lastName,
            genre,
            email,
            grade,
            zipCode,
            studentID
        FROM
            ".$table."
        WHERE
            ID = :ID
        LIMIT 1";
    $stmt = $db->prepare($q);
    $stmt->execute(array(
        "ID" => $user_id
    ));
    return (array) $stmt->fetchObject();
}


function updateUser($table, $key, $data) {
    global $db;
    $q = "
        UPDATE
            ".$table."
        SET
            firstName = :firstName,
            lastName = :lastName,
            genre = :genre,
            email = :email,
            grade = :grade,
            zipCode = :zipCode,
            studentID = :studentID
        WHERE
            ".$key." = :ID";
    $stmt = $db->prepare($q);
    $stmt->execute($data);
}


function confirm($user_id) {
    global $db;
    $q = "
        DELETE FROM algorea_registration_original
        WHERE ID = :ID
        LIMIT 1";
    $stmt = $db->prepare($q);
    $stmt->execute(array(
        "ID" => $user_id
    ));

    $q = "
        UPDATE algorea_registration
        SET confirmed = 1
        WHERE ID = :ID
        LIMIT 1";
    $stmt = $db->prepare($q);
    $stmt->execute(array(
        "ID" => $user_id
    ));
}


function approve($user_id) {
    $data = getUserData($user_id, 'algorea_registration');
    updateUser('contestant', 'registrationID', $data);
    confirm($user_id);
}


function reject($user_id) {
    $data = getUserData($user_id, 'algorea_registration_original');
    updateUser('contestant', 'registrationID', $data);
    updateUser('algorea_registration', 'ID', $data);
    confirm($user_id);
}


$action = isset($_POST['action']) ? $_POST['action'] : null;
switch($action) {
    case 'approve':
        approve($_POST['user_id']);
        break;
    case 'reject':
        reject($_POST['user_id']);
        break;
}

header('HTTP/1.1 200 OK');
header('Location: contestantsParticipations.php');