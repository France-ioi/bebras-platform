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
        $registrationData = $this->getRegistrationData($user);
        $_SESSION["registrationData"] = (object) $registrationData;
        exitWithJson([
            "success" => true,
            "registrationData" => $registrationData
        ]);
    }


/*
    Create user
    `
*/

    public function createRegular($request) {
        $user = $request['user'];
        $user['code'] = $this->genAccessCode();
        $user['guest'] = 0;
        $user['confirmed'] = 1;
        $user['ID'] = $this->insertUser($user);
        $registrationData = $this->getRegistrationData($user);
        $_SESSION["registrationData"] = (object) $registrationData;
        exitWithJson([
            "success" => true,
            "registrationData" => $registrationData
        ]);
    }


/*
    Update user data
*/

    public function update($request)
    {
        $old = $this->loadUser($request['user']['ID']);

        $changed = false;
        foreach($old as $k => $v) {
            if(isset($request['user'][$k]) && $request['user'][$k] != $old[$k]) {
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
            'firstName' => $request['user']['firstName'],
            'lastName' => $request['user']['lastName'],
            'grade' => $request['user']['grade'],
            'genre' => $request['user']['genre'],
            'email' => $request['user']['email'],
            'zipCode' => $request['user']['zipCode'],
            'studentID' => $request['user']['studentID'],
            'ID' => $request['user']['ID'],
            'confirmed' => $request['user']['guest'] == 1 ? 1 : 0
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
        $user['algoreaCategory'] = 1;
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