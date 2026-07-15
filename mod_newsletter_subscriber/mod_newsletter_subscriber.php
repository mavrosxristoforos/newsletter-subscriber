<?php
/*------------------------------------------------------------------------
# mod_newsletter_subscriber - Newsletter Subscriber
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
use \Joomla\CMS\Helper\ModuleHelper;
use \Joomla\CMS\Captcha\Captcha;
use \Joomla\CMS\Mail\MailHelper;
use \Joomla\CMS\Session\Session;
use \Joomla\CMS\Uri\Uri;

$app = Factory::getApplication();
$input = method_exists($app, 'getInput') ? $app->getInput() : $app->input;

// Notification Options
$recipient = $params->get('email_recipient', '');
$fromName = $params->get('from_name', 'Newsletter Subscriber');
$fromEmail = $params->get('from_email', 'newsletter_subscriber@yoursite.com');
$subject = $params->get('subject', 'New subscription to your site!');
$sendingWithSetEmail = $params->get('sending_from_set', true);

// Form Options
// Security: only a sanitized identifier is accepted from the request. (Fixes reflected XSS.)
$unique_id = $input->post->get('modns_uniqid', '', 'cmd');
if ($unique_id === '') {
  $unique_id = preg_replace('/[^A-Za-z0-9_.\-]/', '', (string) $params->get('unique_id', '')) . uniqid();
}

// Texts
$namePlaceholder = $params->get('name_placeholder', 'Name');
$emailPlaceholder = $params->get('email_placeholder', 'email@site.com');
$buttonText = $params->get('button_text', 'Subscribe to Newsletter');
$pageText = $params->get('page_text', 'Thank you for subscribing to our site.');
// 'errot_text' is kept as a fallback for installations that saved the old, misspelled key.
$errorText = $params->get('error_text', $params->get('errot_text', 'Your subscription could not be submitted. Please try again.'));
$noName = $params->get('no_name', 'Please write your name');
$noEmail = $params->get('no_email', 'Please write your email');
$invalidEmail = $params->get('invalid_email', 'Please write a valid email');
$antiSpamError = $params->get('anti_spam_error', 'Please answer the anti-spam question correctly.');
$honeypotLabel = $params->get('honeypot_label', 'Leave this field empty');
$invalidTokenError = $params->get('invalid_token_error', 'Your session has expired. Please try submitting the form again.');
$mailNameText = $params->get('mail_name_text', 'Name: %s');
$mailEmailText = $params->get('mail_email_text', 'Email: %s');
$mailIpText = $params->get('mail_ip_text', 'IP: %s');

// Sizes & Colors
$nameWidth = $params->get('name_width', '12');
$emailWidth = $params->get('email_width', '12');
$buttonWidth = $params->get('button_width', '100');
$thanksTextColor = $params->get('thank_text_color', '#000000');
$errorTextColor = $params->get('error_text_color', '#000000');

// Mailing List
$saveList = $params->get('save_list', true);
$savePath = $params->get('save_path', 'mailing_list.txt');

// Anti-Spam
$enable_anti_spam = $params->get('enable_anti_spam', '1');
$myAntiSpamQuestion = $params->get('anti_spam_q', 'How many eyes has a typical person? (ex: 1)');
$myAntiSpamAnswer = $params->get('anti_spam_a', '2');
$anti_spam_position = $params->get('anti_spam_position', 0);

// Advanced
$mod_class_suffix = $params->get('moduleclass_sfx', '');
$document = $app->getDocument();
$document->addStyleDeclaration('
  .modns .input-group input.modns{max-width: 92%; margin-bottom: 8px;}
  .modns .g-recaptcha {margin-bottom: 5px;}
  .modns .modns-vh{position:absolute;width:1px;height:1px;margin:-1px;padding:0;overflow:hidden;clip:rect(0,0,0,0);white-space:nowrap;border:0;}
  .modns .modns_hp_wrap{position:absolute !important;left:-9999px;top:auto;width:1px;height:1px;overflow:hidden;}
'.$params->get('customcss', ''));


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
    $antiSpamAnswer = $input->post->get('modns_anti_spam_answer'.$unique_id, '', 'string');
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
    if ($input->post->get('modns_hp'.$unique_id, '', 'string') !== '') {
      $uri = Uri::getInstance();
      $uri->setVar('modns_thanks', $unique_id);
      $app->redirect($uri->toString(), 303);
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
      $file = fopen($savePath, "a");
      fwrite($file, $postedName." (".$postedEmail."); ");
      fclose($file);
    }

    if ($mailOk) {
      // Post/Redirect/Get: a refresh will not re-submit the subscription.
      $uri = Uri::getInstance();
      $uri->setVar('modns_thanks', $unique_id);
      $app->redirect($uri->toString(), 303);
    }

    $myReplacement = '<div class="modns"><span style="color: '.$errorTextColor.';">' . $errorText . '</span>'.$mailException.'</div>';
    print $myReplacement;
    return true;
  }
}

// Post/Redirect/Get: display the thank-you message after a successful submission.
$thanksToken = $input->get('modns_thanks', '', 'cmd');
$uniqueIdPrefix = (string) $params->get('unique_id', '');
if ($thanksToken !== '' && ($uniqueIdPrefix === '' || strpos($thanksToken, $uniqueIdPrefix) === 0)) {
  print '<div class="modns"><span style="color: '.$thanksTextColor.';">' . $pageText . '</span></div>';
  return true;
}

if ($recipient === "") {
  $myReplacement = '<div class="modns"><span style="color: '.$errorTextColor.';">No notification recipient specified. That is required.</span></div>';
  print $myReplacement;
  return true;
}

if ($recipient === "email@email.com") {
  $myReplacement = '<div class="modns"><span style="color: '.$errorTextColor.';">Notification Recipient is specified as email@email.com.<br/>Please change it from the Module parameters.</span></div>';
  print $myReplacement;
  return true;
}

// Prepare for Template
$anti_spam_field = '';
if ($enable_anti_spam == '2') {
  $anti_spam_field = ($app->get('captcha') != '0') ? Captcha::getInstance($app->get('captcha'))->display('ns_recaptcha', 'ns_recaptcha', 'g-recaptcha') : '';
}
else if ($enable_anti_spam == '1') {
  // Label as Placeholder option is intentionally overlooked.
  $anti_spam_field = '<label for="modns_anti_spam_answer'.$unique_id.'">'.$myAntiSpamQuestion.'</label>'.
       '<input class="modns inputbox form-control ' . $mod_class_suffix . '" type="text" '.
       ' name="modns_anti_spam_answer'.$unique_id.'" id="modns_anti_spam_answer'.$unique_id.'" size="' . $nameWidth . '"/>';
}
else if ($enable_anti_spam == '3') {
  // Honeypot field: hidden from humans (off-screen) but present in the markup for bots.
  $anti_spam_field = '<div class="modns_hp_wrap" aria-hidden="true">'.
       '<label for="modns_hp'.$unique_id.'">'.$honeypotLabel.'</label>'.
       '<input type="text" name="modns_hp'.$unique_id.'" id="modns_hp'.$unique_id.'" value="" tabindex="-1" autocomplete="off"/>'.
       '</div>';
}

$name_value = (($errors & 1) != 1) ? ' value="'.htmlentities((string) $postedName, ENT_COMPAT, "UTF-8").'"' : '';
$email_value = (($errors & 2) != 2) ? ' value="'.htmlentities((string) $postedEmail, ENT_COMPAT, "UTF-8").'"' : '';

$action = '';
if ($params->get('fixed_url', false)) { $action = ' action="' . $params->get('fixed_url_address', "") . '" '; }

require ModuleHelper::getLayoutPath('mod_newsletter_subscriber');
