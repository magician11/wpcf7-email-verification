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

/*
    get the tags from the senders field in Contact Form 7 and return the actual
    post_data for those tags
    
    e.g. "[your-name] <[your-email]>" --> "Harry <harry@potter.com>"
*/
function wpcf7ev_get_senders_email_address($wpcf7_form)
{    
    // grab sender info    
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

function wpcf7ev_verify_email_address( &$wpcf7_form )
{
    // get the email address it's being sent from
    $submittersEmailAddress = wpcf7ev_get_senders_email_address($wpcf7_form);
    wpcf7ev_debug("Need to verify " . $submittersEmailAddress);
    
    // save submitted form to the database
    wpcf7ev_save_form_submission($wpcf7_form);
    
    // send email to the submitter with a verification link to click on
    wp_mail($submittersEmailAddress , 'Debug code', "Thanks for your message.");
    
    // prevent the form being sent as per usual
    $wpcf7_form->skip_mail = true;
}

function wpcf7ev_save_form_submission($wpcf7_form) {
}


// add uninstall code
//register_uninstall_hook()

register_activation_hook( __FILE__, 'wpcf7ev_activation' );

function wpcf7ev_activation() {

    // Get access to global database access class
    global $wpdb;
    
    // Prepare SQL query to create database table
    // using function parameter
    // todo: decide what should be written to the database.. probably the whole object as a string
    $creation_query =
        'CREATE TABLE IF NOT EXISTS wpcf7ev (
        `wpcf7ev_id` int(20) NOT NULL AUTO_INCREMENT,
        `bug_description` text, 
        `bug_version` varchar(10) DEFAULT NULL,
        `bug_report_date` date DEFAULT NULL,
        `bug_status` int(3) NOT NULL DEFAULT 0,
        PRIMARY KEY (`bug_id`)
    );';
    global $wpdb;
    $wpdb->query( $creation_query );
}


?>