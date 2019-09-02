<?php

class ContestsController extends Controller
{

    public function getData() {
        $res = array(
            "contests" => array(
                "practice" => $this->getPracticeContests(),
                "open" => $this->getOpenContests(),
                "past" => $this->getPastContests()
            ),
            "results" => $this->getResults()
        );
        exitWithJson($res);
    }


    private function getPracticeContests() {
        $q = "
            SELECT
                ID,
                language,
                name,
                year,
                type,
                category
            FROM
                contest
            WHERE
                visibility = 'Visible'";
        $stmt = $this->db->prepare($q);
        $stmt->execute();
        $rows = $stmt->fetchAll();

        // group by category
        $res = array();
        $grouped = array();
        for($i=0; $i<count($rows); $i++) {
            $contest = $rows[$i];
            $contest['languages'] = array();
            $contest['languages'][$contest['ID']] = $contest['language'];
            $grouped[$contest['ID']] = true;
            if($contest['language']) {
                for($j=0; $j<count($rows); $j++) {
                    $jID = $rows[$j]['ID'];
                    if($grouped[$jID] || $rows[$j]['category'] != $contest['category']) continue;
                    $grouped[$jID] = true;
                    $contest['languages'][$jID] = $rows[$j]['language'];
                }
            }
            $res[] = $contest;
        }
        return $res;
    }


    private function getOpenContests() {
        $q = "
            SELECT
                ID,
                name,
                year,
                type
            FROM
                contest
            WHERE
                status = 'RunningContest'";
        $stmt = $this->db->prepare($q);
        $stmt->execute();
        $rows = $stmt->fetchAll();
        return $rows;
    }


    private function getPastContests() {
        $q = "
            SELECT
                ID,
                name,
                year,
                type
            FROM
                contest
            WHERE
                status = 'PastContest'";
        $stmt = $this->db->prepare($q);
        $stmt->execute();
        $rows = $stmt->fetchAll();
        return $rows;
    }


    private function getResults() {
        $q = "
            SELECT
                contestID,
                score,
                startTime as time
            FROM
                team
            WHERE
                groupID = :groupID
        ";
        $stmt = $this->db->prepare($q);
        $stmt->execute(array(
            'groupID' => $_SESSION["groupID"]
        ));
        $res = array();
        while ($row = $stmt->fetchObject()) {
            $res[$row->contestID] = $row;
        }
        return $res;
    }

}