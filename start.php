<?php
/**
 * recaptcha Plugin
 * 
 */

//register the plugin hook handler
elgg_register_event_handler('init', 'system', 'recaptcha_init');

/**
 * recaptcha settings are set in Elgg Admin Menu -> Settings -> recaptcha
 * recaptcha library is included in
 */
function recaptcha_init() {

    /* when we create an elgg plugin settings page, elgg tries to handle the form submission
     * here we override the elggs default submit action by a custom function
     * plugin settings file: /recaptcha/views/default/plugins/recaptcha/settings.php
     * submit handler: /recaptcha/actions/recaptcha/settings/save.php
     */
    $actions = __DIR__ . '/actions/recaptcha';
    elgg_register_action('recaptcha/settings/save', "$actions/settings/save.php", 'admin');

    // include the recaptcha in the registration form
    elgg_register_plugin_hook_handler('view', 'forms/register', 'add_recaptcha_to_form');

	// register form check action when the user registration takes place
	elgg_register_plugin_hook_handler('action', 'register', 'recaptcha_check_form');

    // unset the validated recaptcha session variable
    elgg_register_plugin_hook_handler('register', 'user', 'recaptcha_unset_session');
}


/**
 * function to add recaptcha to the registration page
 * modifies the view /views/default/forms/register.php
 *
 * @param $hook
 * @param $type
 * @param $returnvalue
 * @param $params
 *
 * @return bool
 */
function add_recaptcha_to_form($hook, $type, $returnvalue, $params) {

    //add the recaptcha only if it has been enabled in the plugin setting
    $plugin_entity = elgg_get_plugin_from_id('recaptcha');
    $plugin_settings = $plugin_entity->getAllSettings();

    if(array_key_exists('recaptcha_verified', $_SESSION) && $_SESSION['recaptcha_verified'] == 1) {

        // no need for recaptcha again - user has already entered the correct value previously
        $pattern = '<div class="elgg-foot">';
        $recaptcha_option = "<label>".elgg_echo('recaptcha:label:human_verification')."</label><b>Verified</b><br><br>";
        $replace = $pattern . $recaptcha_option;

        $returnvalue = str_replace($pattern, $replace, $returnvalue);
    }
    else {
        if($plugin_settings['require_recaptcha'] == 'on') {

            // include the recaptcha lib
            require_once('lib/recaptchalib.php');

            $publickey = $plugin_settings['recaptcha_public_key'];
            $recaptcha_option = "<label>".elgg_echo('recaptcha:label:human_verification')."</label><br>";
            $recaptcha_option .= recaptcha_get_html($publickey);
            $recaptcha_option .= '<br>';

            $pattern = '<div class="elgg-foot">';
            $replace = $pattern . $recaptcha_option;

            $returnvalue = str_replace($pattern, $replace, $returnvalue);
        }
    }

    return $returnvalue;
}

/**
 * @param $hook
 * @param $type
 * @param $returnvalue
 * @param $params
 *
 * @return bool
 *
 * function called when the below plugin trigger is initiated
 * @see /engine/lib/actions.php
 * @see elgg_trigger_plugin_hook('action', $action, null, $event_result);
 *
 * this hook is triggered for the action = "register"
 * this hooks is called before the default "register" action handler at /actions/register.php
 * checks if recaptcha is valid - if not register an error
 */
function recaptcha_check_form($hook, $type, $returnvalue, $params) {

    // retain entered form values and re-populate form fields if validation error
    elgg_make_sticky_form('register');

    /*-- check if the 'Use Recaptcha for user registration' Plugin setting is enabled --*/

    //fetch the plugin settings
    $plugin_entity = elgg_get_plugin_from_id('recaptcha');
    $plugin_settings = $plugin_entity->getAllSettings();

    if(array_key_exists('recaptcha_verified', $_SESSION) && $_SESSION['recaptcha_verified'] == 1) {
        ; //do nothing
    }
    else {
        if($plugin_settings['require_recaptcha'] == 'on') { //if the setting is enabled

            // include the recaptcha lib
            require_once('lib/recaptchalib.php');

            // check the recaptcha
            $resp = recaptcha_check_answer (
                $plugin_settings['recaptcha_private_key'],
                $_SERVER["REMOTE_ADDR"],
                $_POST["recaptcha_challenge_field"],
                $_POST["recaptcha_response_field"]
            );

            if (! $resp->is_valid) {
                register_error(elgg_echo('recaptcha:human_verification_failed'));
                forward(REFERER);
            }
            else {
                /* note that the user has successfully passed the captcha
                * in case the form submission fails due to other factors, we do not want to
                * ask the user to fill in the captcha details again
                * so we store it in a session variable and destroy it after the form is successfully submitted
                */
                $_SESSION['recaptcha_verified'] = 1;
            }
        }
    }

    return true;
}


/**
 * when the user passes recaptcha for the first time, a value is stored in the session variable
 * $_SESSION['recaptcha_verified'] = 1 - indicates that recaptcha was successful
 * if there is any other error in the form, the user is not presented with the recaptcha again
 * in this function, we unset this session variable after the user registration is successful
 *
 * @param string $hook
 * @param string $type
 * @param bool   $value
 * @param array  $params
 * @return bool
 */
function recaptcha_unset_session($hook, $type, $value, $params) {
    if(array_key_exists('recaptcha_verified', $_SESSION)) {
        unset($_SESSION['recaptcha_verified']);
    }
    return true;
}