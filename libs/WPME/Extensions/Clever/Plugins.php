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

namespace WPME\Extensions\Clever;

use WPME\Nag\Nag;

/**
 * Class Plugins
 *
 * @package WPME\Extensions\Clever
 */
class Plugins
{
    /** @var array */
    var $notifications = array();

    /** @var array|mixed */
    var $supportedPlugins = array();

    /** @var array  */
    var $installedPlugins = array();

    /**
     * Plugins constructor.
     */
    public function __construct()
    {
        $this->supportedPlugins = $this->getSupportedPlugins();
        if (!function_exists( 'get_plugins')){
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        $this->installedPlugins = \get_plugins();
        $this->nag = new Nag();
    }

    /**
     * Register
     */
    public function register()
    {
        add_action('plugins_loaded', array($this, 'pluginsLoaded'), 345);
        add_action('admin_notices', array($this, 'pluginNotices'), 999);
        add_action('admin_enqueue_scripts', function(){
            wp_enqueue_script('updates');
        }, 999);
        add_action('admin_head', function(){
            ?>
            <script type="text/javascript">
                jQuery(function() {
                    // On install
                    jQuery(document).on('wp-plugin-install-success', function(event, data){
                        if (data.slug.indexOf("wpmktgengine") !== -1) {
                            // Get container
                            var url = ajaxurl.replace(/[^\/]*$/, '');
                            var append = jQuery('.wpme-plugin-notice.plugin-card-' + data.slug);
                            // Append activate message
                                append.find('.notice-left').html(
                                    '<p>Plugin installed successfully, you can proceed to ' +
                                    '<a href="'+ url +'plugins.php">activate it here.</a>' +
                                    '</p>'
                                );
                                append.find('a.install-now')
                                    .attr('href', '')
                                    .removeClass('thickbox')
                                    .removeClass('open-plugin-details-modal');
                        }
                        return;
                    });
                });
            </script>
            <?php
        }, 999);
        // Activated plugin
        add_action('activated_plugin', array($this, 'reactivate'), 10, 2 );
    }

    /**
     * Reactivate?
     *
     * @param $plugin
     * @param $reactivate
     */
    public function reactivate($plugin, $reactivate)
    {
        $plugins = $this->getSupportedPlugins();
        if(array_key_exists($plugin, $plugins)){
            $key = crc32($plugin);
            $keyUser = wp_get_current_user();
            \delete_user_meta($keyUser->ID, 'wp_hide-' . $key);
        }
    }

    /**
     * Check plugins
     */
    public function pluginsLoaded()
    {
        // Search for plugins
        $plugins = $this->getActivePlugins();
        if($plugins && !empty($plugins)){
            foreach($plugins as $plugin){
                // Check if we support plugin
                if($this->isSupportedPlugin($plugin) && !$this->isExtensionInstalled($plugin)){
                    $this->addNotificationFor($plugin);
                }
            }
        }
    }

    /**
     * Render Admin notices
     */
    public function pluginNotices()
    {
        // Add modal
        add_thickbox();
        // Render notices
        foreach($this->notifications as $notification){
            // Plugin definition
            $pluginDefinition = $this->supportedPlugins[$notification];
            // Get plugin message
            // Render message
            $this->nag->renderNotice(
                $this->generateInstallMessage($pluginDefinition),
                crc32($notification),
                true
            );
        }
    }

    /**
     * Generate message
     *
     * @param $pluginDefinition
     * @return string
     */
    public function generateInstallMessage($pluginDefinition)
    {
        $pluginOwner = apply_filters('genoo_wpme_clever_plugins_owner', '<strong>WPMKTGENGINE: </strong>');
          if(isset($_POST['submit']))
          {
          if($pluginDefinition['slug']=='wp-gravity-forms-extension-master' && $_POST['slug']=='wp-gravity-forms-extension-master') :
          $file_name='https://github.com/genoo-source/wp-gravity-forms-extension/archive/master.zip';
          $this->extraction($file_name);
          endif;
          if($pluginDefinition['slug']=='wp-genoo-elementor-addon-master' && $_POST['slug']=='wp-genoo-elementor-addon-master'):
          $file_name='https://github.com/genoo-source/wp-genoo-elementor-addon/archive/master.zip';
          $this->extraction($file_name);
        endif;
          if($pluginDefinition['slug']=='wp-genoo-auto-segmentation-main' && $_POST['slug']=='wp-genoo-auto-segmentation-main'):
          $file_name='https://github.com/genoo-source/wp-genoo-auto-segmentation/archive/main.zip';
          $this->extraction($file_name);
        endif;
      
        } 
        ?>
    <div id="myModal" class="modal_slug" style="diplay:none;">
<!-- Modal content -->
  <div class="modal-content">
      <form method="POST">
    <span class="modal_close">&times;</span>
  </br>
<div class="form-group">
     <input type="hidden" class="slug" name="slug" value="" />
  <input type="submit" class="btn btn-default" name="submit" value="InstallNow"/>
   </div>
    </form>
  </div>
</div>
  
<style>

/* The Modal (background) */
.modal_slug {
  display: none; /* Hidden by default */
  position: fixed; /* Stay in place */
  z-index: 1; /* Sit on top */
  padding-top: 100px; /* Location of the box */
  left: 0;
  top: 0;
  width: 100%; /* Full width */
  height: 100%; /* Full height */
  overflow: auto; /* Enable scroll if needed */
  background-color: rgb(0,0,0); /* Fallback color */
  background-color: rgba(0,0,0,0.4); /* Black w/ opacity */
}

/* Modal Content */
.modal-content {
  background-color: #fefefe;
  margin: auto;
  padding: 20px;
  border: 1px solid #888;
  width: 50%;
}

/* The Close Button */
.modal_close{
  color: #aaaaaa;
  float: right;
  font-size: 28px;
  font-weight: bold;
}
.modal_close:hover,
.modal_close:focus{
  color: #000;
  text-decoration: none;
  cursor: pointer;
}
</style>
<script>
jQuery( document ).ready(function() {
 jQuery(".testpopup").each(function(index) {
    jQuery(this).on("click", function(){
        // For the boolean value
        var attri = jQuery(this).attr('dataattribute');
        jQuery(".slug").val(attri);
       jQuery(".modal_slug").show();
       
    });
});
 jQuery(".modal_close").on('click',function(event){
           event.preventDefault();
          jQuery(".modal_slug").hide();
      });
});
</script>
        
        <?php
             if($pluginDefinition['slug']=='wp-genoo-elementor-addon-master' || $pluginDefinition['slug']=='wp-gravity-forms-extension-master' || $pluginDefinition['slug']=='wp-genoo-auto-segmentation-main'){
              
                     return "
            <div class='wpme-plugin-notice plugin-card-{$pluginDefinition['slug']}'>
            
                <div class='notice-right tester'>
                   <a href='#' 
                    class='install-now button button-primary open-plugin-details-modal testpopup' dataattribute='{$pluginDefinition['slug']}'>Check it out!</a>
                </div>
                <div class='notice-left'><p>{$pluginOwner}{$pluginDefinition['message']}</p></div>
                <div class='clear cls cf'></div>
            </div>
        "; 
              
          }
       
      else
          {
        return "
            <div class='wpme-plugin-notice plugin-card-{$pluginDefinition['slug']}'>
                <div class='notice-right'>
                   <a 
                    data-slug='{$pluginDefinition['slug']}'
                    data-title='{$pluginDefinition['name']}' 
                    href='{$this->getPluginInfoUrl($pluginDefinition['slug'])}' 
                    class='install-now button button-primary thickbox open-plugin-details-modal'>Check it out!</a>
                </div>
                <div class='notice-left'><p>{$pluginOwner}{$pluginDefinition['message']}</p></div>
                <div class='clear cls cf'></div>
            </div>
        ";
    }

    /**
     * @param $plugin
     * @return string
     */
    public function getPluginInfoUrl($plugin)
    {
        return admin_url("/plugin-install.php?tab=plugin-information&plugin=$plugin&TB_iframe=true&width=600&height=550");
    }

    /**
     * Get's plugin link
     *
     * @param $plugin
     * @return string
     */
    public function getPluginInfoLink($plugin)
    {
        $link = $this->getPluginInfoUrl($plugin);
        return "<a href=\"$link\" class=\"thickbox open-plugin-details-modal\" aria-label=\"More information about $plugin\" data-title=\"$plugin\">More Details</a>";
    }

    /***
     * @param $plugin
     * @return bool
     */
    public function isSupportedPlugin($plugin)
    {
        return array_key_exists($plugin, $this->supportedPlugins);
    }

    /**
     * Is extension installed?
     *
     * @param $plugin
     * @return bool
     */
    public function isExtensionInstalled($plugin)
    {
        $supportedPlugin = $this->supportedPlugins[$plugin];
        return array_key_exists($supportedPlugin['file'], $this->installedPlugins);
    }

    /**
     * @param $plugin
     */
    public function addNotificationFor($plugin)
    {
        $this->notifications[] = $plugin;
    }

    /**
     * @return mixed
     */
    public function getSupportedPlugins()
    {
        //
        // Array
        $plugins = array();
        // Add supported plugins
        $plugins['bbpress/bbpress.php'] = array(
            'connection' => 'https://wordpress.org/plugins/wpmktgengine-extension-bbpress/',
            'slug' => 'wpmktgengine-extension-bbpress',
            'message' => 'Hey there, I see that you are using bbPress. We have an integration with bbPress and can get that working by installing our plugin extension.',
            'name' => '',
            'file' => 'wpmktgengine-extension-bbpress/wpmktgengine-bbpress.php'
        );
        $plugins['buddypress/bp-loader.php'] = array(
            'connection' => 'https://wordpress.org/plugins/wpmktgengine-extension-buddypress/',
            'slug' => 'wpmktgengine-extension-buddypress',
            'message' => 'Hey there, I see that you are using BuddyPress. We have an integration with BuddyPress and can get that working by installing our plugin extension.',
            'name' => '',
            'file' => 'wpmktgengine-extension-buddypress/wpmktgengine-buddypress.php'
        );
        $plugins['lifterlms/lifterlms.php'] = array(
            'connection' => 'https://wordpress.org/plugins/lifterlms-wpmktgengine-extension/',
            'slug' => 'lifterlms-wpmktgengine-extension',
            'message' => 'Hey there, I see that you are using LifterLMS. We have an integration with LifterLMS and can get that working by installing our plugin extension.',
            'name' => '',
            'file' => 'lifterlms-wpmktgengine-extension/wpmktgengine-lifter-lms.php'
        );
        $plugins['woocommerce/woocommerce.php'] = array(
            'connection' => 'https://wordpress.org/plugins/wpmktgengine-extension-woocommerce/',
            'slug' => 'wpmktgengine-extension-woocommerce',
            'message' => 'Hey there, I see that you are using WooCommerce. We have an integration with WooCommerce and can get that working by installing our plugin extension.',
            'name' => '',
            'file' => 'wpmktgengine-extension-woocommerce/wpmktgengine-woocommerce.php'
        );
        
       
            $plugins['gravityforms/gravityforms.php'] = array(
            'connection' => '',
            'slug' => 'wp-gravity-forms-extension-master',
            'message' => 'Hey there, I see that you are using Gravityforms. We have an integration with Gravityforms and can get that working by installing our plugin extension.',
            'name' => '',
            'file' => 'wp-gravity-forms-extension-master/wp-starter.php'
        );
          $plugins['elementor-pro/elementor-pro.php'] = array(
            'connection' => '',
            'slug' => 'wp-genoo-elementor-addon-master',
            'message' => 'Hey there, I see that you are using Elementor. We have an integration with Elementor and can get that working by installing our plugin extension.',
            'name' => '',
            'file' => 'wp-genoo-elementor-addon-master/Genno_Elementor_Extension.php'
        );
           $plugins['wpmktgengine/wpmktgengine.php'] = array(
            'connection' => '',
            'slug' => 'wp-genoo-auto-segmentation-main',
            'message' => 'Hey there, I see that you are using Wpmktgengine. We have an integration with Wpmktgengine and can get that working by installing our plugin extension.',
            'name' => '',
            'file' => 'wp-genoo-auto-segmentation-main/wp-genoo-auto-segmentation.php'
        );
        /*
        $plugins['woocommerce-subscriptions/woocommerce-subscriptions.php'] = array(
            'connection' => '',
            'message' => 'Hey there, I see you have WooCommerce Subscriptions installed, we can make it work for you.',
        );
        */
        // Return
        return $plugins;
    }

    /**
     * @return mixed
     */
    public function getPlugins(){ return get_plugins(); }

    /**
     * @return mixed
     */
    public function getActivePlugins(){ return get_option ('active_plugins', array()); }

    /**
     * Install plugin
     * http://stackoverflow.com/questions/10353859/is-it-possible-to-programmatically-install-plugins-from-wordpress-theme
     */
    public function installPlugin($plugin)
    {
        $api = plugins_api( 'plugin_information', array(
            'slug' => $plugin,
            'fields' => array(
                'short_description' => false,
                'sections' => false,
                'requires' => false,
                'rating' => false,
                'ratings' => false,
                'downloaded' => false,
                'last_updated' => false,
                'added' => false,
                'tags' => false,
                'compatibility' => false,
                'homepage' => false,
                'donate_link' => false,
            ),
        ));
        include_once(ABSPATH . 'wp-admin/includes/file.php');
        include_once(ABSPATH . 'wp-admin/includes/misc.php');
        include_once(ABSPATH . 'wp-admin/includes/class-wp-upgrader.php');
        $upgrader = new \Plugin_Upgrader(
            new \Plugin_Installer_Skin(
                compact('title', 'url', 'nonce', 'plugin', 'api')
            )
        );
        $upgrader->install($api->download_link);
    }

    /**
     * @param $notification
     */
    public function addNotification($notification)
    {
        $this->notifications[] = $notification;
    }
    
    public function extraction($file_name)
    {

            $ch = curl_init();
			$headers = array();
			$headers[] = 'Authorization: token d94b82591aa67cedac19a2e24a53d033df07fb17';
			curl_setopt($ch, CURLOPT_URL, $file_name);
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST,"GET");
			curl_setopt($ch, CURLOPT_HTTPHEADER,$headers);
			Curl_setopt($ch,CURLOPT_FOLLOWLOCATION, true);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

			curl_setopt($ch,CURLOPT_BINARYTRANSFER, true);
			$result = curl_exec($ch);
			$down_url=curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
          include_once(ABSPATH . 'wp-admin/includes/file.php');
        include_once(ABSPATH . 'wp-admin/includes/misc.php');
       include_once(ABSPATH . 'wp-admin/includes/class-wp-upgrader.php');
       $upgrader = new \Plugin_Upgrader(
            new \Plugin_Installer_Skin(
                compact('title', 'url', 'nonce', 'plugin', 'api')
            )
        );
        $upgrader->install($down_url);
 }




    
}