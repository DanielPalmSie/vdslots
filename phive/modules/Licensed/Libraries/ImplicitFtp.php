<?php



class ImplicitFtp
{

    private $server;
    private $username;
    private $password;
    private $port;
    private $curlhandle;

    public function __construct($server, $username, $password, $port)
    {
        $this->server = $server;
        $this->username = $username;
        $this->password = $password;
        $this->port = $port;
        $this->curlhandle = curl_init();
    }

    public function __destruct()
    {
        if (!empty($this->curlhandle)) {
            @curl_close($this->curlhandle);
        }
    }

    /**
     * @param string $remote remote path
     * @return resource a cURL handle on success, false on errors.
     */
    public function common($remote)
    {
        curl_reset($this->curlhandle);

        curl_setopt($this->curlhandle, CURLOPT_URL, 'ftps://' . $this->server . '/' . $remote);
        curl_setopt($this->curlhandle, CURLOPT_USERPWD, $this->username . ':' . $this->password);
        curl_setopt($this->curlhandle, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($this->curlhandle, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($this->curlhandle, CURLOPT_FTP_SSL, CURLFTPSSL_TRY);
        curl_setopt($this->curlhandle, CURLOPT_FTPSSLAUTH, CURLFTPAUTH_TLS);

        return $this->curlhandle;
    }

    /**
     * @param $local
     * @param $remote
     * @return bool
     *
     * upload the files to the SAFE
     */
    public function upload($local, $remote)
    {
        if ($fp = fopen($local, 'r')) {
            $this->curlhandle = self::common($remote);
            curl_setopt($this->curlhandle, CURLOPT_FTP_CREATE_MISSING_DIRS, true);
            curl_setopt($this->curlhandle, CURLOPT_UPLOAD, 1);
            curl_setopt($this->curlhandle, CURLOPT_INFILE, $fp);
            $response= curl_exec($this->curlhandle);
            if ($response === false) {
                phive()->dumpTbl('implicit-ftp_upload_error', "Upload curl error " . curl_errno ( $this->curlhandle ) . ": " . curl_error ( $this->curlhandle ) );
            }
            return $response;
        }
        return false;
    }


    /**
     * @param $remote
     *
     * remove the files when the Tamper token is closed
     */
    public function delete($remote, $extension = 'zip')
    {
        $files = $this->list($remote);
        foreach ($files as $file) {
            $pathinfo = pathinfo($remote . "/{$file['name']}");
            if (!isset($pathinfo['extension'])) {
                $this->delete($remote . "/{$file['name']}",$extension);
            } else {
                $testfile = $remote . "/" . $file['name'];
                $path_info = pathinfo($testfile);
                if ($path_info['extension'] != $extension) {
                    $this->curlhandle = self::common($testfile);
                    curl_setopt($this->curlhandle, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($this->curlhandle, CURLOPT_QUOTE, array('DELE /' . $testfile));
                    curl_exec($this->curlhandle);
                    $err = curl_error($this->curlhandle);

                }
            }
            $this->curlhandle = self::common($remote);
            curl_setopt($this->curlhandle, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($this->curlhandle, CURLOPT_QUOTE, array('RMD /' . $remote));
            curl_exec($this->curlhandle);
            $err = curl_error($this->curlhandle);

        }
        return $err;
        $this->curlhandle->close();
    }


    /**
     * TODO : this logic is not being used, should be removed if we dont need this
     *
     * @param $remote
     * @return bool
     */
    public function isThereTemp($remote)
    {
        $original = $remote;
        $files = $this->list($remote);
        foreach ($files as $file) {
            $remote .= "/{$file['name']}";
            $pathinfo = pathinfo($remote);
            if (!isset($pathinfo['extension'])) {
                $recursive = $this->isThereTemp($remote);
                if ($recursive == true) {
                    return true;
                }
            } else {
                if (strpos(basename($remote), '-temp.xml') !== false) {
                    return true;
                }
            }
            $remote = $original;
        }
        return false;
    }


    /**
     * Get file/folder raw data
     * @param string $remote
     * @return string[]
     */
    public function rawlist($remote)
    {
        if (substr($remote, -1) != '/') {
            $remote .= '/';
        }
        $this->curlhandle = self::common($remote);
        curl_setopt($this->curlhandle, CURLOPT_UPLOAD, 0);
        curl_setopt($this->curlhandle, CURLOPT_RETURNTRANSFER, 1);

        $result = curl_exec($this->curlhandle);

        if (curl_error($this->curlhandle)) {
            return false;
        } else {
            $files = explode("\n", trim($result));
            return $files;
            return $local;
        }
    }

    /**
     * Get file/folder parsed data into an array
     * @param string $remote
     * @return array[]
     */
    public function list($remote)
    {
        $this->curlhandleildren = $this->rawlist($remote);
        if (!empty($this->curlhandleildren)) {
            $items = array();
            foreach ($this->curlhandleildren as $this->curlhandleild) {
                $chunks = preg_split("/\s+/", $this->curlhandleild);
                list($item['rights'], $item['number'], $item['user'], $item['group'], $item['size'], $item['month'], $item['day'], $item['time']) = $chunks;
                array_splice($chunks, 0, 8);
                $item['name'] = trim(implode(" ", $chunks));
                $item['type'] = $chunks[0][0];


                $items[] = $item;
            }
            return $items;
        }
        return false;
    }

}
