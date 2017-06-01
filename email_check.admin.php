<?php
/**
 * @file
 */

function _email_check_system_site_information_settings_alter(&$form, &$form_state) {
  $form['site_information']['site_replyto_mail'] = array(
    '#type'          => 'textfield',
    '#title'         => t('ReplyTo E-mail address'),
    '#default_value' => variable_get('site_replyto_mail', variable_get('site_mail', 'admin@localhost')),
    '#description'   => t("The <em>ReplyTo</em> address set for all outgoing e-mails. When the site mail address is \"info@sub.example.net\", but you want replies to be sent to \"office@example.net\", set the latter as the <em>ReplyTo</em> address."),
    '#required'      => FALSE,
  );
}
