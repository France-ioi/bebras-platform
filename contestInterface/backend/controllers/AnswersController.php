<?php
use Aws\DynamoDb\Exception;

//$noSQL = true;
//$noSessions = true;

class AnswersController extends Controller
{

    public function add($request)
    {
        if ($this->validate($request)) {
            $tinyOrm = new tinyOrm();
            $this->handleAnswers($request, $tinyOrm);
        }
    }

    private function validate($request)
    {
        if (!isset($request["answers"]) || !isset($request["teamID"]) || !isset($request["teamPassword"])) {
            error_log("answers, teamID or teamPassword is not set : " . json_encode($_REQUEST));
            exitWithJsonFailure("Requête invalide", array('error' => 'invalid'));
        }
        return true;
    }

    private function handleAnswers($request, $tinyOrm)
    {
        global $config;
        $testMode = $config->db->testMode;
        $teamID = $request["teamID"];
        $teamPassword = $request["teamPassword"];
        try {
            $rows = $tinyOrm->select('team', array('password', 'startTime', 'nbMinutes'), array('ID' => $teamID));
        } catch (Aws\DynamoDb\Exception\DynamoDbException $e) {
            error_log($e->getAwsErrorCode() . " - " . $e->getAwsErrorType());
            error_log('DynamoDB error trying to get record: teamID: ' . $teamID);
            exitWithJsonFailure($e->getMessage(), array('error' => 'DynamoDB'));
        }
        if ($testMode == false && (!count($rows) || $teamPassword != $rows[0]['password'])) {
            error_log('teamID ' . $teamID . ' sent answer with password ' . $teamPassword . (count($rows) ? ' instead of ' . $rows[0]['password'] : ' (no such team)'));
            exitWithJsonFailure("Requête invalide (password)");
        }
        $row = $rows[0];
        $answers = $request["answers"];
        $curTime = new DateTime(null, new DateTimeZone("UTC"));
        $startTime = new DateTime($row['startTime'], new DateTimeZone("UTC"));
        $nbMinutes = intval($row['nbMinutes']);
        /*
        // We leave 2 extra minutes to handle network lag. The interface already prevents trying to answer after the end.
        if ((($curTime->getTimestamp() - $startTime->getTimestamp()) > ((intval($nbMinutes) + 2) * 60)) && !$testMode && ($nbMinutes > 0)) {
        error_log("submission by team ".$teamID.
        " after the time limit of the contest! curTime : ".$curTime->format(DateTime::RFC850).
        " startTime :".$startTime->format(DateTime::RFC850).
        " nbMinutes : ".$nbMinutes);
        exitWithJsonFailure("La réponse a été envoyée après la fin de l'épreuve", array('error' => 'invalid'));
        }
         */
        $curTimeDB = new DateTime(null, new DateTimeZone("UTC"));
        $curTimeDB = $curTimeDB->format('Y-m-d H:i:s');
        $items = array();
        foreach ($answers as $questionID => $answerObj) {
            $items[] = array('teamID' => $teamID, 'questionID' => $questionID, 'answer' => $answerObj["answer"], 'ffScore' => $answerObj['score'], 'date' => $curTimeDB);
        }
        try {
            $tinyOrm->batchWrite('team_question', $items, array('teamID', 'questionID', 'answer', 'ffScore', 'date'), array('answer', 'ffScore', 'date'));
        } catch (Aws\DynamoDb\Exception\DynamoDbException $e) {
            error_log($e->getAwsErrorCode() . " - " . $e->getAwsErrorType());
            error_log('DynamoDB error trying to write records: teamID: ' . $teamID . ', answers: ' . json_encode($items) . ', items: ' . json_encode($items));
            exitWithJsonFailure($e->getAwsErrorCode(), array('error' => 'DynamoDB'));
        }
        addBackendHint("ClientIP.answer:pass");
        addBackendHint(sprintf("Team(%s):answer", escapeHttpValue($teamID)));
        exitWithJson(array("success" => true));
    }

}
