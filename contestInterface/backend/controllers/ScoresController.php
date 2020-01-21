<?php
header("Content-Type: application/json");
header("Connection: close");

class ScoresController extends Controller
{

    public function add($request)
    {
        if($this->validate($request)) {
            $this->handleScores($request);
        }
    }


    private function validate($request)
    {
        if (!isset($request['scores'])) {
            echo json_encode(['status' => 'fail', 'error' => "missing scores"]);
            exit;
        }
        if (!isset($_SESSION["teamID"])) {
            echo json_encode(['status' => 'fail', 'error' => "team not logged"]);
            exit;
        }
        if (!isset($_SESSION["closed"])) {
            echo json_encode(['status' => 'fail', 'error' => "contest is not over (scores)!"]);
            exit;
        }
        $teamID = $_SESSION["teamID"];
        $query = "SELECT `contest`.`ID`, `contest`.`folder`, `group`.`participationType` FROM `team` LEFT JOIN `group` ON (`team`.`groupID` = `group`.`ID`) LEFT JOIN `contest` ON (`group`.`contestID` = `contest`.`ID`) WHERE `team`.`ID` = ?;";
        $stmt = $this->db->prepare($query);
        $stmt->execute([
            $_SESSION["teamID"]
        ]);
        if (!($row = $stmt->fetchObject())) {
            echo json_encode(['status' => 'fail', 'error' => "invalid teamID"]);
            exit;
        }
    }


    private function handleScores($request)
    {
        $teamScore = intval($_SESSION["bonusScore"]);
        foreach ($request['scores'] as $key => $score) {
            $teamScore += intval($score['score']);
        }
        // Update the team score in DB
        $query = "UPDATE `team` SET `team`.`score` = ? WHERE  `team`.`ID` = ? AND `team`.`score` IS NULL;";
        $stmt = $this->db->prepare($query);
        $stmt->execute(array(
            $teamScore,
            $_SESSION["teamID"]

        ));
        updateTeamCategories(
            $this->db,
            $_SESSION["teamID"]
        );
        echo json_encode(['status' => 'success']);
    }

}