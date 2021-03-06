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

use WPMKTENGINE\Url;

class Request
{
    /**
     * Has request in?
     *
     * @param $id
     * @return bool
     */

    public static function has($id)
    {
        if(isset($_GET[$id])){
            return true;
        }
        return false;
    }


    /**
     * Form result?
     *
     * @return bool|null
     */

    public static function formResult()
    {
        if(isset($_GET['formResult'])){
            if($_GET['formResult'] == 'true'){
                return true;
            } elseif($_GET['formResult'] == 'false'){
                return false;
            }
        }
        return null;
    }
}