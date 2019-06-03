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

use WPMKTENGINE\Wordpress\Filter;

/**
 * Class WidgetsDashboard
 * @package WPMKTENGINE
 */

class WidgetsDashboard
{

    /**
     * Render Feed
     *
     * @param $feedUrl
     * @param $course
     */
    public static function renderFeed($feedUrl, $course = FALSE)
    {
        if(!empty($feedUrl)){
            // Get feed
            require_once(ABSPATH . WPINC . '/feed.php');
            // Set fedd time only 6 hours
            Filter::add('wp_feed_cache_transient_lifetime', function($url){
                if($url == 'https://wpmktgengine.com/members/feed/?post_type=news' || $url == 'https://wpmktgengine.com/members/feed/?post_type=course'){
                    // Our feed is in
                    return 3 * HOUR_IN_SECONDS;
                }
                return 12 * HOUR_IN_SECONDS;
            }, 30, 1);
            // Get a SimplePie feed object from the specified feed source.
            $rss = fetch_feed($feedUrl);
            $maxitems = 3;
            if(!is_wp_error($rss)){ // Checks that the object is created correctly
                // Figure out how many total items there are, but limit it to 5.
                $maxitems = $rss->get_item_quantity(3);
                // Build an array of all the items, starting with element 0 (first element).
                $rss_items = $rss->get_items(0, $maxitems);
            }
            if($maxitems == 0){
                echo __('No new items.', 'wpmktengine');
            } else {
                echo '<div class="rss-widget">';
                    echo '<ul>';
                    if(!empty($rss_items)){
                        foreach($rss_items as $item){
                            echo '<li>';
                            if($course){
                                echo '<a target="_blank" href="'. esc_url($item->get_permalink()) .'">';
                                echo $item->get_title();
                                echo '</a>';
                            } else {
                                echo '<strong>';
                                echo $item->get_title();
                                echo '</strong>';
                            }
                            echo '<span class="rss-date">'. $item->get_date(get_option('date_format', 'Y-m-d')) .'</span>';
                            if($course){
                                $description = self::getDescription($item->get_description()) . '&hellip;';
                            } else {
                                $description = $item->get_content();
                            }
                            echo '<div class="rssSummary">'. $description  .'</div>';
                            echo '</li>';
                        }
                    } else {
                        echo '<li>No new items.</li>';
                    }
                    echo '</ul>';
                echo '</div>';
            }
        }
    }

    /**
     * @param $description
     * @param int $length
     * @return mixed
     */
    public static function getDescription($description, $length = 150)
    {
        return preg_replace('/\s+?(\S+)?$/', '', substr($description, 0, 150));
    }

    /**
     * Render News Feed
     */
    public static function renderNews()
    {
        self::renderFeed('https://wpmktgengine.com/members/feed/?post_type=news&refresh=' . date('Ymd'));
    }

    /**
     * Render New lessons
     */
    public static function renderNewLessons()
    {
        self::renderFeed('https://wpmktgengine.com/members/feed/?post_type=course&refresh=' . date('Ymd'), TRUE);
    }
}