<?php
/*
    todo: split aws and local
*/
use Aws\S3\S3Client;

require_once 'Path.php';
require_once 'MimeTypes.php';

class Filesystem
{

    private $publicClient;
    private $publicBucket;
    private $contestLocalDir;

    public function __construct($config)
    {
        /* Contest generation has three modes, which you can set in config.json:
         *   - "local": the contest is generated in ../../../contestInterface/contests/
         *   - "aws": the contest is generated in the buckets set in config.json
         *   - "aws+local": both
         */
        $mode = $config->teacherInterface->generationMode;
        $this->doLocal = strpos($mode, 'local') !== false;
        if ($this->doLocal) {
            $this->contestLocalDir = $this->getContestLocalDir($config);
        }
        $this->doAws = strpos($mode, 'aws') !== false;
        if ($this->doAws) {
            $this->initAws();
        }
    }

    private function getContestLocalDir($config)
    {
        if (property_exists($config->teacherInterface, 'sContestGenerationPath')) {
            return realpath(__DIR__ . '/../../' . $config->teacherInterface->sContestGenerationPath);
        } else {
            return realpath(__DIR__ . '/../../../contestInterface/contests');
        }
    }

    private function initAws()
    {
        $this->publicClient = S3Client::factory(array(
            'credentials' => array(
                'key' => $config->aws->key,
                'secret' => $config->aws->secret,
            ),
            'region' => $config->aws->s3region,
            'version' => '2006-03-01',
        ));
        $this->publicBucket = $config->aws->bucketName;
    }

    private function getZippedVersion($src)
    {
        $gzfilename = $src . '.gz';
        $fp = gzopen($gzfilename, 'w9');
        gzwrite($fp, file_get_contents($src));
        gzclose($fp);
        return $gzfilename;
    }

    /* generic bucket-scoped AWS filesystem operations */

    private function awsMkdir($path)
    {
        return $this->publicClient->putObject(array(
            'Bucket' => $this->publicBucket,
            'Key' => rtrim($path, '/') . '/',
            'Body' => "",
        ));
    }

    private function awsCopyFile($src, $dst, $adminOnly = false)
    {
        $mime_type = MimeTypes::getMimeTypeOfFilename($dst);
        $args = array(
            'Bucket' => $this->publicBucket,
            'SourceFile' => $src,
            'Key' => $dst,
            'ContentType' => $mime_type,
            'CacheControl' => 'public, max-age=86400',
        );
        $zipped = false;
        if (MimeTypes::compressMimeType($mime_type)) {
            $src = $this->getZippedVersion($src); // XXX missing error handling
            $args['SourceFile'] = $src;
            $zipped = true;
            $args['ContentEncoding'] = 'gzip';
        }
        if (!$adminOnly) {
            $args['ACL'] = 'public-read';
        }
        $result = $this->publicClient->putObject($args);
        if ($zipped) {
            unlink($src);
        }
        return !!$result;
    }

    private function awsPutContents($dst, $content, $adminOnly = false)
    {
        $src_temp = tempnam('/tmp', 'bebras-platform');
        $success = false !== file_put_contents($src_temp, $content);
        if ($success) {
            $success = $this->awsCopyFile($src_temp, $dst, $adminOnly);
        }
        unlink($src_temp);
        return $success;
    }

    /* path utilities */

    public function joinPaths($lhs, $rhs)
    {
        return rtrim($lhs, '/') . '/' . ltrim($rhs, '/');
    }

    private function makeLocalPath($path)
    {
        return $this->joinPaths($this->contestLocalDir, $path);
    }

    private function makeAwsPath($path)
    {
        return $this->joinPaths('contests', $path);
    }

    /* mode-aware contests-directory-scoped filesystem operations */

    public function myMkdir($path)
    {
        if ($this->doLocal) {
            $localPath = $this->makeLocalPath($path);
//TODO: dev
if(file_exists($localPath)) return;

            if (!mkdir($localPath, 0777, true)) {
                throw new Exception('local mkdir failed');
            }
        }
        if ($this->doAws) {
            // Creating a directory is a no-op for S3.
            /*
            if (!$this->awsMkdir($this->makeAwsPath($path))) {
            throw new Exception('AWS mkdir failed');
            }
            */
        }
    }


    public function myCopyFile($src, $dst, $adminOnly = false)
    {
        if ($this->doLocal) {
            $local_dst = $this->makeLocalPath($dst);
            $local_dir = dirname($local_dst);
            if (!file_exists($local_dir)) {
                mkdir($local_dir, 0777, true);
            }
            if (!copy($src, $local_dst)) {
                throw new Exception('local copyFile failed');
            }
        }
        if ($this->doAws) {
            if (!$this->awsCopyFile($src, $this->makeAwsPath($dst))) {
                throw new Exception('AWS copyFile failed');
            }
        }
    }


    public function myPutContents($dst, $content, $adminOnly = false)
    {
        if ($this->doLocal) {
            if (!file_put_contents($this->makeLocalPath($dst), $content)) {
                throw new Exception('local putContent failed');
            }
        }
        if ($this->doAws) {
            if (!awsPutContents($this->makeAwsPath($dst), $content, $adminOnly)) {
                throw new Exception('AWS putContent failed');
            }
        }
    }

}
