<?php

require_once __DIR__ . '/../../api/PhModule.php';
require_once __DIR__ . '/../Mailer/Mailer.php';

class MailHandler2 extends Mailer {

  const PRIORITY_NEWSLETTER = 3;
  const TRANSACTIONAL_TYPE  = 'transactional';

  const MAIL_SENT_LOG_TBL = 'mail_last_sent_log';

    private $skip_consent = false;
    private $bcc_limiter = [];

    public function phAliases(){
    return array('MailHandler');
  }

    public function __construct($lang = null) {
        if($this->getSetting("ENABLE_LANG")){
            $this->lang = $lang !== null ? $lang : $this->getSetting("DEFAULT_LANG");
        }

        $this->deposit_statuses = "'approved','pending'";
    }

    function getDepositSums(){
        $res = phive('SQL')->shs()
            ->loadArray("SELECT SUM(amount) AS amount, user_id
                        FROM deposits
                            LEFT JOIN users u ON u.id = deposits.user_id
                            WHERE u.active = 1  AND status IN('approved','pending') GROUP BY user_id");
        foreach($res as &$tr){
            $u_obj = cu($tr['user_id']);
            if(!empty($u_obj)){
                $old_ids = $u_obj->getPreviousCurrencyUserIds();
                foreach ($old_ids as $old_id) {
                    $old_sum = phive('Cashier')->getUserDepositSum($old_id, $this->deposit_statuses);
                    if(!empty($old_sum)){
                        $old_u_obj = cu($old_id);
                        if(!empty($old_u_obj)){
                            $tr['amount'] += chg($old_u_obj, $u_obj, $old_sum, 1);
                        }
                    }
                }
            }
        }
        return $res;
    }

    function getUserDeposits($ud){
        $current_deposits = phive('Cashier')->getUserDeposits($ud['id']);

        $u_obj = cu($ud);
        if(!empty($u_obj)){
            $old_ids = $u_obj->getPreviousCurrencyUserIds();
            foreach ($old_ids as $old_id) {
                $old_deposits = phive('Cashier')->getUserDeposits($old_id);

                // NOTE that as the current logic stands there is no need for FX here.
                $current_deposits = array_merge((array)$old_deposits, $current_deposits);
            }
        }

        return $current_deposits;
    }

    function getUserDepositSum($ud){
        $current_sum = phive('Cashier')->getUserDepositSum($ud['id'], $this->deposit_statuses);

        $u_obj = cu($ud);
        if(!empty($u_obj)){
            $old_ids = $u_obj->getPreviousCurrencyUserIds();
            foreach ($old_ids as $old_id) {
                $old_sum = phive('Cashier')->getUserDepositSum($old_id, $this->deposit_statuses);
                if(!empty($old_sum)){
                    $old_u_obj = cu($old_id);
                    if(!empty($old_u_obj)){
                        $current_sum += chg($old_u_obj, $u_obj, $old_sum, 1);
                    }
                }
            }
        }

        return $current_sum;
    }

    /**
     * Get the unsub html link
     *
     * @param string $email
     * @param string|null $site
     * @param string|null $channel
     * @param string|null $trigger
     * @return string
     */
  function getUnsubExtra(string $email, string $site = null, string $trigger = null)
  {
      /** @var PrivacyHandler $ph */
      $ph = phive('DBUserHandler/PrivacyHandler');

      /** @var DBUserHandler $uh */
      $uh = phive('DBUserHandler');

      if ($trigger) $settings = $ph->getTriggerSettings($ph::CHANNEL_EMAIL, $trigger);
      $unsub_key = (empty($settings)) ? $ph::CHANNEL_EMAIL : $ph->parseSettingArray($settings);

      $domain = (empty($site))
          ? rtrim($uh->getSiteUrl($this->cur_user->data['country']), '/')
          : 'http'.phive()->getSetting('http_type').'://www.'.$site.'.com';

      $url = "{$domain}/unsubscribe?e=" . md5($email) . "&t={$unsub_key}";
      return "<br><br>Unsubscribe <a href=\"$url\">here</a>";
  }

  function unsubscribe($md5_email, $trigger)
  {
      /** @var SQL $sql */
      $sql = phive('SQL');

      /** @var PrivacyHandler $ph */
      $ph = phive('DBUserHandler/PrivacyHandler');

      $ud = $sql->shs()->loadAssoc("SELECT * FROM users WHERE MD5( email ) = '$md5_email'");
      if (empty($ud)) return false;

      $user = cu($ud['id']);
      if (empty($user)) return false;

      if (strtolower($trigger) === $ph::CHANNEL_EMAIL) {
          foreach ($ph->getAllConsentOptions($user) as $settings) {
              if ($settings[$ph::CHANNEL] === $ph::CHANNEL_EMAIL) {
                  $ph->setPrivacySetting($user, $settings, false);
              }
          }

          phive('UserHandler')->logAction(
              $ud['id'],
              $ud['username'] . " unsubscribed from all emails using the unsubscribe link",
              'generic-consent'
          );
      } else {
          $settings = $ph->parseSettingString($trigger);
          if (empty($settings)) return false;

          $ph->setPrivacySetting($user, $settings, false);

          phive('UserHandler')->logAction(
              $ud['id'],
              $ud['username'] . " unsubscribed from {$trigger} using the unsubscribe link",
              'generic-consent'
          );
      }

      return $ud;
  }

  protected $lang;

  public function setupTempFolder(){
    if (!opendir(__DIR__."/../../tmp")){
      if(!mkdir(__DIR__."/../../tmp"))
        return new PhMessage(PHM_ERROR,"Couldn't create tmp folder");
      chmod(__DIR__."/../../tmp",0777);

    }
    if(!opendir(__DIR__."/../../tmp/mail_querys")){
      if(!mkdir(__DIR__."/../../tmp/mail_querys"))
        return new PhMessage(PHM_ERROR,"Couldn't create tmp/mail_querys folder");
      chmod(__DIR__."/../../tmp/mail_querys",0777);
    }
    return new PhMessage(PHM_OK,"Successfully created folders");
  }

    /**
     * Add email in the queue
     * @param $mail_trigger
     * @param $email
     * @param null $replacers
     * @param null $lang
     * @param null $from
     * @param null $reply_to
     * @param null $bcc
     * @param null $cc
     * @param int $priority
     * @param string $extra
     * @return bool
     */
    public function sendMailToEmail($mail_trigger, $email, $replacers = null, $lang = null, $from = null, $reply_to = null, $bcc = null, $cc = null, $priority = 1, $extra = '')
    {
        $to = $email;
        if ($lang === null) {
            $lang = phive('Localizer')->getDefaultLanguage();
        }

        if ($from === null) {
            $from = $this->getSetting("DEFAULT_FROM_EMAIL");
        }

        if ($reply_to === null) {
            $reply_to = $this->getSetting("DEFAULT_REPLY_TO");
        }

        if (is_array($bcc)) {
            $bcc = implode(",", $bcc);
        }

        if (is_array($to)) {
            $to = implode(",", $to);
        }

        if (is_array($cc)) {
            $cc = implode(",", $cc);
        }

        if ($this->getSetting("ENABLE_LANG") && !$this->getSetting("LOCALIZE")) {
            $select_lang = " AND lang = " . phive("SQL")->escape($lang);
        }

        $mail = is_string($mail_trigger) ? $this->getMail($mail_trigger, $lang) : $mail_trigger;

        if (empty($mail) || empty($mail['content']) || empty($mail['subject'])) {
            return false;
        }

        $content = $this->replaceKeywords($mail['content'] . $extra, $replacers);
        $subject = $this->replaceKeywords($mail['subject'], $replacers);

        $subject_alias = "mail.".$mail_trigger.".subject";
        $content_alias = "mail.".$mail_trigger.".content";

        if ($subject === "($subject_alias)" || $content === "($content_alias)") {
            return false;
        }

        $mail_fields = [$content, $subject];
        foreach ($mail_fields  as $field) {
            $trimmedValue = trim($field);
            if (empty($trimmedValue) || $trimmedValue === '.') {
                return false;
            }
        }

        /** @var PrivacyHandler $ph */
        $ph = phive('DBUserHandler/PrivacyHandler');

        /** @var UserHandler $uh */
        $uh = phive('UserHandler');

        $settings = $ph->getTriggerSettings($ph::CHANNEL_EMAIL, $mail_trigger);
        $key = $ph->parseSettingArray($settings);

        if ($this->unsubscribe_link && !empty($key)) $content .= $this->getUnsubExtra($to, null, $mail_trigger);

        if (!$ph->canReceiveTrigger($uh->getUserByEmail($to), $ph::CHANNEL_EMAIL, $mail_trigger)) {
            return false;
        }

        //update email count using trigger as keyword
        $email_count_for_trigger = phMgetArr("email-count-{$mail_trigger}") ?? 0;
        phMset("email-count-{$mail_trigger}", $email_count_for_trigger + 1);

        return $this->queueMail($to, $reply_to, $subject, strtr($content, array("€" => "&euro;")), html_entity_decode(strip_tags($content), ENT_QUOTES, "UTF-8"), $priority, $from, $cc, $bcc);
    }


    public function sendMail($mail_trigger, $user, $replacers = null,$lang = null, $from = null,$reply_to = null,$bcc = null,$cc = null,$priority=1)
    {
        if (empty($mail_trigger)) return false;
        if (!is_object($user)) return false;

        // If mail is sent with highest priority we don't check for a block because use must get reset password mail for instance.
        if($user->isBlocked() && $priority !== 0){
            phive('UserHandler')->logAction($user->getId(), "Didn't get mail with trigger $mail_trigger because inactive.", 'email');
            return false;
        }

        if (!$this->skip_consent && !phive('UserHandler')->canSendTo($user, $mail_trigger)) {
            phive('UserHandler')->logAction($user->getId(), "Didn't get mail with trigger $mail_trigger because of privacy settings.", 'email');
            return false;
        }

        $this->cur_user = $user;

        $email = $user->getAttribute("email");

        if(empty($email))
            return false;

        if (empty($lang)) {
            $lang = $user->getAttribute("preferred_lang");
            if (empty($lang)) {
                $lang = phive('Localizer')->getLanguage();
            }
            if (empty($lang)) {
                phive( 'Localizer' )->getDefaultLanguage();
            }
        }

        if($priority == self::PRIORITY_NEWSLETTER && !empty($bcc)) {
            $key = $mail_trigger.'_'.$lang;
            if(!isset($this->bcc_limiter[$key])) {
                $this->bcc_limiter[$key] = true;
            } else {
                $bcc = null;
            }
        }

        if($replacers === null)
            $replacers = $this->getDefaultReplacers($user);

        $sent = $this->sendMailToEmail($mail_trigger,$email,$replacers,$lang,$from,$reply_to,$bcc,$cc,$priority);

        // Log mail
        $this->logMailSent($user, $mail_trigger, !empty($sent));

        try {
            $callback = $this->mailSentCallback($user, $mail_trigger);
            if ($sent && !empty($callback) && is_callable($callback)) {
                $callback();
            }
        } catch (\Exception $e) {
            // we don't want to stop any send out just because the callback returned errors
            phive()->dumpTbl('send-mail-success-callback-exception', $e->getMessage());
        }

        return $sent;
    }

    public function addAttachments($emailId, $fileName, $fileData, $mimeType)
    {
        $attachment = phive('SQL')->insertArray('attachments', array('email_id' => $emailId, 'data' => $fileData, 'file_name' => $fileName, 'mime_type' => $mimeType));
        return $attachment;
    }

    public function skipConsent($skip = true){
        $this->skip_consent = $skip;
    }

  function advancedReplace($str){
    if(empty($this->adv_flag)){
      preg_match_all('/__(.+?)__/', $str, $m);
      $this->adv_content = array();
      foreach($m[1] as $q){
            $tmp = "";
            $parameters = explode('||', $q);
            list($table, $id_col, $id_val, $val_col, $format) = $parameters;
            if (count($parameters) >= 4) {
                $sql = "SELECT $val_col FROM $table WHERE $id_col = '$id_val'";
                $tmp = phive('SQL')->getValue($sql);
            }

        if(empty($tmp))
          continue;

        if($format == 'currency')
          $tmp = efEuro($tmp, true);
        $this->adv_content[] = $tmp;
      }
      $this->adv_replaced = $m[0];
      $this->adv_flag 	= true;
    }

    if(empty($this->adv_poker_badbeat)){
      if(strpos($str, '--POKERBADBEAT--') !== false){
        $this->adv_content[] 	= efEuro(phive('MicroRaker')->getBadBeat(), true);
        $this->adv_replaced[] 	= '--POKERBADBEAT--';
      }
      $this->adv_poker_badbeat = true;
    }

    if(is_array($this->adv_content) && !empty($this->adv_content))
      return str_replace($this->adv_replaced, $this->adv_content, $str);
    else
      return $str;
  }

  public function replaceKeywords($mail,$replacers){
    if ($replacers !== null)
      $mail = strtr($mail, $replacers);

    if(preg_match('/__(\w+?)__/', $mail, $m)){
      if(phive()->getSetting('logging') && empty($GLOBALS['pr_mail_missing'])){
        echo "Missing replacer {$m[0]}";
        $GLOBALS['pr_mail_missing'] = true;
      }

      return '';
    }

    return $this->advancedReplace($mail);
  }

  public function decodeMail($text)
  {
    return utf8_decode(strtr($text,array("€" => "&euro;")));
  }

  public function sendMailFromString($content,$subject,$user,$lang = null,$replacers = null,$from = null,$reply_to = null,$bcc = null,$cc = null,$priority=1){

    if(!$user){
      trigger_error("Called sendMailFromString with $user == null");
      return;
    }
    $to = $user->getAttribute("email");
    $this->cur_user = $user;
    if ($from === null)
      $from = $this->getSetting("DEFAULT_FROM_EMAIL");

    if($reply_to === null)
      $reply_to = $this->getSetting("DEFAULT_REPLY_TO");

    if(is_array($bcc))
      $bcc = implode(",",$bcc);

    if(is_array($to))
      $to = implode(",",$to);

    if(is_array($cc))
      $cc = implode(",",$cc);

    $content = $this->replaceKeywords($content,$replacers);
    $subject = $this->replaceKeywords($subject,$replacers);

    if(empty($content) || empty($subject))
      return false;

    $ret = $this->queueMail(
      $to,
      $reply_to,
      $subject,
      strtr($content,array("€" => "&euro;")),
      html_entity_decode(strip_tags($content),ENT_QUOTES,"UTF-8"),
      $priority,
      $from,
      $cc,
      $bcc,
      null,
      $user->getId()
    );

    if ($ret)
      return true;
    else
    {
      trigger_error("Mail not sent, RET: " . $ret);
      return false;
    }
  }

  public function addMail($mail_trigger,$subject,$content,$replacers = null,$lang = null){
    if ($lang === null)
      $lang = $this->lang;
    if($mail_trigger == "")
      return false;
    if($this->getSetting("LOCALIZE")){
      $loc = phive('Localizer');
      $loc->addString("mail.".$mail_trigger.".subject", $subject, $lang, false);
      $loc->addString("mail.".$mail_trigger.".content", $content, $lang, false);
      $inserts = array("mail_trigger" => $mail_trigger,
      "subject" => "mail.".$mail_trigger.".subject",
      "content" => "mail.".$mail_trigger.".content");
    }
    else {
      $inserts = array("mail_trigger" => $mail_trigger,
      "subject" => $subject,
      "content" => $content);
      if ($this->getSetting("ENABLE_LANG"))
        $inserts['lang'] = $lang;
    }
    if ($replacers){
      $inserts['replacers'] = $replacers;
    }

    phive('SQL')->save($this->getSetting("DB_MAILS"), $inserts);

  }

  public function editMail($mail_trigger,$new_subject,$new_content,$replacers = null,$lang = null){
    if ($lang === null)
      $lang = $this->lang;
    if($this->getSetting("LOCALIZE")){
      $loc = phive('Localizer');
      $loc->editString("mail.".$mail_trigger.".subject",$new_subject,$lang);
      $loc->editString("mail.".$mail_trigger.".content",$new_content,$lang);
    }
    else {
      $updates = array("subject" => $new_subject, "content" => $new_content);
      $where_query = "mail_trigger = " . phive("SQL")->escape($mail_trigger);
      if ($this->getSetting("ENABLE_LANG"))
        $where_query .= " AND lang = " . phive("SQL")->escape($lang);
      phive("SQL")->insertArray($this->getSetting("DB_MAILS"),$updates,$where_query);
    }
    if ($replacers !== null){
      $where_query = "mail_trigger = " . phive("SQL")->escape($mail_trigger);
      $updates = array("replacers" => $replacers);
      phive("SQL")->insertArray($this->getSetting("DB_MAILS"),$updates,$where_query);
    }

  }

  function purgeMail($trigger){
    phive("SQL")->query("DELETE FROM mails WHERE mail_trigger = '$trigger'");
    phive('Localizer')->deleteString("mail.$trigger.content");
    phive('Localizer')->deleteString("mail.$trigger.subject");
  }

  public function removeMail($mail_trigger,$lang = null){
    if($lang === null){
      $lang = $this->lang;
    }
    if($this->getSetting("LOCALIZE")){
      if (sizeof($this->getAvailableLanguages($mail_trigger)) == 1){
        $query = "DELETE FROM ".$this->getSetting("DB_MAILS")." WHERE mail_trigger = ".phive("SQL")->escape($mail_trigger);
        phive("SQL")->query($query);
      }
      phive('Localizer')->deleteString("mail.".$mail_trigger.".content",$lang);
      phive('Localizer')->deleteString("mail.".$mail_trigger.".subject",$lang);
    }
    else{
      if($lang !== null){
        $where_lang = " AND lang = ".phive("SQL")->escape($lang);
      }
      $query = "DELETE FROM ".$this->getSetting("DB_MAILS")." WHERE mail_trigger = ".phive("SQL")->escape($mail_trigger).$where_lang;
      phive("SQL")->query($query);
    }

  }

  function getRawStrings($trigger, $lang){
    return array('subject' => traw("mail.$trigger.subject", $lang), 'content' => traw("mail.$trigger.content", $lang));
  }

    /**
     * Get translated content for mail
     * Tries to get the mail for jurisdiction, $lang, 'en'
     *
     * @param $mail_id
     * @param  null  $lang
     *
     * @return array|false|mixed
     */
    public function getMail($mail_id, $lang = null)
    {
        $query = "SELECT * FROM ".$this->getSetting("DB_MAILS")." WHERE mail_trigger = ".phive("SQL")->escape($mail_id);
        $multi_currency = phive()->moduleExists('Currencer') && phive("Currencer")->getSetting('multi_currency') == true;

        if (!$this->getSetting("LOCALIZE")) {
            $select_lang = $this->getSetting("ENABLE_LANG") ? " AND lang = ".phive("SQL")->escape($lang) : '';
            phive("SQL")->query($query.$select_lang);
            return phive("SQL")->fetch();
        }

        if ($multi_currency) {
            setCur($this->cur_user);
        }

        $mail = phive("SQL")->loadAssoc($query);

        $mail['mail_trigger'] = $mail_id;

        $subject_alias = "mail.".$mail_id.".subject";
        $content_alias = "mail.".$mail_id.".content";

        // Try to get the email translated to jurisdiction language or fallback to country language or EN
        foreach ([phive('Localizer')->getDomainLanguageOverwrite($this->cur_user), $lang, 'en'] as $language) {
            if (empty($language) || !is_string($language)) {
                continue;
            }
            $mail['lang'] = $language;
            $mail['subject'] = t($subject_alias, $language);
            $mail['content'] = t($content_alias, $language);

            if ($mail['subject'] !== "($subject_alias)" && $mail['content'] !== "($content_alias)") {
                return $mail;
            }
        }

        if ($multi_currency) {
            setDefCur();
        }

        return $mail;
    }

  public function getMails($lang = null){
    if ($lang){
      $where = " WHERE lang = ".phive("SQL")->escape($lang);
    }
    phive("SQL")->query("SELECT mail_trigger FROM ".$this->getSetting("DB_MAILS").$where);
     $array = phive("SQL")->fetchArray();
    $ret = array();
    foreach ($array as $a) {
      $ret[] = $a['mail_trigger'];
    }
    return $ret;
  }

  function getMailsSelect($lang = null){
    $mails = $this->getMails($lang);
    return array_combine(array_values($mails), array_values($mails));
  }

  public function getAvailableLanguages($mail_trigger){
    if($this->getSetting("LOCALIZE")){
      $langs = phive('Localizer')->getStringLanguages("mail.".$mail_trigger.".subject");
      return $langs;
    }
    phive("SQL")->query("SELECT lang FROM ".$this->getSetting("DB_MAILS"));
    return phive("SQL")->fetchArray();
  }

  function getLocalEmail($email){
    return "$email@".$this->getSetting('domain').".com";
  }

    function mailLocal($subject, $content, $address, $raw_address = ''){
        $to = empty($raw_address) ? $this->getSetting($address) : $raw_address;
        $from = $this->getLocalEmail('notifications');
        phive('MailHandler2')->saveRawMail($subject, $content, $from, $to, $from, 0);
    }

    /**
     * We send an email as local notification to recipients in a config
     *
     * @param string $subject
     * @param string $content
     * @param string $config_tag
     * @param string $config_name
     */
    public function mailLocalFromConfig($subject, $content, $config_name, $config_tag = 'emails')
    {
        foreach (phive('Config')->valAsArray($config_tag, $config_name) as $to) {
            $this->mailLocal($subject, $content, '', $to);
        }
    }

  function saveRawMail($subject, $content, $from, $to, $replyto = '', $priority = 0){
    if(empty($to))
      $to = $this->getSetting('DEFAULT_REPLY_TO');

    if(empty($from))
      $from = $this->getSetting('DEFAULT_FROM_EMAIL');

    $replyto = empty($replyto) ? $from : $replyto;

    phive("SQL")->insertArray('mailer_queue',
      array('priority' => $priority, 'from' => $from, 'replyto' => $replyto, 'to' => $to, 'subject' => $subject, 'messageHTML' => $content, 'messageText' => strip_tags($content)
    ));
  }

  public function getLang(){
    return $this->lang;
  }

  public function setLang($lang){
    $this->lang = $lang;
  }

    /**
     * To generate to_email when sending contact us email
     *
     * Generated with pattern {COUNTRY_CODE}.support@{DOMAIN}.com
     * Pattern can be overridden by setting config like en = uk.support@videoslots.com
     *
     * Exception for lang EN, default email will be support@{DOMAIN}.com
     *
     * @return string
     */
    public function buildToEmail(): string
    {
        $domain = $this->getSetting('domain', 'videoslots');
        $current_lang = cLang();
        $default_support_email = $this->getSetting('support_email', "support@{$domain}.com");

        $enable_lang_based_support_email = $this->getSetting("enable_lang_based_support_email");
        if (!$enable_lang_based_support_email) {
            return $default_support_email;
        }

        $lang_emails = $this->getSetting("lang_emails");
        $to_email = $lang_emails[$current_lang];

        if(empty($to_email) && $current_lang === 'en') {
            return $default_support_email;
        }

        if(empty($to_email)) {
            $countries = phive('Localizer')->getCountries();
            $language_subdomain_arr = [];
            foreach($countries as $country) {
                $language_subdomain_arr[$country['language']] = $country['subdomain'];
            }

            $to_email = "{$language_subdomain_arr[$current_lang]}.support@{$domain}.com";
        }

        return $to_email;
    }

    /**
     * @param $to
     * @param $subject
     * @param $body
     * @param string $from
     * @param false $priority
     * @param string $from_name
     * @param string $a
     * @return false|mixed
     */
    public function sendRawMail($to, $subject, $body, $from = '', $priority = 0, $from_name = '', $a = '')
    {
        /* TODO Attachments not supported
        if (!empty($a)) {
            $at = $mail->createAttachment(file_get_contents($a['file']));
            $at->type = $a['type'] == 'image' ? 'image/gif' : 'text/plain';
            $at->disposition = $a['disposition'] == 'inline' ? Zend_Mime::DISPOSITION_INLINE : Zend_Mime::DISPOSITION_ATTACHMENT;
            $at->encoding = $a['type'] == 'image' ? Zend_Mime::ENCODING_BASE64 : Zend_Mime::TYPE_TEXT;
            $at->filename = $a['fname'];
        }
        */

        if (empty($from)) {
            $from = $this->getSetting('default_from_email');
        }

        if (empty($from_name)) {
            $from_name = $this->getSetting('default_from_name');
        }

        return $this->sendEmail($to, $from, $subject, $body, $from, $from_name, $priority);
    }

  // Checks if a function exists and adds a replacer for it
  public function getDefaultReplacers($user){

    $ret = array();

    if (method_exists($user, "getUsername"))
      $ret["__USERNAME__"] = $user->getUsername();

    if (method_exists($user, "getFullname"))
      $ret['__FULLNAME__'] = $user->getFullname();

    if (method_exists($user, "getId"))
      $ret['__USERID__'] = $user->getId();

    if (method_exists($user, "getCurrency"))
      $ret['__CURRENCY__'] = $user->getCurrency();

    if (method_exists($user, "getAttribute")) {
      $ret['__EMAIL__']	= $user->getAttribute("email");
      $ret['__COUNTRY__']	= $user->getAttribute("country");
      $ret['__FIRSTNAME__']	= $user->getAttribute("firstname");
    }

    if (method_exists($user, "getActivationCode"))
      $ret['__ACTIVATIONCODE__']	= $user->getActivationCode();

    return $ret;
  }

    /**
     * Checks if a mail trigger exists in the database
     *
     * @param string $mail_trigger
     * @return bool
     */
    public function hasMailTrigger($mail_trigger)
    {
        if(!empty(phive('SQL')->loadArray("SELECT * FROM mails WHERE mail_trigger = '{$mail_trigger}'"))) {

            return true;
        }

        return false;
    }

    /**
     * Checks if we have localized strings for the provided language
     *
     * NOTE: this assumes we always have both subject and content saved  (PROBLEM: for mt we only have content, no subject)
     *
     * @param string $mail_trigger
     * @param bool
     */
    public function hasMailForLanguage($mail_trigger, $language)
    {
        $content_alias = "mail.".$mail_trigger.".content";
        //$subject_alias = "mail.".$mail_trigger.".subject";

        if(!empty(phive('SQL')->loadArray("SELECT * FROM localized_strings WHERE alias = '{$content_alias}' AND language = '{$language}'"))) {
            return true;
        }

        return false;
    }


    /**
     * Send the OTP email which contains the OTP code
     *
     * @param string|int $user_id
     * @param string|int $code
     * @return mixed
     */
    public function sendOtpMail($user_id, $code)
    {
        $mail_trigger = 'email-code-otp';
        $user = cu($user_id);
        if (empty($user)) {
            return false;
        }
        $replacers = $this->getDefaultReplacers($user);
        $replacers['__CODE__'] = $code;
        $conf = $this->getSetting('prio_config');
        $from = empty($conf) ? null : $conf['from_email'];
        return $this->sendMail($mail_trigger, $user, $replacers, cLang(), $from, $from, null, null, 0);
    }

    /**
     * Send Email to dev support, or custom address, when exception occurs in $callback
     *
     * @param callable $callback
     * @param string $location
     * @param string $email
     */
    public function notifyException(callable $callback, string $location, $email = '')
    {
        if (empty($email)) {
            $email = $this->getSetting('dev_support');
        }

        try {
            $callback();
        } catch (Exception $e) {
            phive('MailHandler2')->sendRawMail($email, "$location - Exception", "Got this exception: {$e->getMessage()} \nTrace: \n{$e->getTraceAsString()}");
        }
    }

    // CRON MAILS starts here, moved from DBUserHandler
    function getGroupedByType($where = '1', $trigger = false, $type = 'email', $group_by = 'email'){

        $right_join_settings = $this->getConsentJoin($trigger, 'users', $type);

        return phive("SQL")->shs('merge', '', null, 'users')->loadArray("
            SELECT users.* FROM users
            {$right_join_settings}
            WHERE $where AND active = 1
            GROUP BY {$group_by}
        ");
    }

    function wedWeek1mail(){
        /** @var MailHandler2 $mh */
        $mh 		= $this->setFrom();
        $mail_trigger 	= "monthly-week1";
        $mh->unsubscribe_link = true;

        $query = "((register_date > CURDATE() - INTERVAL 12 MONTH) OR (last_login > NOW() - INTERVAL 12 MONTH))";
        $excluded_countries = array_keys(phive('Config')->valAsArray('countries', 'block-monthly-week1'));
        if (!empty($excluded_countries[0])) {
            $query .= " AND country NOT IN(" . phive('SQL')->makeIn($excluded_countries) . ")";
        }
        $us = $this->getGroupedByType($query, $mail_trigger);
        $us = $this->filterMarketingBlockedUsers($us);
        phive("Logger")->logPromo($mail_trigger, "Filter finished");
        $this->addSupport($us);

        foreach($us as $u){
            $user = cu($u['id']);
            if (empty($user) || $user->isBlocked() || $this->isMailBlocked($u)) continue;
            $replacers 	= $this->getDefaultReplacers($user);
            $user->marketing_blocked = false;
            $mh->sendMail($mail_trigger, $user, $replacers, null, $this->from, $this->from, $this->getCrmEmail(), null, self::PRIORITY_NEWSLETTER);
        }
        $this->logEmailCount($mail_trigger);
    }

    /*
       function week2mail(){
       $us = array();
       foreach($this->getGroupedByType("1", "monthly-week2") as $u){
       if(phive("Cashier")->hasDeposited($u['user_id']))
       $us[] = $u;
       }
       $this->addSupport($us);
       // marketing blocked users are filtered in $this->mailVoucher
       $this->mailVouchers('voucher-templates', 'voucher-week2', $us, "monthly-week2", date('dmy'));
       }
     */

    /**
     * @param $alias
     * @param $country
     * @param bool $non_depositors
     * @param null|string $date
     * @param null|string $time
     */
    public function weekSMSCommon($alias, $country, $non_depositors = false, $date = null, $time = null, $v_extra = true){
        list($not_used, $alias, $network) = explode('-', $alias);
        $alias = empty($network) ? $alias : "$alias-$network";
        $trigger = "monthly-$alias";

        $us = [];
        foreach ($this->getGroupedByType("country = '{$country}'", $trigger, 'sms', 'mobile') as $u) {
            $is_depositor = phive("Cashier")->hasDeposited($u['id']);
            if ($non_depositors == false) {
                $us[] = $u;
            } else {
                if (!$is_depositor) {
                    $us[] = $u;
                }
            }
        }

        if (empty($date) && empty($time)) {
            $forced_schedule = null;
        } else {
            $forced_schedule = [
                'date' => $date,
                'time' => $time
            ];
        }
        $v_extra = $v_extra === true ? date('dmy') : '';
        $this->mailVouchers('voucher-templates', "sms-voucher-$alias", $us, "sms-{$trigger}", $v_extra, true,
            $forced_schedule);
        phive("Logger")->logPromo("sms-{$trigger}", "Execution finished", true);
    }

    function weekMailCommon($alias){
        list($not_used, $alias, $network) = explode('-', $alias);
        $alias = empty($network) ? $alias : "$alias-$network";
        $mail_trigger = "monthly-$alias";

        $us = array();
        foreach($this->getGroupedByType('1', $mail_trigger) as $u){
            if(phive("Cashier")->hasDeposited($u['id']))
                $us[] = $u;
        }

        $this->addSupport($us);
        // marketing blocked users are filtered in $this->mailVoucher
        $this->mailVouchers('voucher-templates', "voucher-$alias", $us, $mail_trigger, date('dmy'));
        $this->logEmailCount($mail_trigger);
    }

    function week3mail(){
        $mail_trigger = "monthly-week3";
        $countries = phive("SQL")->makeIn(explode(' ', phive("Config")->getValue('countries', 'week3-email')));
        $us = $this->getGroupedByType("country IN ($countries)", $mail_trigger);
        $this->addSupport($us);
        // marketing blocked users are filtered in $this->mailVoucher
        $this->mailVouchers('voucher-templates', 'voucher-week3', $us, $mail_trigger, date('dmy'));
        $this->logEmailCount($mail_trigger);
    }

    function wedWeek3mail(){
        $mh       = $this->setFrom();
        $mail_trigger   = "monthly-week3-wednesday";
        $mh->unsubscribe_link = true;
        $bonus = phive("Bonuses")->insertTemplate('bonus-templates', $mail_trigger);
        if(empty($bonus)){
            phive()->dumpTbl('bonusmails', "no bonus created so no mail sent");
            return;
        }
        $users = $this->getGroupedByType('1', $mail_trigger);
        $users = $this->filterMarketingBlockedUsers($users);
        $support = phive('UserHandler')->getUserByEmail($mh->getSetting('support_mail'))->data;
        $this->wedWeek3Send($support, $mail_trigger, $mh, $bonus, true);
        foreach($users as $u)
            $this->wedWeek3Send($u, $mail_trigger, $mh, $bonus);
        $this->logEmailCount($mail_trigger);
    }

    function wedWeek3Send($u, $mail_trigger, $mh, $bonus, $test = false){
        $deposits = $this->getUserDeposits($u);
        if(empty($deposits) && !$test)
            return;
        $dep = array_pop($deposits);
        if($dep['timestamp'] > phive()->hisMod('-45 day') && !$test)
            return;
        $user     = cu($u['id']);
        if(empty($user))
            return;
        if(($user->isBlocked() || ($user->isBonusBlocked()) && !$test) || $this->isMailBlocked($u))
            return;
        $replacers  = phive('MailHandler2')->getDefaultReplacers($user);
        $replacers["__RELOADCODE__"] 	= $bonus['reload_code'];
        if(!empty($bonus['id']))
            phive('Bonuses')->insertCheck($user, $bonus['id']);
        $user->marketing_blocked = $u['marketing_blocked'];
        $mh->sendMail($mail_trigger, $user, $replacers, null, $this->from, $this->from, $this->getCrmEmail(), null, self::PRIORITY_NEWSLETTER);
    }

    function isMailBlocked(&$ud){
        if(empty($this->mailblock_countries))
            $this->mailblock_countries = phive('Config')->valAsArray('countries', 'deposit-blocked', ' ');
        if(in_array(ud($ud)['country'], $this->mailblock_countries))
            return true;
        return false;
    }

    function inactiveCashMail(){
        $mh = $this->setFrom();
        $mail_trigger = 'inactive-cash-balance';
        $mh->unsubscribe_link = true;

        $right_join_settings = $this->getConsentJoin($mail_trigger);

        $users = phive('SQL')->shs('merge', '', null, 'users')->loadArray("
            SELECT users.* FROM users
            {$right_join_settings}
            WHERE cash_balance > 50
            AND active = 1
            AND DATEDIFF(NOW(), last_login) > 30
        ");
        $users = $this->filterMarketingBlockedUsers($users);
        foreach($users as $u){
            $user 		= cu($u['id']);
            if($user->isBlocked() || $this->isMailBlocked($u))
                continue;
            $replacers 	= $this->getDefaultReplacers($user);
            $replacers['__BALANCE__'] = $user->getBalance() / 100;
            $user->marketing_blocked = $u['marketing_blocked'];
            $mh->sendMail($mail_trigger, $user, $replacers, null, $this->from, $this->from, $this->getCrmEmail(), null, self::PRIORITY_NEWSLETTER);
        }

        $this->logEmailCount($mail_trigger);
    }

    function depLimPlayBlockMail($uid, $dep_amount, $type){
        $user                    = cu($uid);
        $mh                      = $this->setFrom();
        $prio_config             = $mh->getSetting('prio_config');
        $from                    = empty($prio_config) ? $this->from : $prio_config['from_email'];
        $site                    = phive('UserHandler')->getSiteUrl();
        //$replacers 	       = phive('MailHandler2')->getDefaultReplacers($user);
        //$replacers['__AMOUNT__'] = $dep_amount / 100;
        //$mh->sendMail('dep-limit-playblock', $user, $replacers, null, $from, $from, null, null, 0);
        if ($type == 'site') {
            $emails_config = 'dep-lim-playblock-emailto';
            $mail_title = "Daily play block limit reached on $site.";
        } else {
            $emails_config = 'dep-lim-playblock-negative-remaining-emailto';
            $mail_title = "Deposit limit remaining amount is negative [user id: {$uid}]";
        }
        $emails = phive()->explode(phive('Config')->getValue("in-limits", $emails_config));
        foreach($emails as $email){
            $this->saveRawMail(
                $mail_title,
                "Link to $site  account: ".phive('UserHandler')->getBOAccountUrl($uid),
                $from,
                $email,
                $from
            );
        }
    }

    function bonusMails(){
        //$this->mailUnblockedBonusLovers();
        $this->mailInactivesFirstWeek();
        $this->mailInactivesSecondWeek();
    }

    function addSupport(&$us){
        $support_email = $this->getSetting('support_mail');
        if(empty($support_email))
            return;
        $support = phive('UserHandler')->getUserByEmail($support_email);
        array_unshift($us, $support->data);
        $us = array_values(array_filter($us));
    }

    /**
     * Converts a string representation of a weekday into a number from 1 to 7:
     * 1 = Monday, 7 = Sunday.
     * Returns null if the input cannot be recognized.
     */
    function mapDayOfWeekToNumber($dayStr) {
        $dayStr = strtolower(trim($dayStr));

        $mapDays = [
            'mon'      => 1, 'monday'    => 1, 'mo'   => 1,
            'tue'      => 2, 'tuesday'   => 2, 'tu'   => 2,
            'wed'      => 3, 'wednesday' => 3, 'we'   => 3,
            'thu'      => 4, 'thursday'  => 4, 'th'   => 4,
            'fri'      => 5, 'friday'    => 5, 'fr'   => 5,
            'sat'      => 6, 'saturday'  => 6, 'sa'   => 6,
            'sun'      => 7, 'sunday'    => 7, 'su'   => 7,
        ];

        return $mapDays[$dayStr] ?? null;
    }

    /**
     * Main function to form and (optionally) execute the mailing schedule.
     *
     * @param string $date     Date in YYYY-MM-DD format
     * @param bool   $execute  Whether to actually execute mail sending (true) or just form the list (false)
     * @return string          A string containing a list of the "alias" values for emails that should be sent on the given date
     */
    function mailSchedule($date, $execute = false)
    {
        $str = '';
        list($year, $month, $day) = explode('-', $date);
        $currentMonthDay = (int)$day;
        $currentWeekDay  = (int)date('N', strtotime($date));

        $ss = phive('Config')->getByTag("mails");
        $map = [
            'deposit-freeroll'              => ['func' => 'mailDepositorsFreeroll',        'alias' => "deposit-freeroll"],
            'no-deposit-freeroll'           => ['func' => 'mailNonDepositorsFreeroll',     'alias' => "no-deposit-freeroll"],
            'monthly-week1'                 => ['func' => 'wedWeek1mail',                  'alias' => "monthly-week1"],
            'bonus-mail'                    => ['func' => 'bonusMails',                    'alias' => "weekly-bonuslovers, 1w-inactive-XXX, 2w-inactive-XXX"],
            'monthly-week3-wednesday'       => ['func' => 'wedWeek3mail',                  'alias' => "monthly-week3-wednesday"],
            'inactive-cash-balance'         => ['func' => 'inactiveCashMail',              'alias' => "inactive-cash-balance"],
            'monthly-week2'                 => ['func' => 'weekMailCommon',                'alias' => "monthly-week2"],
            'monthly-week3'                 => ['func' => 'week3mail',                     'alias' => "monthly-week3"],
            'no-deposit-weekly'             => ['func' => 'mailNonDepositorsWeekly',       'alias' => "no-deposit-weekly"],
            'no-deposit-weekly-2'           => ['func' => 'mailNonDepositorsWeekly2',      'alias' => "no-deposit-weekly-2"],
            'welcome.mrvegas'               => ['func' => 'mailBrandedDepositors',         'alias' => "welcome.mrvegas"],
            'welcome.bonus_reminder'        => ['func' => 'sendWelcomeBonusReminderMail',  'alias' => "welcome.bonus_reminder"],
            'welcome.bonus_percentage_reminder' => ['func' => 'sendWelcomeBonusPercentageReminderMail', 'alias' => "welcome.bonus_percentage_reminder"],
        ];

        foreach (['betsoft','ygg','wms','microgaming','playngo','quickspin','relax'] as $alias) {
            $map["monthly-week2-$alias"] = ['func'  => 'weekMailCommon', 'alias' => "monthly-week2-$alias",];
        }

        foreach (range(1, 15) as $num) {
            $map["nodeposit-newbonusoffers-mail-$num"] = ['func'  => 'mailNonDepositors3days', 'alias' => $num];
            $map["deposit-newbonusoffers-mail-$num"] = ['func'  => 'mailDepositors60days', 'alias' => $num];
            $map["29daydeposit-newbonusoffers-mail-$num"] = ['func'  => 'mailDepositors29days', 'alias' => $num];
        }

        // Parse config_value into an array "parsed"
        // If the element is numeric -> it's a day of the month
        // If it's a string like "mon", "fri", or "monday","friday" etc. -> it's a day of the week
        array_walk($ss, function (&$item) {
            $sending_dates = explode(',', $item['config_value']);
            $parsed = [];

            foreach ($sending_dates as $sending_day) {
                $sending_day = trim($sending_day);

                if (ctype_digit($sending_day)) {
                    $parsed[] = (int)$sending_day;
                } else {
                    // Attempt to map weekday string to a number (1..7) and store it as negative number (to differentiate from day of month)
                    $weekDayNum = $this->mapDayOfWeekToNumber($sending_day);
                    if ($weekDayNum !== null) {
                        $parsed[] = -$weekDayNum;
                    }
                }
            }

            $item['parsed'] = $parsed;
        });

        foreach ($ss as $oneConfig) {
            $configName = $oneConfig['config_name'] ?? '';
            if (!isset($map[$configName])) {
                continue;
            }

            $parsedDays = $oneConfig['parsed'] ?? [];
            $needToSend = false;

            if (in_array($currentMonthDay, $parsedDays, true)) {
                $needToSend = true;
            }
            // Check for matching day of week (e.g., -5 for Friday)
            if (in_array(-$currentWeekDay, $parsedDays, true)) {
                $needToSend = true;
            }

            if ($needToSend) {
                // Append to the result string for admin panel
                $str .= $map[$configName]['alias'] . ",<br>";

                if ($execute) {
                    phive("Logger")->logPromo($map[$configName]['func'], "Starting with arg " . $map[$configName]['alias']);
                    phive()->pexec('MailHandler2', $map[$configName]['func'], [$map[$configName]['alias']], '500-0');
                }
            }
        }

        return $str;
    }

    public function payoutDetailsRequest(DBUser $user): bool
    {
        switch ($user->getCountry()) {
            case 'GB':
                $language = 'en';
                break;
            case 'DK':
                $language = 'da';
                break;
            case 'SE':
                $language = 'sv';
                break;
            case 'CA':
                $province = $user->getMainProvince();
                if (!empty($province) && $province === 'ON') {
                    $language = 'on';
                } else {
                    return false;
                }
                break;
            default:
                return false;
        }

        $mailTrigger = 'AML-52';
        $email = $user->getAttr('email');
        $replacers['__FIRSTNAME__'] = $user->getAttribute("firstname");
        $senderEmail = $this->getSetting('support_mail');

        $emailSent = $this->sendMailToEmail($mailTrigger, $email, $replacers, $language, $senderEmail, $senderEmail);
        return (bool)$emailSent;
    }

    function sendWelcomeMail($user)
    {
        $mail_trigger = 'welcome.mail';

        $excluded_countries = phive('Config')->valAsArray('countries', $mail_trigger);
        if(!empty($excluded_countries[$user->getCountry()])) {
            return;
        }

        $replacers = $this->getDefaultReplacers($user);
        $replacers['__USERNAME__'] = $user->getUserName();

        $bonus_code = phive('Bonuses')->getBonusCode();
        if(!empty($bonus_code)) {
            $mail_trigger = "welcome.mail.{$bonus_code}";

            // If the mail_trigger is inside mails_connections, we need to use the mail_trigger_target
            $mail_trigger_target = phive('SQL')->getValue('', 'mail_trigger_target', 'mails_connections', ['mail_trigger_target' => $mail_trigger]);

            if(!empty($mail_trigger_target)) {
                $mail_trigger = $mail_trigger_target;
            }

            if(!$this->hasMailTrigger($mail_trigger)) {
                // send default welcome mail if the mail trigger does not exist
                $mail_trigger = 'welcome.mail';
            }
        }

        $user->refresh();

        $this->sendMail($mail_trigger, $user, $replacers);
    }

    /**
     * Send email reminder for users with welcome bonus about progress in percentage
     */
    public function sendWelcomeBonusPercentageReminderMail()
    {
        //Return if it's welcome bonus email reminder off in config
        if (phive('Config')->getValue('bonus_percentage_reminder_email', 'welcome.bonus_percentage_reminder') === 'off') {
            return;
        }

        $mail_trigger = 'welcome.bonus_percentage_reminder';
        $bonus_status = 'active';

        $included_countries = phive('Config')->valAsArray('included_countries', $mail_trigger);
        $users_list = phive('CasinoBonuses')->getAllUserIdsWithWelcomeBonuses($bonus_status);

        //Exit we we don't have any users or included countries
        if (empty($users_list) || empty($included_countries)) {
            return;
        }

        foreach ($users_list as $user_data) {
            $user = $user_data['user_obj'];

            //Check if user country allow to get this type of mail.
            if(!in_array($user_data['user_obj']->getCountry(), $included_countries)) {
                continue;
            }

            if (empty($user) || $user->isBlocked() || $this->isMailBlocked($user)) {
                phive("Logger")->logPromo($mail_trigger, "Can't send - {$mail_trigger} - email to user with id: {$user->getId()}");
                continue;
            }

            $replacers = $this->getDefaultReplacers($user);

            $replacers['__AMOUNT__'] = (phive('CasinoBonuses')->getCasinoWagerProgressPercentage($user_data['bonus_entry'])['progress']) ?: '0.00';
            $replacers['__VALUE__'] = phive('Currencer')->getCurSym($user->getCurrency()) .' '. number_format($user_data['bonus_entry']['progress'] / 100, 2);
            $this->sendWelcomeBonusPercentageReminderSms($user, $replacers);
            $this->sendMail($mail_trigger, $user, $replacers);
        }
    }

    /**
     * Send welcome bonus percentage sms notification
     * @param $user
     */
    public function sendWelcomeBonusPercentageReminderSms($user, $replacers = false)
    {
        if (phive('Config')->getValue('bonus_percentage_reminder_sms', 'welcome.bonus_percentage_reminder_sms') === 'on') {
            $sms_content = 'sms.welcome.bonus_pecentage_reminder.content';

            if (!phive('UserHandler')->canSendTo($user, null, $sms_content)) {
                phive('UserHandler')->logAction($user->getId(), "Didn't get SMS with trigger $sms_content because of privacy settings.", 'sms');
                return;
            }

            $lang = $user->getAttribute("preferred_lang") ?? phive('Localizer')->getDefaultLanguage();
            $content = t($sms_content, $lang);
            $scheduled_at = phive()->hisMod('+1 hour', '', 'Y-m-d');
            $message = $this->replaceKeywords($content, $replacers);

            phive('Mosms')->putInQ($user, $message, true, 1, $scheduled_at);

        }
    }

    /**
     * Send email reminder for users with welcome bonus at pending state
     */
    public function sendWelcomeBonusReminderMail()
    {
        if (phive('Config')->getValue('bonus_reminder_email', 'welcome.bonus_reminder') === 'off') {
            return;
        }

        $mail_trigger = 'welcome.bonus_reminder';
        $status = 'pending';

        $included_countries = phive('Config')->valAsArray('included_countries', $mail_trigger);
        $users_bonus_data = phive('CasinoBonuses')->getAllUserIdsWithWelcomeBonuses($status);

        //Exit we we don't have any users or included countries
        if (empty($users_bonus_data) || empty($included_countries)) {
            return;
        }

        foreach ($users_bonus_data as $user_data) {
            $user = $user_data['user_obj'];
            //Check if user country allow to get this type of mail.
            if(!in_array($user->getCountry(), $included_countries)) {
                continue;
            }

            if (empty($user) || $user->isBlocked() || $this->isMailBlocked($user)) {
                phive("Logger")->logPromo($mail_trigger, "Can't send - {$mail_trigger} - email to user with id: {$user->getId()}");
                continue;
            }

            $this->sendMail($mail_trigger, $user);
            $this->sendWelcomeBonusReminderSms($user);
        }
    }

    /**
     * Send welcome bonus sms notification
     * @param $user
     */
    public function sendWelcomeBonusReminderSms($user)
    {
        if (phive('Config')->getValue('bonus_reminder_sms', 'welcome.bonus_reminder') === 'on') {
            $sms_content = 'sms.welcome.bonus_reminder.content';

            if (!phive('UserHandler')->canSendTo($user, null, $sms_content)) {
                phive('UserHandler')->logAction($user->getId(), "Didn't get SMS with trigger $sms_content because of privacy settings.", 'sms');
                return;
            }

            $lang = $user->getAttribute("preferred_lang") ?? phive('Localizer')->getDefaultLanguage();
            $content = t($sms_content, $lang);
            $scheduled_at = phive()->hisMod('+1 hour', '', 'Y-m-d');

            phive('Mosms')->putInQ($user, $content, true, 1, $scheduled_at);
        }

    }
    /**
     * @param $tag
     * @param $name
     * @param $users
     * @param $trigger
     * @param string $v_extra
     * @param bool $check_bonus_block
     * @param null|array $force_schedule Array with [date => date,time => time] strings
     * @return bool|void
     */
    function mailVouchers($tag, $name, $users, $trigger, $v_extra = '', $check_bonus_block = true, $force_schedule = null){

        $is_sms = explode('-', $name)[0] === 'sms';

        if (!empty($users)) {
            $users = $this->filterMarketingBlockedUsers($users);
        }

        if(empty($users))
            return;

        /** @var Vouchers $v */
        $v = phive("Vouchers");

        list($vname, $vcode, $bid, $count, $aid) = $v->insertTemplate($tag, $name, $v_extra);

        if(empty($vname) || empty($vcode) || (empty($bid) && empty($aid))){
            phive()->dumpTbl('bonusmails', "problem with voucher template data, vname: $vname, vcode: $vcode, bid: $bid, tag: $tag, name: $name, extra: $v_extra");
            return false;
        }

        if(!empty($bid)){
            $bonus = phive("Bonuses")->getBonus($bid);
            $game = phive('MicroGames')->getByGameId($bonus['game_id']);

            if(empty($bonus)){
                phive()->dumpTbl('bonusmails', "no bonus created so no mail sent");
                return false;
            }
        }

        if(!empty($aid))
            $award = phive('Trophy')->getAward($aid);

        if ($is_sms === true) {
            /** @var Mosms $smsh */
            $smsh  = phive('Mosms');
        } else {
            $mh = $this->setFrom();
            $mh->unsubscribe_link = true;
        }

        foreach($users as $u){
            $user = is_object($u) ? $u : cu($u['id']);
            if(empty($user))
                continue;

            if(!empty($bonus)){
                if((int)$this->getUserDepositSum($u) < mc((int)$bonus['deposit_amount'], $user))
                    continue;
            }

            if($user->isBlocked() || !$this->voucherMailGateKeeper($trigger, $user)  || $this->isMailBlocked($u))
                continue;

            if(!empty($bonus) && phive('Bonuses')->allowCountry($bonus, $user) !== true)
                continue;

            if($check_bonus_block && $user->isBonusBlocked())
                continue;

            $replacers 	= $this->getDefaultReplacers($user);

            $replacers["__AMOUNT__"] 		= !empty($bonus) ? round(mc($bonus['reward'], $user) / 100) : $award['valid_days'];
            $replacers["__DAYS__"] 		= $award['valid_days'];
            $replacers["__COUNT__"] 		= $count;
            $replacers["__VOUCHERNAME__"] 	= $vname;
            $replacers["__VOUCHERCODE__"] 	= $vcode;
            $replacers["__GAME__"] 	        = $game['game_name'];
            $replacers["__SPINS__"] 	        = $bonus['reward'];

            if(!empty($bonus))
                phive('Bonuses')->insertCheck($user, $bonus['id']);

            $user->marketing_blocked = false;

            if ($is_sms === true) {
                if (empty($force_schedule) || !is_array($force_schedule)) {
                    $date = $time = null;
                } else {
                    $date = $force_schedule['date'];
                    $time = $force_schedule['time'];
                }
                $smsh->sendSMSPromo($trigger, $user, $date, $replacers, null, $time);
                phive('UserHandler')->logAction($user, "System sent mail with trigger: $trigger", "sms_reminders", false);
            } else {
                $mh->sendMail($trigger, $user, $replacers, null, $this->from, $this->from, $this->getCrmEmail(), null, self::PRIORITY_NEWSLETTER);
                phive('UserHandler')->logAction($user, "System sent SMS with trigger: $trigger", "mail_reminders", false);
            }

            phive('UserHandler')->logAction($user, $vcode, "voucher", false);
        }
    }

    function setFrom(){
        $this->from = phive("Config")->getValue('newsletter', 'from-email');
        $this->from_name = phive("Config")->getValue('newsletter', 'from-name');
        $mh = phive("MailHandler2");
        $mh->from_name = $this->from_name;
        return $mh;
    }

    function mailUnblockedBonusLovers(){
        $mh = $this->setFrom();
        $bonus = phive("Bonuses")->insertTemplate('bonus-templates', "weekly-bonuslovers");
        if(empty($bonus)){
            phive()->dumpTbl('bonusmails', "no bonus created so no mail sent");
            return;
        }
        $mail_trigger 	= "weekly-bonuslovers";
        $mh->unsubscribe_link = true;
        $users = phive("Bonuses")->getUnblockedBonusWhores();
        $users = $this->filterMarketingBlockedUsers($users);
        $this->addSupport($users);
        foreach($users as $u){

            $user 		= cu($u['id']);

            if(empty($user)){
                print_r($u);
                continue;
            }

            if($user->isBlocked() || $user->isBonusBlocked() || $this->isMailBlocked($u))
                continue;

            $replacers 	= $this->getDefaultReplacers($user);

            $replacers["__AMOUNT__"] 		= mc(100, $user);
            $replacers["__RELOADCODE__"] 	= $bonus['reload_code'];

            //TODO bonus email fix
            phive('Bonuses')->insertCheck($user, $bonus['id']);

            $user->marketing_blocked = false;
            $mh->sendMail($mail_trigger, $user, $replacers, null, $this->from, $this->from, null, null, self::PRIORITY_NEWSLETTER);

            $_SESSION['sent_to'][] = $u['email'];
        }
    }

    function mailVoucherToInactives($voucher_name, $mail_trigger, $amount_sql = " NOT NULL "){
        $date 			= phive()->hisMod("-30 day");

        $right_join_settings = $this->getConsentJoin($mail_trigger, 'u');

        $users 			= phive('SQL')->shs()->loadArray("
            SELECT u.*, fd.amount FROM users u
            LEFT JOIN first_deposits AS fd ON fd.user_id = u.id
            {$right_join_settings}
            WHERE u.last_login < '$date'
                AND fd.amount IS $amount_sql
                AND u.active = 1
            GROUP BY u.email LIMIT 0,10");
        // marketing blocked users are filtered in $this->mailVoucher
        $this->mailVouchers('voucher-templates', $voucher_name, $users, $mail_trigger);
    }

    function mailNonDepositors($days, $operator = '=', $mail_trigger = 'nodeposit-reminder', $setting = 'no-deposit-mail-times', $times = 6){
        $mh = $this->setFrom();

        $mh->unsubscribe_link = true;

        $default_lang 	= phive( 'Localizer' )->getDefaultLanguage();
        $mail 		= $mh->getMail($mail_trigger, $default_lang);

        if(empty($mail))
            return;

        $right_join_settings = $this->getConsentJoin($mail_trigger, 'u');

        $date = phive()->modDate('', "-$days day");
        $str  = "SELECT u.*, COALESCE( us.value, 0 ) AS mail_times
                 FROM users u
                 $right_join_settings
                 LEFT JOIN users_settings AS us ON ( u.id = us.user_id AND us.setting = '$setting' )
                 WHERE DATE(u.register_date) $operator '$date' AND active = 1
                 GROUP BY email
                 HAVING mail_times < $times";

        $users = phive('SQL')->shs()->loadArray($str);
        $users = $this->filterMarketingBlockedUsers($users);

        foreach($users as $u){
            if(in_array($u['email'], $_SESSION['sent_to']))
                continue;
            $has_deposited = phive('Cashier')->hasDeposited($u['id']);
            if(!$has_deposited){
                $user 	= cu($u['id']);
                if (!$user){
                  continue;
                }
                $user->setSetting($setting, $u['mail_times'] + 1);
                $user->marketing_blocked = false;
                $mh->sendMail($mail_trigger, $user, null, null, $this->from, $this->from, null, null, self::PRIORITY_NEWSLETTER);
                $_SESSION['sent_to'][] = $u['email'];
            }
        }
        $this->logEmailCount($mail_trigger);
    }

    /*
       no-deposit-3-days-fs
       no-deposit-6-days-fs
       no-deposit-9-days-fs
       no-deposit-12-days-fs
       no-deposit-15-days-fs
       no-deposit-30-days-fs
     */
    function mailNonDepositors2($days = '', $mail_trigger = '', $filter_closure = '', $operator = '=', $do_sent_to = true){
        $mail_trigger = empty($mail_trigger) ? "no-deposit-$days-days-fs" : $mail_trigger;
        $mh                   = $this->setFrom();
        $mh->unsubscribe_link = true;
        $default_lang 	      = phive('Localizer')->getDefaultLanguage();
        $mail 		      = $mh->getMail($mail_trigger, $default_lang);

        if(empty($mail))
            return;

        $right_join_settings = $this->getConsentJoin($mail_trigger, 'u');

        $date  = phive()->modDate('', "-$days day");
        $str   = "
            SELECT u.* FROM users u
            $right_join_settings
            WHERE DATE(register_date) $operator '$date'
            GROUP BY email
        ";
        $tmp   = phive('SQL')->shs('merge', '', null, 'users')->loadArray($str);

        if(!empty($filter_closure))
            $tmp = $filter_closure($tmp);

        $users = array();

        if($do_sent_to){
            foreach($tmp as $u){
                if(in_array($u['email'], $_SESSION['sent_to']))
                    continue;
                $has_deposited = phive('Cashier')->hasDeposited($u['id']);
                if(!$has_deposited){
                    $_SESSION['sent_to'][] = $u['email'];
                    $users[] = $u;
                }
            }
        }else{
            $users = array_filter($tmp, function($u){
                $has_deposited = phive('Cashier')->hasDeposited($u['id']);
                return $has_deposited == false;
            });
        }
        // marketing blocked users are filtered in $this->mailVoucher
        $this->mailVouchers('voucher-templates', "voucher-$mail_trigger", $users, $mail_trigger, date('dmy'), false); // We don't want to check bonus block
    }

    function getFreerollMailCountries($tag){
        //freeroll-deposit-mail and freeroll-no-deposit-mail
        return [
            explode(' ', phive('Config')->getValue('include-countries', $tag)),
            explode(' ', phive('Config')->getValue('exclude-countries', $tag))
        ];
    }

    function countryAllowed($user, $include, $exclude){
        $include = phive()->explode($include, ' ');
        $exclude = phive()->explode($exclude, ' ');
        if(!in_array(ud($user)['country'], $include) && !empty($include))
            return false;
        if(in_array(ud($user)['country'], $exclude))
            return false;
        return true;
    }

    function filterCountries($users, $include, $exclude){
        return array_filter($users, function($user) use($include, $exclude){
            return $this->countryAllowed($user, $include, $exclude);
        });
    }

    function filterFreerollMailCountries($users, $tag){
        list($include, $exclude) = $this->getFreerollMailCountries($tag);
        return $this->filterCountries($users, $include, $exclude);
    }

    function mailDepositorsFreeroll(){
        $users     = [];
        $threshold = (int)phive('Config')->getValue('thresholds', 'freeroll-deposit-mail');
        foreach($this->getDepositSums() as $r){
            if($r['amount'] < $threshold)
                continue;
            $u = cu($r['user_id']);
            if(!is_object($u))
                continue;
            if((int)$u->getSetting('deposit-freeroll-num') >= 3)
                continue;
            $users[] = $u;
            $u->incSetting('deposit-freeroll-num');
        }
        $mail_trigger = 'deposit-freeroll';
        $users        = $this->filterFreerollMailCountries($users, 'freeroll-deposit-mail');
        $this->addSupport($users);
        // marketing blocked users are filtered in $this->mailVoucher
        $this->mailVouchers('voucher-templates', "voucher-$mail_trigger", $users, $mail_trigger, date('dmy'));
    }

    function mailNonDepositorsFreeroll($days = 7){
        // marketing blocked users are filtered in $this->mailNonDepositors2 > $this->mailVoucher
        $this->mailNonDepositors2(
            $days,
            'no-deposit-freeroll',
            function($users){
                $users = $this->filterFreerollMailCountries($users, 'freeroll-no-deposit-mail');
                $ret = [];
                foreach($users as $ud){
                    $u = cu($ud);
                    if((int)$u->getSetting('no-deposit-freeroll-num') >= 3)
                        continue;
                    $u->incSetting('no-deposit-freeroll-num');
                    $ret[] = $ud;
                }
                return $ret;
            },
            '<',
            false
        );
    }

    /**
     * Return the callback based on mail_trigger which will run after the email was sent
     *
     * @param DBUser $user
     * @param string $mail_trigger
     * @return Closure|null
     */
    function mailSentCallback($user, $mail_trigger) {
        $user = cu($user);
        if (empty($user)) {
            return null;
        }

        switch ($mail_trigger) {
            case 'no-deposit-weekly':
            case 'no-deposit-weekly-2':
            $callback = function() use ($user) {
                phive('SQL')->incrValue('users_settings', 'value', "user_id = {$user->getId()} AND setting = 'no-deposit-mail-times'");
            };
            break;
            default:
                $callback = null;
        }

        return $callback;
    }

    /**
     * Send email to users who didn't deposit in past 7
     *
     * @param string $mail_trigger
     * @param string $setting
     */
    function mailNonDepositorsWeekly($mail_trigger = 'no-deposit-weekly', $setting = 'no-deposit-mail-times')
    {
        $date = phive()->modDate('', "-7 day");
        $excluded_countries = phive('SQL')->makeIn(phive('Config')->valAsArray('exclude-countries', $mail_trigger));

        $query = "
            SELECT u.*, COALESCE( us.value, 0 ) AS mail_times FROM users u
            " . $this->getConsentJoin($mail_trigger, 'u') . "
            LEFT JOIN users_settings AS us ON ( u.id = us.user_id AND us.setting = '$setting' )
            WHERE DATE(u.register_date) < '$date' AND active = 1 AND u.country NOT IN ({$excluded_countries})
            GROUP BY email
            HAVING mail_times < 6
        ";

        $this->mailDepositorsCommon($mail_trigger, $query);
    }

    function mailNonDepositorsWeekly2(){
        $this->mailNonDepositorsWeekly('no-deposit-weekly-2', 'no-deposit-mail-times-2');
    }

    function getInsertedTemplates($week){
        foreach(array(100, 200, 500) as $dep_amount){
            $bonus = phive("Bonuses")->insertTemplate('bonus-templates', "{$week}w-inactive-$dep_amount");
            if(empty($bonus))
                return array();
            $new_bonuses[$dep_amount] = $bonus;
        }
        return $new_bonuses;
    }

    function getBonusTemplateAmount($deps){
        $max = max(phive()->arrCol($deps, 'amount'));
        if($max <= 10000)
            $bamount = 100;
        else if($max >= 10001 && $max <= 20000)
            $bamount = 200;
        else
            $bamount = 500;
        return $bamount;
    }


    function getInactiveMailInfo($deps, $new_bonuses, $week, &$replacers, $user){
        $bamount 			= $this->getBonusTemplateAmount($deps);
        $mail_trigger 		        = "{$week}w-inactive-$bamount";
        $replacers["__AMOUNT__"] 	= mc($bamount, $user);
        $cur_bonus = $new_bonuses[$bamount];
        $replacers["__RELOADCODE__"] 	= $cur_bonus['reload_code'];
        if(!$this->countryAllowed($user, $cur_bonus['included_countries'], $cur_bonus['excluded_countries']))
            return false;
        phive('Bonuses')->insertCheck($user, $cur_bonus['id']);
        return $mail_trigger;
    }

    function getReminderInfo($b, &$replacers){
        $replacers["__PROGRESS__"] 		= phive("Bonuses")->progressPercent($b);
        $replacers["__REWARD__"] 		= number_format($b['reward'] / 100, 2);
        $replacers["__EXPIREDATE__"] 	= $b['end_time'];
    }

    function getInactiveData($u, $check_bonus_block = true){
        $user 		= cu($u['id']);

        if($user->isBlocked())
            return [];

        if($check_bonus_block && $user->isBonusBlocked())
            return [];

        $replacers 	= $this->getDefaultReplacers($user);
        $deps 		= $this->getUserDeposits($u);
        return array($user, $replacers, $deps);
    }


    /**
     * @param string|null $trigger
     * @param string $users_table
     * @param string $type
     * @return string
     */
    public function getConsentJoin(?string $trigger, string $users_table = 'users', string $type = 'email'): string
    {
        if (!$trigger) return '';

        /** @var PrivacyHandler $ph */
        $ph = phive('DBUserHandler/PrivacyHandler');

        $setting = $ph->getTriggerSettings(($type == 'sms') ? $ph::CHANNEL_SMS : $ph::CHANNEL_EMAIL, $trigger);
        if ($setting === null) return '';

        $max_rg_grs         = phive('Config')->getValue('grs', 'rg-grs-marketing-limit', 100);
        $countries_rg_grs   = phive('Config')->valAsArray('grs', 'rg-grs-marketing-countries');
        $countries_rg_grs   = phive('SQL')->makeIn($countries_rg_grs);
        $privacy_table      = $ph::PRIVACY_TABLE;
        $privacy_where      = $ph->getSettingWhereQuery($setting) . ' AND opt_in = 1';

        return <<<SQL
            LEFT JOIN config AS marketing_limit_countries ON (
                {$users_table}.country IN ({$countries_rg_grs})
                AND marketing_limit_countries.config_name = 'rg-grs-marketing-countries'
                AND marketing_limit_countries.config_tag = 'grs'
            )
            RIGHT JOIN (
                SELECT DISTINCT ups.user_id, IFNULL(rating.rating, 0) as rating
                FROM {$privacy_table} AS ups
                LEFT JOIN (
                    SELECT DISTINCT sub.user_id, rprl.rating
                    FROM (
                        SELECT user_id, MAX(id) as last_id FROM risk_profile_rating_log
                        WHERE rating_type = 'RG' GROUP BY user_id
                    ) as sub
                    LEFT JOIN risk_profile_rating_log AS rprl ON rprl.id = sub.last_id
                ) AS rating ON ups.user_id = rating.user_id
                WHERE {$privacy_where}
            ) AS usr on {$users_table}.id = usr.user_id
            AND (
                (marketing_limit_countries.id is not null AND usr.rating < {$max_rg_grs})
                OR marketing_limit_countries.id is null
            )
        SQL;
    }

    /**
     * Sends branded email reminders to users who meet specific conditions.
     *
     * This function identifies eligible users based on their activity and deposit status
     * and sends them tailored emails linked to their remote brand association.
     *
     * ### Process:
     * 1. Initializes the mail handler and fetches the email template for the given mail trigger.
     * 2. Determines the remote brand and its corresponding brand ID:
     *    - If SCV is activated, the ID will be `999`.
     *    - Otherwise, it will be:
     *        - `101` for VS.
     *        - `100` for MRV.
     *    - For `remote_brand_user_id`:
     *        - If SCV is not enabled, it will be `user_id`.
     *        - If SCV is enabled, it will be  `customer_id`.
     * 3. Queries the database to find eligible users:
     *    - Users must have made at least one deposit on local brand.
     *    - Excludes users from specific countries.
     *    - Filters users whose last deposit was more than 45 days ago.
     * 4. Filters out users who have opted out of marketing emails.
     * 5. Checks if the user has a remote brand account:
     *    - If they do, their deposit status in the remote brand is checked using `getFirstDeposit`:
     *        - **When SCV is not enabled**:
     *            - Checks if the VS user has a deposit on cross brand MR.
     *        - **When SCV is enabled**:
     *            - If the user has deposits on both MRV and MR, it returns the latest first deposit.
     *            - If the user has deposits only on MRV, it returns the first deposit details of MRV.
     *            - If the user has no deposits on MRV or MR, it returns `null`.
     *    - If the user does not have a remote brand account, they are considered eligible for the email.
     * 6. Sends the email and logs the action if successful.
     *
     * @param string $mail_trigger The identifier for the email template to be used, now it is using `welcome.mrvegas`.
     */
    public function mailBrandedDepositors($mail_trigger)
    {
        /** @var MailHandler2 $mh */
        $mh = $this->setFrom();

        $mh->unsubscribe_link = true;

        $default_lang = phive('Localizer')->getDefaultLanguage();
        $mail = $mh->getMail($mail_trigger, $default_lang);

        if (empty($mail)) {
            return;
        }

        $remote_brand   = getRemote();
        $remote_brand_id = distId($remote_brand);

        $excluded_countries = phive('SQL')->makeIn(phive('Config')->valAsArray('exclude-countries', $mail_trigger));

        $query = "SELECT users.*, MAX(d.timestamp) AS last_deposit, us.value AS remote_brand_user_id FROM users
                    LEFT JOIN first_deposits fd ON fd.user_id = users.id
                    LEFT JOIN deposits d ON users.id = d.user_id
                    LEFT JOIN users_settings us on users.id = us.user_id AND us.setting = 'c{$remote_brand_id}_id'
                    ". $this->getConsentJoin($mail_trigger) ."
                    WHERE fd.id IS NOT NULL AND users.active = 1 AND users.country NOT IN ({$excluded_countries})
                    GROUP BY users.email
                    HAVING last_deposit < NOW() - INTERVAL 45 DAY";

        $users = phive('SQL')->shs()->loadArray($query);

        $users = $this->filterMarketingBlockedUsers($users);

        foreach ($users as $user) {

            $can_send = false;

            //checking if user has a remote brand account
            if(!empty($user['remote_brand_user_id'])){

                $first_deposit = toRemote($remote_brand, 'getFirstDeposit', [$user['remote_brand_user_id']]);

                if(is_null($first_deposit['result'])){
                    //no deposit lifetime in remote brand so eligible for mail
                    $can_send = true;
                }

            } else{
                //no account exits in remote brand so eligible for mail
                $can_send = true;
            }

            if($can_send){
                $u_obj = cu($user['id']);
                $u_obj->marketing_blocked = $user['marketing_blocked'];
                $sent = $mh->sendMail($mail_trigger, $u_obj, null, null, $mh->from, $mh->from, $this->getCrmEmail(), null, self::PRIORITY_NEWSLETTER);

                if($sent){
                    phive('UserHandler')->logAction($user, "System sent mail with trigger: $mail_trigger", "mail_reminders", false);
                }
            }
        }
        $this->logEmailCount($mail_trigger);
    }

    /**
    * Bonus email targeting customers that have not  deposited in the last x days
    * x is gotten from a config
    *
    * @param int $num
    * @return mixed
    **/
    public function mailDepositors60days($num)
    {
        $mail_trigger    = "deposit-newbonusoffers-mail-$num";
        $mail_duration = phive('Config')->getValue('mails', 'enhanced-offer-duration', 30);
        $excluded_countries = phive('SQL')->makeIn(phive('Config')->valAsArray('exclude-countries', 'deposit-newbonusoffers-mail-x'));

        $query = "SELECT users.*, MAX(d.timestamp) as last_deposit FROM users
                    LEFT JOIN first_deposits fd ON fd.user_id = users.id
                    LEFT JOIN deposits d ON users.id = d.user_id
                    ". $this->getConsentJoin($mail_trigger) ."
                    WHERE fd.id IS NOT NULL AND users.active = 1 AND users.country NOT IN ({$excluded_countries})
                    GROUP BY users.email
                    HAVING MAX(d.timestamp) < NOW() - INTERVAL {$mail_duration} DAY";

        $this->mailDepositorsCommon($mail_trigger, $query, true);
    }

   /**
    * Bonus email targeting customers that have  deposited in the last 29 days
    *
    *
    * @param type $num
    * @return mixed
    **/
   public function mailDepositors29days($num)
   {
       $mail_trigger    = "29daydeposit-newbonusoffers-mail-$num";
       $excluded_countries = phive('SQL')->makeIn(phive('Config')->valAsArray('exclude-countries', '29daydeposit-newbonusoffers-mail-x'));

       $query = "SELECT users.*, MAX(d.timestamp) as last_deposit FROM users
                   LEFT JOIN first_deposits fd ON fd.user_id = users.id
                   LEFT JOIN deposits d ON users.id = d.user_id
                   ". $this->getConsentJoin($mail_trigger) ."
                   WHERE fd.id IS NOT NULL AND users.active = 1 AND users.country NOT IN ({$excluded_countries})
                   GROUP BY users.email
                   HAVING MAX(d.timestamp) > NOW() - INTERVAL 29 DAY";// query is inverse of mailDepositors60days

       $this->mailDepositorsCommon($mail_trigger, $query, true);
   }

    public function mailNonDepositors3days($num)
    {
        $date 	         = phive()->hisMod("-3 day");
        $mail_trigger    = "nodeposit-newbonusoffers-mail-$num";
        $excluded_countries = phive('SQL')->makeIn(phive('Config')->valAsArray('exclude-countries', 'nodeposit-newbonusoffers-mail-x'));

        $query = "SELECT users.* FROM users
            ". $this->getConsentJoin($mail_trigger) ."
            WHERE DATE(register_date) < '$date' AND (register_date > CURDATE() - INTERVAL 12 MONTH OR last_login > NOW() - INTERVAL 12 MONTH)
            AND active = 1 AND users.country NOT IN ({$excluded_countries})
            GROUP BY email";

        $this->mailDepositorsCommon($mail_trigger, $query);
    }

    /**
     * Convert country_version from 'SE:trigger1,DK:trigger2' to [SE => trigger1, DK => trigger2]
     *
     * @param array $template_arr
     * @return array
     */
    public function setupCountryVersion(array $template_arr): array
    {
        if (empty($template_arr['country_version'])) {
            return $template_arr;
        }

        $country_version = explode(',', $template_arr['country_version']);
        $template_arr['country_version'] =  array_reduce($country_version, function ($carry, $item) {
            [$country, $trigger] = explode(':', $item);
            $carry[$country] = $trigger;
            return $carry;
        }, []);

        return $template_arr;
    }

    /**
     * Handle deposit related emails
     *
     * @param string $mail_trigger
     * @param string $query
     * @param bool   $skip_deposits_check
     *
     * @return void
     */
    public function mailDepositorsCommon(string $mail_trigger, string $query, bool $skip_deposits_check = false): void
    {
        $users_start = phive('SQL')->shs('merge', '', null, 'users')->loadArray($query);
        $template_arr = phive('Bonuses')->templateToArr('bonus-templates', $mail_trigger);
        if (empty($template_arr)) {
            return;
        }
        $template_arr = $this->setupCountryVersion($template_arr);
        $users = $this->filterCountries($users_start, $template_arr['included_countries'], $template_arr['excluded_countries']);
        if (empty($users)) {
            return;
        }
        $get_templates = function () use ($mail_trigger, $template_arr) {
            $country_version = [
                'default' => phive("Bonuses")->insertTemplate('bonus-templates', $mail_trigger)
            ];
            if (empty($country_version['default'])) {
                return [];
            }

            foreach ($template_arr['country_version'] as $country => $trigger) {
                $bonus = phive("Bonuses")->insertTemplate('bonus-templates', $trigger, $country_version['default']);
                if (!empty($bonus)) {
                    $country_version[$country] = $bonus;
                }
            }

            //We need to input at least one flag so that unauthorized people can't use the bonus
            foreach ($country_version as $item) {
                $insert_arr = ['user_id' => 0, 'flag' => "bonus-{$item['id']}"];
                if (phive('SQL')->isSharded('user_flags')) {
                    phive('SQL')->loopShardsSynced(function ($db) use ($insert_arr) {
                        $db->insertArray('user_flags', $insert_arr);
                    });
                } else {
                    phive('SQL')->insertArray('user_flags', $insert_arr);
                }
            }

            return $country_version;
        };

        $abort_logic = function ($user, $deps, $test) use ($skip_deposits_check) {
            //They must never have deposited.
            return !(!empty($deps) && !$test && !$skip_deposits_check);
        };

        $get_trigger = function ($deps, $new_bonuses, $user, &$replacers) use ($mail_trigger) {
            $nb = $new_bonuses[ud($user)['country']] ?? $new_bonuses['default'];
            if (empty($nb)) {
                return null;
            }
            $award = phive('Trophy')->getAward($nb['award_id']);
            $bonus_award = phive('Bonuses')->getBonus($award['bonus_id']);
            $bonus_game = phive('MicroGames')->getByGameId($bonus_award['game_id']);
            $replacers["__GAME__"] = $bonus_game['game_name'];
            $replacers["__WAGERREQ__"] = $nb['rake_percent'] / 100;
            $replacers["__AMOUNT__"] = mc($nb['deposit_limit'] / 100, $user);
            $replacers["__EXTRAAMOUNT__"] = $award['amount'];
            $content_alias = $award['type'] === 'freespin-bonus' ? 'free.spin' : 'free.cash';
            $replacers["__EXTRA__"] = t($content_alias, $user->getLang());
            $replacers["__RELOADCODE__"] = $nb['reload_code'];
            phive('Bonuses')->insertCheck($user, $nb['id']);

            return empty($replacers["__RELOADCODE__"]) ? null : $mail_trigger;
        };

        $this->mailInactivesCommon($users, $get_templates, $abort_logic, $get_trigger, false);
        $this->logEmailCount($mail_trigger);
    }

    /**
     * Get and log email count for a specific mail trigger
     * helps us know when emails stop sending for some reason
     * @param string $mail_trigger
     * @return void
     */
    public function logEmailCount($mail_trigger){

       $inactive_trigger_array = ['1w-inactive', '2w-inactive'];
       $triggers = [$mail_trigger];

       //check if mail trigger is an inactive mail trigger
       if(in_array($mail_trigger, $inactive_trigger_array)){
            $triggers = $this->getInactiveMailCurrentTriggers($mail_trigger);
       }

       foreach($triggers as $mail_trigger){
            //Get email count from cache after all emails are sent
            $email_count_for_trigger = phMgetArr("email-count-{$mail_trigger}") ?? 0;
            phive("Logger")->logPromo("{$mail_trigger}", "Execution finished with count {$email_count_for_trigger}", true);
            //delete count for trigger
            phMdel("email-count-{$mail_trigger}");
       }

    }

    /**
     * Get current append for an inactive mail trigger
     * @param string $mail_trigger
     * @return string
     */
    public function getInactiveMailCurrentTriggers($mail_trigger){
        //array of known append values for inactive mail triggers
        $trigger_appends = [100, 200, 500];
        $triggers = [];
        foreach($trigger_appends as $append){
            //check for inactive mail trigger with append
            $email_trigger = phMgetArr("email-count-{$mail_trigger}-{$append}") ? "{$mail_trigger}-{$append}": null;
            if($email_trigger){
                //add to list of active triggers
                 $triggers[] = $email_trigger;
            }
        }

        $triggers = count($triggers) > 0 ? $triggers : [$mail_trigger];
        return $triggers;
    }

    /**
     * Common logic for sending emails to inactive users
     *
     * @param array $users
     * @param $get_templates
     * @param $abort_logic
     * @param $get_trigger
     * @param bool $check_bonus_block
     * @return void
     */
    public function mailInactivesCommon($users, $get_templates, $abort_logic, $get_trigger, $check_bonus_block = true): void
    {
        $users = $this->filterMarketingBlockedUsers($users);
        phive("Logger")->logPromo('mailInactivesCommon', "Filter finished");
        $this->addSupport($users);
        $mh = $this->setFrom();
        //closure as parameter, we try and skip the week parameter
        $new_bonuses = $get_templates();
        if (empty($new_bonuses)) {
            phive()->dumpTbl('bonusmails', "no bonus created so no mail sent");
            return;
        }
        $admin = cu('admin');
        $mh->unsubscribe_link = true;
        $support = phive('UserHandler')->getUserByEmail($mh->getSetting('support_mail'))->data;
        if (!empty($support)) {
            $this->mailInactiveUser($support, $new_bonuses, $admin, $mh, $abort_logic, $get_trigger, true, $check_bonus_block);
        }
        foreach ($users as $u) {
            $this->mailInactiveUser($u, $new_bonuses, $admin, $mh, $abort_logic, $get_trigger, false, $check_bonus_block);
        }

    }


    /**
     * Common logic to send emails to inactive users
     *
     * @param $u
     * @param $new_bonuses
     * @param $admin
     * @param MailHandler2 $mh
     * @param $abort_logic
     * @param $get_trigger
     * @param bool $test
     * @param bool $check_bonus_block
     */
    public function mailInactiveUser($u, $new_bonuses, $admin, $mh, $abort_logic, $get_trigger, $test = false, $check_bonus_block = true): void
    {
        if (in_array($u['email'], $_SESSION['sent_to'], false)) {
            return;
        }

        if ($this->isMailBlocked($u)) {
            return;
        }

        [$user, $replacers, $deps] = $this->getInactiveData($u, $check_bonus_block);
        //closure that if it evaluates to false returns
        if (empty($user)) {
            return;
        }

        if (!$abort_logic($user, $deps, $test)) {
            return;
        }

        $bonuses = phive("Bonuses")->getUserBonuses($u['id'], '', " = 'active'");

        if (empty($bonuses)) {
            $mail_trigger = $get_trigger($deps, $new_bonuses, $user, $replacers);

            if (empty($mail_trigger)) {
                return;
            }

            $user->marketing_blocked = $u['marketing_blocked'];
            $mh->sendMail($mail_trigger, $user, $replacers, null, $this->from, $this->from, $this->getCrmEmail(), null, self::PRIORITY_NEWSLETTER);
            phive('UserHandler')->logAction($user, "System sent mail with trigger: $mail_trigger", "mail_reminders", false);

            if (!$test) {
                $_SESSION['sent_to'][] = $u['email'];
            }
        }
    }

    function getInactiveWeekAbortLogic(){
        return function($user, $deps, $test){
            if(empty($deps) && !$test)
                return false;
            return true;
        };
    }

    function getInactiveWeekTrigger($week){
        return function($deps, $new_bonuses, $user, &$replacers) use ($week){
            $mail_trigger = $this->getInactiveMailInfo($deps, $new_bonuses, $week, $replacers, $user);
            if(empty($replacers["__AMOUNT__"]) || empty($replacers["__RELOADCODE__"]))
                return null;
            return $mail_trigger;
        };
    }

    function mailInactivesSecondWeek(){
        $date 			= phive()->hisMod("-14 day");
        $excluded_countries = phive('SQL')->makeIn(phive('Config')->valAsArray('exclude-countries', '2w-inactive-x'));
        $users 			= phive('SQL')->shs('merge', '', null, 'users')->loadArray("SELECT * FROM users WHERE last_login < '$date' AND active = 1 AND users.country NOT IN ({$excluded_countries}) GROUP BY email");
        // marketing blocked users are filtered in $this->mailInactivesCommon
        $this->mailInactivesCommon(
            $users,
            function(){ return $this->getInsertedTemplates(2); },
            $this->getInactiveWeekAbortLogic(),
            $this->getInactiveWeekTrigger(2)
        );
        $this->logEmailCount('2w-inactive');
    }

    function mailInactivesFirstWeek(){
        $sdate 			= phive()->hisMod("-14 day");
        $edate 			= phive()->hisMod("-1 day");
        $excluded_countries = phive('SQL')->makeIn(phive('Config')->valAsArray('exclude-countries', '1w-inactive-x'));
        $str			= "SELECT * FROM users WHERE last_login > '$sdate' AND last_login < '$edate' AND active = 1 AND users.country NOT IN ({$excluded_countries}) GROUP BY email";
        $users 			= phive('SQL')->shs('merge', '', null, 'users')->loadArray($str);
        // marketing blocked users are filtered in $this->mailInactivesCommon
        $this->mailInactivesCommon(
            $users,
            function(){ return $this->getInsertedTemplates(1); },
            $this->getInactiveWeekAbortLogic(),
            $this->getInactiveWeekTrigger(1)
        );
        $this->logEmailCount('1w-inactive');
    }

    function voucherMailGateKeeper($trigger, $user, $thold = 3){
        if(!in_array($trigger, array('monthly-week3', 'monthly-week2')))
            return true;
        $cnt = $user->getSetting("$trigger-num") + 1;
        $user->setSetting("$trigger-num", $cnt);
        return $cnt < $thold + 1;
    }

    /**
     * @param $users
     * @param bool $only_ids
     * @return array
     */
    function filterMarketingBlockedUsers($users, $only_ids = false) {
        $users = array_map('ud', $users);
        $blocked_users = phive()->flatten(lics('getMarketingBlockedUsers', [$users], true), true);

        foreach ($users as $key => $user) {
            if (in_array($user['id'], $blocked_users)) {
                unset($users[$key]);
                continue;
            }
            $users[$key]['marketing_blocked'] = false;
        }

        $users = array_values($users);

        if ($only_ids) {
            return array_column($users, 'id');
        }
        return $users;
    }
    // CRON Mails end here, moved from DBUserHandler

    /**
     * Return the notification email address
     *
     * @return string
     */
    public function getNotificationAddress()
    {
        return "notifications@" . $this->getSetting('domain') . ".com";
    }

    /**
     *
     * Sample config for priorities:
     *
     *  'mailer.provider' => [
     *       'priority_map' => [
     *           1 => 'SES'
     *           3 => 'SparkPost'
     *       ],
     *       'default' => 'SMTP'
     *  ]
     *
     * @param $recipient_emails
     * @param $reply_to
     * @param $subject
     * @param $html_body
     * @param $sender_email
     * @param string|null $sender_name
     * @param null $queued_at
     * @param null $user_id
     * @param bool $log_it
     * @param null $cc_emails
     * @param null $bcc_emails
     * @param int $priority
     * @return mixed
     */
    public function sendEmail(
        $recipient_emails,
        $reply_to,
        $subject,
        $html_body,
        $sender_email,
        $sender_name = null,
        $priority = 0,
        $queued_at = null,
        $user_id = null,
        $log_it = true,
        $cc_emails = null,
        $bcc_emails = null
    )
    {
        try {
            $supplier_map = $this->getSetting('mailer.provider');
            $supplier = $supplier_map['priority_map'][$priority] ?? $supplier_map['default'];

            /** @var SES $module */
            $module = phive("Mailer/{$supplier}");
            if (empty($module) || !method_exists($module, 'sendEmail')) {
                error_log("Mail supplier {$supplier} not found");
                return false;
            }

            $plaintext_body = html_entity_decode(strip_tags($html_body), ENT_QUOTES, "UTF-8");

            return $module->sendEmail($recipient_emails, $reply_to, $subject, $html_body, $plaintext_body, $sender_email,
                $sender_name, $queued_at, $user_id, $log_it, $cc_emails, $bcc_emails);

        } catch (Exception $e) {
            error_log("Email send error: {$e->getMessage()}");
            return false;
        }
    }

    private function getCrmEmail(): ?string
    {
        $email = phive('MailHandler2')->getSetting('crm_emails');
        if (empty($email)) $email = 'crmemails@immensegroup.com';

        return $email;
    }

    /**
     * Sends an email for a seasonal promotion and saves the participation info.
     *
     * This function processes a seasonal promotion form submission, checks if the
     * email is already registered for the selected promotion, and triggers an email
     * if applicable. It also stores the user's participation details in the database.
     *
     * Workflow:
     * 1. Decode the submitted form data from JSON.
     * 2. Extract email, mobile number, and promotion details.
     * 3. Check if the email already exists in the `mails_promo_contact` table.
     *    - If it exists, return a response indicating the email is already registered.
     *    - Otherwise, proceed with sending an email.
     * 4. Determine the appropriate email trigger key and send the promotional email.
     * 5. If storing participation is enabled, save the user’s details in the database.
     *
     * @return void Outputs a JSON response indicating success or email duplication.
     */
    public function sendEmailForSeasonalPromotion()
    {

        if (!isset($_POST['saveSeasonalPromotion'])) {
            return;
        }

        $json_data = $_POST['saveSeasonalPromotion'];
        $form_data = json_decode($json_data, true);

        // Check if JSON decoding failed
        if (json_last_error() !== JSON_ERROR_NONE) {
            return;
        }

        $email = strtolower($form_data['email']);
        $promotions = phive('MailHandler2')->getSetting('seasonal_promotions_partner');
        $promotion_theme = $promotions['PROMOTION_THEME'][strtoupper($form_data['tag'])];
        $privacy = $form_data['privacy'];
        $age = $form_data['age'];
        $description = "User agreed on privacy policy ($privacy) and confirmed 18 years or older ($age)";

        $countryPrefix = preg_replace("/[^0-9]/", "", $form_data['country_prefix']);
        $mobileNumber = preg_replace("/[^0-9]/", "", $form_data['mobile']);
        $fullMobileNumber = $countryPrefix . $mobileNumber;
        $sql = "SELECT mail FROM mails_promo_contact WHERE mail = '{$email}' AND tag = '{$promotion_theme}'";
        $mailExistingRecords = phive('SQL')->loadArray($sql);

        // check if email is already exit or not
        if ($email) {
            if (count($mailExistingRecords) > 0) {
                echo json_encode([
                    'status' => 'emailExit',
                ]);
                exit();
            } else {
                echo json_encode([
                    'status' => 'success'
                ]);
            }
        }

        $tag = strtolower($form_data['tag']);

        if ($tag === 'bandy' && isset($form_data['bandySelection'])) {
            $bandySelection = $form_data['bandySelection'];
            $replacers['__GOALSPREDICTIONS__'] = $bandySelection;
            $description .= " with predicted goals: $bandySelection";
        }

        $replacers['__FIRSTNAME__'] = $email;
        $mailTrigger = null;
        $promotionPartnerTag = $promotion_theme;

        if (!empty($promotions['TRIGGER_KEY'])) {
            foreach ($promotions['TRIGGER_KEY'] as $key => $triggerKey) {
                if (strtolower($key) === $tag) {
                    $mailTrigger = $triggerKey;
                    break;
                }
            }
        }

        if ($mailTrigger !== null) {
            $this->sendMailToEmail($mailTrigger, $email, $replacers);
        }

        // to save information in mails_promo_contact table
        $saveInfo = $promotions['STORE_PARTICIPATION_ENABLED'];
        if ($saveInfo && $mailTrigger) {
            $target = $promotionPartnerTag;
            $licJurisdiction = licJur();

            $action = [
                'mobile' => $fullMobileNumber,
                'mail' => $email,
                'tag' => $target,
                'country' => $licJurisdiction,
                'descr' => $description,
                'extra' => $mailTrigger,
                'created_at' => date('Y-m-d H:i:s'),
            ];
            phive('SQL')->insertArray('mails_promo_contact', $action);
        }
    }

    /**
     * Log mail sent action
     *
     * @param User $user
     * @param string $trigger
     * @param bool $sent
     * @return void
     */
    public function logMailSent(User $user, string $trigger, bool $sent): void
    {
        if ($sent) {
            /** @var SQL $sql */
            $sql = phive('SQL');
            $tbl = self::MAIL_SENT_LOG_TBL;

            $find = $sql->sh($user->getId())
                ->getValue("SELECT id FROM {$tbl} WHERE mail_trigger = '{$trigger}' AND user_id = {$user->getId()}");

            $data = ['user_id' => $user->getId(), 'mail_trigger' => $trigger, 'updated_at' => date('Y-m-d h:i:s')];

            if (empty($find)) $sql->sh($user->getId())->insertArray($tbl, $data);
            else $sql->sh($user->getId())->updateArray($tbl, $data, "id = {$find}");
        } else {
            phive('Logger')->info("User {$user->getId()} did not get email with trigger '{$trigger}'");
        }
    }
}
