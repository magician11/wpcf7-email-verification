<?php

/**
 * Plugin Name: Contact Form 7 email verification
 * Plugin URI: http://golightlyplus.com/code/contact-form-7-email-verification/
 * Description: Extends Contact Form 7 to allow for email addresses to be verified via a link sent to the sender's email address. There is currently no settings page for this plugin.
 * Version: 0.38
 * Author: Andrew Golightly
 * Author URI: http://www.golightlyplus.com
 * License: GPL2
 */

/*  Copyright 2013  Andrew Golightly  (email : andrew@golightlyplus.com)

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

add_action( 'wpcf7_before_send_mail', 'wpcf7ev_verify_email_address' );

function wpcf7ev_verify_email_address( &$wpcf7_form )
{
    // Grab the mail template (re-using the code from CF7)
    $mail_template = $wpcf7_form->setup_mail_template( $wpcf7_form->mail, 'mail' );
    // get the sender's email address
    $senders_email_address = $wpcf7_form->replace_mail_tags( $mail_template['sender'] );
    // send an email to the recipient to let them know verification is pending
    wp_mail($wpcf7_form->replace_mail_tags( $mail_template['recipient'] ), 'Form notice',
            "Hi,\n\nYou've had a form submission on " . get_option('blogname') . " from " . $senders_email_address .
            ".\n\nWe are waiting for them to confirm their email address.");

    //create hash code for verification key
    $random_hash = substr(md5(uniqid(rand(), true)), -16, 16);

    // save submitted form as a transient object
    wpcf7ev_save_form_submission($wpcf7_form, $random_hash);

    // send email to the sender with a verification link to click on
    wp_mail($senders_email_address , 'Verify your email address',
            "Hi,\n\nThanks for your your recent submission on " . get_option('blogname') .
            ".\n\nIn order for your submission to be processed, please verify this is your email address by clicking on the following link:\n\n" . 
            get_site_url() . "/wp-admin/admin-post.php?action=wpcf7ev&email-verification-key={$random_hash}" . "\n\nThanks.");

    // prevent the form being sent as per usual
    $wpcf7_form->skip_mail = true;
}

/**
 * Save the Contact Form 7 object as transient data.
 * The saved object is automatically serialized.
 */

function wpcf7ev_save_form_submission($cf7_object, $random_hash) {

    // make a copy of the cf7 object so that it keeps a reference to the original filepaths
    $cf7ev_object = clone $cf7_object;

    // if there are attachemnts, save them
    if(!empty($cf7ev_object->uploaded_files)) {

        //if the wpcf7ev directory does not exist, create it
        if (!is_dir(WPCF7EV_UPLOADS_DIR)) {
            mkdir(WPCF7EV_UPLOADS_DIR, 0733, true);
        }

        // move the attachments to wpcf7ev temp folder
        $updated_filepaths = array();
        foreach ($cf7ev_object->uploaded_files as $key => $uploaded_file_path) {
            // make sure the file name is unique in the directory it's being saved to
            $new_filepath = WPCF7EV_UPLOADS_DIR . wp_unique_filename( WPCF7EV_UPLOADS_DIR, basename($uploaded_file_path) );
            rename($uploaded_file_path, $new_filepath);
            $updated_filepaths[$key] = $new_filepath;
        }

        // update the cloned cf7 object with the new filepaths of the attachments
        $cf7ev_object->uploaded_files = $updated_filepaths;
    }

    $data_to_save = array($cf7ev_object, $random_hash);

    set_transient( wpcf7ev_get_slug($random_hash), $data_to_save , WPCF7EV_STORAGE_TIME );
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
                // remove the action that triggers this plugin's code
                remove_action( 'wpcf7_before_send_mail', 'wpcf7ev_verify_email_address' );
                $cf7 = $storedValue[0]; // get the saved CF7 object
                $cf7->skip_mail = false; // allow mail to be sent as per usual
                $cf7->mail(); // send mail using the CF7 core code
                // display a confirmation message then redirect back to the homepage after 8 seconds
                echo('<h2>Thank you. Verification key accepted.</h2>' . 
                     '<p>Your form submission will now be processed.</p>' . 
                     '<p>If you are not redirected back to the homepage in 8 seconds, <a href="' . get_site_url() . '">click here</a>.</p>' .
                     '<script> setTimeout(function () { window.location.href = "' . get_site_url() . '"; }, 8000); </script>');
            }
        }
    }

    get_footer();
}

/**
 * Clean up any attachments that are older than the transient storage time.
 */

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