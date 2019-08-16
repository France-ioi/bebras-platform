<?php

class UserController extends Controller
{

    public function update()
    {
        $stmt = $this->db->prepare("
            UPDATE
                `algorea_registration`
            SET
                `firstName` = :firstName,
                `lastName` = :lastName,
                `grade` = :grade,
                `genre` = :genre,
                `email` = :email,
                `zipCode` = :zipCode,
                `studentId` = :studentId
            WHERE
                `ID` = :ID
            LIMIT 1
        ");
        $stmt->execute([
            'firstName' => $_POST['user']['firstName'],
            'lastName' => $_POST['user']['lastName'],
            'grade' => $_POST['user']['grade'],
            'genre' => $_POST['user']['genre'],
            'email' => $_POST['user']['email'],
            'zipCode' => $_POST['user']['zipCode'],
            'studentId' => $_POST['user']['studentId'],
            'ID' => $_POST['user']['ID']
        ]);
        exitWithJson(["success" => true]);
    }
}
