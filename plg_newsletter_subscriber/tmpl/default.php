<?php
// no direct access
defined( '_JEXEC' ) or die( 'Restricted access' );

?>
<div class="ns">
  <?php if ($myError != "") { print '<div class="ns_error">'.$myError.'</div>'; } ?>
  <form method="post" <?php print $action; ?>>
    <?php if ($pluginParams->get('pre_text', '') != '') { ?>
      <div class="nsintro"><?php print $pluginParams->get('pre_text', ''); ?></div>
    <?php } ?>

    <div class="ns_form">
      <?php if ($anti_spam_position == 0) { // Anti-Spam Before ?>
        <div class="input-group"><?php print $anti_spam_field; ?></div>
      <?php } ?>

      <div class="input-group">
        <input class="ns inputbox form-control <?php print $class_suffix;?>" type="text"
               name="m_name<?php print $unique_id; ?>" id="m_name<?php print $unique_id; ?>"
               placeholder="<?php print $namePlaceholder;?>"
               size="<?php print $nameWidth; ?>" <?php print $name_value;?>/>
      </div>

      <div class="input-group">
        <input class="ns inputbox form-control <?php print $class_suffix;?>" type="email"
               name="m_email<?php print $unique_id; ?>" id="m_email<?php print $unique_id; ?>"
               placeholder="<?php print $emailPlaceholder;?>"
               size="<?php print $emailWidth; ?>" <?php print $email_value;?>/>
      </div>

      <?php if ($anti_spam_position == 1) { // Anti-Spam After ?>
        <div class="input-group"><?php print $anti_spam_field; ?></div>
      <?php } ?>

      <div class="input-group">
        <input class="ns btn btn-primary button <?php print $class_suffix;?>" type="submit" value="<?php print $buttonText; ?>"/>
      </div>

    </div>
    <input type="hidden" name="ns_uniqid" value="<?php print $unique_id; ?>"/>
  </form>
</div>

