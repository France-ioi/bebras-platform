<?php


class GroupController extends Controller
{

    public function recover($request)
    {
        //function handleRecoverGroup($this->db) {
        if (!isset($request['groupCode']) || !isset($request['groupPass'])) {
            exitWithJson((object)array("success" => false, "message" => 'Code ou mot de passe manquant'));
        }
        $stmt = $this->db->prepare("SELECT `ID`, `bRecovered`, `contestID`, `expectedStartTime`, `name`, `userID`, `gradeDetail`, `grade`, `schoolID`, `nbStudents`, `nbTeamsEffective`, `nbStudentsEffective`, `noticePrinted`, `isPublic`, `participationType`, `password`, `language`, `minCategory`, `maxCategory` FROM `group` WHERE `code` = ?");
        $stmt->execute(array($request['groupCode']));
        $row = $stmt->fetchObject();
        if (!$row || $row->password != $request['groupPass']) {
            exitWithJson((object)array("success" => false, "message" => 'Mot de passe invalide'));
        }
        if ($row->bRecovered == 1) {
            exitWithJson((object)array("success" => false, "message" => 'L\'opération n\'est possible qu\'une fois par groupe.'));
        }
        $stmtUpdate = $this->db->prepare("UPDATE `group` SET `code` = ?, `password` = ?, `bRecovered`=1 WHERE `ID` = ?;");
        $stmtUpdate->execute(array('#' . $request['groupCode'], '#' . $row->password, $row->ID));
        $groupID = getRandomID();
        $stmtInsert = $this->db->prepare("INSERT INTO `group` (`ID`, `startTime`, `bRecovered`, `contestID`, `expectedStartTime`, `name`, `userID`, `gradeDetail`, `grade`, `schoolID`, `nbStudents`, `nbTeamsEffective`, `nbStudentsEffective`, `noticePrinted`, `isPublic`, `participationType`, `password`, `code`, `language`, `minCategory`, `maxCategory`) values (:groupID, UTC_TIMESTAMP(), 1, :contestID, UTC_TIMESTAMP(), :name, :userID, :gradeDetail, :grade, :schoolID, :nbStudents, 0, 0, 0, :isPublic, :participationType, :password, :code, :language, :minCategory, :maxCategory);");
        $stmtInsert->execute(array(
            'groupID' => $groupID,
            'contestID' => $row->contestID,
            'name' => ($row->name) . '-bis',
            'userID' => $row->userID,
            'gradeDetail' => $row->gradeDetail,
            'grade' => $row->grade,
            'schoolID' => $row->schoolID,
            'nbStudents' => $row->nbStudents,
            'isPublic' => $row->isPublic,
            'participationType' => $row->participationType,
            'password' => $row->password,
            'code' => $request['groupCode'],
            'language' => $row->language,
            'minCategory' => $row->minCategory,
            'maxCategory' => $row->maxCategory
        ));
        $_SESSION["groupID"] = $groupID;
        $_SESSION["closed"] = false;
        $_SESSION["groupClosed"] = false;
        exitWithJson((object)array("success" => true));
    }


    public function loadPublicGroups()
    {
        addBackendHint("ClientIP.loadPublicGroups");
        $stmt = $this->db->prepare("SELECT `group`.`name`, `group`.`code`, `contest`.`year`, `contest`.`category`, `contest`.`level` " .
            "FROM `group` JOIN `contest` ON (`group`.`contestID` = `contest`.`ID`) WHERE `isPublic` = 1 AND `contest`.`visibility` <> 'Hidden';");
        $stmt->execute(array());
        $groups = array();
        while ($row = $stmt->fetchObject()) {
            $groups[] = $row;
        }
        exitWithJson(array("success" => true, "groups" => $groups));
    }




    public function checkConfirmationInterval($request) {
        global $config;

        $q = "
            SELECT
                g.grade as `grade`,
                g.name as `group`,
                c.name as `contest`,
                g.expectedStartTime as `expectedStartTime`
            FROM
                `group` as g
            LEFT JOIN
                `contest` as c
            ON
                g.contestID = c.ID
            WHERE
                g.code = :code AND
                g.expectedStartTime >= DATE_ADD(NOW(), INTERVAL ".$config->contestInterface->groupConfirmationInterval." DAY)
            LIMIT 1";
        $stmt = $this->db->prepare($q);
        $stmt->execute([
            "code" => $request["code"]
        ]);
        exitWithJson([
            "group" => $stmt->fetchObject()
        ]);
    }

}
