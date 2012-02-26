<?php
// no direct access
defined( '_JEXEC' ) or die( 'Restricted access' );

jimport( 'joomla.plugin.plugin' );

class plgContentNewsletter_subscriber extends JPlugin {

  function plgContentNewsletter_subscriber( &$subject, $config ) {
    parent::__construct( $subject, $config );
  }

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
    $pluginParams = $this->params;

    $myNameLabel = $pluginParams->get('name_label', 'Name:');
    $myEmailLabel = $pluginParams->get('email_label', 'Email:');

    $recipient = $pluginParams->get('email_recipient', '');

    $buttonText = $pluginParams->get('button_text', 'Subscribe to Newsletter');
    $pageText = $pluginParams->get('page_text', 'Thank you for subscribing to our site.');
    $errorText = $pluginParams->get('errot_text', 'Your subscription could not be submitted. Please try again.');

    $subject = $pluginParams->get('subject', 'New subscription to your site!');
    $fromName = $pluginParams->get('from_name', 'Newsletter Subscriber');
    $fromEmail = $pluginParams->get('from_email', 'newsletter_subscriber@yoursite.com');
    $sendingWithSetEmail = $pluginParams->get('sending_from_set', true);

    $noName = $pluginParams->get('no_name', 'Please write your name');
    $noEmail = $pluginParams->get('no_email', 'Please write your email');
    $invalidEmail = $pluginParams->get('invalid_email', 'Please write a valid email');

    $classSuffix = $pluginParams->get('class_suffix', '');

    $addcss = $pluginParams->get('addcss', 'div.ns tr, div.ns td { border: none; padding: 3px; }');

    $saveList = $pluginParams->get('save_list', true);
    $savePath = $pluginParams->get('save_path', 'mailing_list.txt');

    $thanksTextColor = $pluginParams->get('thank_text_color', '#000000');
    $errorTextColor = $pluginParams->get('error_text_color', '#000000');

    $disable_https = $pluginParams->get('disable_https', true);

    $exact_url = $pluginParams->get('exact_url', true);
    if (!$exact_url) {
      $url = JURI::current();
    }
    else {
      if (!$disable_https) {
        $url = (!empty($_SERVER['HTTPS'])) ? "https://".$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'] : "http://".$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'];
      }
      else {
        $url = "http://".$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'];
      }
    }

    $fixed_url = $pluginParams->get('fixed_url', true);
    if ($fixed_url) {
      $url = $pluginParams->get('fixed_url_address', "");
    }

    $url = htmlentities($url, ENT_COMPAT, "UTF-8");

    $unique_id = $pluginParams->get('unique_id', "");

    $enable_anti_spam = $pluginParams->get('enable_anti_spam', true);
    $myAntiSpamQuestion = $pluginParams->get('anti_spam_q', 'How many eyes has a typical person? (ex: 1)');
    $myAntiSpamAnswer = $pluginParams->get('anti_spam_a', '2');

    $myError = "";
    $errors = 3;

    if (isset($_POST["name".$unique_id])) {
      $errors = 0;
      if ($enable_anti_spam) {
        if ($_POST["ns_anti_spam_answer".$unique_id] != $myAntiSpamAnswer) {
          $myError = $myError. '<span style="color: '.$errorTextColor.';">' . JText::_('Wrong anti-spam answer') . '</span><br/>';
        }
      }
      if ($_POST["name".$unique_id] === "") {
        $myError = $myError . '<span style="color: '.$errorTextColor.';">' . $noName . '</span><br/>';
        $errors = $errors + 1;
      }
      if ($_POST["email".$unique_id] === "") {
        $myError = $myError. '<span style="color: '.$errorTextColor.';">' . $noEmail . '</span><br/>';
        $errors = $errors + 2;
      }
      else if (!preg_match("/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,3})$/", $_POST["email".$unique_id])) {
        $myError = $myError. '<span style="color: '.$errorTextColor.';">' . $invalidEmail . '</span><br/>';
        $errors = $errors + 2;
      }

      if ($myError == "") {

        $myMessage = $myNameLabel . ' ' . $_POST["name".$unique_id] . ', ' .
                     $myEmailLabel . ' ' . $_POST["email".$unique_id] . ', ' .
                     date("r");
        $mailSender = &JFactory::getMailer();
        $mailSender->addRecipient($recipient);
        if ($sendingWithSetEmail) {
          $mailSender->setSender(array($fromEmail,$fromName));
        }
        else {
          $mailSender->setSender(array($_POST["email".$unique_id],$_POST["name".$unique_id]));
        }
        $mailSender->setSubject($subject);
        $mailSender->setBody($myMessage);

        if (!$mailSender->Send()) {
          $myReplacement = '<span style="color: '.$errorTextColor.';">' . $errorText . '</span>';
          $text = JString::str_ireplace('{newsletter_subscriber}', $myReplacement, $text);
        }
        else {
          $myReplacement = '<span style="color: '.$thanksTextColor.';">' . $pageText . '</span>';
          $text = JString::str_ireplace('{newsletter_subscriber}', $myReplacement, $text);
        }
        if ($saveList) {
          $file = fopen($savePath, "a");
          fwrite($file, htmlentities($_POST["name".$unique_id], ENT_COMPAT, "UTF-8")." (".
                        htmlentities($_POST["email".$unique_id], ENT_COMPAT, "UTF-8")."); ");
          fclose($file);
        }
        return true;
      }
    }

    if ($recipient === "") {
      $myReplacement = '<span style="color: '.$errorTextColor.';">No recipient specified</span>';
      $text = JString::str_ireplace('{newsletter_subscriber}', $myReplacement, $text);
      return true;
    }

    if ($recipient === "email@email.com") {
      $myReplacement = '<span style="color: '.$errorTextColor.';">Mail Recipient is specified as email@email.com.<br/>Please change it from the Plugin parameters.</span>';
      $text = JString::str_ireplace('{newsletter_subscriber}', $myReplacement, $text);
      return true;
    }

    $myReplacement = "";

    if ($myError != "") {
      $myReplacement = $myError;
    }
    $myReplacement = $myReplacement . '<style type="text/css"><!--' . $addcss . '--></style>';
    $myReplacement = $myReplacement . '<div class="ns ' . $classSuffix . '"><form action="' . $url . '" method="post"><table>' . "\n";
    if ($enable_anti_spam) {
      $myReplacement = $myReplacement . '<tr><td colspan="2">' . $myAntiSpamQuestion . '</td></tr><tr><td></td><td><input class="ns inputbox ' . $classSuffix . '" type="text" name="ns_anti_spam_answer'.$unique_id.'" width="250"/></td></tr>' . "\n";
    }
    $myReplacement = $myReplacement . '<tr><td>' . $myNameLabel . '</td><td><input class="ns inputbox ' . $classSuffix . '" type="text" name="name'.$unique_id.'" width="250"';
    if (($errors & 1) != 1) {
      $myReplacement = $myReplacement . ' value="'.htmlentities($_POST["name".$unique_id], ENT_COMPAT, "UTF-8").'"';
    }
    $myReplacement = $myReplacement . '/></td></tr>' . "\n" .
                     '<tr><td>' . $myEmailLabel . '</td><td><input class="ns inputbox ' . $classSuffix . '" type="text" name="email'.$unique_id.'" width="250"';
    if (($errors & 2) != 2) {
      $myReplacement = $myReplacement . ' value="'.htmlentities($_POST["email".$unique_id], ENT_COMPAT, "UTF-8").'"';
    }
    $myReplacement = $myReplacement . '/></td></tr>' . "\n" .
                     '<tr><td colspan="2"><input class="ns button ' . $classSuffix . '" type="submit" value="' . $buttonText . '" style="width: 100%;"/></td></tr></table></form></div>' . "\n";
    $text = JString::str_ireplace('{newsletter_subscriber}', $myReplacement, $text);


    return true;
  }

}