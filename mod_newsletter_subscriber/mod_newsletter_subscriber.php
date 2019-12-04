<?php
/*------------------------------------------------------------------------
# mod_newsletter_subscriber - Newsletter Subscriber
# ------------------------------------------------------------------------
# author    Christopher Mavros - Mavrosxristoforos.com
# copyright Copyright (C) 2008 Mavrosxristoforos.com. All Rights Reserved.
# @license - http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
# Websites: https://mavrosxristoforos.com
# Technical Support:  Forum - https://mavrosxristoforos.com/support/forum
-------------------------------------------------------------------------*/

// no direct access
defined( '_JEXEC' ) or die( 'Restricted access' );

// Notification Options
$recipient = $params->get('email_recipient', '');
$fromName = $params->get('from_name', 'Newsletter Subscriber');
$fromEmail = $params->get('from_email', 'newsletter_subscriber@yoursite.com');
$subject = $params->get('subject', 'New subscription to your site!');
$sendingWithSetEmail = $params->get('sending_from_set', true);

// Form Options
$unique_id = (isset($_POST["modns_uniqid"])) ? $_POST["modns_uniqid"] : $params->get('unique_id', "").uniqid();

// Texts
$namePlaceholder = $params->get('name_placeholder', 'Name');
$emailPlaceholder = $params->get('email_placeholder', 'email@site.com');
$buttonText = $params->get('button_text', 'Subscribe to Newsletter');
$pageText = $params->get('page_text', 'Thank you for subscribing to our site.');
$errorText = $params->get('errot_text', 'Your subscription could not be submitted. Please try again.');
$noName = $params->get('no_name', 'Please write your name');
$noEmail = $params->get('no_email', 'Please write your email');
$invalidEmail = $params->get('invalid_email', 'Please write a valid email');

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
$document = JFactory::getDocument();
$document->addStyleDeclaration('
  .modns .input-group input.modns{max-width: 92%; margin-bottom: 8px;}
  .modns .g-recaptcha {margin-bottom: 5px;}
'.$params->get('customcss', ''));


$myError = "";
$errors = 3;
if (isset($_POST["m_name".$unique_id])) {
  $errors = 0;
  if ($enable_anti_spam == '1') {
    if (strtolower($_POST['modns_anti_spam_answer'.$unique_id]) != strtolower($myAntiSpamAnswer)) {
      $myError = '<span style="color: '.$errorTextColor.';">' . JText::_('Wrong anti-spam answer') . '</span><br/>';
    }
  }
  else if ($enable_anti_spam == '2') {
    // check captcha plugin.
    $isCaptchaValidated = true;
    if (JFactory::getConfig()->get('captcha') != '0') {
      $captcha = JCaptcha::getInstance(JFactory::getConfig()->get('captcha'));
      try {
        $isCaptchaValidated = $captcha->checkAnswer('ns_recaptcha');
      }
      catch(RuntimeException $e) {
        $isCaptchaValidated = false;
      }
    }
    if (!$isCaptchaValidated) {
      $myError = '<span style="color: '.$errorTextColor.';">' . JText::_('Wrong anti-spam answer') . '</span><br/>';
    }
    /*JPluginHelper::importPlugin('captcha');
    $d = JEventDispatcher::getInstance();
    $res = $d->trigger('onCheckAnswer', 'not_used');
    if( (!isset($res[0])) || (!$res[0]) ){
      $myError = '<span style="color: '.$errorTextColor.';">' . JText::_('Wrong anti-spam answer') . '</span><br/>';
    }*/
  }
  if ($_POST["m_name".$unique_id] === "") {
    $myError = $myError . '<span style="color: '.$errorTextColor.';">' . $noName . '</span><br/>';
    $errors = $errors + 1;
  }
  if ($_POST["m_email".$unique_id] === "") {
    $myError = $myError . '<span style="color: '.$errorTextColor.';">' . $noEmail . '</span><br/>';
    $errors = $errors + 2;
  }
  else if (!preg_match("/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,4})$/", strtolower($_POST["m_email".$unique_id]))) {
    $myError = $myError . '<span style="color: '.$errorTextColor.';">' . $invalidEmail . '</span><br/>';
    $errors = $errors + 2;
  }

  if ($myError == "") {
    $myMessage = JText::_('Name') . ': ' . $_POST["m_name".$unique_id] . ', ' ."\n".
                 JText::_('Email') . ': ' . $_POST["m_email".$unique_id] . ', ' ."\n".
                 date("r")."\n".
                 JText::_('IP').': '.$_SERVER['REMOTE_ADDR'];

    $mailSender = JFactory::getMailer();
    $mailSender->addRecipient($recipient);
    if ($sendingWithSetEmail) {
      $mailSender->setSender(array($fromEmail,$fromName));
    }
    else {
      $mailSender->setSender(array($_POST["m_email".$unique_id],$_POST["m_name".$unique_id]));
    }

    $mailSender->setSubject($subject);
    $mailSender->setBody($myMessage);

    if (!$mailSender->Send()) {
      $myReplacement = '<div class="modns"><span style="color: '.$errorTextColor.';">' . $errorText . '</span></div>';
      print $myReplacement;
    }
    else {
      $myReplacement = '<div class="modns"><span style="color: '.$thanksTextColor.';">' . $pageText . '</span></div>';
      print $myReplacement;
    }
    if ($saveList) {
      $file = fopen($savePath, "a");
      fwrite($file, $_POST["m_name".$unique_id]." (".$_POST["m_email".$unique_id]."); ");
      fclose($file);
    }
    return true;
  }
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
  $anti_spam_field = (JFactory::getConfig()->get('captcha') != '0') ? JCaptcha::getInstance(JFactory::getConfig()->get('captcha'))->display('ns_recaptcha', 'ns_recaptcha', 'g-recaptcha') : '';
}
else if ($enable_anti_spam == '1') {
  // Label as Placeholder option is intentionally overlooked.
  $anti_spam_field = '<label for="modns_anti_spam_answer'.$unique_id.'">'.$myAntiSpamQuestion.'</label>'.
       '<input class="modns inputbox form-control ' . $mod_class_suffix . '" type="text" '.
       ' name="modns_anti_spam_answer'.$unique_id.'" id="modns_anti_spam_answer'.$unique_id.'" size="' . $nameWidth . '"/>';
}

$name_value = (($errors & 1) != 1) ? ' value="'.htmlentities($_POST["m_name".$unique_id], ENT_COMPAT, "UTF-8").'"' : '';
$email_value = (($errors & 2) != 2) ? ' value="'.htmlentities($_POST["m_email".$unique_id], ENT_COMPAT, "UTF-8").'"' : '';

$action = '';
if ($params->get('fixed_url', false)) { $action = ' action="' . $params->get('fixed_url_address', "") . '" '; }

require JModuleHelper::getLayoutPath('mod_newsletter_subscriber');