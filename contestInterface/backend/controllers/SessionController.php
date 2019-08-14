<?php

class SessionController extends Controller
{

    //function handleLoadSession() {
    public function load()
    {
        global $config;
        $sid = session_id();
        // If the session is new or closed, just return the SID.
        if (!isset($_SESSION["teamID"]) || isset($_SESSION["closed"])) {
            addBackendHint("ClientIP.loadSession:new");
            exitWithJson(['success' => true, "SID" => $sid]);
        }
        // Otherwise, data from the session is also returned.
        addBackendHint("ClientIP.loadSession:found");
        addBackendHint(sprintf("SessionId(%s):loadSession", escapeHttpValue($sid)));
        $message = "Voulez-vous reprendre l'épreuve commencée ?";
        $lang = (isset($_SESSION['language']) ? $_SESSION['language'] : $config->defaultLanguage);
        if ($lang == "es") {
            $message = "¿Desea reiniciar la prueba comenzada anteriormente?";
        }
        if ($lang == "en") {
            $message = "Would you like to continue the participation that was started?";
        }
        exitWithJson(array(
            "success" => true,
            "teamID" => $_SESSION["teamID"],
            "message" => $message,
            "nbMinutes" => $_SESSION["nbMinutes"],
            "bonusScore" => $_SESSION["bonusScore"],
            "allowTeamsOfTwo" => $_SESSION["allowTeamsOfTwo"],
            "askParticipationCode" => $_SESSION["askParticipationCode"],
            "newInterface" => $_SESSION["newInterface"],
            "customIntro" => $_SESSION["customIntro"],
            "fullFeedback" => $_SESSION["fullFeedback"],
            "nbUnlockedTasksInitial" => $_SESSION["nbUnlockedTasksInitial"],
            "subsetsSize" => $_SESSION["subsetsSize"],
            "contestID" => $_SESSION["contestID"],
            "isPublic" => $_SESSION["isPublic"],
            "contestFolder" => $_SESSION["contestFolder"],
            "contestName" => $_SESSION["contestName"],
            "contestOpen" => $_SESSION["contestOpen"],
            "contestShowSolutions" => $_SESSION["contestShowSolutions"],
            "contestVisibility" => $_SESSION["contestVisibility"],
            "SID" => $sid
        ));
    }


    //function handleDestroySession() {
    public function destroy()
    {
        $sid = session_id();
        addBackendHint("ClientIP.destroySession");
        restartSession();
        exitWithJson(array("success" => true, "SID" => $sid));
    }
}
