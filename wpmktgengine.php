<?php
/*
    Plugin Name: WPMKTGENGINE
    Description: Genoo, LLC
    Author:  Genoo, LLC
    Author URI: http://www.genoo.com/
    Author Email: info@genoo.com
    Version: 4.0.20
    License: GPLv2
    Text Domain: wpmktgengine
*/
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
 * 1. If no WordPress, go home
 */

if (!defined('ABSPATH')) {
    exit;
}
define('WPMKTENGINE_PLUGIN', 'wpmktgengine/wpmktgengine.php');

/**
 * 2. Check minimum requirements (wp version, php version)
 * Reason behind this is, we just need PHP 5.3.1 at least,
 * and WordPress 3.3 or higher. We just can't run the show
 * on some outdated installation.
 */

require_once('wpmktgengine-requirements.php');
Requirements::check();

/**
 * 3. Activation / deactivation
 * Turned off for now
 */

register_activation_hook(__FILE__, array('WPMKTENGINE', 'activate'));

/**
 * 4. Go, and do WPMKTENGINE!
 */

require_once('wpmktgengine-init.php');
