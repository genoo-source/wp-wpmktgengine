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

namespace WPMKTENGINE\Wordpress;

use WPMKTENGINE\Utils\Strings,
    WPMKTENGINE\Utils\Json,
    WPMKTENGINE\Users,
    WPMKTENGINE\Import;

/**
 * Class Ajax
 *
 * @package WPMKTENGINE\Wordpress
 */
class Ajax
{

    /**
     * Ajax register hook,
     * done automatically through auto-wiring.
     */

    public static function register()
    {
        $methods = get_class_methods(__CLASS__);
        foreach ($methods as $method){
            // Is it "on" event, and not return?
            if(Strings::startsWith($method, 'on') && ($method != 'onReturn')){
                $methodAction = lcfirst(str_replace('on', '', $method));
                Action::add('wp_ajax_' . $methodAction, array(__CLASS__, $method));
            }
        }
    }


    /**
     * WPMKTENGINE import
     */

    public static function onGenooImportStart()
    {
        /**
         * $comments_count->moderated
         * $comments_count->approved
         * $comments_count->spam
         * $comments_count->trash
         * $comments_count->total_comments
         */

        $commentsCount = Comments::getCount();
        $commentsStatus = $commentsCount->approved > 0 ? true : false;
        if(!$commentsStatus){
            self::onReturn(array(
                'commentsMessage' => __('Unfortunately there are no comments to be imported.', 'wpmktengine' ),
                'commentsStatus' => $commentsStatus
            ));
        } else {
            self::onReturn(array(
                'commentsMessage' => sprintf(__( 'We have found %1$s comment(s) to be imported.', 'wpmktengine' ), $commentsCount->approved),
                'commentsStatus' => $commentsStatus,
                'commentsCount' => $commentsCount->approved
            ));
        }
    }


    /**
     * WPMKTENGINE import comments - step based
     */

    public static function onGenooImportComments()
    {
        $import = new Import();
        self::onReturn(array(
            'messages' => $import->importComments(Comments::getAjaxComments($_REQUEST['per'], $_REQUEST['offset'])),
        ));
    }


    /**
     * WPMKTENGINE start subscribers import
     */

    public static function onGenooImportSubscribersStart()
    {
        $subscribersCount = Users::getCount();
        $subscribersStatus = $subscribersCount > 0 ? true : false;
        if(!$subscribersStatus){
            self::onReturn(array(
                'message' => __('Unfortunately there are no subscribers to be imported.', 'wpmktengine' ),
                'status' => $subscribersStatus
            ));
        } else {
            self::onReturn(array(
                'message' => sprintf(__( 'We have found %1$s subscriber(s) to be imported.', 'wpmktengine' ), $subscribersCount),
                'status' => $subscribersStatus,
                'count' => $subscribersCount
            ));
        }
    }


    /**
     * Import subscribers
     */

    public static function onGenooImportSubscribers()
    {
        $import = new Import();
        self::onReturn(array(
            'messages' => $import->importSubscribers(
                Users::getAjaxUsers($_REQUEST['per'], $_REQUEST['offset']),
                $_REQUEST['leadType']
            ),
        ));

    }


    /**
     * Return
     *
     * @param $data
     */

    public static function onReturn($data)
    {
        // Only suppress error reporting for production, allow debugging in development
        $restoreReporting = error_reporting();
        if (!defined('WP_DEBUG') || !WP_DEBUG) {
            error_reporting(0);
        }
        
        header('Content-type: application/json');
        try{
            $json_data = Json::encode($data);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('JSON encoding error: ' . json_last_error_msg());
            }
            die($json_data);
        } catch (\Exception $e){
            // Log the exception for debugging but don't expose it to the client
            error_log('WPMKTGENGINE AJAX Error: ' . $e->getMessage());
            
            // Return a generic error response
            $error_response = array('error' => 'Internal server error');
            if (defined('WP_DEBUG') && WP_DEBUG) {
                $error_response['debug_message'] = $e->getMessage();
            }
            die(Json::encode($error_response));
        } finally {
            // Restore error reporting
            error_reporting($restoreReporting);
        }
    }
}