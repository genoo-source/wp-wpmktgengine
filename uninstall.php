<?php
/**
 * This file is part of the WPMKTGENGINE plugin.
 *
 * Copyright 2016 Genoo, LLC. All rights reserved worldwide.  (web: http://www.wpmktgengine.com/)
 * GPL Version 2 Licensing:
 *  PHP code is licensed under the GNU General Public License Ver. 2 (GPL)
 *  Licensed "As-Is"; all warranties are disclaimed.
 *  HTML: http://www.gnu.org/copyleft/gpl.html
 *  Text: http://www.gnu.org/copyleft/gpl.txt
 *
 * Proprietary Licensing:
 *  Remaining code elements, including without limitation:
 *  images, cascading style sheets, and JavaScript elements
 *  are licensed under restricted license.
 *  http://www.wpmktgengine.com/terms-of-service
 *  Copyright 2016 Genoo LLC. All rights reserved worldwide.
 */

/**
 * Wordpress core & uninstall check
 */

if (!defined('ABSPATH') && !defined('WP_UNINSTALL_PLUGIN')){ exit(); }

/**
 * WPMKTENGINE Uninstall function
 */

function wpmktgengineUninstall()
{
    global $wpdb;

    /**
     * 1. Delete genoo options in worpdress database,
     *    clean after us.
     */

    delete_option('WPMKTENGINEApiSettings');
    delete_option('WPMKTENGINEApiGeneral');
    delete_option('WPMKTENGINEThemeSettings');
    delete_option('WPMKTENGINEFormMessages');
    delete_option('WPMKTENGINEDebug');
    delete_option('WPMKTENGINEDebugCheck');

    /**
     * 2. Delete saved Widget settings
     */

    delete_option('widget_genoocta');
    delete_option('widget_genooform');
    delete_option('widget_genoolumen');


    /**
     * 3. Go through users, and delete user nag meta
     */

    $users = get_users(array('who' => array('administrator')));
    if(!empty($users)){
        foreach($users as $user){
            delete_user_option($user->ID, 'hideGenooNag');
            delete_user_option($user->ID, 'hideGenooApi');
            delete_user_option($user->ID, 'hideGenooSidebar');
        }
    }

    /**
     * 4. Delete widgets backup
     */

    delete_option('genoo_sidebars_widgets');

    /**
     * 5. Delete CTA"s
     */

    // Table names
    $posts = $wpdb->posts;
    $postsMeta = $wpdb->postmeta;
    $postsRelationships = $wpdb->term_relationships;
    // Queries
    $wpdb->query("DELETE FROM $posts WHERE post_type='cta'");
    $wpdb->query("DELETE FROM $postsMeta WHERE post_id NOT IN (SELECT id FROM $posts)");
    $wpdb->query("DELETE FROM $postsRelationships WHERE object_id NOT IN (SELECT id FROM $posts)");
}


/**
 * Go Go Go!
 */

if (function_exists('is_multisite') && is_multisite()){
    global $wpdb;
    $blogs = $wpdb->get_col("SELECT blog_id FROM {$wpdb->blogs}");
    foreach ($blogs as $blog){
        switch_to_blog($blog);
        wpmktgengineUninstall();
        restore_current_blog();
    }
} else {
    wpmktgengineUninstall();
}


