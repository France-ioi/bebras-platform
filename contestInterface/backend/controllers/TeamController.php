<?php

class TeamController extends Controller
{


    public function create()
    {
        global $tinyOrm, $config;
        if (!isset($_POST["contestants"])) {
            exitWithJsonFailure("Informations sur les candidats manquantes");
        }
        if (!isset($_SESSION["groupID"])) {
            exitWithJsonFailure("Groupe non chargé");
        }
        if ($_SESSION["groupClosed"]) {
            error_log("Hack attempt ? trying to create team on closed group " . $_SESSION["groupID"]);
            exitWithJsonFailure("Groupe fermé");
        }
        if (isset($_POST["contestID"])) {
            if ($_SESSION["contestID"] != $_POST["contestID"]) {
                $_SESSION["contestID"] = $_POST["contestID"];
                $stmt = $this->db->prepare("SELECT `folder` FROM contest WHERE ID = ?");
                $stmt->execute(array($_SESSION["contestID"]));
                $row = $stmt->fetchObject();
                $_SESSION["contestFolder"] = $row->folder;
                $groupData = $this->getGroupForSubContest($_SESSION["groupID"], $_SESSION["contestID"]);
                $_SESSION["groupID"] = $groupData["ID"];
            }
        }
        // $_SESSION['userCode'] is set by optional password handling function,
        // see comments of createTeamFromUserCode in common_contest.php.
        $groupID = $_SESSION["groupID"];
        if (isset($_SESSION["userCode"]) && isset($_SESSION["userCodeGroupCode"]) && $_SESSION["userCodeGroupCode"] == $_SESSION["groupCode"]) {
            $password = $_SESSION["userCode"];
        } else {
            $password = genAccessCode($this->db);
        }
        unset($_SESSION["userCode"]);
        unset($_SESSION["userCodeGroupCode"]);
        $teamID = getRandomID();
        $stmt = $this->db->prepare("INSERT INTO `team` (`ID`, `groupID`, `password`, `nbMinutes`, `contestID`) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute(array($teamID, $groupID, $password, $_SESSION["nbMinutes"], $_SESSION["contestID"]));
        if ($config->db->use == 'dynamoDB') {
            try {
                $tinyOrm->insert('team', array(
                    'ID'       => $teamID,
                    'groupID'  => $groupID,
                    'password' => $password,
                    'nbMinutes' => $_SESSION["nbMinutes"]
                ));
            } catch (Aws\DynamoDb\Exception\DynamoDbException $e) {
                error_log($e->getAwsErrorCode() . " - " . $e->getAwsErrorType());
                error_log('DynamoDB error creating team, teamID: ' . $teamID);
            }
        }

        $contestants = $_POST["contestants"];
        $stmt = $this->db->prepare("UPDATE `group` SET `startTime` = UTC_TIMESTAMP() WHERE `group`.`ID` = ? AND `startTime` IS NULL");
        $stmt->execute(array($groupID));
        $stmt = $this->db->prepare("UPDATE `group` SET `nbTeamsEffective` = `nbTeamsEffective` + 1, `nbStudentsEffective` = `nbStudentsEffective` + ? WHERE `ID` = ?");
        $stmt->execute(array(count($contestants), $groupID));

        if (isset($_SESSION['mysqlOnly'])) {
            unset($_SESSION['mysqlOnly']);
        }
        $_SESSION["teamID"] = $teamID;
        $_SESSION["teamPassword"] = $password;
        foreach ($contestants as $contestant) {
            if (isset($contestant["registrationCode"])) {
                $stmt = $this->db->prepare("INSERT INTO `contestant` (`ID`, `lastName`, `firstName`, `genre`, `grade`, `studentId`, `teamID`, `cached_schoolID`, `saniValid`, `email`, `zipCode`, `registrationID`)
              SELECT :contestantID, `lastName`, `firstName`, `genre`, `grade`, `studentId`, :teamID, `schoolID`, 1, `email`, `zipCode`, `ID`
              FROM `algorea_registration` WHERE `algorea_registration`.`code` = :code");
                $stmt->execute(array(
                    "contestantID" => getRandomID(),
                    "code" => $contestant["registrationCode"],
                    "teamID" => $teamID
                ));
            } else {
                if ((!isset($contestant["grade"])) || ($contestant["grade"] == '')) {
                    $contestant["grade"] = -2;
                }
                if (!isset($contestant["genre"])) {
                    $contestant["genre"] = 0;
                }
                if (!isset($contestant["studentId"])) {
                    $contestant["studentId"] = "";
                }
                list($contestant["firstName"], $contestant["lastName"], $saniValid, $trash) =
                    DataSanitizer::formatUserNames($contestant["firstName"], $contestant["lastName"]);
                $stmt = $this->db->prepare("
                 INSERT INTO `contestant` (`ID`, `lastName`, `firstName`, `genre`, `grade`, `studentId`, `teamID`, `cached_schoolID`, `saniValid`, `email`, `zipCode`)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute(array(getRandomID(), $contestant["lastName"], $contestant["firstName"], $contestant["genre"], $contestant["grade"], $contestant["studentId"], $teamID, $_SESSION["schoolID"], $saniValid, $contestant["email"], $contestant["zipCode"]));
            }
        }
        addBackendHint(sprintf("ClientIP.createTeam:%s", $_SESSION['isPublic'] ? 'public' : 'private'));
        addBackendHint(sprintf("Group(%s):createTeam", escapeHttpValue($groupID)));
        exitWithJson((object)array("success" => true, "teamID" => $teamID, "password" => $password));
    }



    private function getGroupForSubContest($oldGroupID, $newContestID)
    {
        $query = "SELECT `group`.`ID`, `group`.`code` FROM `group` JOIN `group` `oldGroup` ON (`group`.`grade` = `oldGroup`.`grade` AND `group`.`schoolID` = `oldGroup`.`schoolID` AND `group`.`userID` = `oldGroup`.`userID`) WHERE `oldGroup`.ID = :oldGroupID AND `group`.`contestID` = :newContestID AND `group`.`parentGroupID` = :oldGroupID";
        $stmt = $this->db->prepare($query);
        $stmt->execute(array("oldGroupID" => $oldGroupID, "newContestID" => $newContestID));
        $row = $stmt->fetchObject();
        if ($row) {
            return array("code" => $row->code, "ID" => $row->ID);
        } else {
            $groupCode = genAccessCode($this->db);
            $groupPassword = genAccessCode($this->db);
            $groupID = getRandomID();
            $query = "INSERT INTO `group` (`ID`, `name`, `contestID`, `schoolID`, `userID`, `grade`, `expectedStartTime`, `startTime`, `code`, `password`, `isGenerated`, nbStudents, nbStudentsEffective, nbTeamsEffective, parentGroupID, language, minCategory, maxCategory) " .
                "SELECT :groupID, LEFT(CONCAT(IF(`oldGroup`.`isGenerated`, CONCAT(CONCAT('Indiv ', `oldGroup`.`grade`), ' '), CONCAT(`oldGroup`.`name`, '/')), `contest`.`name`), 50), :contestID, `oldGroup`.`schoolID`, `oldGroup`.`userID`, `oldGroup`.`grade`, NOW(), NOW(), :groupCode, :password, 1, 0, 0, 0, :oldGroupID, `contest`.`language`, `contest`.`categoryColor`, `contest`.`categoryColor` FROM `contest`, `group` `oldGroup` WHERE `contest`.`ID` = :contestID AND `oldGroup`.`ID` = :oldGroupID";
            $stmt = $this->db->prepare($query);
            $stmt->execute(array(
                "contestID" => $newContestID,
                "oldGroupID" => $oldGroupID,
                "groupID" => $groupID,
                "groupCode" => $groupCode,
                "password" => $groupPassword
            ));
            return array("code" => $groupCode, "ID" => $groupID);
        }
    }
}
