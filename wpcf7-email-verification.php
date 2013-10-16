<?php

/**
 * Plugin Name: Contact Form 7 email verification
 * Plugin URI: http://andrewgolightly.com/contact-form-7-email-verification/
 * Description: Extends Contact Form 7 to allow for email addresses to be verified. On a form submission 1) the sender will get emailed a link to click on 2) the form submission will not be sent, but instead will be saved for later. On verification, the form gets sent as per usual for CF7.
 * Version: 0.11
 * Author: Andrew Golightly
 * Author URI: http://andrewgolightly.com
 * License: GPL2
 */

/*  Copyright 2013  Andrew Golightly  (email : support@andrewgolightly.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as 
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

/**
 * Intercept forms being sent by first verifying the senders email address.
 */

add_action( 'wpcf7_before_send_mail', 'wpcf7ev_verify_email_address' );

function wpcf7ev_verify_email_address( &$wpcf7_form )
{
    // Grab the mail template (re-using the code from CF7)
    $mail_template = $wpcf7_form->setup_mail_template( $wpcf7_form->mail, 'mail' );
    // get the sender's email address
    $senders_email_address = $wpcf7_form->replace_mail_tags( $mail_template['sender'] );
    // (optional) send an email to the recipient to let them know verification is pending
    wp_mail($wpcf7_form->replace_mail_tags( $mail_template['recipient'] ), 'Form notice',
            'Hi, You have had a form submission from ' . $senders_email_address .
            '. We are waiting for them to confirm their email address.');
    
    //create hash code for verification key
    $random_hash = substr(md5(uniqid(rand(), true)), -16, 16);
    
    // save submitted form as a transient object
    wpcf7ev_save_form_submission($wpcf7_form, $random_hash);
    
    // send email to the sender with a verification link to click on
    wp_mail($senders_email_address , 'Verify your email address',
            'Hi, For your recent submission to be submitted, please click on the following link: ' . 
            get_site_url() . "/?email-verification-key={$random_hash}");
    
    // prevent the form being sent as per usual
    $wpcf7_form->skip_mail = true;
}

/**
 * Save the Contact Form 7 object as transient data (lifespan = 4 hours).
 * The saved object is automatically serialized.
 */

function wpcf7ev_save_form_submission($cf7_object, $random_hash) {

    $data_to_save = array($cf7_object, $random_hash);
    
    $result = set_transient( wpcf7ev_get_slug($random_hash), $data_to_save , 4 * HOUR_IN_SECONDS );
}

/**
 * Create the slug key for the transient CF7 object
 */

function wpcf7ev_get_slug($random_hash) {
 
    return 'wpcf7ev_' . $random_hash;
}

/**
 * On a page load, check if the query string has the email verification key.
 * If a key exists in the query string and it is found in the database,
 * the saved CF7 object gets sent out as per usual.
 */

add_action( 'template_redirect', 'check_for_verifier' );

function check_for_verifier() {
    
    if(isset($_GET['email-verification-key']))
    {
        $verification_key = $_GET['email-verification-key'];
    
        if(!empty($verification_key))
        {
            $slug = wpcf7ev_get_slug($verification_key);
            
            if(false === ($storedValue = get_transient($slug)))
            {
                wp_mail(get_settings('admin_email'), 'Could not find verification key' ,
                       'Someone clicked on a verification link for a form submission and the '.
                       'corresponding key and transient CF7 object could not be found.');
            }
            else
            {
                // remove the action that triggers this plugin's code
                remove_action( 'wpcf7_before_send_mail', 'wpcf7ev_verify_email_address' );
                $cf7 = $storedValue[0]; // get the saved CF7 object
                $cf7->skip_mail = false; // allow mail to be sent as per usual
                $cf7->mail(); // send mail using the CF7 core code
                        
                // Delete the transient to make sure the email(s) can't be 
                // re-sent if that verification link is clicked on again.
                delete_transient($slug);
            }
        }
    }
}

?>