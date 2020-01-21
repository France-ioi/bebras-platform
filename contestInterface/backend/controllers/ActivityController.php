<?php

//$noSessions = true;

class ActivityController extends Controller
{

    public function add($request)
    {
        if($this->validate($request)) {
            $this->handleActivity($request);
        }
    }


    private function validate($request)
    {
        if(!isset($request["teamID"]) || !isset($request["questionID"]) || !isset($request["type"])) {
            error_log("teamID, questionID or type is not set : ".json_encode($request));
            exitWithJsonFailure("Requête invalide", array('error' => 'invalid'));
        }
        return true;
    }


    private function handleActivity($request)
    {
        global $config;
        addBackendHint("ClientIP.activity:pass");
        if(!$config->contestInterface->logActivity) {
            exitWithJson([
                "success" => true,
                "ignored" => true
            ]);
        }

        $stmt = $this->db->prepare("
            INSERT INTO
                `activity`
                (teamID, questionID, type, answer, score, date)
            VALUES
                (:teamID, :questionID, :type, :answer, :score, NOW())
        ");
        $stmt->execute([
            'teamID' => $request['teamID'],
            'questionID' => $request['questionID'],
            'type' => $request['type'],
            'answer' => isset($request['answer']) ? json_encode($request['answer']) : null,
            'score' => isset($request['score']) ? $request['score'] : null
        ]);

        exitWithJson([
            "success" => true
        ]);
    }
}