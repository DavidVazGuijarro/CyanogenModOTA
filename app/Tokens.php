<?php
    class Token {
        var $version = '';
        var $date = '';
        var $channel = '';
        var $model = '';
        var $file = '';
        var $url = '';
        var $changelogUrl = '';
        var $md5 = '';
        var $timestamp = '';
        var $incremental = '';

        public function __construct($fileName, $dirPath, $baseUrl){
            /*
                $tokens = array(
                    1 => [CM VERSION] (ex. 10.1.x, 10.2, 11, etc.)
                    2 => [DATE OF BUILD] (ex. 20140130)
                    3 => [CHANNEL OF THE BUILD] (ex. RC, RC2, NIGHTLY, etc.)
                    4 => [MODEL] (ex. i9100, i9300, etc.)
                )
            */
            preg_match_all('/cm-([0-9\.]+-)(\d+-)?([a-zA-Z0-9]+-)?([a-zA-Z0-9]+)/', $fileName, $tokens);
            $tokens = $this->removeTrailingDashes($tokens);

            $this->version = $tokens[1];
            $this->date = $tokens[2];
            $this->channel = ( $tokens[3] > '' ? strtolower( str_replace(range(0,9), '', $tokens[3]) ) : 'stable' ); // Strip numbers
            $this->model = $tokens[4];
            $this->file = $fileName;
            $this->url = $this->getUrl($fileName, $baseUrl);
            $this->changelogUrl = $this->getChangelogUrl($this->url);
            $this->md5 = $this->getMD5($fileName,$dirPath);
            $this->timestamp = filemtime($dirPath.$fileName);
            $this->incremental = $this->getIncremental($fileName,$dirPath);
        }
        public function isValid($params){
            $ret = true;

            if ( $params['device'] == $this->model ) {
                if ( count($params['channels']) > 0 ) {
                    foreach ( $params['channels'] as $channel ) {
                        var_dump($channel);
                        if ( strtolower($channel) == $this->channel ) $ret = true;
                    }
                }
            }

            return $ret;
        }
        public function getAPILevel(){
            $ret = -1;

            if ( strpos($this->version, '10.1') !== false ) $ret = 17;
            else if ( strpos($this->version, '10.2') !== false ) $ret = 18;
            else if ( strpos($this->version, '11') !== false ) $ret = 19;

            return $ret;
        }
        /* UTILITY */
        private function removeTrailingDashes($token){
            foreach ( $token as $key => $value ) {
                $token[$key] = rtrim( $value[0], '-' );
            }
            return $token;
        }
        private function getUrl($filename, $baseUrl){
            return 'http://' . $_SERVER['SERVER_NAME'] . $baseUrl . '/_builds/' . $filename;
        }
        private function getChangelogUrl($url){
            return str_replace('.zip', '.txt', $url);
        }
        private function getMD5($fileName,$dirPath){
            return md5_file($dirPath.$fileName);
        }
        private function getIncremental($fileName,$dirPath){
            $ret = '';

            // Read ZIP file build.prop to get incremental
            $buildProp = file_get_contents('zip://'.__DIR__.'/../'.$dirPath.$fileName.'#system/build.prop');
            $buildProp = explode("\n", $buildProp);
            foreach ($buildProp as $line) {
                if ( strpos($line, 'ro.build.version.incremental') !== false ) {
                    $tmp = explode('=', $line);
                    $ret = $tmp[1];
                }
            }

            return $ret;
        }
    };