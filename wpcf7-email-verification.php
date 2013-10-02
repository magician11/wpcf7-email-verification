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

// debug function that emails me human-readable information about a variable
function wpcf7ev_debug( $message ) {
    wp_mail( 'support@andrewgolightly.com', 'Debug code', print_r($message, true));
}

/* get the tags from the senders field in Contact Form 7 and return the actual
post_data for those tags
*/
function wpcf7ev_get_senders_email_address($wpcf7_form)
{
    wpcf7ev_debug("Getting email address to verify...");
    $tagName = "your-name";
    wpcf7ev_debug($wpcf7_form->posted_data[$tagName]);
    
    $senderTags = $wpcf7_form->mail['sender'];
    
    // replace <> with posted_data
    
    $sendersEmailAddress = preg_replace_callback('/\[(.+?)\]/',
                                                function ($matches)
                                                {
                                                    //wpcf7ev_debug($wpcf7_form->posted_data);
                                                    $currentTag = $wpcf7_form->posted_data[$tagName];
                                                    wpcf7ev_debug($currentTag);
                                                    //return 
                                                    //wpcf7ev_debug($matches);
                                                    return "bob";
                                                },
                                                $senderTags
                                               );
                                               
    //$sendersEmailAddress = preg_replace('/\[(.+?)\]/', "bob", $senderTags);
    
    wpcf7ev_debug("Senders email address: " . $sendersEmailAddress);
}

function wpcf7ev_verify_email_address( &$wpcf7_form )
{
    // get the email address it's being sent from
    /* 
        todo: I wonder if there is a way to not hardcode in "your-email" as senders email address?
        Something to do with parsing $wpcf7_form->mail['sender']; ??
    */
    //wpcf7ev_debug( "Need to verify the following email address " .  $submittersEmailAddress);
    $submittersEmailAddress = wpcf7ev_get_senders_email_address($wpcf7_form);
    
    // save submitted form to the database
    
    // get the email address it's being sent from
    
    
    // send email to the submitter with a verification link to click on
    //$submittersEmail = $wpcf7_form->posted_data["your-email"];
    
    // prevent the form being sent as per usual
    $wpcf7_form->skip_mail = true;
}



// add uninstall code
//register_uninstall_hook()

?>