<?php
use UAParser\Parser;

class LogErrorController extends Controller
{

    public function add($request)
    {
        if($this->validate($request)) {
            $this->loadError($request);
        }
    }


    private function logError($request) {
        $parser = Parser::create();
        $teamID = isset($_SESSION["teamID"]) ? $_SESSION["teamID"] : null;
        $questionKey = isset($request["questionKey"]) ? $request["questionKey"] : null;
        $errormsg = $request['errormsg'];
        $browserStr = $parser->parse($_SERVER['HTTP_USER_AGENT']);
        $browserStr = $browserStr->toString();
        $stmt = $db->prepare('insert into error_log (date, teamID, message, browser, questionKey) values (UTC_TIMESTAMP(), :teamID, :errormsg, :browserStr, :questionKey);');
        $stmt->execute(['teamID' => $teamID, 'errormsg' => $errormsg, 'browserStr' => $browserStr, 'questionKey' => $questionKey]);
        echo json_encode(['success' => true]);
    }


    private function validate($request) {
        if (!isset($request['errormsg'])) {
            die(json_encode(['success' => false, 'error' => 'missing errormsg argument']));
        }
    }

}