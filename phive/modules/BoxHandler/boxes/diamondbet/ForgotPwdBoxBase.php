<?php
require_once __DIR__.'/../../../../../diamondbet/boxes/DiamondBox.php';
class ForgotPwdBoxBase extends DiamondBox{

    /**
     *
     */
    public function init()
    {
        $this->handlePost(array('site_type'));

        $is_email_ok = !empty($_POST['forgotform-email']);
        $is_username_ok = !empty($_POST['forgotform-username']);
        $is_dob_ok = !empty($_POST['forgotform-year']);

        if (licSetting('forgotten_password_security_questions')) { // If must check security question & answer
            $is_security_ok = !empty($_POST['forgotform-securityquestion']) && !empty($_POST['forgotform-securityanswer']);

            if ($is_email_ok && $is_dob_ok && $is_security_ok) { // If 1st form seems ok
                $this->resetCommon('email', 'getUserByEmail', 'emailUsername');
            } elseif ($is_username_ok && $is_dob_ok && $is_security_ok) { // If 2nd form seems ok
                $this->resetCommon('username', 'getUserByUsername', 'resetPwd');
            } else { // Error, no form seems ok
                $this->err = 'forgot.pwd.missing';
            }
        } else { // If must NOT check security question & answer
            if ($is_email_ok && $is_dob_ok) { // If 1st form seems ok
                $this->resetCommon('email', 'getUserByEmail', 'emailUsername');
            } elseif ($is_username_ok && $is_dob_ok) { // If 2nd seems ok
                $this->resetCommon('username', 'getUserByUsername', 'resetPwd');
            } else { // Error, no form seems ok
                $this->err = 'forgot.pwd.missing';
            }
        }
    }

    /**
     * @param $user
     * @return bool
     */
    private function checkForgotForm($user): bool
    {
        $requested_captcha = empty($_REQUEST['forgotform-username']) ? 'username' : 'password';
        $attribute = $_REQUEST['forgotform-username'] ?? $_REQUEST['forgotform-email'];
        if (limitAttempts('forgot-password-captcha', $attribute, 30)) {
            phive()->dumpTbl('forgot-password', ['Error', 'Too many attempts, limited.', remIp()]);
            return false;
        }

        if (PhiveValidator::captchaCode(true, $requested_captcha) != $_POST['captcha']) {
            phive()->dumpTbl('forgot-password', ['Error', 'Failed captcha.', remIp(), PhiveValidator::captchaCode(), $_POST['captcha']]);
            return false;
        }

        $dob_key = "{$_POST['forgotform-year']}-{$_POST['forgotform-month']}-{$_POST['forgotform-day']}";
        $is_dob_ok = $user->checkDob($dob_key);

        if (!$is_dob_ok) {
            phive()->dumpTbl('forgot-password', ['Error', 'Failed DOB.', remIp(), $dob_key]);
        }

        $is_secret_ask_ok = true;
        if (licSetting('forgotten_password_security_questions')) {
            $is_secret_ask_ok = $user->checkSecretAnswer(
                $_POST['forgotform-securityquestion'] ?? '',
                $_POST['forgotform-securityanswer'] ?? ''
            );
        }

        phive()->dumpTbl('forgot-password', ['Success', remIp(), $user->getId()]);

        return $is_dob_ok && $is_secret_ask_ok;
    }

  /**
   * The error message must be the same due to OWASP A2:2017 (Enumeration attacks):
   * We are keeping the old error message commented for an easier understanding of the real reason
   * By returning a dedicated response a malicious user could find valid username of users
   * by receiving a different error message when the user existed.
   * @param string $attr
   * @param Callable $get_func
   * @param Callable $reset_func
   */
    public function resetCommon($attr, $get_func, $reset_func)
    {

        if ($attr === 'email') {
            $validator = PhiveValidator::start($_POST['forgotform-' . $attr]);
            if ($validator->email()->error) {
                $this->err = $validator->error;
                return;
            }
        }

        if (!empty($_POST["forgotform-" . $attr]) && !empty($_POST['forgotform-year'])) {
            $user = phive('UserHandler')->$get_func($_POST['forgotform-' . $attr]);
            if (is_object($user)) {
                if ($this->checkForgotForm($user)) {
                    phive('UserHandler')->$reset_func($_POST['forgotform-' . $attr]);
                } else {
                    $this->err = 'forgot.pwd.missing';
                }
            } else {
                $this->err = 'forgot.pwd.missing';
            }
        } else {
            $this->err = 'forgot.pwd.missing';
        }
    }

    /**
     * Print 3 drop-down selection controls for day of birth
     *
     */
    private function printDob()
    {
        $fc = new FormerCommon();
        dbSelect(
            "forgotform-year",
            $fc->getYears(18),
            empty($_POST['forgotform-year']) ? '' : $_POST['forgotform-year'],
            array('', t('year'))
        );
        dbSelect("forgotform-month", $fc->getMonths(), $_POST['forgotform-month'], array('', t('month')));
        dbSelect("forgotform-day", $fc->getDays(), $_POST['forgotform-day'], array('', t('day')));
    }

    /**
     * Print the forgot-email form and the forgot-password form
     *
     */
    private function printForgotForms()
    {
        $username_captcha_image = PhiveValidator::captchaImg('username', true);
        $password_captcha_image = PhiveValidator::captchaImg('password', true);
        $this->printForgotForm('email', 'username', $username_captcha_image);
        $this->printForgotForm('username', 'password', $password_captcha_image);
    }

    /**
     * Print a Forgot form about username or password
     *
     * @param string $key Input key: 'email' or 'username'
     * @param string $fkey Form key: 'username' or 'password
     * @param string $captcha_src Captcha src URL: Generated URL pointing to captcha image
     *
     */
    private function printForgotForm($key, $fkey, $captcha_src)
    {
        ?>
        <form method="post">
            <input type="hidden" name="token" value="<?echo $_SESSION['token'];?>">
            <table class="registerform">
                <tr>
                    <td><?php et("forgot.$fkey.$key") ?></td>
                </tr>
                <tr>
                    <td class="forgotform-content">
                        <p><?php 
                            $maxlength_setting = '';
                            if ($key === 'email' || $key === 'username') {
                                $maxlength_setting = lic('getMaxLengthAttribute', ['email']);
                            }
                            dbInput("forgotform-" . $key, '', 'text', '', $maxlength_setting, true, false, false, t("forgotform-placeholder-".$key)) 
                        ?></p>
                        <p class="forgotform-dob"><?php $this->printDob() ?></p>
                        <?php
                        if (licSetting('forgotten_password_security_questions')) {
                            $security_questions = [
                                'q1' => t('registration.security.question1'),
                                'q2' => t('registration.security.question2'),
                                'q3' => t('registration.security.question3')
                            ];
                            ?>
                            <p>
                                <label for="security_question">
                                    <?php dbSelect(
                                       "forgotform-securityquestion",
                                       $security_questions,
                                       '',
                                       ['', t('register.security.question')],
                                       null,
                                       false,
                                       "style='width: 250px;'"
                                    ) ?>
                                </label>
                            </p>
                            <p>
                                <label for="security_answer">
                                    <input
                                        name="forgotform-securityanswer"
                                        type="text" autocapitalize="off"
                                        autocorrect="off"
                                        autocomplete="off"
                                    />
                                </label>
                            </p>
                            <?php
                        }
                        ?>
                        <p><img id="captcha_img_<?=$fkey?>" src="<?=$captcha_src?>"></p>
                        <p>
                            <label class="captcha-container" for="captcha-<?=$fkey?>">
                                <input type="text" class="captcha-input" name="captcha" id="captcha-<?=$fkey?>" value="" placeholder="<?php et("forgot.password.captcha") ?>"/>
                                <input
                                        type="button"
                                        onclick="licFuncs.resetCaptcha('<?=$fkey?>')"
                                        name="reset-captcha"
                                        class="reset-captcha"
                                        id="reset-captcha-<?=$fkey?>"
                                        value="<?php echo t("reset.code") ?>"
                                />
                            </label>
                        </p>
                        <input
                            class="btn btn-l btn-default-l"
                            type="submit"
                            name="submit"
                            value="<?php echo t("submit") ?>"
                        />
                    </td>
                </tr>
            </table>
        </form>
        <br/>
        <?php
    }

    /**
     *
     */
    private function printForgotFormArea()
    {
          ?>
          <div class="forgot-pwd-forms">
              <?php $this->printForgotForms() ?>
          </div>
          <?php
    }

    /**
     *
     */
    private function printExplain()
    {
        ?>
        <div class="forgot-pwd-explain">
            <?php et('forgot.pwd.info.html') ?>
            <?php if(!empty($_POST['submit'])): ?>
                <?php if(empty($this->err)): ?>
                    <h3><?php et('forgot.pwd.success') ?></h3>
                <?php else: ?>
                    <p class="errors">
                        <?php et($this->err) ?>
                    </p>
                <?php endif ?>
            <?php endif ?>
        </div>
        <?php
    }

    /**
     *
     */
    protected function printForgotContent()
    {
        if ($this->site_type == 'mobile') {
            $this->printExplain();
            $this->printForgotFormArea();
        } else {
            $this->printForgotFormArea();
            $this->printExplain();
        }
    }

    /**
     *
     */
    public function printHTML()
    {
        ?>
        <div class="profile-content">
            <div class="forgot-password">
                <?php $this->printForgotContent() ?>
            </div>
        </div>
        <?php
    }

    /**
     *
     */
    public function printExtra()
    {
        ?>
        <p>
            <label>Site Type (mobile/normal): </label>
            <input type="text" name="site_type" value="<?=$this->site_type?>" />
        </p>
        <?php
    }

}
