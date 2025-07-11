<?php
// Include this file after Phive if custom sessionhandling is desirable
class SessionManager {

    function __construct() {
        $this->sess_timeout = (int)(lic('getSessionTimeout') / 2);
        // Register this object as the session handler
        session_set_save_handler(
            array( &$this, "open" ),
            array( &$this, "close" ),
            array( &$this, "read" ),
            array( &$this, "write"),
            array( &$this, "destroy"),
            array( &$this, "gc" )
        );
    }

   function open( $save_path, $session_name ) {
    global $sess_save_path;
    $sess_save_path = $save_path;
    return true;
  }

  function close() {
    return true;
  }

    function read( $id ) {
        return (string)phMget($id);
    }

  /**
   * Start the session for a specific user extracting the session from redis
   *
   * NOTE: Currently not used, but can be helpful for debugging purposes according to Henrik /Paolo
   *
   * @param $user
   * @param bool $sid
   * @return bool
   */
  function startByUser($user, $sid = false){

    if(empty($sid)){
      if(!is_object($user) && !is_array($user))
        return false;

      $uid = is_array($user) ? $user['user_id'] : $user->getId();
      $sid = phMget(mKey($uid, 'session'));
    }


    if(!empty($sid)){
      session_id($sid);
      session_start();
      return true;
    }

    return false;
  }

    function write($id, $data) {
        if(empty($id))
            $id = session_id();

        empty($data) ? phMdel($id) : phMset($id, $data, $this->sess_timeout);
        return true;
    }

  // TODO is used? /Paolo
  function clear(){
    $this->write('', '');
  }

    function destroy( $id = '', $regenerate = true ) {
        if(empty($id))
            $id = session_id();
        phMdel($id);
        $uid = phMget("sessionuid-$id");
        phMdel(mKey($uid, 'session'));
        phMdel("sessionuid-$id");
        if($regenerate)
            session_regenerate_id();
        return true;
    }

  function gc() {
    return true;
  }

    function setUid($uid){
        $old_id = phMget(mKey($uid, 'session'));
        if(!empty($old_id))
            $this->destroy($old_id, false);

        session_regenerate_id();
        $sid = session_id();
        phMset(mKey($uid, 'session'), $sid);
        phMset("sessionuid-$sid", $uid);
        return $old_id;
    }
}
