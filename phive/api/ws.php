<?php
class Ws{
    
    /**
     * Creates the websocket endpoint
     *
     * Depending on whether or not we're looking at notifications / info only meant for a unique individual
     * we pass in a proper id (currently the php session id). The tag however is the real "channel".
     *
     * TODO refactor this so that it looks like this: wsUrl($tag, $id = '') and redo all invocations.
     *
     * @uses Ws::getPath()
     *
     * @param string $id The session id, we do NOT use the user id as it can be guessed and if guessed
     * correctly the subscription can easily be hijacked.
     * @param string $tag The tag for this communication, if $id is empty it is basically the channel name.
     *
     * @return string The WS endpoint.
     */  
    static function wsUrl($id, $tag){
        $ss   = phive()->getSetting('websockets');
        $e_ss = $ss[$ss['engine']];
        $port = empty($e_ss['ext_port']) ? $ss['ext_port'] : $e_ss['ext_port'];
        $host = $e_ss['ext_host'];

        if(empty($host) || empty($port))
            return '';

        $sub_dir = empty($e_ss['sub_dir']) ? '' : $e_ss['sub_dir'].'/';
        
        $ssl = phive()->getSetting('http_type');
        return "ws$ssl://$host:$port/$sub_dir" . self::getPath($id, $tag) . "/";
    }

    /**
     * Creates and returns the meat of the WS URL.
     *
     * @see Ws::wsUrl()
     *
     * @param string $id The session id.
     * @param string $tag The tag. 
     *
     * @return string The tag/id section of the URL.
     */    
    static function getPath($id, $tag){
        $url_arr = array();
        
        if(!empty($id)) {
            // 2018-04-02 following the PENTEST it's reported that we cannot pass in the URLs or GET params sensitive information like: userId, session_id, etc...
            // to avoid showing the PHPSESSID we use an md5 version of it that should do the trick
            $id = md5($id);
            $url_arr['id'] = "id/$id";
        }
        
        if(!empty($tag))
            $url_arr['tag'] = "tag/$tag";    
        
        return implode('/', $url_arr);
    }

    /**
     * return the unencrypted channel
     *
     * @param $secret
     * @param $id
     * @param $tag
     * @return string
     */
    static function getChannel($secret, $id, $tag) {
        return $secret . '/' . self::getPath($id, $tag) . '/';
    }

    /**
     * return the sha1 of the channel
     *
     * @param $secret
     * @param $id
     * @param $tag
     * @return string
     */
    static function getHashedChannel($secret, $id, $tag) {
      return sha1(static::getChannel($secret, $id, $tag));
    }

    /**
     * This is the core method that is responsible for sending websocket messages.
     *
     * This method is heavily dependent on a config setting in Phive.config.php which can look like this:
     * 
     * ```php
     * $this->setSetting('websockets', [
     *     'engine'   => 'nchan',
     *     'ext_port' => 9090,
     *     'logging'  => false,
     *     'nchan' => [
     *         'int_port' => 80,
     *         'ext_host' => 'nchan.loc',
     *         'int_host' => 'nchan.loc',
     *         'sub_dir'  => 'sub',
     *         'pub_dir'  => 'pub'
     *     ],
     *     'node' => [
     *         'redis_port' => 6379,
     *         'ext_host'   => 'socket.videoslots.loc',
     *         'redis_host' => 'sockets',
     *         'secret_key' => '1@tu0IOla&vxkS'
     *      ],
     *      'picolisp' => [
     *          'redis_port' => 6379,
     *          'ext_host'   => 'socket.videoslots.loc',
     *          'redis_host' => 'sockets'
     *      ]
     *  ]);
     * ```
     * 
     * As can be seen we're looking at currently 3 different possibilities:
     * - Nchan which is an nginx module.
     * - Node.js which is our own custom socket server written for node.js.
     * - PicoLisp which is our own custom socket server written in PicoLisp, this alternative is deprecated.
     *
     * Nchan exposes its own REST based HTTP api endpoints and the other two rely on the pub / sub functionality within Redis.
     *
     * @link https://wiki.videoslots.com/index.php?title=Nchan Nchan wiki entry.
     * @uses Ws::getPath()
     *
     * @param string|array $msg The message to send to the user / channel. Will be converted to JSON if array. 
     * @param string $id The session id of the user. 
     * @param string $tag The tag. 
     *
     * @return null
     */    
    static function send($msg, $id, $tag){
        $logger = phive('Logger')->getLogger('web_sockets');

        if(is_array($msg)){
            $msg = json_encode($msg);
            $content_header = 'application/json';
        } else {
            $content_header = 'text/html';
        }
        
        $ss = phive()->getSetting('websockets');
        
        $e_ss = $ss[$ss['engine']];
        
        switch($ss['engine']){
            case 'nchan':
                $path      = self::getPath($id, $tag);
                $pub_dir   = empty($e_ss['pub_dir']) ? '' : '/'.$e_ss['pub_dir'];
                $url       = "http://{$e_ss['int_host']}:{$e_ss['int_port']}$pub_dir/$path/";
                $debug_key = $ss['logging'] === true ? 'nchan' : '';              
                phive()->post($url, $msg, $content_header, '', $debug_key);              
                break;
            case 'node':
                $channel = self::getHashedChannel($e_ss['secret_key'], $id, $tag);
                $logger->debug('WS::send() node', [
                    'tag' => $tag,
                    'user_id' => uid(),
                    'session_id' => $id,
                    'session_id/channel' => "{$id}/{$tag}",
                    'hashed_channel' => $channel,
                    'message' => $msg,
                ]);

                phive('Redis')->exec('publish', [$channel, $msg], $e_ss['redis_host'], '', $e_ss['redis_port']);
                break;
            case 'picolisp':
                $logger->debug('WS::send() picolisp', [
                    'tag' => $tag,
                    'user_id' => uid(),
                    'session_id' => $id,
                    'session_id/channel' => "{$id}/{$tag}",
                    'message' => $msg,
                ]);

                phive('Redis')->exec('publish', [implode('', [$tag, $id]), $msg], $e_ss['redis_host'], 'pl-ws-', $e_ss['redis_port']);
                break;
            default:
                return false;
                break;
        }
    }
}
