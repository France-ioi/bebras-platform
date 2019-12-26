<?php

class ContestController extends Controller
{

    public function loadData()
    {
        global $tinyOrm, $config;
        if (!isset($_SESSION["teamID"])) {
            if (!isset($_POST["groupPassword"])) {
                exitWithJsonFailure("Mot de passe manquant");
            }
            if (!isset($_POST["teamID"])) {
                exitWithJsonFailure("Équipe manquante");
            }
            if (!isset($_SESSION["groupID"])) {
                exitWithJsonFailure("Groupe non chargé");
            }
            $password = strtolower(trim($_POST["groupPassword"]));
            reloginTeam($this->db, $password, $_POST["teamID"]);
        }
        $teamID = $_SESSION["teamID"];
        $stmt = $this->db->prepare("UPDATE `team` SET `createTime` = UTC_TIMESTAMP() WHERE `ID` = :teamID AND `createTime` IS NULL");
        $stmt->execute(array("teamID" => $teamID));

        $questionsData = getQuestions($this->db, $_SESSION["contestID"], $_SESSION["subsetsSize"], $teamID);
        $mode = null;
        if (isset($_SESSION['mysqlOnly']) && $_SESSION['mysqlOnly']) {
            $mode = 'mysql';
        }
        try {
            $results = $tinyOrm->select('team_question', array('questionID', 'answer', 'ffScore', 'score'), array('teamID' => $teamID), null, $mode);
        } catch (Aws\DynamoDb\Exception\DynamoDbException $e) {
            if (strval($e->getAwsErrorCode()) != 'ConditionalCheckFailedException') {
                error_log($e->getAwsErrorCode() . " - " . $e->getAwsErrorType());
                error_log('DynamoDB error retrieving team_questions for teamID: ' . $teamID);
            }
            $results = [];
        }
        $answers = array();
        $scores = array();
        foreach ($results as $row) {
            if (isset($row['answer'])) {
                $answers[$row['questionID']] = $row['answer'];
            }
            if (isset($row['score'])) {
                $scores[$row['questionID']] = $row['score'];
            } elseif (isset($row['ffScore'])) {
                $scores[$row['questionID']] = $row['ffScore'];
            }
        }

        addBackendHint("ClientIP.loadContestData:pass");
        addBackendHint(sprintf("Team(%s):loadContestData", escapeHttpValue($teamID)));
        exitWithJson((object)array(
            "success" => true,
            "questionsData" => $questionsData,
            'scores' => $scores,
            "answers" => $answers,
            "isTimed" => ($_SESSION["nbMinutes"] > 0),
            "teamPassword" => $_SESSION["teamPassword"]
        ));
    }



    public function close()
    {
        if (!isset($_SESSION["teamID"]) && !reconnectSession($this->db)) {
            exitWithJsonFailure("Pas de session en cours");
        }
        $teamID = $_SESSION["teamID"];
        $stmtUpdate = $this->db->prepare("UPDATE `team` SET `endTime` = UTC_TIMESTAMP() WHERE `ID` = ? AND `endTime` is NULL");
        $stmtUpdate->execute(array($teamID));
        $_SESSION["closed"] = true;
        $stmt = $this->db->prepare("SELECT `endTime` FROM `team` WHERE `ID` = ?");
        $stmt->execute(array($teamID));
        $row = $stmt->fetchObject();
        addBackendHint("ClientIP.closeContest:pass");
        addBackendHint(sprintf("Team(%s):closeContest", escapeHttpValue($teamID)));
        exitWithJson((object)array("success" => true));
    }


    public function get() {
        $q = "SELECT * FROM contest WHERE ID = ? LIMIT 1";
        $stmt = $this->db->prepare($q);
        $stmt->execute(array($_POST['ID']));
        $contest = $stmt->fetchObject();
        $_SESSION["contestID"] = $contest->ID;
        $_SESSION["nbMinutes"] = $contest->nbMinutes;
        exitWithJson(array(
            "success" => true,
            "contest" => $contest
        ));
    }

    private function readDir($path, $rel_path = '') {
        $res = array();
        $data = scandir($path);
        foreach($data as $item) {
            if($item == '.' ||
                $item == '..' ||
                $item == '.htaccess' ||
                strpos($item, '_sols.html') !== false ||
                strpos($item, '_graders.html') !== false ||
                $item == 'bebras.js') {
                continue;
            }
            $subpath = $path.'/'.$item;
            $rel_subpath = ($rel_path != '' ? $rel_path.'/' : '').$item;
            if(is_dir($subpath)) {
                $subitems = $this->readDir($subpath, $rel_subpath);
                if(count($subitems)) {
                    $res = array_merge($res, $subitems);
                }
            } else {
                $res[] = $rel_subpath;
            }
        }
        return $res;
    }


    public function getFilesList() {
        //TODO: s3 hosting, get list from there

        global $config;
        $folder = $_POST['folder'];
        if($config->contestInterface->contestLoaderVersion == '2') {
            $folder .= '.v2';
        }
        $path = dirname(__FILE__).'/../../contests/'.$folder;
        exitWithJson(array(
            "success" => true,
            "list" => $this->readDir($path, $folder)
        ));
    }
}
