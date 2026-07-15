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

use \Joomla\CMS\HTML\HTMLHelper;

?>
<div class="modns">
  <?php if ($myError != "") { print '<div class="modns_error" role="alert">'.$myError.'</div>'; } ?>
  <form method="post" <?php print $action; ?>>
    <?php if ($params->get('pre_text', '') != '') { ?>
      <div class="modnsintro"><?php print $params->get('pre_text', ''); ?></div>
    <?php } ?>

    <div class="modns_form">
      <?php if ($anti_spam_position == 0) { // Anti-Spam Before ?>
        <div class="control-group"><?php print $anti_spam_field; ?></div>
      <?php } ?>

      <div class="control-group">
        <label class="modns-vh" for="m_name<?php print $unique_id; ?>"><?php print $namePlaceholder;?></label>
        <input class="modns inputbox form-control <?php print $mod_class_suffix;?>" type="text"
               name="m_name<?php print $unique_id; ?>" id="m_name<?php print $unique_id; ?>"
               placeholder="<?php print $namePlaceholder;?>"
               autocomplete="name" required
               size="<?php print $nameWidth; ?>" <?php print $name_value;?>/>
      </div>

      <div class="control-group">
        <label class="modns-vh" for="m_email<?php print $unique_id; ?>"><?php print $emailPlaceholder;?></label>
        <input class="modns inputbox form-control <?php print $mod_class_suffix;?>" type="email"
               name="m_email<?php print $unique_id; ?>" id="m_email<?php print $unique_id; ?>"
               placeholder="<?php print $emailPlaceholder;?>"
               autocomplete="email" required
               size="<?php print $emailWidth; ?>" <?php print $email_value;?>/>
      </div>

      <?php if ($anti_spam_position == 1) { // Anti-Spam After ?>
        <div class="control-group"><?php print $anti_spam_field; ?></div>
      <?php } ?>

      <div class="control-group">
        <input class="modns btn btn-primary button <?php print $mod_class_suffix;?>" type="submit" value="<?php print $buttonText; ?>"/>
      </div>

    </div>
    <input type="hidden" name="modns_uniqid" value="<?php print $unique_id; ?>"/>
    <?php print HTMLHelper::_('form.token'); ?>
  </form>
</div>
