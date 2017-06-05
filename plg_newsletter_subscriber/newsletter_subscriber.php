<?php
// no direct access
defined( '_JEXEC' ) or die( 'Restricted access' );

jimport( 'joomla.plugin.plugin' );

class plgContentNewsletter_subscriber extends JPlugin {

  public function onContentPrepare($context, &$row, &$params, $page = 0) {
    if (is_object($row)) {
        $text = &$row->text;
    }
    else {
      $text = &$row;
    }

    if (JString::strpos($text, '{newsletter_subscriber}') === false) {
      return true;
    }
    // Because $params is one of the function's arguments.
    $pluginParams = $this->params;

    // Notification Options
    $recipient = $pluginParams->get('email_recipient', '');
    $fromName = $pluginParams->get('from_name', 'Newsletter Subscriber');
    $fromEmail = $pluginParams->get('from_email', 'newsletter_subscriber@yoursite.com');
    $subject = $pluginParams->get('subject', 'New subscription to your site!');
    $sendingWithSetEmail = $pluginParams->get('sending_from_set', true);

    // Form Options
    $unique_id = (isset($_POST["ns_uniqid"])) ? $_POST["ns_uniqid"] : $params->get('unique_id', "").uniqid();

    // Texts
    $namePlaceholder = $pluginParams->get('name_placeholder', 'Name');
    $emailPlaceholder = $pluginParams->get('email_placeholder', 'email@site.com');
    $buttonText = $pluginParams->get('button_text', 'Subscribe to Newsletter');
    $pageText = $pluginParams->get('page_text', 'Thank you for subscribing to our site.');
    $errorText = $pluginParams->get('errot_text', 'Your subscription could not be submitted. Please try again.');
    $noName = $pluginParams->get('no_name', 'Please write your name');
    $noEmail = $pluginParams->get('no_email', 'Please write your email');
    $invalidEmail = $pluginParams->get('invalid_email', 'Please write a valid email');

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
    $document = JFactory::getDocument();
    $document->addStyleDeclaration('
      .ns .input-group input.ns{max-width: 92%;}
      .ns .g-recaptcha {margin-bottom: 5px;}
    '.$pluginParams->get('customcss', ''));

    $myReplacement = "";
    $myError = "";
    $errors = 3;
    if (isset($_POST["m_name".$unique_id])) {
      $errors = 0;
      if ($enable_anti_spam == '1') {
        if (strtolower($_POST['ns_anti_spam_answer'.$unique_id]) != strtolower($myAntiSpamAnswer)) {
          $myError = '<span style="color: '.$errorTextColor.';">' . JText::_('Wrong anti-spam answer') . '</span><br/>';
        }
      }
      else if ($enable_anti_spam == '2') {
        // check captcha plugin.
        JPluginHelper::importPlugin('captcha');
        $d = JEventDispatcher::getInstance();
        $res = $d->trigger('onCheckAnswer', 'not_used');
        if( (!isset($res[0])) || (!$res[0]) ){
          $myError = '<span style="color: '.$errorTextColor.';">' . JText::_('Wrong anti-spam answer') . '</span><br/>';
        }
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
          $myReplacement = '<div class="ns"><span style="color: '.$errorTextColor.';">' . $errorText . '</span></div>';
          $text = JString::str_ireplace('{newsletter_subscriber}', $myReplacement, $text);
        }
        else {
          $myReplacement = '<div class="ns"><span style="color: '.$thanksTextColor.';">' . $pageText . '</span></div>';
          $text = JString::str_ireplace('{newsletter_subscriber}', $myReplacement, $text);
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
      $myReplacement = '<div class="ns"><span style="color: '.$errorTextColor.';">No notification recipient specified. That is required.</span></div>';
      $text = JString::str_ireplace('{newsletter_subscriber}', $myReplacement, $text);
      return true;
    }

    if ($recipient === "email@email.com") {
      $myReplacement = '<div class="ns"><span style="color: '.$errorTextColor.';">Notification Recipient is specified as email@email.com.<br/>Please change it from the Module parameters.</span></div>';
      $text = JString::str_ireplace('{newsletter_subscriber}', $myReplacement, $text);
      return true;
    }

    // Prepare for Template
    $anti_spam_field = '';
    if ($enable_anti_spam == '2') {
      $anti_spam_field = (JFactory::getConfig()->get('captcha') != '0') ? JCaptcha::getInstance(JFactory::getConfig()->get('captcha'))->display('ns_recaptcha', 'ns_recaptcha', 'g-recaptcha') : '';
    }
    else if ($enable_anti_spam == '1') {
      // Label as Placeholder option is intentionally overlooked.
      $anti_spam_field = '<label for="ns_anti_spam_answer'.$unique_id.'">'.$myAntiSpamQuestion.'</label>'.
           '<input class="ns inputbox form-control ' . $class_suffix . '" type="text" '.
           ' name="ns_anti_spam_answer'.$unique_id.'" id="ns_anti_spam_answer'.$unique_id.'" size="' . $nameWidth . '"/>';
    }

    $name_value = (($errors & 1) != 1) ? ' value="'.htmlentities($_POST["m_name".$unique_id], ENT_COMPAT, "UTF-8").'"' : '';
    $email_value = (($errors & 2) != 2) ? ' value="'.htmlentities($_POST["m_email".$unique_id], ENT_COMPAT, "UTF-8").'"' : '';

    $action = '';
    if ($pluginParams->get('fixed_url', false)) { $action = ' action="' . $pluginParams->get('fixed_url_address', "") . '" '; }

    $path = JPluginHelper::getLayoutPath('content', 'newsletter_subscriber');
    ob_start();
    require $path;
    $myReplacement = ob_get_clean();
    $text = JString::str_ireplace('{newsletter_subscriber}', $myReplacement, $text);
    return true;
  }

}