<?php

class AuthController extends Controller
{



    public function checkRegistration($request)
    {
        $code = $request["code"];
        $registrationData = $this->getRegistrationData($code);
        if (!$registrationData) {
            exitWithJson((object)array("success" => false));
        } else {
            $registrationData->success = true;
            exitWithJson($registrationData);
        }
    }


    public function checkPassword($request)
    {
        global $config;

        addFailureBackendHint("ClientIP.checkPassword:fail");
        addFailureBackendHint("ClientIP.error");

        // Check common.js version
        $commonJsVersion = isset($request['commonJsVersion']) ? intval($request['commonJsVersion']) : 0;
        $commonJsTimestamp = isset($request['commonJsTimestamp']) ? $request['commonJsTimestamp'] : '[none]';
        $timestamp = isset($request['timestamp']) ? $request['timestamp'] : '[none]';

        if ($commonJsVersion < $config->minimumCommonJsVersion) {
            // Reject old common.js versions
            $errormsg = "Mauvaise version de common.js : client " . $commonJsVersion . " < minimum " . $config->minimumCommonJsVersion . " (server timestamp " . $timestamp . ", common.js loaded at " . $commonJsTimestamp . ").";

            // Log error
            $stmt = $this->db->prepare('insert into error_log (date, message) values (UTC_TIMESTAMP(), :errormsg);');
            $stmt->execute(['errormsg' => $errormsg]);
            unset($stmt);

            // Send back error message to user
            $userMsg = "Vous utilisez une ancienne version de l'interface, veuillez rafraîchir la page. Si cela ne règle pas le problème, essayez de vider votre cache.";
            if (isset($config->contestBackupURL)) {
                $userMsg .= " Vous pouvez aussi essayer le domaine alternatif du concours http://" . $config->contestBackupURL . ".";
            }
            exitWithJsonFailure($userMsg);
        }

        if (!isset($request["password"])) {
            exitWithJsonFailure("Mot de passe manquant");
        }
        $getTeams = array_key_exists('getTeams', $request) ? $request["getTeams"] : False;
        $password = strtolower($request["password"]);
        // Search for a group matching the entered password, and if found create
        // a team in that group (and end the request).
        $this->handleCheckGroupPassword($password, $getTeams);


        $this->handleGroupFromRegistrationCode($password, $request);

        // If no matching group was found, look for a team with the entered password.
        $this->handleCheckTeamPassword($password);
    }





    private function handleCheckGroupPassword($password, $getTeams, $extraMessage = "", $registrationData = null, $isOfficialContest = false)
    {
        global $allCategories, $config;

        // Find a group whose code matches the given password.
        $query = "
            SELECT
                `group`.`ID`,
                `group`.`name`,
                `group`.`bRecovered`,
                `group`.`contestID`,
                `group`.`isPublic`,
                `group`.`schoolID`,
                `group`.`startTime`,
                TIMESTAMPDIFF(MINUTE, `group`.`startTime`, UTC_TIMESTAMP()) as `nbMinutesElapsed`,
                `contest`.`nbMinutes`,
                `contest`.`bonusScore`,
                `contest`.`allowTeamsOfTwo`,
                `contest`.`askParticipationCode`,
                `contest`.`newInterface`,
                `contest`.`customIntro`,
                `contest`.`fullFeedback`,
                `contest`.`nextQuestionAuto`,
                `contest`.`folder`,
                `contest`.`nbUnlockedTasksInitial`,
                `contest`.`subsetsSize`,
                `contest`.`open`,
                `contest`.`showSolutions`,
                `contest`.`visibility`,
                `contest`.`askEmail`,
                `contest`.`askZip`,
                `contest`.`askGenre`,
                `contest`.`askGrade`,
                `contest`.`askStudentId`,
                `contest`.`name` as `contestName`,
                `contest`.`allowPauses`,
                `group`.`isGenerated`,
                `group`.`language`,
                `group`.`minCategory`,
                `group`.`maxCategory`
            FROM
                `group`
            JOIN
                `contest` ON (`group`.`contestID` = `contest`.`ID`)
            WHERE
                `code` = ?";
        $stmt = $this->db->prepare($query);
        $stmt->execute(array($password));
        $row = $stmt->fetchObject();
        if (!$row) {
            // No such group.
            return;
        }
        if (($row->open != "Open") && ($row->schoolID != 9999999999)) { // temporary hack to allow test groups
            $messages = array(
                "fr" => "Le concours de ce groupe n'est pas ouvert.",
                "en" => "The contest associated with this group is not open",
                "ar" => "المسابقة لم تبدأ بعد"
            );
            $lang = (isset($_SESSION['language']) ? $_SESSION['language'] : $config->defaultLanguage);
            if (isset($messages[$lang])) {
                $message = $messages[$lang];
            } else {
                $message = $messages["en"];
            }
            exitWithJson((object)array("success" => false, "message" => $message));
        }
        $groupID = $row->ID;
        $schoolID = $row->schoolID;
        $isPublic = intval($row->isPublic);
        $isGenerated = intval($row->isGenerated);

        if ($row->startTime === null) {
            $nbMinutesElapsed = 0;
        } else {
            $nbMinutesElapsed = intval($row->nbMinutesElapsed);
        }
        if ($getTeams === "true") {
            $teams = $this->getGroupTeams($groupID);
        } else {
            $teams = "";
        }
        if (isset($_SESSION['mysqlOnly'])) {
            unset($_SESSION['mysqlOnly']);
        }
        $_SESSION["groupID"] = $groupID;
        $_SESSION["groupCode"] = $password;
        $_SESSION["schoolID"] = $schoolID;
        $_SESSION["nbMinutes"] = intval($row->nbMinutes);
        $_SESSION["isPublic"] = intval($row->isPublic);
        $_SESSION["language"] = $row->language;
        $_SESSION["groupClosed"] = (($nbMinutesElapsed > 60) && !$isPublic && !$isGenerated);

        updateSessionWithContestInfos($row);

        $query = "
            SELECT
                contest.ID as contestID,
                contest.folder,
                contest.name,
                contest.language,
                contest.categoryColor,
                contest.customIntro,
                contest.imageURL,
                contest.description,
                contest.allowTeamsOfTwo,
                contest.askParticipationCode
            FROM
                contest
            WHERE
                parentContestID = :contestID";
        $stmt = $this->db->prepare($query);
        $stmt->execute(array("contestID" => $row->contestID));
        $childrenContests = array();
        $hadChildrenContests = false;
        while ($rowChild = $stmt->fetchObject()) {
            $hadChildrenContests = true;
            $discardCategory = false;
            if ($isOfficialContest) {
                foreach ($allCategories as $category) {
                    if ($rowChild->categoryColor == $category) {
                        break;
                    }
                    if ($registrationData->qualifiedCategory == $category) { // the contest's category is higher than the user's qualified category
                        $discardCategory = true;
                    }
                }
                foreach ($registrationData->participations as $participation) {
                    if (($participation->parentContestID == $row->contestID) && ($participation->contestCategory == $rowChild->categoryColor)) {
                        if ($participation->startTime == null) {
                            $query = "DELETE contestant.* FROM `team` JOIN contestant ON team.ID = contestant.teamID WHERE team.ID = :teamID AND startTime IS NULL";
                            $stmt2 = $this->db->prepare($query);
                            $stmt2->execute(array("teamID" => $participation->teamID));
                            $query = "DELETE FROM `team` WHERE ID = :teamID AND startTime IS NULL";
                            $stmt2 = $this->db->prepare($query);
                            $stmt2->execute(array("teamID" => $participation->teamID));
                        } else if ($participation->remainingSeconds < (20 * $participation->nbMinutes)) {
                            $discardCategory = true;
                        }
                        break;
                    }
                }
            }
            if (!$discardCategory) {
                $childrenContests[] = $rowChild;
            }
        };
        $allContestsDone = ((count($childrenContests) == 0) && $hadChildrenContests);

        addBackendHint("ClientIP.checkPassword:pass");
        addBackendHint(sprintf("Group(%s):checkPassword", escapeHttpValue($groupID)));
        exitWithJson((object)array(
            "success" => true,
            "groupID" => $_SESSION["groupID"],
            "nbMinutes" => $_SESSION["nbMinutes"],
            "contestID" => $_SESSION["contestID"],
            "contestName" => $_SESSION["contestName"],
            "contestFolder" => $_SESSION["contestFolder"],
            "contestOpen" => $_SESSION["contestOpen"],
            "contestShowSolutions" => $_SESSION["contestShowSolutions"],
            "contestVisibility" => $_SESSION["contestVisibility"],
            "bonusScore" => $_SESSION["bonusScore"],
            "allowTeamsOfTwo" => $_SESSION["allowTeamsOfTwo"],
            "askParticipationCode" => $_SESSION["askParticipationCode"],
            "newInterface" => $_SESSION["newInterface"],
            "nextQuestionAuto" => $_SESSION["nextQuestionAuto"],
            "customIntro" => $_SESSION["customIntro"],
            "fullFeedback" => $_SESSION["fullFeedback"],
            "nbUnlockedTasksInitial" => $_SESSION["nbUnlockedTasksInitial"],
            "subsetsSize" => $_SESSION["subsetsSize"],
            "name" => $row->name,
            "teams" => $teams,
            'bRecovered' => $row->bRecovered,
            "nbMinutesElapsed" => $nbMinutesElapsed,
            "askEmail" => !!intval($row->askEmail),
            "askZip" => !!intval($row->askZip),
            "askGenre" => !!intval($row->askGenre),
            "askGrade" => !!intval($row->askGrade),
            "askStudentId" => !!intval($row->askStudentId),
            "extraMessage" => $extraMessage,
            "isPublic" => $isPublic,
            "isGenerated" => $isGenerated,
            "minCategory" => $_SESSION["minCategory"],
            "maxCategory" => $_SESSION["maxCategory"],
            "language" => $_SESSION["language"],
            "childrenContests" => $childrenContests,
            "registrationData" => $registrationData,
            "isOfficialContest" => $isOfficialContest,
            "allContestsDone" => $allContestsDone
        ));
    }



    private function handleGroupFromRegistrationCode($code, $request)
    {
        global $config;
        $registrationData = $this->getRegistrationData($code);
        if (!$registrationData) {
            return;
        }
        $newCategories = updateRegisteredUserCategory($this->db, $registrationData->ID, $registrationData->qualifiedCategory, $registrationData->validatedCategory);
        $registrationData->qualifiedCategory = $newCategories["qualifiedCategory"];
        $registrationData->validatedCategory = $newCategories["validatedCategory"];


        $query = "
            SELECT
                IFNULL(tmp.score, 0) as score,
                tmp.sumScores,
                tmp.password,
                tmp.startTime,
                tmp.contestName,
                tmp.contestID,
                tmp.parentContestID,
                tmp.contestCategory,
                tmp.nbMinutes,
                tmp.remainingSeconds,
                tmp.teamID,
                GROUP_CONCAT(CONCAT(CONCAT(contestant.firstName, ' '), contestant.lastName)) as contestants,
                tmp.rank,
                tmp.schoolRank,
                count(*) as nbContestants
            FROM
                (
                    SELECT
                        team.ID as teamID,
                        team.score,
                        SUM(team_question.ffScore) as sumScores,
                        contestant.rank,
                        contestant.schoolRank,
                        team.password,
                        team.startTime,
                        contest.ID as contestID,
                        contest.parentContestID,
                        contest.name as contestName,
                        contest.categoryColor as contestCategory,
                        team.nbMinutes,
                        (team.`nbMinutes` * 60) - TIME_TO_SEC(TIMEDIFF(UTC_TIMESTAMP(), `team`.`startTime`)) as remainingSeconds
                    FROM
                        `contestant`
                    JOIN team ON `contestant`.teamID = `team`.ID
                    JOIN `group` ON team.groupID = `group`.ID
                    JOIN `contest` ON `group`.contestID = `contest`.ID
                    LEFT JOIN `team_question` ON team_question.teamID = team.ID
                    WHERE
                        contestant.registrationID = :registrationID
                    GROUP BY team.ID
                ) tmp
            JOIN contestant ON tmp.teamID = contestant.teamID
            GROUP BY tmp.teamID
            ORDER BY tmp.startTime ASC
        ";
        $stmt = $this->db->prepare($query);
        $stmt->execute(array("registrationID" => $registrationData->ID));
        $participations = array();
        while ($row = $stmt->fetchObject()) {
            $participations[] = $row;
        }
        $registrationData->participations = $participations;

        addBackendHint("ClientIP.checkPassword:pass");
        addBackendHint(sprintf("Group(%s):checkPassword", escapeHttpValue($registrationData->ID))); // TODO : check hint
        $contestID = $config->trainingContestID;
        $isOfficialContest = false;
        if (isset($request["startOfficial"])) {
            $contestID = "337033997884044050"; // hard-coded real contest
            $isOfficialContest = true;
        }
        if (isset($_SERVER['HTTP_HOST']) && ($_SERVER['HTTP_HOST'] == "chticode.algorea.org")) {
            $contestID = "100001";
        }

        $query = "SELECT `code` FROM `group` WHERE `contestID` = :contestID AND `schoolID` = :schoolID AND `userID` = :userID AND `grade` = :grade AND isGenerated = 1";
        $stmt = $this->db->prepare($query);
        $stmt->execute(array("contestID" => $contestID, "schoolID" => $registrationData->schoolID, "userID" => $registrationData->userID, "grade" => $registrationData->grade));
        $rowGroup = $stmt->fetchObject();
        if (!$rowGroup) {
            $groupData = $this->createGroupForContestAndRegistrationCode($code, $contestID);
            $groupCode = $groupData["code"];
        } else {
            $groupCode = $rowGroup->code;
        }
        $this->handleCheckGroupPassword($groupCode, false, "", $registrationData, $isOfficialContest);
    }


    private function handleCheckTeamPassword($password)
    {
        $result = commonLoginTeam($this->db, $password);
        if ($result->success) {
            addBackendHint("ClientIP.checkPassword:pass");
            addBackendHint(sprintf("Team(%s):checkPassword", escapeHttpValue($result->teamID)));
        }
        exitWithJson($result);
    }


    private function createGroupForContestAndRegistrationCode($code, $contestID)
    {
        $groupCode = genAccessCode($this->db);
        $groupPassword = genAccessCode($this->db);
        $groupID = getRandomID();
        $query = "
            INSERT INTO
                `group`
                (
                    `ID`,
                    `name`,
                    `contestID`,
                    `schoolID`,
                    `userID`,
                    `grade`,
                    `expectedStartTime`,
                    `startTime`,
                    `code`,
                    `password`,
                    `isGenerated`,
                    nbStudents,
                    nbStudentsEffective,
                    nbTeamsEffective
                )
                SELECT
                    :groupID,
                    LEFT(CONCAT(CONCAT(CONCAT('Indiv', `algorea_registration`.`grade`), ' '), `contest`.`name`), 50),
                    :contestID,
                    `algorea_registration`.`schoolID`,
                    `algorea_registration`.`userID`,
                    `algorea_registration`.`grade`,
                    NOW(),
                    NOW(),
                    :groupCode,
                    :password,
                    1,
                    0,
                    0,
                    0
                FROM
                    `contest`,
                    `algorea_registration`
                WHERE
                    `contest`.`ID` = :contestID AND
                    `algorea_registration`.`code` = :code";
        $stmt = $this->db->prepare($query);
        $stmt->execute(array(
            "contestID" => $contestID,
            "code" => $code,
            "groupID" => $groupID,
            "groupCode" => $groupCode,
            "password" => $groupPassword
        ));
        return array("code" => $groupCode, "ID" => $groupID);
    }


    private function getGroupTeams($groupID)
    {
        $stmt = $this->db->prepare("
            SELECT
                `team`.`ID`,
                `contestant`.`lastName`,
                `contestant`.`firstName`
            FROM
                `contestant`
            JOIN
                `team`
            ON
                `contestant`.`teamID` = `team`.`ID`
            JOIN
                `group`
            ON
                `team`.groupID = `group`.ID
            WHERE
                `team`.`groupID` = :groupID OR
                `group`.`parentGroupID` = :groupID
        ");
        $stmt->execute(array("groupID" => $groupID));
        $teams = array();
        while ($row = $stmt->fetchObject()) {
            if (!isset($teams[$row->ID])) {
                $teams[$row->ID] = (object)array("nbContestants" => 0, "contestants" => array());
            }
            $teams[$row->ID]->nbContestants++;
            $contestant = (object)array("lastName" => htmlentities(utf8_decode($row->lastName)), "firstName" => htmlentities(utf8_decode($row->firstName)));
            $teams[$row->ID]->contestants[] = $contestant;
        }
        return $teams;
    }


    private function getRegistrationData($code)
    {
        $query = "
            SELECT
                `algorea_registration`.`ID`,
                `code`,
                `category` as `qualifiedCategory`,
                `validatedCategory`,
                `firstName`,
                `lastName`,
                `genre`,
                `grade`,
                `studentID`,
                `email`,
                `zipCode`,
                `algorea_registration`.`confirmed`,
                `guest`,
                IFNULL(`algorea_registration`.`schoolID`, 0) as `schoolID`,
                IFNULL(`algorea_registration`.  `userID`, 0) as `userID`,
                IFNULL(`school_user`.`allowContestAtHome`, 1) as `allowContestAtHome`
            FROM
                `algorea_registration`
            LEFT JOIN
                `school_user`
            ON (
                `school_user`.`schoolID` = `algorea_registration`.`schoolID` AND
                `school_user`.`userID` = `algorea_registration`.`userID`)
            WHERE
                `code` = :code
        ";
        $stmt = $this->db->prepare($query);
        $stmt->execute(array("code" => $code));
        $res = $stmt->fetchObject();
        if($res) {
            $res->original = $this->getUserOriginal($res->ID);
        }
        return $res;
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