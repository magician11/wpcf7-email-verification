<?php

/**
 * Plugin Name: Contact Form 7 email verification
 * Plugin URI: http://golightlyplus.com/code/#contact-form-7-email-verification
 * Description: Extends Contact Form 7 to allow for email addresses to be verified via a link sent to the sender's email address. There is currently no settings page for this plugin.
 * Version: 0.55
 * Author: Andrew Golightly
 * Author URI: http://www.golightlyplus.com
 * License: GPL2
 */

/*  Copyright 2014  Andrew Golightly  (email : andrew@golightlyplus.com)

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
 * Globals
 */

define('WPCF7EV_UPLOADS_DIR', ABSPATH . 'wp-content/uploads/wpcf7ev_files/');
define('WPCF7EV_STORAGE_TIME', 16 * HOUR_IN_SECONDS);

/**
 * Intercept Contact Form 7 forms being sent by first verifying the senders email address.
 */

function wpcf7ev_skip_sending($components) {

    $components['send'] = false;

    return $components;
}

// prettify the email addresses being sent
add_filter( 'wp_mail_from', function($email_address){

    return get_option('admin_email');
}, 9);

add_filter( 'wp_mail_from_name', function($from_name){

    return get_option('blogname');
}, 9);

// then request the email address to be verified and save the submission as a transient
add_action( 'wpcf7_before_send_mail', 'wpcf7ev_verify_email_address' );

function wpcf7ev_verify_email_address( $wpcf7_form )
{
    // first prevent the emails being sent as per usual
    add_filter('wpcf7_mail_components', 'wpcf7ev_skip_sending');

    // fetch the submitted form details   
    $mail_tags = $wpcf7_form->prop('mail');
    $mail_fields = wpcf7_mail_replace_tags( $mail_tags );
    $senders_email_address = $mail_fields['sender'];

    // save any attachments to a temp directory
    $mail_string = trim($mail_fields['attachments']);
    if(strlen($mail_string) > 0 and !ctype_space($mail_string)) {
        $mail_attachments = explode(" ", $mail_string);
        foreach($mail_attachments as $attachment) {
            $uploaded_file_path = ABSPATH . 'wp-content/uploads/wpcf7_uploads/' . $attachment;
            $new_filepath = WPCF7EV_UPLOADS_DIR . $attachment;
            rename($uploaded_file_path, $new_filepath);
        }
    }

    // send an email to the recipient to let them know verification is pending
    wp_mail($mail_fields['recipient'], 'Form notice',
            "Hi,\n\nYou've had a form submission on " . get_option('blogname') . " from " .
            $senders_email_address .
            ".\n\nWe are waiting for them to confirm their email address.");

    //create hash code for verification key
    $random_hash = substr(md5(uniqid(rand(), true)), -16, 16);

    // save submitted form as a transient object
    $data_to_save = array($mail_fields, $random_hash);
    set_transient( wpcf7ev_get_slug($random_hash), $data_to_save , WPCF7EV_STORAGE_TIME );

    // send email to the sender with a verification link to click on
    wp_mail($senders_email_address , 'Verify your email address',
            "Hi,\n\nThanks for your your recent submission on " . get_option('blogname') .
            ".\n\nIn order for your submission to be processed, please verify this is your email address by clicking on the following link:\n\n" . 
            get_site_url() . "/wp-admin/admin-post.php?action=wpcf7ev&email-verification-key={$random_hash}" . "\n\nThanks.");
}

add_action('wpcf7_mail_sent', 'wpcf7ev_cleanup');
add_action('wpcf7_mail_failed', 'wpcf7ev_cleanup');

function wpcf7ev_cleanup() {
    // remove the action that triggers this plugin's code
    remove_action( 'wpcf7_before_send_mail', 'wpcf7ev_verify_email_address' );
    remove_filter( 'wpcf7_mail_components', 'wpcf7ev_skip_sending' ); // allow mail to be sent as per usual
}

/**
 * Create the slug key for the transient CF7 object
 */

function wpcf7ev_get_slug($random_hash) {

    return 'wpcf7ev_' . $random_hash;
}

/**
 * Process the clicked link sent to the sender's email address.
 * If the verification key exists in the query string and it is found in the database,
 * the saved form submission gets sent out as per usual.
 */

// creating custom handlers for my own custom GET requests.
add_action( 'admin_post_wpcf7ev', 'wpcf7ev_check_verifier' );
add_action( 'admin_post_nopriv_wpcf7ev', 'wpcf7ev_check_verifier' );

// check the verification key
function wpcf7ev_check_verifier() {

    set_current_screen('wpcf7ev');

    // output the header of the theme being used
    status_header(200);
    get_header();

    if(isset($_GET['email-verification-key']))
    {
        $verification_key = $_GET['email-verification-key'];

        if(!empty($verification_key))
        {
            $slug = wpcf7ev_get_slug($verification_key);

            // if the stored data is not found, send out an error message
            if(false === ($storedValue = get_transient($slug)))
            {
                wp_mail(get_settings('admin_email'), 'Something went wrong' ,
                        'Someone attempted to verify a link for a form submission and the '.
                        "corresponding key and transient CF7 object could not be found.\n\n".
                        "The verification key used was: {$verification_key}");
                echo('<h2>Whoops! Something went wrong.</h2>' . 
                     "<ul><li>Did you make sure you clicked on the link and not copy-and-pasted it incorrectly?</li><li>Otherwise it's most likely you took more than a few hours to click the verification link?</li></ul><p>No problem, please submit your form again.</p>");
            }
            else
            {
                $cf7_mail_fields = $storedValue[0]; // get the saved CF7 object
                // create an array of the temp location of any attachments
                $mail_string = trim($cf7_mail_fields['attachments']);
                $mail_attachments = (strlen($mail_string) > 0 and !ctype_space($mail_string)) ? array_map(function($attachment) {
                    return WPCF7EV_UPLOADS_DIR . $attachment;
                }, explode(" ", $mail_string)) : ' ';
                // send out the email as per usual
                wp_mail($cf7_mail_fields['recipient'], $cf7_mail_fields['subject'], $cf7_mail_fields['body'],'', $mail_attachments);

                // display a confirmation message then redirect back to the homepage after 8 seconds
                echo('<h2 style="text-align:center;">Thank you. Verification key accepted.</h2>' . 
                     '<p style="text-align:center;">Your form submission will now be processed.</p>' . 
                     '<p style="text-align:center;">If you are not redirected back to the homepage in 8 seconds, <a href="' . get_site_url() . '">click here</a>.</p>' .
                     '<script> setTimeout(function () { window.location.href = "' . get_site_url() . '"; }, 8000); </script>');
            }
        }
    }

    get_footer();
}

/**
 * Clean up any attachments that are older than the transient storage time.
 */

// this hook gets called everytime a form submission is made (verified or not)

add_action( 'wpcf7_mail_sent', 'wpcf7ev_cleanup_attachments' );

function wpcf7ev_cleanup_attachments() {

    if ( $handle = @opendir( WPCF7EV_UPLOADS_DIR ) ) {

        while ( ( $file = readdir( $handle ) ) !== false ) {

            // if the current file is any of these, skip it
            if ( $file == "." || $file == ".." || $file == ".htaccess" )
                continue;

            $file_info = stat( WPCF7EV_UPLOADS_DIR . $file );
            if ( $file_info['mtime'] + WPCF7EV_STORAGE_TIME < time() ) {
                @unlink( WPCF7EV_UPLOADS_DIR . $file );
            }
        }

        closedir( $handle );
    }
}

?>