<?php
require_once __DIR__ . '/../../api/PhModule.php';
require_once __DIR__ . '/mandrill/Mandrill.php';
//require_once __DIR__ . '/../Former/Validator.php';

class Mailer extends PhModule {

  private $Mail = null;
  private $sql;

  /*
   * Adds a mail to the mailer queue
   * $to			Receivers e-mail address
   * $subject		Mail Subject
   * $message		Content of the mail
   * $priority	Mail priority: 0 - Mail is sent immediately, 1 Mail is sent based on Cron schedule
   *				If the send mail fails, sendmail will prioritize priority = 0 mails over mails that have been queued longer
   * $from		Address e-mail is sent from
   * $cc			Carbon copy, http://se2.php.net/manual/en/function.mail.php
   * $bcc			Blind carbon copy
   * $headers		Additional headers
   */
  public function queueMail($to, $replyTo, $subject, $messageHTML, $messageText, $priority = 1, $from, $cc = null, $bcc = null, $headers = null, $user_id = null) {

    if(empty($subject) || (empty($messageHTML) && empty($messageText)))
      return false;

    if($priority == 0){
      $prio_config = $this->getSetting('prio_config');
      if(!empty($prio_config)){
        $this->from_mail = $prio_config['from_email'];
      }
    }

    if(phive("SQL")->settingExists('maildb') && empty($prio_config))
      $sql = phive("SQL")->doDb('maildb');
    else
      $sql = phive('SQL');
    //$Mail['to']				= $to;
    $Mail['replyto']		= empty($this->from_mail) ? $replyTo : $this->from_mail;
    $Mail['subject']		= $subject;
    $Mail['messageHTML']	= $messageHTML;
    $Mail['messageText']	= $messageText;
    $Mail['priority']		= $priority;
    $Mail['from']		= empty($this->from_mail) ? $from : $this->from_mail;
    $Mail['cc']			= $cc;
    $Mail['bcc']		= $bcc;
    $Mail['headers']		= $headers;
    $Mail['from_name']		= empty($this->from_name) ? $this->getSetting("default_from_name") : $this->from_name;
    $Mail['to']			= $to;

    if(!empty($this->cur_user))
      $Mail['to_name']		= $this->cur_user->getFullName();

    if(empty($user_id) && !empty($this->cur_user))
      $user_id = $this->cur_user->getId();

      $sql->insertArray($this->getSetting("DB_MAILER_QUEUE"),$Mail);

      if($user_id > 0){
          /*
      $sql->insertArray('users_messages', array(
        'user_id' => $user_id,
        'recipient' => $Mail['to'],
        'subject' => $Mail['subject'],
        'body' => $Mail['messageText']
      ));
          */
    }

      return  $sql->insertBigId();
  }

  /**
   * Remove line feeds(\n) and carriage(\r) returns from a string
   * @param string $str
   * @return string
   **/
  function removeLfAndCr($str){
    return str_replace(array("\n", "\r"), '', $str);
  }

  function getBounces(){
    set_include_path('.' . PATH_SEPARATOR . '/opt/lib/' . PATH_SEPARATOR . get_include_path());
    include_once('Zend/Loader.php');
    Zend_Loader::loadClass('Zend_Mail_Storage_Pop3');
    $mails = new Zend_Mail_Storage_Pop3(
      array(
        'host'     	=> 'localhost',
        'user'     	=> phive('MailHandler2')->getSetting('DEFAULT_FROM_EMAIL'),
        'password' 	=> 'MaNYANA4Ever'));

    foreach($mails as $mid => $m){
      print_r($m);
      $mails->removeMessage($mid);
    }

  }

    /**
     * Send mails from the queue
     * $priority		Which type of mails to be sent if specified, otherwise all mail types in queue are sent
     * $limit			Number of mails from the queue to send
    INSERT INTO `videoslots`.`mailer_queue` (`mail_id`, `to`, `replyto`, `subject`, `messageHTML`, `messageText`, `priority`, `from`, `cc`, `bcc`, `headers`, `time_queued`, `attempts`, `site_id`, `from_name`, `to_name`) VALUES (NULL, 'hsarvell@gmail.com', 'vip@videoslots.com', 'Test 2', 'test 2', 'test', '1', 'vip@videoslots.com', NULL, NULL, NULL, CURRENT_TIMESTAMP, '0', '0', 'Videoslots.com', 'Henrik Sarvell');
     */
    public function sendMailQueue($priority = null, $limit = 50, $email = '') {

        if($this->getSetting('do_not_send_emails') === true)
            return;

        if(!empty($email))
            $email = str_replace(' ', '', $email);

        $ss = $this->allSettings();

        $this->sql = phive("SQL");
        $db_mailer_queue = $ss['DB_MAILER_QUEUE'];
        $attempts_limit = $ss['ATTEMPTS_LIMIT'];

        if (empty($email)) {
            $q = "SELECT * FROM `$db_mailer_queue` left join attachments on mailer_queue.mail_id = attachments.email_id " . "WHERE `attempts` < $attempts_limit ";
            if ($priority !== null) {
                $q .= "AND `priority` = $priority ";
            }
            if ($this->getSetting('IGNORE_NEWSLETTERS', true)) {
                $q .= "AND `priority` != ". MailHandler2::PRIORITY_NEWSLETTER ." ";
            }
            $q .= "ORDER BY `priority` ASC , `time_queued` ASC " . "LIMIT $limit";
        } else {
            $q = "SELECT * FROM `mailer_queue`  left join attachments on mailer_queue.mail_id = attachments.email_id WHERE `to` LIKE '$email'";
        }

        $mq = phive('SQL')->loadArray($q);

        // Archive/delete emails which failed several times?
        foreach($mq as $m){
            $this->archiveMail($m['mail_id']);
            $this->removeMailQueue($m['mail_id']);
        }

        $Message = '';

        if (count($mq) == 0)
            $Message = "Queue is empty, no mail sent.";
        else{
            foreach($mq as $m) {
                $text = $m['messageText'];
                $html = $m['messageHTML'];
                $subject = $m['subject'];
                $to = trim($m['to']);

                // Simple PHP email validation
                if(filter_var($to, FILTER_VALIDATE_EMAIL) && !empty($to)){

                    // Use existing SES (AWS client) sendEmail function
                    try {
                        $success = $this->sendEmail(
                            $to,                      // recipient_emails
                            empty($m['replyto']) ? $ss['DEFAULT_REPLY_TO'] : $m['replyto'], // reply_to
                            $subject,                 // subject
                            $this->removeLfAndCr(stripslashes($html)), // html_body
                            trim($m['from']),         // sender_email
                            trim($m['from_name']),    // sender_name
                            $m['priority'],           // priority
                            null,           // queued_at
                            null,             // user_id
                            true,              // log_it
                            $m['cc'],                 // cc_emails
                            $m['bcc']                 // bcc_emails
                        );

                        if($success) {
                            $ret = true;
                        } else {
                            $ret = false;
                        }

                    } catch(Exception $e) {
                        //TODO move the gateway name to the mail config
                        $gateway = $m['priority'] == 0 ? 'Outlook' : 'Mandrill';
                        $this->logFailedAttempt($to, "Email delivery failed using {$gateway}", 'email_attempts_failed');
                        error_log("Send mail error, exception: {$e->getMessage()}, subject: $subject");
                        $ret = false;
                    }

                }else{
                    $ret = '';
                    $this->removeMailQueue($m['mail_id']);
                }

                if ($ret === true) {
                    $Message .= "Mail sent to: " . $to . "<br />";
                    // Move mail to form queue to archive
                    $this->archiveMail($m['mail_id']);
                    $this->removeMailQueue($m['mail_id']);
                } else if ($ret === false) {
                    if ($m['attempts'] == 4) {
                        $res = $this->logFailedAttempt($to, "Email attempts block set due to exceeded limit", 'email_attempts_blocked');
                        if ($res === false) {
                            continue;
                        }
                        $this->removeMailQueue($m['mail_id']);

                    } else {
                        $Message .= "Failed to send to: " . $to .
                            " (Attempt " . $m['attempts'] . ")<br />";
                        $q = "UPDATE `$db_mailer_queue` " .
                            "SET `attempts`=`attempts`+1 " .
                            "WHERE `mail_id`=" . phive('SQL')->escape($m['mail_id']) . " " .
                            "LIMIT 1";
                        phive('SQL')->query($q);

                    }
                }
            }
        }
        return new PhMessage(PHM_OK, $Message);
    }

  public function logFailedAttempt($to, $message, $tag)
  {
      /** @var DBUser $u */
      $u = phive('UserHandler')->getUserByAttr('email', $to);
      if(empty($u)) {
          return false;
      }
      if ($tag == 'email_attempts_blocked') {
          $u->setAttr('email_attempts_blocked', 1);
      }
      phive('UserHandler')->logAction($u, $message, $tag);
      return true;
  }

  public function archiveMail($mail_id) {
    $sql = phive("SQL");
    $mail = $sql->loadAssoc("SELECT * FROM mailer_queue WHERE mail_id = '$mail_id'");
    $mail['time_sent'] = phive()->hisNow();
    unset($mail['mail_id']);
    if($sql->insertArray('mailer_log', $mail))
      return $this->removeMailQueue($mail_id);
    return false;
  }

  public function removeMailQueue($mail_id) {
    $sql = phive("SQL");
    // Remove the data from the queue table
    $q =	"DELETE FROM " . $this->getSetting("DB_MAILER_QUEUE") . " " .
        "WHERE `mail_id`=" . $sql->escape($mail_id) . " " .
        "LIMIT 1";

    $sql->query($q);
    return true;
  }

  public function getMail($mail_id) {
    $this->sql			= phive("SQL");
    $db_mailer_queue	= $this->getSetting('DB_MAILER_QUEUE');

    $q =	"SELECT * FROM `$db_mailer_queue` " .
        "WHERE `mail_id` = " . $this->sql->escape($mail_id) . " " .
        "LIMIT 1";
    $this->sql->query($q);
    $m = $this->sql->fetchArray();
    return $m;
  }

}
