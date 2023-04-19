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

namespace WPMKTENGINE;

use WPME\ApiExtension\Surveys;
use WPME\Extensions\RepositorySurveys;
use WPME\Extensions\TableSurveys;
use WPME\RepositorySettingsFactory;
use WPMKTENGINE\Wordpress\Helpscreen;
use WPMKTENGINE\Wordpress\MetaboxArea;
use WPMKTENGINE\Wordpress\MetaboxStyles;
use WPMKTENGINE\Wordpress\Utils;
use WPMKTENGINE\Wordpress\Settings;
use WPMKTENGINE\Wordpress\Page;
use WPMKTENGINE\Wordpress\Notice;
use WPMKTENGINE\Wordpress\Nag;
use WPMKTENGINE\Wordpress\Metabox;
use WPMKTENGINE\Wordpress\PostType;
use WPMKTENGINE\Wordpress\Action;
use WPMKTENGINE\Wordpress\Filter;
use WPMKTENGINE\Utils\Strings;
use WPMKTENGINE\Wordpress\MetaboxCTA;





class Admin
{

    /** @var bool */

    private static $instance = false;

    /** @var array Admin Messages */

    var $notices = array();

    /** @var \WPMKTENGINE\RepositorySettings */

    var $repositarySettings;

    /** @var \WPMKTENGINE\RepositoryForms */
    var $repositaryForms;
    /** @var \WPMKTENGINE\RepositoryPages */
    var $repositaryPages;
    /** @var \WPMKTENGINE\RepositoryLumens */
    var $repositaryLumens;
    /** @var \WPMKTENGINE\RepositoryCTA  */
    var $repositaryCTAs;
    /** @var \WPME\Extensions\RepositorySurveys */
    var $repositorySurveys;
    /** @var \WPMKTENGINE\RepositoryUser */
    var $user;
    /** @var \WPMKTENGINE\Api */
    var $api;
    /** @var \WPMKTENGINE\Wordpress\Settings */
    var $settings;
    /** @var \WPMKTENGINE\Cache */
    var $cache;
    /** @var \WPMKTENGINE\TableForms */
    var $tableForms;
    /** @var \WPMKTENGINE\TableLumens */
    var $tableLumens;
    /** @var \WPMKTENGINE\TablePages */
    var $tablePages;


    /**
     * Constructor
     */

    public function __construct(\WPME\ApiFactory $api = null, \WPME\CacheFactory $cache = null)
    {
        // vars
        $this->cache = $cache ? $cache : new \WPME\CacheFactory(WPMKTENGINE_CACHE);
        $this->repositarySettings = new RepositorySettingsFactory();
        $this->api = $api ? $api : new \WPME\ApiFactory($this->repositarySettings);
        $this->repositaryForms = new RepositoryForms($this->cache, $this->api);
        $this->repositaryPages = new RepositoryPages($this->cache, $this->api);
        $this->repositaryLumens = new RepositoryLumens($this->cache, $this->api);
        $this->repositaryCTAs = new RepositoryCTA($this->cache);
        $this->repositarySurveys = new RepositorySurveys($this->cache, new Surveys($this->repositarySettings));
        // Flush cache on settings page
        $currentUrl = \WPMKTENGINE\Wordpress\Utils::getRealUrl();
        global $WPME_CACHE;
        if($WPME_CACHE && \WPMKTENGINE\Utils\Strings::endsWith($currentUrl, '/admin.php?page=WPMKTENGINE')){
            try {
                $WPME_CACHE->remove('leadtypes', 'settings');
            } catch (\Exception $e){
                // File doesn't exist, good
            }
        }
        // initialise settings and users
        Action::add('init', array($this, 'init'), 1);
        // admin constructor
        Action::add('current_screen', array($this, 'adminCurrentScreen'), 10, 1);
        Action::add('admin_init', array($this, 'adminInit'));
        Action::add('init', array($this, 'adminUI'));
        Action::add('admin_menu', array($this, 'adminMenu'));
        Action::add('init', array($this, 'adminPostTypes'));
        Action::add('admin_notices', array ($this, 'adminNotices'));
        Action::add('admin_enqueue_scripts', array($this, 'adminEnqueueScripts'), 10, 1);
        Action::add('elementor/editor/before_enqueue_scripts', array($this, 'adminEnqueueScripts'), 10);
        Action::add('admin_head', array($this, 'adminHead'), 10);
        Action::add('do_meta_boxes', array($this, 'removeMetaboxes'), 10000); // remove metaboxes
        Action::add('wp_print_scripts', array($this, 'removeDequeue'), 10000); // remove hooks colliding
        // we need this for dashicons fallback
        Filter::add('admin_body_class', array($this, 'adminBodyClass'), 20, 1);
        // Update option api
        Action::add('wp_ajax_update_option_api', function(){
            // Check
            if (!current_user_can('edit_posts')) return;
            check_ajax_referer('Genoo');
            // Code
            $option = sanitize_text_field($_POST['option']);
            $value = sanitize_text_field($_POST['value']);
            if((isset($option) && !empty($option)) && (isset($value) && !empty($value))){
                $option = $option == 'apikey' ? 'apiKey' : 'apiExternalTrackingCode';
                $repo = new \WPME\RepositorySettingsFactory();
                $repo->injectSingle($option, $value, 'WPMKTENGINEApiSettings');
                echo json_encode(array(
                    'status' => 'ok',
                ));
                die;
            }
            echo json_encode(array(
                'status' => 'fail',
            ));
            die;
        });
        // Update option api
        Action::add('wp_ajax_update_leads', function(){
            // Check
            if (!current_user_can('edit_posts')) return;
            check_ajax_referer('Genoo');
            // Code
            try {
                $settingsRepo = new \WPME\RepositorySettingsFactory();
                $settingsApi = new \WPME\ApiFactory($settingsRepo);
                $settingsRepo->setFirstLeadTypes($settingsApi);
                echo json_encode(array(
                    'status' => 'ok',
                ));
            } catch (\Exception $e){
                echo json_encode(array(
                    'status' => 'fail',
                ));
            }
            die;
        });
        // Update option api
        Action::add('wp_ajax_refresh_forms', function(){
            // Check
            if (!current_user_can('edit_posts')) return;
            check_ajax_referer('Genoo');
            // Code
            try {
                $cache = new \WPMKTENGINE\Cache(WPMKTENGINE_CACHE);
                $cache->flush(\WPMKTENGINE\RepositoryForms::REPO_NAMESPACE);
                echo json_encode(array(
                    'status' => 'ok',
                ));
            } catch (\Exception $e){
                echo json_encode(array(
                    'status' => 'fail',
                ));
            }
            die;
        });
        // Update option api
        Action::add('wp_ajax_refresh_surveys', function(){
            // Check
            if (!current_user_can('edit_posts')) return;
            check_ajax_referer('Genoo');
            // Code
            try {
                $cache = new \WPMKTENGINE\Cache(WPMKTENGINE_CACHE);
                $cache->flush(\WPME\Extensions\RepositorySurveys::REPO_NAMESPACE);
                echo json_encode(array(
                    'status' => 'ok',
                ));
            } catch (\Exception $e){
                echo json_encode(array(
                    'status' => 'fail',
                ));
            }
            die;
        });
        // Check if url exists
        Action::add('wp_ajax_check_url', function(){
            // Check
            if (!current_user_can('edit_posts')) return;
            check_ajax_referer('Genoo');
            // Code
            $url = esc_url($_POST['url']);
            $exists = get_page_by_path(ltrim($url, '/'));
            echo is_null($exists) ? 'FALSE' : 'TRUE';
            die;
        });
        // Post edit and Preview Modal
        Filter::add('redirect_post_location', function($location, $post){
            // No need to sanatize, not saving
            if(isset($_POST['previewModal'])){
                $location = Utils::addQueryParam($location, 'previewModal', 'true');
            }
            if(isset($_POST['previewLandingPage'])){
                $location = Utils::addQueryParam($location, 'previewLandingPage', 'true');
            }
            return $location;
        }, 10, 2);
    }

    /**
     * Init variables
     */
    public function init()
    {
        global $WPME_API;
        // We first need to get these, but only after init,
        // to retrieve all custom post types correclty
        $this->user = new RepositoryUser();
        $this->settings = new Settings($this->repositarySettings, $this->api);
    }

    /**
     * Admin Head used only to open new preview landing page window
     */

    public function adminHead()
    {
        // Global post
        global $post;
        // Is preview landing page?
        if(isset($_GET) && is_array($_GET) && array_key_exists('previewLandingPage', $_GET)){
            if(isset($post) && $post instanceof \WP_Post && isset($post->post_type)){
                if($post->post_type == 'wpme-landing-pages'){
                    // url
                    $url = get_post_meta($post->ID, 'wpmktengine_landing_url', TRUE);
                    $url = RepositoryLandingPages::base() . $url;
                    $location = $url;
                    // We have lanidng page URL, let's see
                    ?>
                    <script type="text/javascript"> var win = window.open('<?php echo $url; ?>', '_blank'); win.focus(); </script>
                    <?php
                }
            }
        }
    }

    /**
     * Enqueue Scripts
     */

    public function adminEnqueueScripts($hook)
    {
        // scripts
        wp_enqueue_style('core', WPMKTENGINE_ASSETS . 'GenooAdmin.css', null, WPMKTENGINE_REFRESH);
        wp_enqueue_script('Genoo', WPMKTENGINE_ASSETS . 'Genoo.js', null, WPMKTENGINE_REFRESH, false);
        // if post edit or add screeen
        if($hook == 'post-new.php' || $hook == 'post.php'){
            wp_enqueue_script('GenooEditPost', WPMKTENGINE_ASSETS . 'GenooEditPosts.js', array('jquery'), WPMKTENGINE_REFRESH);
        }
        // if setup up add vars
        if(WPMKTENGINE_SETUP){
            // JS variables
            wp_localize_script('Genoo', 'GenooVars', array(
                'GenooSettings' => array(
                    'WPMKTENGINE_PART_SETUP' => WPMKTENGINE_PART_SETUP,
                    'WPMKTENGINE_SETUP' => WPMKTENGINE_SETUP,
                    'WPMKTENGINE_LUMENS' => WPMKTENGINE_LUMENS
                ),
                'DOMAIN' => WPMKTENGINE_DOMAIN,
                'AJAX' => admin_url('admin-ajax.php'),
                'AJAX_NONCE' => wp_create_nonce('Genoo'),
                'GenooPluginUrl' => WPMKTENGINE_ASSETS,
                'GenooMessages'  => array(
                    'importing'  => __('Importing...', 'wpmktengine'),
                ),
                'SHORTCODE' => array(
                    'CTA' => apply_filters('genoo_wpme_cta_shortcode', 'WPMKTENGINECTA'),
                    'FORM' => apply_filters('genoo_wpme_form_shortcode', 'WPMKTENGINEForm'),
                    'SURVEY' => apply_filters('genoo_wpme_survey_shortcode', 'WPMKTENGINESurvey'),
                    'LUMENS' => 'genooLumens',
                ),
                'EDITOR' => array(
                    'CTA' => $this->repositaryCTAs->getArrayTinyMCE(),
                    'Form' => $this->repositaryForms->getFormsArrayTinyMCE(),
                    'Lumens' => $this->repositaryLumens->getLumensArrayTinyMCE(),
                    'Survey' => $this->repositarySurveys->getSurveysArrayTinyMCE(),
                    'Themes' => $this->repositarySettings->getSettingsThemesArrayTinyMCE(),
                ),
            ));
            // Register editor styles
            add_editor_style(WPMKTENGINE_ASSETS . 'GenooEditor.css?v=' . WPMKTENGINE_REFRESH);
        } else {
            wp_localize_script('Genoo', 'GenooVars', array(
                'GenooSettings' => array(
                    'WPMKTENGINE_PART_SETUP' => WPMKTENGINE_PART_SETUP,
                    'WPMKTENGINE_SETUP' => WPMKTENGINE_SETUP,
                    'WPMKTENGINE_LUMENS' => WPMKTENGINE_LUMENS
                ),
                'DOMAIN' => WPMKTENGINE_DOMAIN,
                'AJAX' => admin_url('admin-ajax.php'),
                'AJAX_NONCE' => wp_create_nonce('Genoo'),
                'GenooPluginUrl' => WPMKTENGINE_ASSETS,
                'GenooMessages'  => array(
                    'importing'  => __('Importing...', 'wpmktengine'),
                )
            ));
        }
    }


    /**
     * Admin body class
     * - used to add lower than 3.8, dashicons edit
     *
     * @param $classes
     * @return mixed
     */
    public function adminBodyClass($classes)
    {
        global $wp_version;
        if(isset($_REQUEST['page'])){
            if(Strings::contains($_REQUEST['page'], 'WPMKTENGINE')){
                $classes .= ' WPMKTENGINE ';
            }
        }
        return $classes;
    }


    /**
     * Current screen
     */

    public function adminCurrentScreen($currentScreen)
    {
        // Load libs on excact screens only
        switch($currentScreen->id){
            case 'wpmktgengine_page_WPMKTENGINEForms':
                $this->tableForms = new TableForms($this->repositaryForms, $this->repositarySettings);
                break;
            case 'wpmktgengine_page_WPMKTENGINESurveys':
                $this->tableSurveys = new TableSurveys($this->repositarySurveys);
                break;
            case 'wpmktgengine_page_WPMKTENGINEPages':
                $this->tablePages = new TablePages($this->repositaryPages, $this->repositarySettings);
                break;
            case 'widgets':
                wp_enqueue_media();
                break;
        }
    }


    /**
     * Admin Init
     */

    public function adminInit()
    {

        /**
         * 1. Check and hide user nag, if set + Check tool's requests
         */

        Nag::check(array('hideGenooNag', 'hideGenooApi', 'hideGenooSidebar'));
        Tools::check(array('genooActionImport', 'genooActionFlush', 'genooActionDelete', 'genooActionValidate', 'genooActionCheck'));

        /**
         * 2. Check if set up, display nag if not
         */

        if(!WPMKTENGINE_SETUP && !Nag::visible('hideGenooNag')){
            $msgPluginLink = ' ' . Nag::adminLink(__('WPMKTGENGINE Login Page.', 'wpmktengine'), 'WPMKTENGINELogin&reset=true') . ' | ';
            $msgHideLink = Nag::hideLink(__('Hide this warning.', 'wpmktengine'), 'hideGenooNag');
            if(!isset($_GET['page']) && (isset($_GET['page']) && $_GET['page'] !== 'WPMKTENGINELogin')){
                $this->addNotice('error', sprintf(__('WPMKTGENGINE plugin requires setting up. To finish your setup please login to your account.', 'wpmktengine')) . $msgPluginLink . $msgHideLink);
            }
        }

        /**
         * 3. Check sideber ID compatibility
         */

        $this->adminCheckSidebars();

        /**
         * 4. Plugin meta links
         */

        Filter::add('plugin_row_meta', array($this, 'adminPluginMeta'), 10, 2);
    }


    /**
     * Check sidebars compatibility
     */

    public function adminCheckSidebars()
    {
        global $wp_registered_sidebars;
        $errors = array();
        // Go through sidebars
        if(isset($wp_registered_sidebars) && is_array($wp_registered_sidebars)){
            // We have sidebars
            foreach($wp_registered_sidebars as $sidebar_id => $sidebar_info){
                if(strtolower($sidebar_id) != $sidebar_id){
                    $errors[] = $sidebar_id;
                }
            }
        }
        if(!empty($errors)){
            if(!Nag::visible('hideGenooSidebar')){
                $msgHideLink = Nag::hideLink(__('Hide this warning.', 'wpmktengine'), 'hideGenooSidebar');
                $this->addNotice('error', sprintf(__('WPMKTGENGINE plugin has found that some of your sidebars use camel-case style as their ID.  This might cause a conflict and make your widgets dissapear. We recommend that you change the sidebar ID to all lower case.  The sidebars in question are: ', 'wpmktengine')) . '<span style="text-decoration: underline;">' . substr(implode(', ', $errors), 0, -2) . '</span>' . ' | ' . $msgHideLink);
            }
        }
    }


    /**
     * Admin Menu
     */

    public function adminMenu()
    {
        // Admin menus
        global $menu;
        global $submenu;
        // Admin Pages
        add_menu_page('WPMKTGENGINE', 'WPMKTGENGINE', 'manage_options', 'WPMKTENGINELogin', array($this, 'renderWPMKTENGINELogin'), 'dashicons-editor-paste-word', '4.123456789');
        if(WPMKTENGINE_SETUP){
            add_submenu_page('WPMKTENGINELogin', 'Surveys', 'Surveys', 'manage_options', 'WPMKTENGINESurveys', array($this, 'renderGenooSurveys'));
            add_submenu_page('WPMKTENGINELogin', 'Forms', 'Forms', 'manage_options', 'WPMKTENGINEForms', array($this, 'renderGenooForms'));
            add_submenu_page('WPMKTENGINELogin', 'Page Builder', 'Page Builder', 'manage_options', 'WPMKTENGINEPages', array($this, 'renderGenooPages'));
            if(WPMKTENGINE_LUMENS){ add_submenu_page('WPMKTENGINELogin', 'Lumens', 'Lumens', 'manage_options', 'WPMKTENGINELumens', array($this, 'renderGenooLumens')); }
        }
        // Tools before end
        if(WPMKTENGINE_SETUP){
            add_submenu_page('WPMKTENGINELogin', 'Tools', 'Tools', 'manage_options', 'WPMKTENGINETools', array($this, 'renderGenooTools'));
        }
        add_submenu_page('WPMKTENGINELogin', 'Settings', 'Settings', 'manage_options', 'WPMKTENGINE', array($this, 'renderGenooSettings'));
        // Settings page change
        $wpmktetitle = WPMKTENGINE_SETUP || WPMKTENGINE_PART_SETUP ? 'The Engine' : 'Login';
        // Reapend first menu
        $wpmkteMenu = array();
        $wpmkteMenu[] = $wpmktetitle;
        $wpmkteMenu[] = 'manage_options';
        $wpmkteMenu[] = 'WPMKTENGINELogin';
        $wpmkteMenu[] = 'WPMKTGENGINE';
        // TICKET-281577
        // SM had an issue
        // Check if menu exists
        if(isset($submenu['WPMKTENGINELogin'])){
            // Add login
            if(!WPMKTENGINE_SETUP){
                unset($submenu['WPMKTENGINELogin'][0]);
                array_unshift($submenu['WPMKTENGINELogin'], $wpmkteMenu);
                // Moving Page Builder
                if(isset($submenu['WPMKTENGINELogin'][4])){
                    $wpmkteMenu = $submenu['WPMKTENGINELogin'][4];
                }
                //$submenu['WPMKTENGINELogin'] = \WPMKTENGINE\Utils\ArrayObject::appendTo($submenu['WPMKTENGINELogin'], 0, $wpmkteMenu);
            } else {
                // Adding menu
                array_unshift($submenu['WPMKTENGINELogin'], $wpmkteMenu);
                // Moving Page Builder
                $wpmkteMenu = $submenu['WPMKTENGINELogin'][4];
                unset($submenu['WPMKTENGINELogin'][4]);
               $submenu['WPMKTENGINELogin'] = \WPMKTENGINE\Utils\ArrayObject::appendTo($submenu['WPMKTENGINELogin'],  $wpmkteMenu, 2);
                }
            // Last menu movement
            if(WPMKTENGINE_SETUP){
                \WPMKTENGINE\Utils\ArrayObject::moveFromPositionToPosition($submenu['WPMKTENGINELogin'], 2, 1);
                \WPMKTENGINE\Utils\ArrayObject::moveFromPositionToPosition($submenu['WPMKTENGINELogin'], 4, 3);
                \WPMKTENGINE\Utils\ArrayObject::moveFromPositionToPosition($submenu['WPMKTENGINELogin'], 5, 4);
                \WPMKTENGINE\Utils\ArrayObject::moveFromPositionToPosition($submenu['WPMKTENGINELogin'], 6, 5);
                // Remove Landing pages
                unset($submenu['WPMKTENGINELogin'][6]);
            }
        }
    }


    /**
     * Remove metaboxes from our post types
     */
    public function removeMetaboxes()
    {
        // Listly
        remove_meta_box('ListlyMetaBox', 'wpme-landing-pages', 'side');
        remove_meta_box('ListlyMetaBox', 'cta', 'side');
        remove_meta_box('ListlyMetaBox', 'wpme-styles', 'side');
        // Redirect
        remove_meta_box('edit-box-ppr', 'wpme-styles', 'normal');
        remove_meta_box('edit-box-ppr', 'cta', 'normal');
        remove_meta_box('edit-box-ppr', 'wpme-landing-pages', 'normal');
        // Yoast SEO
        remove_meta_box('wpseo_meta', 'cta', 'normal');
        remove_meta_box('wpseo_meta', 'wpme-styles', 'normal');
    }


    /**
     * Remove hooks colliding
     */
    public function removeDequeue()
    {
        // Yoast SEo
        wp_dequeue_script('wp-seo-metabox');
        wp_dequeue_script('wpseo-admin-media');
        wp_dequeue_script('yoast-seo');
        wp_dequeue_script('wp-seo-post-scraper');
        wp_dequeue_script('wp-seo-replacevar-plugin');
        wp_dequeue_script('wp-seo-shortcode-plugin');
        wp_dequeue_script('wp-seo-post-scraper');
        wp_dequeue_script('wp-seo-replacevar-plugin');
        wp_dequeue_script('wp-seo-shortcode-plugin');
        wp_dequeue_script('wp-seo-featured-image');
        wp_dequeue_script('wp-seo-metabox');
    }

    /**
     * Admin post types
     */

    public function adminPostTypes()
    {
        // Setting up post types
        if(WPMKTENGINE_SETUP){
            // Post Type
            new PostType('wpme_landing_pages',
                array(
                    'supports' => array('title'),
                    'label' => __('Landing Pages', 'wpmktengine'),
                    'labels' => array(
                        'add_new' => __('New Landing Page', 'wpmktengine'),
                        'not_found' => __('No Landing Pages found', 'wpmktengine'),
                        'not_found_in_trash' => __('No Landing Pages found in Trash', 'wpmktengine'),
                        'edit_item' => __('Edit Landing Page', 'wpmktengine'),
                        'add_new_item' => __('Add new Landing Page', 'wpmktengine'),
                    ),
                    'public' => true,
                    'exclude_from_search' => false,
                    'publicly_queryable' => false,
                    'show_ui' => true,
                    'show_in_nav_menus' => false,
                    'show_in_menu' => 'WPMKTENGINELogin',
                    'show_in_admin_bar' => false,
                )
            );
            Filter::add('post_updated_messages', function($messages){
                global $post;
                $link = get_post_meta($post->ID, 'wpmktengine_landing_url', TRUE);
                $link = RepositoryLandingPages::base() . $link;
                $linkAppend = '&nbsp;|&nbsp;<a href="'. $link .'">' . __('View Landing Page.', 'wpmktengine') . '</a>';
                $messages['wpme-landing-pages'][1] = __('Landing Page updated.', 'wpmktengine') . $linkAppend;
                $messages['wpme-landing-pages'][4] = __('Landing Page updated.', 'wpmktengine');
                $messages['wpme-landing-pages'][6] = __('Landing Page published.', 'wpmktengine') . $linkAppend;
                $messages['wpme-landing-pages'][7] = __('Landing Page saved.', 'wpmktengine');
                $messages['wpme-landing-pages'][8] = __('Landing Page submitted.', 'wpmktengine') . $linkAppend;
                $messages['wpme-landing-pages'][9] = __('Landing Page scheduled.', 'wpmktengine');
                $messages['wpme-landing-pages'][10] = __('Landing Page updated.', 'wpmktengine') . $linkAppend;
                // Return
                return $messages;
            }, 10, 1);
            // Post Type
            new PostType('wpme_styles',
                array(
                    'supports' => array('title'),
                    'label' => __('Styles', 'wpmktengine'),
                    'labels' => array(
                        'add_new' => __('New Style', 'wpmktengine'),
                        'not_found' => __('No Styles found', 'wpmktengine'),
                        'not_found_in_trash' => __('No Styles found in Trash', 'wpmktengine'),
                        'edit_item' => __('Edit Style', 'wpmktengine'),
                        'add_new_item' => __('Add new Style', 'wpmktengine'),
                    ),
                    'public' => true,
                    'exclude_from_search' => false,
                    'publicly_queryable' => false,
                    'show_ui' => true,
                    'show_in_nav_menus' => false,
                    'show_in_menu' => 'WPMKTENGINELogin',
                    'show_in_admin_bar' => false,
                )
            );
            // Add Post Type Columns
            // TODO: move cta to Extensions\Cta
            PostType::columns('cta', array('cta_type' => 'Type'), __('CTA Title', 'wpmktengine'));
            PostType::columns('wpme-landing-pages', array('wpmktengine_landing_url' => 'Url', 'wpmktengine_landing_template' => 'Page ID', 'setup' => 'Correctly Setup', 'wpmktengine_landing_active' => 'Active', 'wpmktengine_landing_homepage' => 'Homepage', 'wpmktengine_landing_redirect_active' => 'Redirect'), __('Title', 'wpmktengine'));
            // Add Post Type Columns Content
            PostType::columnsContent('cta', array('cta_type'));
            PostType::columnsContent('wpme-landing-pages', array('wpmktengine_landing_url', 'wpmktengine_landing_template', 'setup', 'wpmktengine_landing_active', 'wpmktengine_landing_homepage', 'wpmktengine_landing_redirect_active'), function($column, $post){
                $meta = get_post_meta($post->ID, $column, TRUE);
                if($column == 'wpmktengine_landing_url'){
                    echo RepositoryLandingPages::base() . $meta;
                } elseif($column == 'setup'){
                    $metaTemplate = get_post_meta($post->ID, 'wpmktengine_landing_template', TRUE);
                    $metaUrl = get_post_meta($post->ID, 'wpmktengine_landing_url', TRUE);
                    $validTemplate = !empty($metaTemplate) ? TRUE : FALSE;
                    $validUrl = !empty($metaUrl) && filter_var(RepositoryLandingPages::base() . $metaUrl, FILTER_VALIDATE_URL) === FALSE ? FALSE : TRUE;
                    if($validUrl && $validTemplate){
                        echo '<span class="genooTick active">&nbsp;</span>';
                    } else {
                        echo '<span class="genooCross">&times;</span>';
                    }
                } elseif($column == 'wpmktengine_landing_active'){
                    if($meta == 'true'){
                        echo '<span class="genooTick active">&nbsp;</span>';
                    } else {
                        echo '<span class="genooCross">&times;</span>';
                    }
                } elseif($column == 'wpmktengine_landing_redirect_active'){
                    $metaUrl = get_post_meta($post->ID, 'wpmktengine_landing_redirect_url', TRUE);
                    if($meta == 'true'){
                        echo '<span class="genooTick active">&nbsp;</span>';
                        echo '<br />Redirects to: <strong>'. $metaUrl  .'</strong>';
                    } else {
                        echo '<span class="genooCross">&times;</span>';
                    }
                } elseif($column == 'wpmktengine_landing_homepage'){
                    if($meta == 'true'){
                        $realUrlEmpty = strtok(Utils::getRealUrl(), "?");
                        $realUrl = $realUrlEmpty . "?post_type=wpme-landing-pages";
                        $link = Utils::addQueryParam($realUrl, 'genooDisableLandingHomepage', $post->ID);
                        echo '<span class="genooTick active">&nbsp;</span>&nbsp;|&nbsp;<a href="'. $link .'">'. __('Disable homepage', 'wpmktengine') .'</a>';
                    } else {
                        $realUrlEmpty = strtok(Utils::getRealUrl(), "?");
                        $realUrl = $realUrlEmpty . "?post_type=wpme-landing-pages";
                        $link = Utils::addQueryParam($realUrl, 'genooMakeLandingHomepage', $post->ID);
                        echo '<a href="'. $link .'">'. __('Make this landing page WordPress default homepage.', 'wpmktengine') .'</a>';
                    }
                } else {
                    echo $meta;
                }
            });
            Action::add('manage_posts_extra_tablenav', function($which){
                if(Utils::getParamIsset('post_type') && $_GET['post_type'] == 'wpme-landing-pages' && $which == 'top'){
                    echo '<div class="alignleft actions"><a target="_blank" class="button button-primary genooExtraNav" href="'. WPMKTENGINE_BUILDER_NEW .'">'. __('Add new Template', 'wpmktengine') .'</a></div>';
                }
            }, 10, 1);
            Filter::add('post_row_actions', function($actions, $post){
                if(isset($post) && $post instanceof \WP_Post && isset($post->post_type)){
                    if($post->post_type == 'wpme-landing-pages'){
                        // url
                        $url = get_post_meta($post->ID, 'wpmktengine_landing_url', TRUE);
                        $url = RepositoryLandingPages::base() . $url;
                        // Action link
                        $actions['view'] = '<a target="_blank" href="'. $url .'">'. __('View', 'wpmktengine') .'</a>';
                    }
                }
                return $actions;
            }, 10, 2);
            Action::add('current_screen', function($screen){
                if(is_object($screen) && $screen->id == 'wpmktgengine_page_WPMKTENGINEPages'){
                    if(array_key_exists('genooMakeLandingHomepage', $_GET) && is_numeric($_GET['genooMakeLandingHomepage'])){
                        $id = sanitize_text_field($_GET['genooMakeLandingHomepage']);
                        RepositoryLandingPages::makePageHomepage($id);
                        Action::add('admin_notices', function(){ echo Notice::type('updated')->text('Default homepage changed.'); }, 10, 1);
                    }
                    if(array_key_exists('genooDisableLandingHomepage', $_GET) && is_numeric($_GET['genooDisableLandingHomepage'])){
                        RepositoryLandingPages::removeHomepages();

                        Action::add('admin_notices', function(){ echo Notice::type('updated')->text('Default homepage turned off.'); }, 10, 1);

                    }

                }

                return;

            }, 10, 1);

        }

    }





    /**

     * Metaboxes

     */



    public function adminUI()

    {

        if(WPMKTENGINE_SETUP){

            // Metaboxes
            new Metabox('WPMKTGENGINE CTA Info', 'cta',
                array(
                    array(
                        'type' => 'select',
                        'label' => __('CTA type', 'wpmktengine'),
                        'options' => $this->repositarySettings->getCTADropdownTypes()
                    ),
                    array(
                        'type' => 'select',
                        'label' => __('Display CTA\'s', 'wpmktengine'),
                        'options' => array(
                            '0' => __('No title and description', 'wpmktengine'),
                            'titledesc' => __('Title and Description', 'wpmktengine'),
                            'title' => __('Title only', 'wpmktengine'),
                            'desc' => __('Description only', 'wpmktengine'),
                        )
                    ),
                    array(
                        'type' => 'textarea',
                        'label' => __('Description', 'wpmktengine'),
                    ),
                    array(
                        'type' => 'select',
                        'label' => __('Form', 'wpmktengine'),
                        'options' => (array('' => '-- Select Form') + $this->repositaryForms->getFormsArray()),
                        'atts' => array(
                            'class' => 'bTargeted',
                            'data-target' => 'block-form'
                        )
                    ),
                    array(
                        'type' => 'select',
                        'id' => 'form_theme',
                        'label' => __('Form Style', 'wpmktengine'),
                        'options' => ($this->repositarySettings->getSettingsThemes()),
                        'atts' => array(
                            'style' => 'display: none !important',
                        )
                    ),
                    array(
                        'type' => 'html',
                        'label' => __('If none of the styles fits your needs, you can create your own styles. ', 'wpmktengine') . '<a target="_blank" href="'. admin_url('post-new.php?post_type=wpme-styles') .'">' . __('Would you like to use a custom style?', 'wpmktengine') . '</a><br />',
                    ),
                    array(
                        'type' => 'select',
                        'label' => __('Follow original return URL', 'wpmktengine'),
                        'options' => (
                        array(
                            '' => 'Disable',
                            '1' => 'Enable'
                        )
                        )
                    ),
                    array(
                        'type' => 'textarea',
                        'label' => __('Form success message', 'wpmktengine'),
                    ),
                    array(
                        'type' => 'textarea',
                        'label' => __('Form error message', 'wpmktengine'),
                    ),
                    array(
                        'type' => 'text',
                        'label' => __('Button URL', 'wpmktengine'),
                    ),
                    array(
                        'type' => 'checkbox',
                        'label' => __('Open in new window?', 'wpmktengine')
                    ),
                    array(
                        'type' => 'select',
                        'label' => __('Button Type', 'wpmktengine'),
                        'options' => array(
                            'html' => __('HTML', 'wpmktengine'),
                            'image' => __('Image', 'wpmktengine'),
                        )
                    ),
                    array(
                        'type' => 'text',
                        'label' => __('Button Text', 'wpmktengine'),
                    ),
                    array(
                        'type' => 'image-select',
                        'label' => __('Button Image', 'wpmktengine')
                    ),
                    array(
                        'type' => 'image-select',
                        'label' => __('Button Hover Image', 'wpmktengine')
                    ),
                    array(
                        'type' => 'text',
                        'label' => __('Button CSS ID', 'wpmktengine'),
                    ),
                    array(
                        'type' => 'text',
                        'label' => __('Button CSS Class', 'wpmktengine'),
                    ),
                    $this->repositarySettings->getLumensDropdown($this->repositaryLumens)
                ), 'normal', 'high'
            );
            // Landing pages UI //
            $affiArrayYes = function_exists('affiliate_wp');
            $affiArrayYesVal = $affiArrayYes ? affiliate_wp()->settings->get('referral_var', 'ref') : false;
            $affiArray = $affiArrayYes ? array(
                'type' => 'html',
                'label' => '<span style="color: white; background: orangered">' . __('WARNING! The variable "' . $affiArrayYesVal . '" is reserved by AffiliateWP.', 'wpmktengine') . '</span>'
            ) : null;
            new MetaboxCTA('WPMKTGENGINE Dynamic CTA', $this->repositarySettings->getCTAPostTypes(), array(), $this->repositarySettings->getCTAs());
            new Metabox('Settings', array('wpme-landing-pages'),
                array(
                    array(
                        'type' => 'checkbox',
                        'label' => __('Active?', 'wpmktengine'),
                        'id' => 'wpmktengine_landing_active'
                    ),
                    array(
                        'type' => 'html',
                        'label' => '<strong>' . __('Landing page URL', 'wpmktengine') . '</strong>',
                    ),
                    array(
                        'type' => 'text',
                        'label' => RepositoryLandingPages::base(),
                        'before' => '',
                        'id' => 'wpmktengine_landing_url',
                        'atts' => array(
                            'required' => 'required',
                            'pattern' => '^[a-zA-Z0-9/_-]*$', //^[a-zA-Z0-9/_.-]*$
                            'onkeyup' => 'Api.checkUrl(this);'
                        ),
                    ),
                    array(
                        'type' => 'html',
                        'label' => __('Allowed URL characters are: ', 'wpmktengine') . '[<strong>a-z</strong>][<strong>0-9</strong>][<strong>/</strong>][<strong>_</strong>][<strong>-</strong>]'
                    ),
                    array(
                        'type' => 'html',
                        'label' => '<span style="color: red">' . __('WARNING! Make sure URL is unique across all pages and posts.', 'wpmktengine') . '</span>'
                    ),
                    $affiArray,
                    array(
                        'type' => 'select',
                        'label' => __('Page template', 'wpmktengine'),
                        'options' => $this->repositaryPages->getPagesArrayDropdown(),
                        'id' => 'wpmktengine_landing_template'
                    ),
                    array(
                        'type' => 'textarea',
                        'label' => __('Additional header data', 'wpmktengine'),
                        'id' => 'wpmktengine_data_header'
                    ),
                    array(
                        'type' => 'textarea',
                        'label' => __('Additional footer data', 'wpmktengine'),
                        'id' => 'wpmktengine_data_footer'
                    ),
                    array(
                        'type' => 'checkbox',
                        'label' => __('Render Tracking Code in the &lt;head&gt; of HTML?', 'wpmktengine'),
                        'id' => 'wpmktengine_tracking_data_head'
                    ),
                )
            );
            // Dynamic PopOver
            new Metabox(
                'WPMKTGENGINE Dynamic Pop-Over',
                array_merge($this->repositarySettings->getCTAPostTypes(), array('wpme-landing-pages')),
                array(
                    array(
                        'type' => 'select',
                        'label' => __('Enable Pop-Over to open automatically', 'wpmktengine'),
                        'options' => array('Disable', 'Enable')
                    ),
                    array(
                        'type' => 'select',
                        'label' => __('CTA', 'wpmktengine'),
                        'id' => 'pop_over_cta_id',
                        'options' => $this->repositaryCTAs->getArray()
                    ),
                    array(
                        'type' => 'number',
                        'label' => __('Open Pop-Up after delay (seconds)', 'wpmktengine'),
                        'id' => 'number_of_seconds_to_open_the_pop_up_after'
                    ),
                    array(
                        'type' => 'checkbox',
                        'label' => __('Only display to unknown leads?', 'wpmktengine'),
                        'id' => 'pop_over_only_for_unknown'
                    ),
                )
            );
            // Referer URL redirect
            new Metabox(
                'WPMKTGENGINE Referer URL Redirect',
                array('post', 'page', 'wpme-landing-pages'),
                array(
                    array(
                        'type' => 'select',
                        'label' => __('Enable Referer Redirect', 'wpmktengine'),
                        'options' => array('Disable', 'Enable'),
                        'id' => 'wpmktengine_referer_redirect'
                    ),
                    array(
                        'type' => 'select',
                        'label' => __('Enable when', 'wpmktengine'),
                        'options' => array(
                            'referer_not' => __('user has not come from referer', 'wpmktengine'),
                            'referer_yes' => __('user has come from referer', 'wpmktengine'),
                        ),
                        'id' => 'wpmktengine_referer_redirect_when'
                    ),
                    array(
                        'type' => 'text',
                        'label' => __('Referer URL', 'wpmktengine'),
                        'id' => 'wpmktengine_referer_redirect_from_url'
                    ),
                    array(
                        'type' => 'text',
                        'label' => __('Redirect to URL', 'wpmktengine'),
                        'id' => 'wpmktengine_referer_redirect_url'
                    )
                )
            );
            // Redirect for Landing page
            new Metabox('Redirect', array('wpme-landing-pages'),
                array(
                    array(
                        'type' => 'checkbox',
                        'label' => __('Active?', 'wpmktengine'),
                        'id' => 'wpmktengine_landing_redirect_active'
                    ),
                    array(
                        'type' => 'text',
                        'label' => 'Redirect URL',
                        'id' => 'wpmktengine_landing_redirect_url',
                        'atts' => array(
                            'pattern' => '\bhttps?://[.0-9a-z-]+\.[a-z]{2,6}(?::[0-9]{1,5})?(?:/[!$\'()*+,.0-9_a-z-]+){0,9}(?:/[!$\'()*+,.0-9_a-z-]*)?(?:\?[!$&\'()*+,.0-9=_a-z-]*)?'
                        ),
                    ),

                ),
                'side'
            );
            new Metabox('Preview', array('wpme-landing-pages'),
                array(
                    array(
                        'type' => 'html',
                        'label' => '<a href="#" onclick="Metabox.appendAndFire(event, \'previewLandingPage\', \'true\');" class="button button-primary">'. __('Preview this landing page.', 'wpmktengine') .'</a>',
                    ),
                ),
                'side',
                'high'
            );
            // Metabox with content in for styler
            new MetaboxArea('Elements to style - <span style="font-weight:300;">Click on something to set how you want it to show up</span>', array('wpme-styles'));
            // Metabox in the sidebar for styler
            new Metabox('Form Properties', array('wpme-styles'), array(
                array(
                    'type' => 'checkbox',
                    'label' => __('Make labels input placeholders?', 'wpmktengine'),
                    'id' => 'wpmktengine_style_make_placeholders',
                    'atts' => array(
                        'onclick' => 'Customizer.updateLabels(event, this);',
                    ),
                ),
            ), 'side', 'default');
            new MetaboxStyles('<span class="selectedElem">Applied Style</span>', array('wpme-styles'));
        }
        return NULL;
    }


    /** ----------------------------------------------------- */
    /**                      Renderers                        */
    /** ----------------------------------------------------- */

    /**
     * Renders Admin Page
     */

    public function renderGenooSettings()
    {
        echo '<div class="wrap">'. Helpscreen::getSupportHaderWithLogo(__('WPMKTGENGINE Settings', 'wpmktengine'));
            if(WPMKTENGINE_SETUP){
                $this->settings->render();
            } else {
                echo 'It seems like your installtion is not fully set up, try <a href="'. admin_url('admin.php?page=WPMKTENGINELogin&reset=true') .'">resetting your installation here.</a>';
            }
        echo '</div>';
    }


    /**
     * Renders Admin Page
     */

    public function renderGenooForms()
    {
        echo '<div class="wrap">' . Helpscreen::getSupportHaderWithLogo(__('Lead Capture Forms', 'wpmktengine'));
            $this->tableForms->display();
        echo '</div>';
    }

    /**
     * Renders Admin Page
     */

    public function renderGenooSurveys()
    {
        echo '<div class="wrap">' . Helpscreen::getSupportHaderWithLogo(__('Surveys', 'wpmktengine'));
        $this->tableSurveys->display();
        echo '</div>';
    }

    /**
     * Render Pages
     */
    public function renderGenooPages()
    {
        echo '<div class="wrap">' . Helpscreen::getSupportHaderWithLogo(__('Layout Pages', 'wpmktengine'));
        $this->tablePages->display();
        echo '</div>';
    }


    /**
     * Renders Lumens page
     */

    public function renderGenooLumens()
    {
        echo '<div class="wrap">' . Helpscreen::getSupportHaderWithLogo(__('Class Lists', 'wpmktengine'));
            $this->tableLumens->display();
        echo '</div>';
    }


    /**
     * Renders Tools page
     */

    public function renderGenooTools()
    {
        $page = new Page();
        $page->addTitle(__('WPMKTGENGINE Tools', 'wpmktengine'));
        $page->addWidget('Create WPMKTGENGINE Leads from WordPress Approved Comments.', Tools::getWidgetImport());
        $page->addWidget('Create WPMKTGENGINE Leads from WordPress blog subscribers.', Tools::getWidgetImportSubscribers($this->api));
        $page->addWidget('Delete all cached files.', Tools::getWidgetDelete());
        $page->addWidget('Clear plugin Settings.', Tools::getWidgetFlush());
        $page->addWidget('Validate API key.', Tools::getWidgetValidate());
        $page->addWidget('Theme check.', Tools::getWidgetCheck());
        $page->addWidget('Bug Report Info.', Tools::getWidgetBug());
        $page->addWidget('Active Extensions', Tools::getActiveExtensions());
        if(isset($_GET['debug']) || isset($_COOKIE['debug'])){
            $page->addWidget('Sidebar Report', Tools::getSidebarReport());
        }
        // Add custom widgets
        apply_filters('wpmktengine_tools_widgets', $page);
        echo $page;
    }


    /**
     * Renders Login Page
     */
    public function renderWPMKTENGINELogin()
    {
        // Add https if admin currently in HTTPS
        $http = !isset($_SERVER['HTTPS']) || $_SERVER['HTTPS'] != 'on' ? 'http://' : 'https://';
        $domain = isset($_GET['domain']) ? $_GET['domain'] : $http . $_SERVER['HTTP_HOST'];
        $url = (isset($_GET['subpage']) && $_GET['subpage'] == 'form') ? 'https:'. WPMKTENGINE_DOMAIN .'/app/lead_capture_forms.jsp?tab=3&ptab=2' : 'https:' . WPMKTENGINE_DOMAIN . '/';
        $url = (isset($_GET['subpage']) && $_GET['subpage'] == 'surveys') ? 'https:'. WPMKTENGINE_DOMAIN .'/app/surveys.jsp?tab=2' : $url;
        //https://wpmeapp.genoo.com/app/surveys.jsp?tab=2
        $domainFinal = Utils::addQueryParam($url, 'wpdomain', $domain);
        // Reset?
        if(!WPMKTENGINE_SETUP || isset($_GET['reset'])){
            $domainFinal = Utils::addQueryParam($domainFinal, 'setup', 'true');
        }
        //&setup=true
        echo '<div class="wrap genoWrap" id="iframeHolder">' . Helpscreen::getSupportHaderWithLogo(__('The Engine', 'wpmktengine'));
        echo '<iframe id="genooIframe" class="genooIframe" frameborder="0" scrolling="no" width="100%" height="900px" src="'. $domainFinal .'"></iframe>';
        echo '</div>';
    }

    /** ----------------------------------------------------- */
    /**                 Plugin meta links                     */
    /** ----------------------------------------------------- */

    /**
     * Plugin meta links
     *
     * @param $links
     * @param $file
     * @return mixed
     */

    public function adminPluginMeta($links, $file)
    {
        if ($file == WPMKTENGINE_FILE){
            array_push($links, '<a target="_blank" href="http://wpmktgengine.com/">'. __('Support forum', 'wpmktengine') .'</a>');
        }
        return $links;
    }


    /** ----------------------------------------------------- */
    /**               Notification system                     */
    /** ----------------------------------------------------- */

    /**
     * Adds notice to the array of notices
     *
     * @param string $tag
     * @param string $label
     */

    public function addNotice($tag = 'updated', $label = ''){ $this->notices[] = array($tag, $label); }

    /**
     * Add saved notice
     *
     * @param string $tag
     * @param string $label
     */
    public function addSavedNotice($tag = 'updated', $label = ''){ $this->repositarySettings->addSavedNotice($tag, $label); }


    /**
     * Returns all notices
     *
     * @return array
     */

    public function getNotices(){ return $this->notices; }


    /**
     * Sends notices to renderer
     */

    public function adminNotices()
    {
        // notices saved in db
        $savedNotices = $this->repositarySettings->getSavedNotices();
        if($savedNotices){
            foreach($savedNotices as $value){
                if(array_key_exists('error', $value)){
                    $this->displayAdminNotice('error', $value['error']);
                } elseif(array_key_exists('updated', $value)){
                    $this->displayAdminNotice('updated', $value['updated']);
                }
                // flush notices after display
                $this->repositarySettings->flushSavedNotices();
            }
        }
        // notices saved in this object
        foreach($this->notices as $key => $value){
            $this->displayAdminNotice($value[0], $value[1]);
        }
    }


    /**
     * Display admin notices
     *
     * @param null $class
     * @param null $text
     */

    private function displayAdminNotice($class = NULL, $text = NULL){ echo Notice::type($class)->text($text); }


    /** ----------------------------------------------------- */
    /**                    Get instance                       */
    /** ----------------------------------------------------- */

    /**
     * Does what it says, get's instance
     *
     * @return bool|Admin
     */

    public static function getInstance()
    {
        if (!self::$instance){
            self::$instance = new self();
        }
        return self::$instance;
    }
}
