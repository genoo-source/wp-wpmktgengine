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

namespace WPME\Extensions\ElementorShortcodes;

use Elementor\Plugin;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Class ElementorShortcodes
 *
 * @package WPME\Extensions\ElementorShortcodes
 */
class ElementorShortcodes
{
    /**
     * Register
     */
    public static function register()
    {
      add_action('elementor/widgets/widgets_registered', function() {
        $cta = new \WPME\Extensions\ElementorShortcodes\ElementorShortcodesCta();
        Plugin::instance()->widgets_manager->register_widget_type($cta);
      }); 
    }
}
