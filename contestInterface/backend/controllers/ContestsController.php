<?php

class ContestsController extends Controller
{

    public function getData() {
        $res = array(
            'contests' => array(
                'practice' => $this->getPracticeContests(),
            ),
            'results' => $this->getResults()
        );
        if(isset($_SESSION['registrationData']) && !$_SESSION['registrationData']->guest) {
            $res['contests']['open'] = $this->getOpenContests();
            $res['contests']['past'] = $this->getPastContests();
        }
        exitWithJson($res);
    }


    private function getPracticeContests() {
        $q = "
            SELECT
                contest.ID,
                contest.language,
                contest.name,
                contest.year,
                contest.type,
                contest.category,
                contest.folder,
                contest.thumbnail
            FROM
                `group`
            JOIN
                contest
            ON
                contest.ID = group.contestID
            WHERE
                group.isPublic = 1 AND
                contest.practice = 1
            ORDER BY
                year DESC";
        $stmt = $this->db->prepare($q);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        /*
        team.score,
        DATE(team.endTime) as `date`
        */

        // group by category
        $res = array();
        $grouped = array();
        for($i=0; $i<count($rows); $i++) {
            $contest = $rows[$i];
            if(isset($grouped[$contest['ID']])) continue;
            $contest['group'] = array();
            $contest['group'][] = array(
                'ID' => $contest['ID'],
                'language' => $contest['language']
            );
            $grouped[$contest['ID']] = true;
            if($contest['language']) {
                for($j=0; $j<count($rows); $j++) {
                    $jID = $rows[$j]['ID'];
                    if(isset($grouped[$jID]) || $rows[$j]['category'] != $contest['category']) continue;
                    $grouped[$jID] = true;
                    $contest['group'][] = array(
                        'ID' => $jID,
                        'language' => $rows[$j]['language']
                    );
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
                type,
                category,
                categoryColor,
                folder,
                thumbnail,
                DATE(endDate) as endDate
            FROM
                contest
            WHERE
                UTC_TIMESTAMP() BETWEEN contest.startDate AND contest.endDate AND
                contest.practice = 0";
        $stmt = $this->db->prepare($q);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $rows;
    }


    private function getPastContests() {
        $q = "
            SELECT
                contest.ID,
                contest.name,
                contest.year,
                contest.type,
                contest.category,
                contest.folder,
                contest.thumbnail,
                contest.language,
                team.score,
                DATE(team.startTime) as date,
                team.nbContestants,
                contestant.rank,
                contestant.grade
            FROM
                contestant
            JOIN
                team
            ON
                team.ID = contestant.teamID
            JOIN
                contest
            ON
                team.contestID = contest.ID
            WHERE
                contestant.registrationID = :registrationID AND
                contest.practice = 0";
        $stmt = $this->db->prepare($q);
        $stmt->execute(array(
            'registrationID' => $_SESSION['registrationData']->ID
        ));
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // rank total value
        $q = "
            SELECT
                count(*)
            FROM
                contestant
            JOIN
                team
            ON
                contestant.teamID = team.ID
            WHERE
                contestant.grade = :grade AND
                team.contestID = :contestID AND
                team.nbContestants = :nbContestants AND
                team.participationType = 'Official'";
        $stmt = $this->db->prepare($q);
        foreach($rows as $row) {
            if($row['nbContestants']) {
                $stmt->execute(array(
                    'grade' => $row['grade'],
                    'contestID' => $row['ID'],
                    'nbContestants' => $row['nbContestants']
                ));
                $row['rankTotal'] = $stmt->fetchColumn();
            }
        }
        return $rows;
    }



    function getResults() {
        $q = "
            SELECT
                IFNULL(tmp.score, 0) as score,
                tmp.sumScores,
                tmp.password,
                DATE(tmp.startTime) as date,
                tmp.contestID,
                tmp.nbMinutes,
                tmp.remainingSeconds,
                tmp.rank,
                tmp.schoolRank,
                tmp.grade,
                count(*) as nbContestants
            FROM
                (
                    SELECT
                        team.score,
                        team.contestID,
                        SUM(team_question.ffScore) as sumScores,
                        contestant.rank,
                        contestant.schoolRank,
                        team.password,
                        team.startTime,
                        team.nbMinutes,
                        team.ID as teamID,
                        (team.`nbMinutes` * 60) - TIME_TO_SEC(TIMEDIFF(UTC_TIMESTAMP(), `team`.`startTime`)) as remainingSeconds,
                        group.grade
                    FROM `contestant`
                    JOIN team ON `contestant`.teamID = `team`.ID
                    JOIN `group` ON team.groupID = `group`.ID
                    LEFT JOIN `team_question` ON team_question.teamID = team.ID
                    WHERE contestant.registrationID = :registrationID
                    GROUP BY team.ID
                ) tmp
            JOIN contestant ON tmp.teamID = contestant.teamID
            GROUP BY tmp.teamID
            ORDER BY tmp.startTime ASC
        ";
        $stmt = $this->db->prepare($q);
        $stmt->execute(array(
            "registrationID" => $_SESSION['registrationData']->ID
        ));
        $res = array();
        while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $res[$row['contestID']] = $row;
        }
        return $res;
    }
}