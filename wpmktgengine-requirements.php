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

class Requirements
{
    /**
     * Let's check Wordpress version, and PHP version, PHP memory limit and tell those
     * guys whats needed to upgrade, if anything.
     */

    public static function check()
    {
        return true;
        // get vars
        global $wp_version;
        $memoryLimit = !(defined('WP_CLI') && WP_CLI) ? self::getMemoryLimit() : 128 * (1024 * 1024);
        // minimum versions
        $checkMinWp  = '4.5';
        $checkMinPHP = '5.5.0';
        $checkMinMemory = 20 * (1024 * 1024);
        // recover hideLink
        $recoverLink = '<br /><br /><a href="'. admin_url('plugins.php') .'">' . __('Back to plugins.', 'wpmktengine') . '</a>';
        // Check WordPress version
        if (!version_compare($wp_version, $checkMinWp, '>=')){
            self::deactivatePlugin(
                sprintf(__('We are really sorry, but <strong>WPMKTENGINE plugin</strong> requires at least WordPress varsion <strong>%1$s or higher.</strong> You are currently using <strong>%2$s.</strong> Please upgrade your WordPress.', 'wpmktengine'), $checkMinWp, $wp_version) . $recoverLink
            );
        // Check PHP version
        } elseif (!version_compare(PHP_VERSION, $checkMinPHP, '>=')){
            self::deactivatePlugin(
                sprintf(__('We are really sorry, but you need PHP version at least <strong>%1$s</strong> to run <strong>WPMKTENGINE plugin.</strong> You are currently using PHP version <strong>%2$s</strong>', 'wpmktengine'),  $checkMinPHP, PHP_VERSION) . $recoverLink
            );
        // Check PHP Memory Limit, and fallcase if its not unlimited (-1)
        } elseif(!version_compare($memoryLimit, $checkMinMemory, '>=') && ((self::getMemoryLimit() !== '-1' || self::getMemoryLimit() !== -1))){
            $memoryLimitReadable = self::getReadebleBytes($memoryLimit);
            $minMemoryLimitReadable = self::getReadebleBytes($checkMinMemory);
            self::deactivatePlugin(
                sprintf(__('We are really sorry, but to run <strong>WPMKTENGINE plugin</strong> properly you need at least <strong>%1$s</strong> of PHP memory. You currently have <strong>%2$s</strong>', 'wpmktengine'), $minMemoryLimitReadable, $memoryLimitReadable) . $recoverLink
            );
        } elseif(class_exists('WPMKTENGINE')){
            self::deactivatePlugin(
                __('We are really sorry, but for some reason <strong>WPMKTENGINE class</strong> seems to be already defined. Please contact the plugin author for further help.', 'wpmktengine')
            );
        } elseif(!class_exists('\DOMDocument')){
            self::deactivatePlugin(
                __('We are really sorry, but the PHP library <strong>DOMDocument</strong> in your PHP installation. Please contact your hosting provider.', 'wpmktengine')
            );
        }

    }


    /**
     * Get Memory Limit
     *
     * @return int|string
     */

    public static function getMemoryLimit(){ return self::getBytes(ini_get('memory_limit')); }


    /**
     * Ini get value in bytes, helper for get memory limit.
     *
     * @param $val
     * @return int|string
     */

    public static function getBytes($val)
    {
        // if no value, it's zero
        if(empty($val))return 0;
        // swap around
        switch (substr ($val, -1))
        {
            case 'M': case 'm': return (int)$val * 1048576;
            case 'K': case 'k': return (int)$val * 1024;
            case 'G': case 'g': return (int)$val * 1073741824;
            default: return $val;
        }
    }


    /**
     * Readable human format when low memory
     *
     * @param $bytes
     * @param int $precision
     * @return string
     */

    public static function getReadebleBytes($bytes, $precision = 2)
    {
        $kilobyte = 1024;
        $megabyte = $kilobyte * 1024;
        $gigabyte = $megabyte * 1024;
        $terabyte = $gigabyte * 1024;
        if (($bytes >= 0) && ($bytes < $kilobyte)){
            return $bytes . ' B';
        } elseif (($bytes >= $kilobyte) && ($bytes < $megabyte)){
            return round($bytes / $kilobyte, $precision) . ' KB';
        } elseif (($bytes >= $megabyte) && ($bytes < $gigabyte)) {
            return round($bytes / $megabyte, $precision) . ' MB';
        } elseif (($bytes >= $gigabyte) && ($bytes < $terabyte)) {
            return round($bytes / $gigabyte, $precision) . ' GB';
        } elseif ($bytes >= $terabyte){
            return round($bytes / $terabyte, $precision) . ' TB';
        } else {
            return $bytes . ' B';
        }
    }


    /**
     * Deactivates our plugin if anything goes wrong. Also, removes the
     * "Plugin activated" message, if we don't pass requriments check.
     */

    public static function deactivatePlugin($message)
    {
        require_once(ABSPATH . 'wp-admin/includes/plugin.php');
        deactivate_plugins(WPMKTENGINE_PLUGIN);
        unset($_GET['activate']);
        wp_die($message);
        exit();
    }
}