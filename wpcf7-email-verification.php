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

function wpcf7ev_debug( $message ) {
    echo '<pre>';
    var_dump($message);
    echo '</pre>';
}

function wpcf7ev_verify_email_address( &$wpcf7_form )
{
    //wpcf7ev_debug( "Entering the verify email address function" );
    wp_mail( 'support@andrewgolightly.com', 'The subject', 'The message' + print_r($wpcf7_form) );
    // save submitted form to the database
    
    // send email to the submitter with a verification link to click on
    //$submittersEmail = $wpcf7_form->posted_data["your-email"];
    
    // prevent the form being sent as per usual
    $wpcf7_form->skip_mail = true;
}



// add uninstall code
//register_uninstall_hook()

?>