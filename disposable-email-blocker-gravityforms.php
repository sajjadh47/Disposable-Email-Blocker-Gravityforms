<?php
/**
 * Plugin Name: Disposable Email Blocker - GravityForms
 * Plugin URI: https://wordpress.org/plugins/disposable-email-blocker-wpforms/
 * Author: Sajjad Hossain Sagor
 * Description: Prevent From Submitting Any Disposable/Temporary Emails On GravityForms Forms.
 * Version: 1.0.2
 * Author URI: https://sajjadhsagor.com
 * Text Domain: disposable-email-blocker-gravityforms
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

// plugin root path....
define( 'DEBGFFORMS_ROOT_DIR', dirname( __FILE__ ) );

// plugin root url....
define( 'DEBGFFORMS_ROOT_URL', plugin_dir_url( __FILE__ ) );

// plugin version
define( 'DEBGFFORMS_VERSION', '1.0.0' );

// load translation files...
add_action( 'plugins_loaded', 'debgfforms_load_plugin_textdomain' );

function debgfforms_load_plugin_textdomain()
{	
	load_plugin_textdomain( 'disposable-email-blocker-gravityforms', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
}

add_filter( 'gform_form_settings', 'debgfforms_add_new_form_field', 10, 2 );

function debgfforms_add_new_form_field( $settings, $form )
{    
    $settings[ __( 'Form Options', 'gravityforms' ) ]['block_disposable_emails'] = '
        <tr>
            <th>Block Disposable Emails</th>
            <td><input type="checkbox" ' . checked( rgar( $form, 'block_disposable_emails' ), 'on', false ) . ' name="block_disposable_emails" id="block_disposable_emails">
            	<label for="block_disposable_emails">Enable Blocking Disposable/Temporary Emails</label>
            </td>
        </tr>
        <tr>
            <th>Disposable Email Found Text</th>
            <td><input type="text" class="fieldwidth-3" value="' . rgar( $form, 'disposable_emails_found_msg' ) . '" name="disposable_emails_found_msg" id="disposable_emails_found_msg" placeholder="Disposable/Temporary emails are not allowed!">
            </td>
        </tr>';
 
    return $settings;
}
 
// save your custom form setting
add_filter( 'gform_pre_form_settings_save', 'save_debgfforms_form_field' );

function save_debgfforms_form_field( $form )
{
	$form['block_disposable_emails'] = rgpost( 'block_disposable_emails' );

	$form['disposable_emails_found_msg'] = rgpost( 'disposable_emails_found_msg' );	
    
    return $form;
}

// check if disposable email is found and if so then mark form as invalid and show message

add_filter( 'gform_field_validation', 'debgfforms_block_disposable_emails', 10, 4 );

function debgfforms_block_disposable_emails( $result, $value, $form, $field )
{
	// if not blocking is enabled return early	
	if ( $form['block_disposable_emails'] !== 'on' ) return $result;
    
    if ( $field->get_input_type() === 'email' )
    {
    	$msg = ( empty( $form['disposable_emails_found_msg'] ) ) ? 'Disposable/Temporary emails are not allowed! Please use a non temporary email' : $form['disposable_emails_found_msg'];

    	if( filter_var( $value, FILTER_VALIDATE_EMAIL ) )
    	{
    		// split on @ and return last value of array (the domain)
	    	$domain = explode('@', $value );
	    	
	    	$domain = array_pop( $domain );

	    	// get domains list from json file
			$disposable_emails_db = file_get_contents( DEBGFFORMS_ROOT_DIR . '/assets/data/domains.min.json' );

			// convert json to php array
			$disposable_emails = json_decode( $disposable_emails_db );

			// check if domain is in disposable db
			if ( in_array( $domain, $disposable_emails ) )
			{	
				$result['is_valid'] = false;

				$result['message']  = $msg;
			}
	    }
    }

    return $result;
}
