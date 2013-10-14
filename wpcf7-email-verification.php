<?php

/**
 * Plugin Name: Contact Form 7 email verification
 * Plugin URI: http://andrewgolightly.com/contact-form-7-email-verification/
 * Description: Extends Contact Form 7 to allow for email addresses to be verified by getting a user to click on a link that is sent to their email address.
 * Version: 0.1
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

add_action( 'wpcf7_before_send_mail', 'wpcf7ev_verify_email_address' );

function wpcf7ev_verify_email_address( &$wpcf7_form )
{
    // get the email address it's being sent from
    $submittersEmailAddress = wpcf7ev_get_senders_email_address($wpcf7_form);
    wp_mail('support@andrewgolightly.com', 'Form notice', 'Hi Andrew,
    We have had a form submission from' . $submittersEmailAddress . '. We are waiting for them to confirm their email address');
    
    //create hash code
    $random_hash = substr(md5(uniqid(rand(), true)), -16, 16);
    
    // save submitted form as a transient object
    wpcf7ev_save_form_submission($wpcf7_form, $random_hash);
    
    // send email to the submitter with a verification link to click on
    wp_mail($submittersEmailAddress , 'Verify your email address', "Please verify your email address by clicking " . 
            get_site_url() . "/?email-verification-key={$random_hash}");
    
    // prevent the form being sent as per usual
    $wpcf7_form->skip_mail = true;
}

// debug function that emails me human-readable information about a variable
function wpcf7ev_debug( $message ) {
    wp_mail( 'support@andrewgolightly.com', 'Debug code', print_r($message, true));
}

/*
    get the tags from the senders field in Contact Form 7 and return the actual
    post_data for those tags
    
    e.g. "[your-name] <[your-email]>" --> "Harry <harry@potter.com>"
*/
function wpcf7ev_get_senders_email_address($wpcf7_form)
{    
    // grab sender's tags
    $senderTags = $wpcf7_form->mail['sender'];
    
    // replace tag names with posted_data using regex
    return $sendersEmailAddress = preg_replace_callback('/\[(.+?)\]/',
                                 function ($matches) use ($wpcf7_form)
                                 {
                                     return $wpcf7_form->posted_data[$matches[1]];
                                 },
                                 $senderTags
                                 );
}



// save the Contact Form 7 obje$ct as transient data (lifespan = 4 hours). The object is automatically serialized.
function wpcf7ev_save_form_submission($form_data, $random_hash) {

    $data_to_save = array($form_data, $random_hash);
    
    $result = set_transient( wpcf7ev_get_slug($random_hash), $data_to_save , 4 * HOUR_IN_SECONDS );
}

function wpcf7ev_get_slug($random_hash) {
 
    return 'wpcf7ev_' . $random_hash;
}


add_action( 'template_redirect', 'check_for_verifier' );

// When a user clicks that verification link, this function will be called.
// If that key is found, the emails get sent out as per usual.
function check_for_verifier() {
    
    if(isset($_GET['email-verification-key']))
    {
        $verification_key = $_GET['email-verification-key'];
    
        if(!empty($verification_key))
        {
            if(false === ($storedValue = get_transient(wpcf7ev_get_slug($verification_key))))
            {
                wpcf7ev_debug("Could not find stored value.");
            }
            else
            {
                wpcf7ev_debug("We have a match!!");
                $cf7 = $storedValue[0];
                $cf7->compose_mail( $cf7->setup_mail_template( $cf7->mail, 'mail' ) );
                    
                if ( $cf7->mail_2['active'] )
                    $cf7->compose_mail( $cf7->setup_mail_template( $cf7->mail_2, 'mail_2' ) );
                
                //todo: remove the transient object once the email has been sent
            }
        }
    }
}

?>