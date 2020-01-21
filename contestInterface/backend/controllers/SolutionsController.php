<?php
use Aws\S3\S3Client;

class SolutionsController extends Controller
{

    public function get($request)
    {
        $this->validate();
        $contest = $this->getContest();
        if ($config->teacherInterface->generationMode == 'local') {
            $res = $this->getLocal($contest);
        } else {
            $ieMode = (isset($request['ieMode']) && $request['ieMode'] == 'true') ? true : false;
            $res = $this->getCloud($contest, $ieMode);
        }
        addBackendHint("ClientIP.solutions:pass");
        addBackendHint(sprintf("Team(%s):solutions", escapeHttpValue($_SESSION["teamID"])));
        exitWithJson($res);
    }


    private function validate() {
        if (!isset($_SESSION["teamID"])) {
            exitWithJsonFailure('team not logged');
        }
        if (!isset($_SESSION["closed"])) {
            exitWithJsonFailure('contest is not over (solutions)!');
        }
        if (!isset($_SESSION["contestShowSolutions"]) || !intval($_SESSION["contestShowSolutions"])) {
            exitWithJsonFailure('solutions non disponibles pour ce concours');
        }
    }



    private function getContest() {
        //TODO: why we select score here?
        $query = "
            SELECT
                `contest`.`ID`,
                `contest`.`folder`,
                `team`.score
            FROM
                `team`
            LEFT JOIN
                `group`
            ON
                `team`.`groupID` = `group`.`ID`
            LEFT JOIN
                `contest`
            ON
                `group`.`contestID` = `contest`.`ID`
            WHERE
                `team`.`ID` = ?";
        $stmt = $this->db->prepare($query);
        $stmt->execute([
            $_SESSION["teamID"]
        ]);
        if (!($contest = $stmt->fetchObject())) {
            exitWithJsonFailure('contestID inconnu');
        }
    }


    private function getLocal($contest) {
        global $config;
        $solutions_path =
            __DIR__.
            $config->teacherInterface->sContestGenerationPath.
            $contest->folder.
            '/contest_'.$contest->ID.'_sols.html';
        return [
            'error' => null,
            'solutionsUrl' => null,
            'solutions' => file_get_contents($solutions_path)
        ];
    }


    private function getCloud($contest, $ieMode) {
        global $config;

        $s3Client = S3Client::factory(array(
            'credentials' => array(
                 'key'    => $config->aws->key,
                 'secret' => $config->aws->secret
             ),
            'region' => $config->aws->s3region,
            'version' => '2006-03-01'
        ));

        if ($ieMode) {
            try {
                $solutions = $s3Client->getObject(array(
                    'Bucket' => $config->aws->bucketName,
                    'Key'    => 'contests/'.$contest->folder.'/contest_'.$contest->ID.'_sols.html'
                ));
                return [
                    'solutions' => (string) $solutions['Body'],
                    'solutionsUrl' => null,
                    'error' => null
                ];
            } catch(S3Exception $e) {
                $error = $e->getMessage()."\n";
                error_log($error);
                return [
                    'solutions' => null,
                    'solutionsUrl' => null,
                    'error' => $error
                ];
            }
        }

        $cmd = $s3Client->getCommand('GetObject', [
            'Bucket' => $config->aws->bucketName,
            'Key'    => 'contests/'.$contest->folder.'/contest_'.$contest->ID.'_sols.html'
        ]);
        $s3request = $s3Client->createPresignedRequest($cmd, '+10 minutes');

        return [
            'solutionsUrl' => (string) $s3request->getUri(),
            'solutions' => null,
            'error' => null
        ];
    }

}