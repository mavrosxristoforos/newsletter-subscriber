<?php
/*------------------------------------------------------------------------
# mod_newsletter_subscriber - Newsletter Subscriber
# ------------------------------------------------------------------------
# author    Christopher Mavros - Mavrosxristoforos.com
# copyright Copyright (C) 2008 Mavrosxristoforos.com. All Rights Reserved.
# @license - http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
# Websites: http://www.mavrosxristoforos.com
# Technical Support:  Forum - http://www.mavrosxristoforos.com/support/forum
-------------------------------------------------------------------------*/

// no direct access
defined( '_JEXEC' ) or die( 'Restricted access' );

?>
<div class="modns">
  <?php if ($myError != "") { print '<div class="modns_error">'.$myError.'</div>'; } ?>
  <form method="post" <?php print $action; ?>>
    <?php if ($params->get('pre_text', '') != '') { ?>
      <div class="modnsintro"><?php print $params->get('pre_text', ''); ?></div>
    <?php } ?>

    <div class="modns_form">
      <?php if ($anti_spam_position == 0) { // Anti-Spam Before ?>
        <div class="input-group"><?php print $anti_spam_field; ?></div>
      <?php } ?>

      <div class="input-group">
        <input class="modns inputbox form-control <?php print $mod_class_suffix;?>" type="text"
               name="m_name<?php print $unique_id; ?>" id="m_name<?php print $unique_id; ?>"
               placeholder="<?php print $namePlaceholder;?>"
               size="<?php print $nameWidth; ?>" <?php print $name_value;?>/>
      </div>

      <div class="input-group">
        <input class="modns inputbox form-control <?php print $mod_class_suffix;?>" type="email"
               name="m_email<?php print $unique_id; ?>" id="m_email<?php print $unique_id; ?>"
               placeholder="<?php print $emailPlaceholder;?>"
               size="<?php print $emailWidth; ?>" <?php print $email_value;?>/>
      </div>

      <?php if ($anti_spam_position == 1) { // Anti-Spam After ?>
        <div class="input-group"><?php print $anti_spam_field; ?></div>
      <?php } ?>

      <div class="input-group">
        <input class="modns btn btn-primary button <?php print $mod_class_suffix;?>" type="submit" value="<?php print $buttonText; ?>"/>
      </div>

    </div>
    <input type="hidden" name="modns_uniqid" value="<?php print $unique_id; ?>"/>
  </form>
</div>

