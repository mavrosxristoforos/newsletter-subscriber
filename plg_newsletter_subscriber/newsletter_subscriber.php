<?php
/*------------------------------------------------------------------------
# newsletter_subscriber - Newsletter Subscriber
# ------------------------------------------------------------------------
# author    Christopher Mavros - Mavxr.com
# copyright Copyright (C) 2008 Mavxr.com. All Rights Reserved.
# @license - http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
# Websites: https://mavxr.com
# Technical Support:  Forum - https://mavxr.com/support/forum
-------------------------------------------------------------------------*/

// no direct access
\defined( '_JEXEC' ) or die( 'Restricted access' );

use \Joomla\CMS\Factory;
use \Joomla\CMS\Plugin\CMSPlugin;
use \Joomla\CMS\Plugin\PluginHelper;
use \Joomla\CMS\Captcha\Captcha;
use \Joomla\CMS\Mail\MailHelper;
use \Joomla\CMS\Session\Session;
use \Joomla\CMS\Uri\Uri;

class plgContentNewsletter_subscriber extends CMSPlugin {

  protected $autoloadLanguage = true;

  public function onContentPrepare($context, &$row, &$params, $page = 0) {
    if (is_object($row)) {
        $text = &$row->text;
    }
    else {
      $text = &$row;
    }

    if (strpos($text, '{newsletter_subscriber}') === false) {
      return true;
    }
    // Because $params is one of the function's arguments.
    $pluginParams = $this->params;

    $app = Factory::getApplication();
    $input = method_exists($app, 'getInput') ? $app->getInput() : $app->input;
    $session = method_exists($app, 'getSession') ? $app->getSession() : Factory::getSession();

    // Notification Options
    $recipient = $pluginParams->get('email_recipient', '');
    $fromName = $pluginParams->get('from_name', 'Newsletter Subscriber');
    $fromEmail = $pluginParams->get('from_email', 'newsletter_subscriber@yoursite.com');
    $subject = $pluginParams->get('subject', 'New subscription to your site!');
    $sendingWithSetEmail = $pluginParams->get('sending_from_set', true);

    // Form Options
    // Security: only a sanitized identifier is accepted from the request. (Fixes reflected XSS.)
    $unique_id = $input->post->get('ns_uniqid', '', 'cmd');
    if ($unique_id === '') {
      $unique_id = preg_replace('/[^A-Za-z0-9_.\-]/', '', (string) $pluginParams->get('unique_id', '')) . uniqid();
    }

    // Texts
    $namePlaceholder = $pluginParams->get('name_placeholder', 'Name');
    $emailPlaceholder = $pluginParams->get('email_placeholder', 'email@site.com');
    $buttonText = $pluginParams->get('button_text', 'Subscribe to Newsletter');
    $pageText = $pluginParams->get('page_text', 'Thank you for subscribing to our site.');
    // 'errot_text' is kept as a fallback for installations that saved the old, misspelled key.
    $errorText = $pluginParams->get('error_text', $pluginParams->get('errot_text', 'Your subscription could not be submitted. Please try again.'));
    $noName = $pluginParams->get('no_name', 'Please write your name');
    $noEmail = $pluginParams->get('no_email', 'Please write your email');
    $invalidEmail = $pluginParams->get('invalid_email', 'Please write a valid email');
    $antiSpamError = $pluginParams->get('anti_spam_error', 'Please answer the anti-spam question correctly.');
    $honeypotLabel = $pluginParams->get('honeypot_label', 'Leave this field empty');
    $invalidTokenError = $pluginParams->get('invalid_token_error', 'Your session has expired. Please try submitting the form again.');
    $mailNameText = $pluginParams->get('mail_name_text', 'Name: %s');
    $mailEmailText = $pluginParams->get('mail_email_text', 'Email: %s');
    $mailIpText = $pluginParams->get('mail_ip_text', 'IP: %s');

    // Sizes & Colors
    $nameWidth = $pluginParams->get('name_width', '12');
    $emailWidth = $pluginParams->get('email_width', '12');
    $buttonWidth = $pluginParams->get('button_width', '100');
    $thanksTextColor = $pluginParams->get('thank_text_color', '#000000');
    $errorTextColor = $pluginParams->get('error_text_color', '#000000');

    // Mailing List
    $saveList = $pluginParams->get('save_list', true);
    $savePath = $pluginParams->get('save_path', 'mailing_list.txt');

    // Anti-Spam
    $enable_anti_spam = $pluginParams->get('enable_anti_spam', '1');
    $myAntiSpamQuestion = $pluginParams->get('anti_spam_q', 'How many eyes has a typical person? (ex: 1)');
    $myAntiSpamAnswer = $pluginParams->get('anti_spam_a', '2');
    $anti_spam_position = $pluginParams->get('anti_spam_position', 0);

    // Advanced
    $class_suffix = $pluginParams->get('class_sfx', '');
    $document = $app->getDocument();
    $document->addStyleDeclaration('
      .ns .input-group input.ns{max-width: 92%; margin-bottom: 8px; position: static; flex: none; width: 92%; }
      .ns .g-recaptcha {margin-bottom: 5px;}
      .ns .ns-vh{position:absolute;width:1px;height:1px;margin:-1px;padding:0;overflow:hidden;clip:rect(0,0,0,0);white-space:nowrap;border:0;}
      .ns .ns_hp_wrap{position:absolute !important;left:-9999px;top:auto;width:1px;height:1px;overflow:hidden;}
    '.$pluginParams->get('customcss', ''));

    $myReplacement = "";
    $myError = "";
    $errors = 3;
    $postedName = $input->post->get('m_name'.$unique_id, null, 'string');
    $postedEmail = $input->post->get('m_email'.$unique_id, '', 'string');
    if ($postedName !== null) {
      $errors = 0;

      // Cross-Site Request Forgery protection.
      if (!$input->post->getInt(Session::getFormToken(), 0)) {
        $myError = '<span style="color: '.$errorTextColor.';">' . $invalidTokenError . '</span><br/>';
      }

      if ($enable_anti_spam == '1') {
        $antiSpamAnswer = $input->post->get('ns_anti_spam_answer'.$unique_id, '', 'string');
        if (strtolower($antiSpamAnswer) != strtolower((string) $myAntiSpamAnswer)) {
          $myError = '<span style="color: '.$errorTextColor.';">' . $antiSpamError . '</span><br/>';
        }
      }
      else if ($enable_anti_spam == '2') {
        // check captcha plugin.
        $isCaptchaValidated = true;
        if ($app->get('captcha') != '0') {
          $captcha = Captcha::getInstance($app->get('captcha'));
          try {
            $isCaptchaValidated = $captcha->checkAnswer($input->get('ns_recaptcha', null, 'string'));
          }
          catch(RuntimeException $e) {
            $isCaptchaValidated = false;
          }
        }
        if (!$isCaptchaValidated) {
          $myError = '<span style="color: '.$errorTextColor.';">' . $antiSpamError . '</span><br/>';
        }
      }
      else if ($enable_anti_spam == '3') {
        // Honeypot: real visitors never see or fill this field.
        // A filled honeypot is silently discarded, so bots cannot tell they were caught.
        if ($input->post->get('ns_hp'.$unique_id, '', 'string') !== '') {
          $session->set('ns_thanks', $unique_id);
          $app->redirect(Uri::getInstance()->toString(), 303);
        }
      }
      if ($postedName === "") {
        $myError = $myError . '<span style="color: '.$errorTextColor.';">' . $noName . '</span><br/>';
        $errors = $errors + 1;
      }
      if ($postedEmail === "") {
        $myError = $myError . '<span style="color: '.$errorTextColor.';">' . $noEmail . '</span><br/>';
        $errors = $errors + 2;
      }
      else if (!MailHelper::isEmailAddress($postedEmail)) {
        $myError = $myError . '<span style="color: '.$errorTextColor.';">' . $invalidEmail . '</span><br/>';
        $errors = $errors + 2;
      }

      if ($myError == "") {
        $myMessage = sprintf($mailNameText, $postedName) . ', ' ."\n".
                     sprintf($mailEmailText, $postedEmail) . ', ' ."\n".
                     date("r")."\n".
                     sprintf($mailIpText, $input->server->getString('REMOTE_ADDR', ''));

        $mailSender = Factory::getMailer();
        $mailSender->addRecipient($recipient);
        if ($sendingWithSetEmail) {
          $mailSender->setSender(array($fromEmail,$fromName));
        }
        else {
          $mailSender->setSender(array($postedEmail,$postedName));
        }

        $mailSender->setSubject($subject);
        $mailSender->setBody($myMessage);

        $mailOk = false;
        $mailException = '';
        try {
          $mailOk = (bool) $mailSender->Send();
        }
        catch(\Throwable $e) {
          $mailException = '<br/>'.$e->getMessage();
        }

        if ($saveList) {
          $guard = "<?php die('Forbidden.'); ?>\n";
          $realSavePath = $savePath;

          $isAbsolute = preg_match('#^([A-Za-z]:[/\\\\]|/)#', $savePath);
          $resolvedDir = $isAbsolute ? realpath(dirname($savePath)) : false;
          $insideWebRoot = !$isAbsolute
            || ($resolvedDir !== false && strpos($resolvedDir . DIRECTORY_SEPARATOR, JPATH_ROOT . DIRECTORY_SEPARATOR) === 0);

          if ($insideWebRoot && strtolower(substr($savePath, -4)) !== '.php') {
            $realSavePath = $savePath . '.php';
            // One-time migration: rescue a legacy, publicly downloadable list file.
            if (is_file($savePath) && !is_file($realSavePath)) {
              file_put_contents($realSavePath, $guard . file_get_contents($savePath));
              unlink($savePath);
            }
          }

          if (!is_file($realSavePath)) {
            file_put_contents($realSavePath, $insideWebRoot ? $guard : '');
          }

          // One-time, lazy conversion of legacy 'Name (email); ' entries to the RFC 5322 recipient format.
          $head = (string) file_get_contents($realSavePath, false, null, 0, 512);
          if (strncmp($head, '<?php', 5) === 0) {
            $head = (string) substr($head, strpos($head, "\n") + 1);
          }
          if (preg_match('/\([^()]*@[^()]*\);/', $head)) {
            $content = (string) file_get_contents($realSavePath);
            $guardLine = '';
            if (strncmp($content, '<?php', 5) === 0) {
              // Never let the conversion touch the die-guard.
              $cut = strpos($content, "\n") + 1;
              $guardLine = substr($content, 0, $cut);
              $content = substr($content, $cut);
            }
            $converted = preg_replace_callback(
              '/(.*?)\s*\(([^()]*@[^()]*)\);\s*/s',
              function ($m) {
                return '"' . str_replace(array('\\', '"'), array('\\\\', '\\"'), trim($m[1])) . '" <' . $m[2] . '>, ';
              },
              $content
            );
            if ($converted !== null) {
              file_put_contents($realSavePath, $guardLine . $converted);
            }
          }

          $safeName = '"' . str_replace(array('\\', '"'), array('\\\\', '\\"'), $postedName) . '"';
          file_put_contents($realSavePath, $safeName . ' <' . $postedEmail . '>, ', FILE_APPEND);
        }

        if ($mailOk) {
          // Post/Redirect/Get: a refresh will not re-submit the subscription.
          // The thank-you state travels in the session, keeping the URL clean.
          $session->set('ns_thanks', $unique_id);
          $app->redirect(Uri::getInstance()->toString(), 303);
        }

        $myReplacement = '<div class="ns"><span style="color: '.$errorTextColor.';">' . $errorText . '</span>'.$mailException.'</div>';
        $text = str_replace('{newsletter_subscriber}', $myReplacement, $text);
        return true;
      }
    }

    // Post/Redirect/Get: display the thank-you message after a successful submission.
    $thanksToken = (string) $session->get('ns_thanks', '');
    $uniqueIdPrefix = (string) $pluginParams->get('unique_id', '');
    if ($thanksToken !== '' && ($uniqueIdPrefix === '' || strpos($thanksToken, $uniqueIdPrefix) === 0)) {
      $session->set('ns_thanks', null);
      $myReplacement = '<div class="ns"><span style="color: '.$thanksTextColor.';">' . $pageText . '</span></div>';
      $text = str_replace('{newsletter_subscriber}', $myReplacement, $text);
      return true;
    }

    if ($recipient === "") {
      $myReplacement = '<div class="ns"><span style="color: '.$errorTextColor.';">No notification recipient specified. That is required.</span></div>';
      $text = str_replace('{newsletter_subscriber}', $myReplacement, $text);
      return true;
    }

    if ($recipient === "email@email.com") {
      $myReplacement = '<div class="ns"><span style="color: '.$errorTextColor.';">Notification Recipient is specified as email@email.com.<br/>Please change it from the Module parameters.</span></div>';
      $text = str_replace('{newsletter_subscriber}', $myReplacement, $text);
      return true;
    }

    // Prepare for Template
    $anti_spam_field = '';
    if ($enable_anti_spam == '2') {
      $anti_spam_field = ($app->get('captcha') != '0') ? Captcha::getInstance($app->get('captcha'))->display('ns_recaptcha', 'ns_recaptcha', 'g-recaptcha') : '';
    }
    else if ($enable_anti_spam == '1') {
      // Label as Placeholder option is intentionally overlooked.
      $anti_spam_field = '<label for="ns_anti_spam_answer'.$unique_id.'">'.$myAntiSpamQuestion.'</label>'.
           '<input class="ns inputbox form-control ' . $class_suffix . '" type="text" '.
           ' name="ns_anti_spam_answer'.$unique_id.'" id="ns_anti_spam_answer'.$unique_id.'" size="' . $nameWidth . '"/>';
    }
    else if ($enable_anti_spam == '3') {
      // Honeypot field: hidden from humans (off-screen) but present in the markup for bots.
      $anti_spam_field = '<div class="ns_hp_wrap" aria-hidden="true">'.
           '<label for="ns_hp'.$unique_id.'">'.$honeypotLabel.'</label>'.
           '<input type="text" name="ns_hp'.$unique_id.'" id="ns_hp'.$unique_id.'" value="" tabindex="-1" autocomplete="off"/>'.
           '</div>';
    }

    $name_value = (($errors & 1) != 1) ? ' value="'.htmlentities((string) $postedName, ENT_COMPAT, "UTF-8").'"' : '';
    $email_value = (($errors & 2) != 2) ? ' value="'.htmlentities((string) $postedEmail, ENT_COMPAT, "UTF-8").'"' : '';

    $action = '';
    if ($pluginParams->get('fixed_url', false)) { $action = ' action="' . $pluginParams->get('fixed_url_address', "") . '" '; }

    $path = PluginHelper::getLayoutPath('content', 'newsletter_subscriber');
    ob_start();
    require $path;
    $myReplacement = ob_get_clean();
    $text = str_replace('{newsletter_subscriber}', $myReplacement, $text);
    return true;
  }

}
