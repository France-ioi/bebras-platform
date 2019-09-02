<?php

class UserController extends Controller
{

/*
    Create user
    `
*/





/*
    Update user data
*/

    public function update()
    {
        $old = $this->loadUser($_POST['user']['ID']);

        $changed = false;
        foreach($old as $k => $v) {
            if(isset($_POST['user'][$k]) && $_POST['user'][$k] != $old[$k]) {
                $changed = true;
            }
        }
        if(!$changed) return;

        $confirmed = $old["confirmed"];
        unset($old["confirmed"]);

        if($confirmed) {
            $this->saveUserOriginal($old);
        }

        $new = [
            'firstName' => $_POST['user']['firstName'],
            'lastName' => $_POST['user']['lastName'],
            'grade' => $_POST['user']['grade'],
            'genre' => $_POST['user']['genre'],
            'email' => $_POST['user']['email'],
            'zipCode' => $_POST['user']['zipCode'],
            'studentID' => $_POST['user']['studentID'],
            'ID' => $_POST['user']['ID'],
            'confirmed' => 0
        ];
        $this->saveUser($new);

        exitWithJson(["success" => true]);
    }



    private function loadUser($id) {
        $stmt = $this->db->prepare("
            SELECT
                `ID`,
                `firstName`,
                `lastName`,
                `grade`,
                `genre`,
                `email`,
                `zipCode`,
                `studentID`,
                `confirmed`
            FROM
                `algorea_registration`
            WHERE
                `ID` = :ID
            LIMIT 1
        ");
        $stmt->execute([
            'ID' => $id
        ]);
        return (array) $stmt->fetchObject();
    }


    private function saveUser($data) {
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
                `studentID` = :studentID,
                `confirmed` = :confirmed
            WHERE
                `ID` = :ID
            LIMIT 1
        ");
        $stmt->execute($data);
    }


    private function saveUserOriginal($data) {
        $stmt = $this->db->prepare("
            INSERT INTO
                `algorea_registration_original`
                (
                    `ID`,
                    `firstName`,
                    `lastName`,
                    `grade`,
                    `genre`,
                    `email`,
                    `zipCode`,
                    `studentID`
                )
                VALUES
                (
                    :ID,
                    :firstName,
                    :lastName,
                    :grade,
                    :genre,
                    :email,
                    :zipCode,
                    :studentID
                )
            ON DUPLICATE KEY UPDATE
                `firstName` = :firstName,
                `lastName` = :lastName,
                `grade` = :grade,
                `genre` = :genre,
                `email` = :email,
                `zipCode` = :zipCode,
                `studentID` = :studentID
        ");
        $stmt->execute($data);
    }


}