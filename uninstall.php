<?php

    // Check that file was called from WordPress admin
    if( !defined( 'WP_UNINSTALL_PLUGIN' ) )
        exit();
    
    global $wpdb;
    $wpdb->query( $wpdb->prepare( 'DROP TABLE wpcf7ev' ) );
?>