<?php

class TimerController extends Controller
{


    public function start()
    {
        $teamID = $_SESSION["teamID"];
        $stmt = $this->db->prepare("UPDATE `team` SET `startTime` = UTC_TIMESTAMP() WHERE `ID` = :teamID AND `startTime` IS NULL");
        $stmt->execute(array("teamID" => $teamID));
        $this->updateDynamoDBStartTime($teamID);

        $remainingSeconds = $this->remainingSeconds($teamID, true);

        if ($remainingSeconds <= 0) {
            $_SESSION["closed"] = true;
        } else {
            unset($_SESSION["closed"]);
        }

        exitWithJson((object)array(
            "success" => true,
            "remainingSeconds" => $remainingSeconds,
            "ended" => ($remainingSeconds <= 0) && ($_SESSION["nbMinutes"] > 0)
        ));
    }



    public function getRemainingSeconds()
    {
        if (!isset($_SESSION["nbMinutes"]) || !isset($_SESSION['teamID'])) {
            exitWithJson((object)array("success" => false));
        }
        $teamID = $_SESSION['teamID'];
        $remainingSeconds = $this->remainingSeconds($teamID);
        addBackendHint("ClientIP.getRemainingTime:pass");
        addBackendHint(sprintf("Team(%s):getRemainingTime", escapeHttpValue($teamID)));
        exitWithJson((object)array("success" => true, 'remainingSeconds' => $remainingSeconds));
    }




    private function updateDynamoDBStartTime($teamID)
    {
        global $tinyOrm, $config;
        if ($config->db->use == 'dynamoDB' && (!isset($_SESSION["mysqlOnly"]) || !$_SESSION["mysqlOnly"])) {
            $stmt = $this->db->prepare("SELECT `startTime` FROM `team` WHERE `ID` = :teamID");
            $stmt->execute(array("teamID" => $teamID));
            $startTime = $stmt->fetchColumn();
            try {
                $tinyOrm->update('team', array('startTime' => $startTime), array('ID' => $teamID));
            } catch (Aws\DynamoDb\Exception\DynamoDbException $e) {
                error_log($e->getAwsErrorCode() . " - " . $e->getAwsErrorType());
                error_log('DynamoDB error updating team for teamID: ' . $teamID);
            }
        }
    }


    //function getRemainingSeconds($this->db, $teamID, $restartIfEnded = false) {
    private function remainingSeconds($teamID, $restartIfEnded = false)
    {
        $stmt = $this->db->prepare("SELECT (`nbMinutes` * 60) - TIME_TO_SEC(TIMEDIFF(UTC_TIMESTAMP(), `team`.`startTime`)) as `remainingSeconds`, " .
            "`endTime`, " .
            "(`nbMinutes` * 60) - TIME_TO_SEC(TIMEDIFF(`team`.`endTime`, `team`.`startTime`)) as `remainingSecondsBeforePause`, " .
            "`extraMinutes` " .
            "FROM `team` WHERE `ID` = ?");
        $stmt->execute(array($teamID));
        $row = $stmt->fetchObject();
        if (!$row) {
            return 0;
        }
        $remainingSeconds = $row->remainingSeconds;
        $pausesAdded = 0;
        $update = false;
        if ($row->endTime != null) {
            if (!$restartIfEnded) {
                return 0;
            }
            if ($_SESSION["contestShowSolutions"]) {
                return 0;
            }
            if ($remainingSeconds < 0) {
                if (!$_SESSION["allowPauses"]) {
                    return 0;
                }
            }
            $pausesAdded = 1;
            $remainingSeconds = $row->remainingSecondsBeforePause;
            $update = true;
        }
        if ($remainingSeconds < 0) {
            $remainingSeconds = 0;
        }
        if ($row->extraMinutes != null) {
            $remainingSeconds += $row->extraMinutes * 60;
            $update = true;
        }
        if ($update) {
            $stmt2 = $this->db->prepare("UPDATE `team` SET " .
                "`endTime` = NULL, " .
                "`nbMinutes` = `nbMinutes` + IFNULL(`extraMinutes`,0), " .
                "`startTime` = DATE_SUB(UTC_TIMESTAMP(), INTERVAL ((`nbMinutes` * 60) - :remainingSeconds) SECOND), " .
                "`extraMinutes` = NULL, " .
                "`nbPauses` = `nbPauses` + :pausesAdded " .
                "WHERE `ID` = :teamID");
            $stmt2->execute(array("teamID" => $teamID, "remainingSeconds" => $remainingSeconds, "pausesAdded" => $pausesAdded));
            $this->updateDynamoDBStartTime($teamID);
        }
        return $remainingSeconds;
    }
}
