<?php
// no direct access
defined( '_JEXEC' ) or die( 'Restricted access' );

$myNameLabel = $params->get('name_label', 'Name:');
$myEmailLabel = $params->get('email_label', 'Email:');

$recipient = $params->get('email_recipient', '');

$buttonText = $params->get('button_text', 'Subscribe to Newsletter');
$pageText = $params->get('page_text', 'Thank you for subscribing to our site.');
$errorText = $params->get('errot_text', 'Your subscription could not be submitted. Please try again.');

$subject = $params->get('subject', 'New subscription to your site!');
$fromName = $params->get('from_name', 'Newsletter Subscriber');
$fromEmail = $params->get('from_email', 'newsletter_subscriber@yoursite.com');
$sendingWithSetEmail = $params->get('sending_from_set', true);

$noName = $params->get('no_name', 'Please write your name');
$noEmail = $params->get('no_email', 'Please write your email');
$invalidEmail = $params->get('invalid_email', 'Please write a valid email');

$nameWidth = $params->get('name_width', '20');
$emailWidth = $params->get('email_width', '20');
$buttonWidth = $params->get('button_width', '100');

$saveList = $params->get('save_list', true);
$savePath = $params->get('save_path', 'mailing_list.txt');

$mod_class_suffix = $params->get('moduleclass_sfx', '');

$addcss = $params->get('addcss', 'div.modns tr, div.modns td { border: none; padding: 3px; }');

$thanksTextColor = $params->get('thank_text_color', '#000000');
$errorTextColor = $params->get('error_text_color', '#000000');
$pre_text = $params->get('pre_text', '');

$disable_https = $params->get('disable_https', true);

$exact_url = $params->get('exact_url', true);
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

$fixed_url = $params->get('fixed_url', true);
if ($fixed_url) {
  $url = $params->get('fixed_url_address', "");
}

$url = htmlentities($url, ENT_COMPAT, "UTF-8");

$unique_id = $params->get('unique_id', "");

$enable_anti_spam = $params->get('enable_anti_spam', true);
$myAntiSpamQuestion = $params->get('anti_spam_q', 'How many eyes has a typical person? (ex: 1)');
$myAntiSpamAnswer = $params->get('anti_spam_a', '2');

$myError = "";
$errors = 3;

if (isset($_POST["m_name".$unique_id])) {
  $errors = 0;
  if ($enable_anti_spam) {
    if ($_POST["modns_anti_spam_answer".$unique_id] != $myAntiSpamAnswer) {
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

    $myMessage = $myNameLabel . ' ' . $_POST["m_name".$unique_id] . ', ' .
                 $myEmailLabel . ' ' . $_POST["m_email".$unique_id] . ', ' .
                 date("r");

    $mailSender = &JFactory::getMailer();
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
      fwrite($file, htmlentities($_POST["m_name".$unique_id], ENT_COMPAT, "UTF-8")." (".
                    htmlentities($_POST["m_email".$unique_id], ENT_COMPAT, "UTF-8")."); ");
      fclose($file);
    }
    return true;
  }
}

if ($recipient === "") {
  $myReplacement = '<div class="modns"><span style="color: '.$errorTextColor.';">No recipient specified</span></div>';
  print $myReplacement;
  return true;
}

if ($recipient === "email@email.com") {
  $myReplacement = '<div class="modns"><span style="color: '.$errorTextColor.';">Mail Recipient is specified as email@email.com.<br/>Please change it from the Module parameters.</span></div>';
  print $myReplacement;
  return true;
}

if ($myError != "") {
  print $myError;
}

print '<style type="text/css"><!--' . $addcss . '--></style>';
print '<div class="modns"><form action="' . $url . '" method="post">' . "\n" .
      '<div class="modnsintro">'.$pre_text.'</div>' . "\n";
print '<table>';
if ($enable_anti_spam) {
  print '<tr><td colspan="2">' . $myAntiSpamQuestion . '</td></tr><tr><td></td><td><input class="modns inputbox ' . $mod_class_suffix . '" type="text" name="modns_anti_spam_answer'.$unique_id.'" size="' . $nameWidth . '"/></td></tr>' . "\n";
}
print '<tr><td>' . $myNameLabel . '</td><td><input class="modns inputbox ' . $mod_class_suffix . '" type="text" name="m_name'.$unique_id.'" size="' . $nameWidth . '"';
if (($errors & 1) != 1) {
  print ' value="'.htmlentities($_POST["m_name".$unique_id], ENT_COMPAT, "UTF-8").'"';
}
print '/></td></tr>' . "\n";
print '<tr><td>' . $myEmailLabel . '</td><td><input class="modns inputbox ' . $mod_class_suffix . '" type="text" name="m_email'.$unique_id.'" size="' . $emailWidth . '"';
if (($errors & 2) != 2) {
  print ' value="'.htmlentities($_POST["m_email".$unique_id], ENT_COMPAT, "UTF-8").'"';
}
print '/></td></tr>' . "\n";
print '<tr><td colspan="2"><input class="modns button ' . $mod_class_suffix . '" type="submit" value="' . $buttonText . '" style="width: ' . $buttonWidth . '%"/></td></tr></table></form></div>' . "\n";
return true;
