<?php

class UserController extends Controller
{

/*
    guest
*/

    public function createGuest() {
        $user = array(
            'firstName' => 'Anonymous',
            'lastName' => 'Anonymous',
            'grade' => '',
            'genre' => '',
            'email' => '',
            'zipCode' => '',
            'studentID' => '',
            'confirmed' => '',
            'code' => $this->genAccessCode(),
            'guest' => 1,
            'confirmed' => 1
        );
        $user['ID'] = $this->insertUser($user);
        $_SESSION['registrationID'] = $user['ID'];
        $_SESSION['guest'] = true;
        $registrationData = $this->getRegistrationData($user);
        exitWithJson([
            "success" => true,
            "registrationData" => $registrationData
        ]);
    }


/*
    Create user
    `
*/

    public function createRegular() {
        $user = $_POST['user'];
        $user['code'] = $this->genAccessCode();
        $user['guest'] = 0;
        $user['confirmed'] = 1;
        $user['ID'] = $this->insertUser($user);
        $_SESSION['registrationID'] = $user['ID'];
        $_SESSION['guest'] = false;
        $registrationData = $this->getRegistrationData($user);
        exitWithJson([
            "success" => true,
            "registrationData" => $registrationData
        ]);
    }


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
            'confirmed' => $_POST['user']['guest'] == 1 ? 1 : 0
        ];
        $this->saveUser($new);

        $_SESSION['guest'] = false;

        $new['guest'] = 0;
        $new['original'] = $this->getUserOriginal($new['ID']);
        exitWithJson(array(
            "success" => true,
            "user" => $new
        ));
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
                `confirmed` = :confirmed,
                `guest` = 0
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



    private function insertUser($data) {
        $stmt = $this->db->prepare("
            INSERT INTO
                `algorea_registration`
                (
                    `firstName`,
                    `lastName`,
                    `grade`,
                    `genre`,
                    `email`,
                    `zipCode`,
                    `studentID`,
                    `confirmed`,
                    `code`,
                    `guest`
                )
            VALUES
                (
                :firstName,
                :lastName,
                :grade,
                :genre,
                :email,
                :zipCode,
                :studentID,
                :confirmed,
                :code,
                :guest
                )
        ");
        $stmt->execute($data);
        return $this->db->lastInsertId();
    }



    private function genAccessCode() {
        srand(time() + rand());
        $charsAllowed = "3456789abcdefghijkmnpqrstuvwxy";
        $query = "SELECT `ID` FROM `group` WHERE `password` = ? OR `code` = ? UNION ".
                 "SELECT `ID` FROM `team` WHERE `password` = ? UNION ".
                 "SELECT `ID` FROM `contestant` WHERE `algoreaCode` = ? UNION ".
                 "SELECT `ID` FROM `algorea_registration` WHERE `code` = ? ";
        $stmt = $this->db->prepare($query);
        do {
           $code = "";
           for ($pos = 0; $pos < 8; $pos++) {
              $iChar = rand(0, strlen($charsAllowed) - 1);
              $code .= substr($charsAllowed, $iChar, 1);
           }
           $stmt->execute(array($code, $code, $code, $code, $code));
           $row = $stmt->fetchObject();
        } while($row);
        return $code;
    }


    private function getRegistrationData($user) {
        $user['qualifiedCategory'] = '';
        $user['validatedCategory'] = '';
        $user['allowContestAtHome'] = 1;
        return $user;
    }


    private function getUserOriginal($id)
    {
        $stmt = $this->db->prepare("
            SELECT
                `firstName`,
                `lastName`,
                `grade`,
                `genre`,
                `email`,
                `zipCode`,
                `studentID`
            FROM
                `algorea_registration_original`
            WHERE
                `ID` = :ID
            LIMIT 1
        ");
        $stmt->execute([
            'ID' => $id
        ]);
        return $stmt->fetchObject();
    }

}