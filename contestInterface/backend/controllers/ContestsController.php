<?php

class ContestsController extends Controller
{

    public function getData() {
        $res = array(
            'practice' => $this->getPracticeContests(),
        );
        if(isset($_SESSION['registrationID'])) {
            $res['open'] = $this->getOpenContests();
            $res['past'] = $this->getPastContests();
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
                contest.type ,
                contest.category,
                contest.hasThumbnail,
                team.score,
                DATE(team.endTime) as `date`
            FROM
                `group`
            LEFT JOIN
                contest
            ON
                contest.ID = group.contestID
            LEFT JOIN
                team
            ON
                team.contestID = contest.ID
            WHERE
                group.isPublic = 1";
        $stmt = $this->db->prepare($q);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // group by category
        $res = array();
        $grouped = array();
        for($i=0; $i<count($rows); $i++) {
            $contest = $rows[$i];
            $contest['group'] = array();
            $contest['group'][$contest['ID']] = array(
                'language' => $contest['language'],
                'score' => $contest['score'],
                'date' => $contest['date']
            );
            $grouped[$contest['ID']] = true;
            if($contest['language']) {
                for($j=0; $j<count($rows); $j++) {
                    $jID = $rows[$j]['ID'];
                    if($grouped[$jID] || $rows[$j]['category'] != $contest['category']) continue;
                    $grouped[$jID] = true;
                    $contest['group'][$jID] = array(
                        'language' => $rows[$j]['language'],
                        'score' => $rows[$j]['score'],
                        'date' => $rows[$j]['date']
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
                contest.ID,
                contest.name,
                contest.year,
                contest.type,
                contest.category,
                contest.hasThumbnail,
                (team.nbMinutes * 60) - TIME_TO_SEC(TIMEDIFF(UTC_TIMESTAMP(), team.startTime)) as `remainingSeconds`,
                team.ID as teamID
            FROM
                contest
            LEFT JOIN
                team
            ON
                team.contestID = contest.ID
            WHERE
                UTC_TIMESTAMP() BETWEEN contest.startDate AND contest.endDate";
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
                contest.hasThumbnail,
                team.score,
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
                contestant.registrationID = :registrationID";
        $stmt = $this->db->prepare($q);
        $stmt->execute(array(
            'registrationID' => $_SESSION['registrationID']
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


}