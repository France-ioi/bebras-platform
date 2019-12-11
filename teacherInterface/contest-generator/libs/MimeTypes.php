<?php
class MimeTypes {

    private static $types = null;

    private static function getMimeTypes() {
        # Returns the system MIME type mapping of extensions to MIME types, as defined in /etc/mime.types.
        $out = array();
        if (file_exists('/etc/mime.types')) {
            $file = fopen('/etc/mime.types', 'r');
            while(($line = fgets($file)) !== false) {
                $line = trim(preg_replace('/#.*/', '', $line));
                if(!$line)
                    continue;
                $parts = preg_split('/\s+/', $line);
                if(count($parts) == 1)
                    continue;
                $type = array_shift($parts);
                foreach($parts as $part)
                    $out[$part] = $type;
            }
            fclose($file);
        } else {
            $out['js'] = 'application/javascript';
            $out['png'] = 'image/png';
            $out['css'] = 'text/css';
            $out['txt'] = 'text/plain';
            $out['gif'] = 'image/gif';
            $out['jpg'] = 'image/jpeg';
            $out['svg'] = 'image/svg+xml';
            $out['mp4'] = 'video/mp4';
            $out['html'] = 'text/html';
            $out['ttf'] = 'application/octet-stream';
            $out['eot'] = 'application/octet-stream';
            $out['woff'] = 'application/octet-stream';
        }
        return $out;
    }


    public static function getMimeTypeOfFilename($filename) {
        if(self::$types === null) {
            self::$types = getMimeTypes();
        }
        $ext = pathinfo($filename, PATHINFO_EXTENSION);
        if(!$ext) {
            $ext = $filename;
        }
        $ext = strtolower($ext);
        return isset(self::$types[$ext]) ? self::$types[$ext] : null;
    }

    public static function compressMimeType($mime_type) {
        return $mime_type == 'application/javascript' ||
                $mime_type == 'text/html' ||
                $mime_type == 'text/css' ||
                $mime_type == 'text/plain';
    }

}