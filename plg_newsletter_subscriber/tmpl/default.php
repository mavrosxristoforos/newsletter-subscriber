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

use \Joomla\CMS\HTML\HTMLHelper;

?>
<div class="ns">
  <?php if ($myError != "") { print '<div class="ns_error" role="alert">'.$myError.'</div>'; } ?>
  <form method="post" <?php print $action; ?>>
    <?php if ($pluginParams->get('pre_text', '') != '') { ?>
      <div class="nsintro"><?php print $pluginParams->get('pre_text', ''); ?></div>
    <?php } ?>

    <div class="ns_form">
      <?php if ($anti_spam_position == 0) { // Anti-Spam Before ?>
        <div class="control-group"><?php print $anti_spam_field; ?></div>
      <?php } ?>

      <div class="control-group">
        <label class="ns-vh" for="m_name<?php print $unique_id; ?>"><?php print $namePlaceholder;?></label>
        <input class="ns inputbox form-control <?php print $class_suffix;?>" type="text"
               name="m_name<?php print $unique_id; ?>" id="m_name<?php print $unique_id; ?>"
               placeholder="<?php print $namePlaceholder;?>"
               autocomplete="name" required
               size="<?php print $nameWidth; ?>" <?php print $name_value;?>/>
      </div>

      <div class="control-group">
        <label class="ns-vh" for="m_email<?php print $unique_id; ?>"><?php print $emailPlaceholder;?></label>
        <input class="ns inputbox form-control <?php print $class_suffix;?>" type="email"
               name="m_email<?php print $unique_id; ?>" id="m_email<?php print $unique_id; ?>"
               placeholder="<?php print $emailPlaceholder;?>"
               autocomplete="email" required
               size="<?php print $emailWidth; ?>" <?php print $email_value;?>/>
      </div>

      <?php if ($anti_spam_position == 1) { // Anti-Spam After ?>
        <div class="control-group"><?php print $anti_spam_field; ?></div>
      <?php } ?>

      <div class="control-group">
        <input class="ns btn btn-primary button <?php print $class_suffix;?>" type="submit" value="<?php print $buttonText; ?>"/>
      </div>

    </div>
    <input type="hidden" name="ns_uniqid" value="<?php print $unique_id; ?>"/>
    <?php print HTMLHelper::_('form.token'); ?>
  </form>
</div>
