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

use WPMKTENGINE\RepositorySettings;
use WPMKTENGINE\Wordpress\Widgets;
use WPMKTENGINE\Shortcodes;
use WPMKTENGINE\Users;
use WPMKTENGINE\Frontend;
use WPMKTENGINE\Admin;
use WPMKTENGINE\Wordpress\Action;
use WPMKTENGINE\Wordpress\Ajax;
use WPMKTENGINE\Wordpress\Debug;
use WPMKTENGINE\Wordpress\Comments;
use WPMKTENGINE\Wordpress\Helpscreen;

class WPMKTENGINE
{
    /** @var \WPMKTENGINE\RepositorySettings */
    private $repositarySettings;
    /** @var \WPMKTENGINE\Api */
    private $api;
    /** @var \WPMKTENGINE\Cache */
    private $cache;
    /** @var bool // this variable is to set whether to check unauthorised next call or not */
    private $skipCheck = false;

    /**
     * Constructor, does all this beautiful magic, loads all libs
     * registers all sorts of funky hooks, checks stuff and so on.
     */

    public function __construct()
    {
        // start the engine last file to require, rest is auto
        // custom auto loader, PSR-0 Standard
        require_once('wpmktgengine-loader.php');
        $classLoader = new WPMKTENGINELoader();
        $classLoader->setPath(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'libs' . DIRECTORY_SEPARATOR);
        $classLoader->addNamespace('WPMKTENGINE');
        $classLoader->addNamespace('WPME');
        $classLoader->register();
        // Cosntants define
        define('WPMKTENGINE_KEY',     'WPMKTENGINE');
        define('WPMKTENGINE_FILE',    WPMKTENGINE_PLUGIN);
        define('WPMKTENGINE_HOME_URL',get_option('siteurl'));
        define('WPMKTENGINE_FOLDER',  plugins_url(NULL, __FILE__));
        define('WPMKTENGINE_ROOT',    dirname(__FILE__) . DIRECTORY_SEPARATOR);
        define('WPMKTENGINE_ASSETS',  WPMKTENGINE_FOLDER . '/assets/');
        define('WPMKTENGINE_ASSETS_DIR', WPMKTENGINE_ROOT . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR);
        // Storage
        $saveToWpContent = \WPMKTENGINE\RepositorySettings::getOption('genooCTASave', \WPMKTENGINE\RepositorySettings::KEY_MISC, false);
        if($saveToWpContent){
            define('WPMKTENGINE_CACHE',   WP_CONTENT_DIR . DIRECTORY_SEPARATOR . 'cache_wpme' . DIRECTORY_SEPARATOR);
        } else {
            define('WPMKTENGINE_CACHE',   WPMKTENGINE_ROOT . 'cache' . DIRECTORY_SEPARATOR);
        }
        define('WPMKTENGINE_DEBUG',   get_option('WPMKTENGINEDebug'));
        define('WPMKTENGINE_REFRESH', sha1('new-admin-styling'));
        define('WPMKTENGINE_BUILDER', 'https://genoolabs.com/simplepagebuilder/');
        define('WPMKTENGINE_LEAD_COOKIE', '_gtld');
        // wp init
        Action::add('plugins_loaded', array($this, 'init'), 1);
    }


    /**
     * Initialize
     */

    public function init()
    {
        // Dropins
        require_once WPMKTENGINE_ROOT .  '/extensions/dropins.php';
        // initialize
        $this->repositarySettings = new \WPME\RepositorySettingsFactory();
        $this->api = new \WPME\ApiFactory($this->repositarySettings);
        $this->cache = new \WPME\CacheFactory(WPMKTENGINE_CACHE);
        // helper constants
        define('WPMKTENGINE_PART_SETUP', $this->api->isSetup());
        define('WPMKTENGINE_SETUP', $this->api->isSetupFull(TRUE));
        define('WPMKTENGINE_SETUP_LEAD_TYPES', $this->api->isSetupFull(FALSE));
        define('WPMKTENGINE_LUMENS', FALSE);
        define('WPMKTENGINE_DEV', apply_filters('wpmktengine_dev', FALSE));
        // Set APIs
        if(WPMKTENGINE_DEV === FALSE){
            define('WPMKTENGINE_DOMAIN', '//wpmeapp.genoo.com');
            define('WPMKTENGINE_API_DOMAIN', '//api.genoo.com');
        } elseif(WPMKTENGINE_DEV === TRUE){
            define('WPMKTENGINE_DOMAIN', '//wpmedev.odportals.com');
            define('WPMKTENGINE_API_DOMAIN', '//wpmedev.odportals.com');
        }
        if(WPMKTENGINE_SETUP){
            define('WPMKTENGINE_BUILDER_NEW', WPMKTENGINE_BUILDER . 'index-login.php?api='. $this->repositarySettings->getApiKey() .'&domain=' . WPMKTENGINE_HOME_URL);
        } else {
            define('WPMKTENGINE_BUILDER_NEW', '');
        }
        // Make globals global
        global $WPME_API;
        global $WPME_CACHE;
        global $WPME_STYLES;
        global $WPME_STYLES_JS;
        global $WPME_MODALS;
        $WPME_API = $this->api;
        $WPME_CACHE = $this->cache;
        $WPME_STYLES = '';
        $WPME_STYLES_JS = '';
        $WPME_MODALS = array();

        /**
         * 0. Text-domain
         */
        load_plugin_textdomain('wpmktengine', false, dirname(plugin_basename(__FILE__)) . '/lang/');

        /**
         * 1. Debug call?
         */
        if(WPMKTENGINE_DEBUG){ new Debug(); }

        /**
         * 2. Register Widgets / Shortcodes / Cron, etc.
         */
        if(WPMKTENGINE_SETUP){
            Ajax::register();
            Comments::register();
            Users::register($this->repositarySettings, $this->api);
            Widgets::register();
            Widgets::registerDashboard();
            Shortcodes::register();
            Helpscreen::register();
            // Extensions
            // Shortocde Surveys
            \WPME\Extensions\ShortcodesSurveys::register();
            // Ctas
            \WPME\Extensions\CTAs::register();
            \WPME\Extensions\ShortcodesInEditor::register();
            \WPME\Extensions\LandingPages\LandingPages::register();
            \WPME\Extensions\TrackingLink\Shortcode::register();
            // Clever plugins
            global $pagenow;
            if(current_user_can('manage_options')){
                $cleverPlugins = new \WPME\Extensions\Clever\Plugins();
                $cleverPlugins->register();
            }
            // Customizer
            $customizerExtension = new \WPME\Customizer\CustomizerExtension();
            $customizerExtension->registerCustomizerPreview();
            // Add Josh's webinar code
            require_once WPMKTENGINE_ROOT .  '/libs/WPME/Extensions/Webinar.php';
            // WP Seo
            add_filter('wpseo_accessible_post_types', function($post_types){
                $post_types[] = 'wpme-landing-pages';
                return $post_types;
            }, 10, 1);
            // Elementor
            // \WPME\Extensions\ElementorShortcodes\ElementorShortcodes::register();
        }

        /**
         * 3. Extensions
         */
        // This runs in plugin_loaded
        Action::run('wpmktengine_init', $this->repositarySettings, $this->api, $this->cache);

        /**
         * 4. Setup settings
         */
        if(WPMKTENGINE_SETUP && WPMKTENGINE_SETUP_LEAD_TYPES == FALSE){
            // Partial setup, lets save the lead types now
            $this->repositarySettings->setFirstLeadTypes($this->api);
        }

        /**
         * 5. Init RSS
         */

        if(WPMKTENGINE_SETUP){
            Action::add('init', array($this, 'jsonApi'));
        }

        /**
         * 6. Admin | Frontend
         */

        if(is_admin()){
            global $WPME_ADMIN;
            $WPME_ADMIN = new Admin($this->api, $this->cache);
            return $WPME_ADMIN;
        }
        global $WPME_FRONTEND;
        $WPME_FRONTEND = new Frontend($this->repositarySettings, $this->api, $this->cache);
        return $WPME_FRONTEND;
    }


    /**
     * Activation Hook
     */
    public static function activate()
    {
        // Save first post types
        RepositorySettings::saveFirstSettings();
    }

    /**
     * API responses
     */
    public function jsonApi()
    {
        \WPME\WPApi\CTAs::register();
        \WPME\WPApi\Surveys::register();
        \WPME\WPApi\Forms::register();
        \WPME\WPApi\Pages::register();
    }

    /**
     * This is ran when API returnes <401 unauthorized />
     * If that's the case, we check the API key, if it's invalid, we "deactive" the plugin
     * in terms of settings for API etc.
     */
    public static function unauthorized()
    {
        // Get api
        global $WPME_API;
        // Check api key
        try {
            // Validate API key
            $WPME_API->validate();
        } catch(\Exception $e){
            // Oh ooooh
            // Ok, we do have an issue
            $WPME_API->settingsRepo->addSavedNotice(
                'error',
                'Your WPMKTENGINE installation was reset because your API key was invalid.
                Please login in again into the engine.'
            );
            //$this->repositarySettings->resetInstallation();
            if(is_admin()){
                //\WPMKTENGINE\Wordpress\Redirect::to(admin_url('admin.php?page=WPMKTENGINELogin'));
            }
        }
    }
}

$genoo = new WPMKTENGINE();

/**
 * Get Domain Name
 */
if(!function_exists('genoo_wpme_get_domain')){
  /**
   * Get Domain Nanem
   */
  function genoo_wpme_get_domain($url)
  {
    $pieces = parse_url($url);
    $domain = isset($pieces['host']) ? $pieces['host'] : $pieces['path'];
    if (preg_match('/(?P<domain>[a-z0-9][a-z0-9\-]{1,63}\.[a-z\.]{2,6})$/i', $domain, $regs)) {
      return $regs['domain'];
    }
    return false;
  }
}

/**
 * Genoo / WPME json return data
 */
if(!function_exists('genoo_wpme_on_return')){

    /**
     * @param $data
     */

    function genoo_wpme_on_return($data)
    {
        @error_reporting(0); // don't break json
        header('Content-type: application/json');
        die(json_encode($data));
    }
}

/**
 * Define if not defined
 */
if(!function_exists('genoo_wpme_define')){

    /**
     * @param $name
     * @param $value
     */
    function genoo_wpme_define($name, $value)
    {
        if(!defined($name) && !empty($value)){
            define($name, $value);
        }
    }
}
