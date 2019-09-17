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

use WPMKTENGINE\Wordpress\Utils;
use WPMKTENGINE\Utils\Strings;
use WPMKTENGINE\Wordpress\Notice;
use WPMKTENGINE\Tools;
use WPMKTENGINE\RepositoryLandingPages;

class TablePages extends Table
{
    /** @var \WPMKTENGINE\RepositoryPages */
    var $repositoryPages;
    /** @var \WPMKTENGINE\RepositorySettings */
    var $repositorySettings;
    /** @var string */
    var $activeForm;
    /** @var bool */
    var $set = FALSE;

    /**
     * Constructor
     *
     * @param RepositoryLog $log
     */

    function __construct(\WPMKTENGINE\RepositoryPages $repositoryPages, \WPME\RepositorySettingsFactory $repositorySettings)
    {
        global $status, $page;
        $this->repositoryPages = $repositoryPages;
        $this->repositorySettings = $repositorySettings;
        parent::__construct();
    }


    /**
     * Basic setup, returns table columns.
     *
     * @return array
     */

    function get_columns()
    {
        return array(
            'name' => __('Template name', 'wpmktengine'),
            'landing' => 'Used by Landing Pages',
        );
    }


    /**
     * Basic setup, returns sortable columns
     *
     * @return array
     */

    function get_sortable_columns(){ return array('name' => array('name', false)); }


    public function column_landing($item)
    {
        if($this->isFolder($item)){
          return '';
        }
        if(!empty($item['landing'])){
            $id = "hidden-list-" . $item['id'];
            $r = "<table class=\"wp-list-table widefat\" id=\"$id\">";
            $r .= "<thead><tr>";
                $r .= "<th class=\"manage-column column-title\" scope=\"col\">Title</th>";
                $r .= "<th class=\"manage-column column-url\" scope=\"col\">Url</th>";
                $r .= "<th class=\"manage-column column-setup\" scope=\"col\">Setup</th>";
                $r .= "<th class=\"manage-column column-active\" scope=\"col\">Active</th>";
                $r .= "<th class=\"manage-column column-home\" scope=\"col\">Homepage</th>";
                $r .= "<th class=\"manage-column column-redirect\" scope=\"col\">Redirect</th>";
            $r .= "</tr></thead>";
            $r .= "<tbody>";
            $counterHide = false;
            $counterMax = 2;
            $counter = 1;
            $counterRemaing = count($item['landing']) > $counterMax ? (count($item['landing']) - $counterMax) : 0;
            $counterJS = "onclick='Api.prolognedList(this, event, \"$id\");'";
            // wpme-landing-pages
            foreach($item['landing'] as $post){
                if($counter > $counterMax){
                    $counterHide = true;
                }
                $link = admin_url('post.php?post='. $post->ID .'&action=edit');
                $class = $counter > $counterMax ? "class='next hidden'" : "\"class='next'";
                $r .= "<tr $class>";
                $r .= "<td><a href=\"". $link ."\">". $post->post_title ."</a></td>";

                // URL
                $metaURL = get_post_meta($post->ID, 'wpmktengine_landing_url', true);
                $r .= "<td>". RepositoryLandingPages::base() . $metaURL .  "</td>";

                // SETUP
                $metaTemplate = get_post_meta($post->ID, 'wpmktengine_landing_template', true);
                $metaUrl = get_post_meta($post->ID, 'wpmktengine_landing_url', true);
                $validTemplate = !empty($metaTemplate) ? true : false;
                $validUrl = !empty($metaUrl) && filter_var(RepositoryLandingPages::base() . $metaUrl, FILTER_VALIDATE_URL) === false ? false : true;
                if ($validUrl && $validTemplate) {
                    $metaSETUP = '<span class="genooTick active">&nbsp;</span>';
                } else {
                    $metaSETUP = '<span class="genooCross">&times;</span>';
                }
                $r .= "<td>$metaSETUP</td>";

                // ACTIVE
                $metaActive = get_post_meta($post->ID, 'wpmktengine_landing_active', true);
                if ($metaActive == 'true') {
                    $metaActive = '<spain class="genooTick active">&nbsp;</spain>';
                } else {
                    $metaActive = '<span class="genooCross">&times;</span>';
                }
                $r .= "<td>". $metaActive ."</td>";

                // HOMEPAGE
                $r .= "<td><span class=\"genooCross\">&times;</span></td>";

                // REDIRECT
                $metaUrlActive = get_post_meta($post->ID, 'wpmktengine_landing_redirect_active', true);
                $metaUrl = get_post_meta($post->ID, 'wpmktengine_landing_redirect_url', true);
                if ($metaUrlActive == 'true') {
                    $metaREDIRECT = '<span class="genooTick active">&nbsp;</span>';
                    $metaREDIRECT .= '<br />Redirects to: <strong>'. $metaUrl  .'</strong>';
                } else {
                    $metaREDIRECT = '<span class="genooCross">&times;</span>';
                }
                $r .= "<td>$metaREDIRECT</td>";
                $r .= "</tr>";
                $counter++;
            }
            $r .= '</tbody>';
            $r .= '</table>';
            if($counterHide){
                $r .= "<a class='button' $counterJS href=\"#\"><span>Show</span> Remaining ($counterRemaing)</a>";
            }
            return $r;
        }
        return __('No Landing Pages are using this template.', 'wpmktengine');
    }

    public function get_column_name($item){
      return isset($item[$this->repositoryPages::REPO_SORT_NAME]) 
        && !empty($item[$this->repositoryPages::REPO_SORT_NAME]) 
        && $item[$this->repositoryPages::REPO_SORT_NAME] !== 'undefined' 
          ? $item[$this->repositoryPages::REPO_SORT_NAME] 
          : __('No title.', 'wpmktengine');      
    }

    /**
     * @param $item
     * @return string
     */
    public function column_name($item, $nesting = null)
    {
        $name = $this->get_column_name($item);
        if($this->isDrafts($item) || $this->isFolder($item)){
           return "<span class=\"dashicons dashicons-portfolio\"></span> $name";
        }
        $actions = $this->row_actions(array(
            'edit' => $this->getLink('edit', $item['id']),
            'create' => $this->getLink('create', $item['id']),
            'prev' => $this->getLink('prev', $item['id']),
            'rename' => $this->getLink('rename', $item['id'], $name),
            'trash' => $this->getLink('trash', $item['id'])
        ));
        $actionsId = $this->row_actions(array('id' => 'ID: ' . $item['id']));
        $actionsBublished = $this->row_actions(array('published' => __('Published: ', 'wpmktengine') . date('Y/m/d', strtotime($item['created']))));
        $actionDiv = $nesting === null ? "<div>" : "<div class=\"nested level-$nesting\">";
        $actionDivClosing = "</div>";
        return $actionDiv . $name . $actionsId . $actionsBublished . $actions . $actionDivClosing;
    }

    public function single_row($item) {
      // Keep the alternating class
      static $level = 0;
      // Extract original classname
      $className = array_key_exists('className', $item) ? $item['className'] : '';
      // Add className
      $item['className'] = $level !== 0 ? $className . ' nested ' . 'nested-level-' . (int)$level : $className;
      $item['className'] = str_replace('--', '-', $item['className']);
      ++$level;
      // Normal rows follow previous logic
      if(!$this->isFolder($item) || $this->isDrafts($item)){
        // Render old way
        parent::single_row($item);
        // Reset leveling
        $level = 0;
        return;
      }
      // First level folder
      parent::single_row($item);
      // Create a sub-loop of internal items
      foreach($item as $innerName => $innerValue){
        if($innerName === $this->repositoryPages::REPO_SORT_NAME){
          continue;
        }
        if($innerName === 'className'){
          continue;
        }
        $goingDeeper = $this->isFolder($innerValue);
        $level = $goingDeeper ? $level + 1 : $level - 1;
        $this->single_row($innerValue);
      }

    }

    public function getNewLandingPageLink($id = false){
      $realUrlEmptyAdmin = rtrim(admin_url(), '/') . '/';
      return Utils::addQueryParams($realUrlEmptyAdmin . 'post-new.php', array(
        'post_type' => 'wpme-landing-pages',
        'wpmktengine_landing_template' => $id,
      ));
    }

    public function isDrafts($item){
      return isset($item['isDrafts']);
    }

    public function isFolder($item){
      return !array_key_exists('id', $item);
    }

    public function getFirstItem(){
      $drafts = __('Drafts (Landing Pages)', 'wpmktengine');
      return array(
        $this->repositoryPages::REPO_SORT_NAME => $drafts,
        'name' => $drafts,
        'isDrafts' => true,
        'className' => 'highlight',
        'id' => null,
        'craeted' => null,
        'landing' => RepositoryLandingPages::findDrafts(),
      );
    }

    /**
     * Get Link
     *
     * @param $which
     * @param null $id
     * @param null $name
     * @return string
     */
    function getLink($which, $id = NULL, $name = NULL)
    {
        $r = new \stdClass();
        // Get url without params
        $realUrlEmpty = strtok(Utils::getRealUrl(), "?");
        $realUrl = $realUrlEmpty . "?page=WPMKTENGINEPages";
        $r->href = '';
        $r->other = '';
        $r->title = '';
        switch($which){
            case 'edit':
                $r->href =  Utils::addQueryParams(WPMKTENGINE_BUILDER_NEW, array(
                  'id' => $id,
                ));
                $r->title = 'Edit';
                $r->other = 'target="_blank"';
                break;
            case 'create':
                $r->href = $this->getNewLandingPageLink($id);
                $r->title = 'Create a Landing Page';
                break;
            case 'prev':
                $r->href = Utils::addQueryParam(WPMKTENGINE_HOME_URL, 'genooIframeBuidler', $id);
                $r->title = 'Preview';
                $r->other = 'target="_blank"';
                break;
            case 'rename':
                $r->href = '#';
                $r->title = 'Rename';
                $title = __('How would you like rename this Page Template?.', 'wpmktengine');
                $url = Utils::addQueryParams($realUrl, array(
                    'genooPagesRename' => $id,
                    'genooPagesRenameTitle' => ''
                ));
                // Convert the value and remove first and last characters
                $value = json_encode($name);
                $value = substr($value, 1);
                $value = substr_replace($value , '', -1);
                $r->other = 'onclick="Tool.promptGo(\''. $title .'\', \''. $url .'=\', \''. $value .'\');"';
                break;
            case 'trash':
                $r->href = Utils::addQueryParams($realUrl, array(
                    'genooPagesDelete' => $id
                ));
                $r->other = 'onclick="Tool.promptBeforeGo(event, this, \'Are you sure you want to delete this Layout Page?\');"';
                $r->title = 'Delete';
                break;
        }
        return '<a href="'. $r->href .'" '. $r->other .'>'. $r->title .'</a>';
    }

    /**
     * Remove cached forms
     *
     * @param $which
     */

    function extra_tablenav($which)
    {
        $where = strtok(Utils::getRealUrl(), "&");
        $whereNewLandingPage = $this->getNewLandingPageLink();
        if($which == 'top'){
            echo '
              <div class="alignleft actions">
                <form style="display: inline; margin: 0" method="POST" action="'. $where .'">
                  <input type="submit" name="genooPagesFlushCache" id="submit" class="button alignCenter genooExtraNav" value="'. __('Sync Templates', 'wpmktengine') .'">
                  <a target="_blank" class="button button-primary genooExtraNav" href="'. WPMKTENGINE_BUILDER_NEW .'">'. __('Add new Page Template', 'wpmktengine') .'</a>
                  <a href="'. $whereNewLandingPage .'" class="button button-primary genooExtraNav">'. __('Add new Landing Page', 'wpmktengine') .'</a>
                </form>
              </div>
            ';
        }
    }

    /**
     * No Items notices
     */
    function no_items(){ echo __('There are no Pages created in your account.', 'wpmktengine'); }

    /**
     * Process it!
     */
    public function process()
    {
        // process actions
        if(isset($_POST['genooPagesFlushCache'])){
            try{
                $this->repositoryPages->flush();
                $this->addNotice('updated', __('All pages successfully synced.', 'wpmktengine'));
            } catch (\Exception $e){
                $this->addNotice('error', $e->getMessage());
            }
        }
        if(isset($_GET['genooPagesDelete'])){
            // Template id
            $template_id = $_GET['genooPagesDelete'];
            // Prepare
            $this->prepare_items();
            // Go through this
            $template_checks = $this->items;
            $template_checks_found = NULL;
            if(Utils::isIterable($template_checks)){
                foreach($template_checks as $item){
                    if($item['id'] == $template_id && !empty($item['landing'])){
                        $template_checks_found = $item['landing'];
                        break;
                    }
                }
            }
            if(!is_null($template_checks_found)){
                // We are not deleting, page has dependencies
                $landing = '<ol>';
                    foreach($template_checks_found as $template){
                        $landing .= '<li>'. $template->post_title .'</li>';
                    }
                $landing .= '</ol>';
                $landingAfter = '</p><p>If you wish to remove the template, please remove the Landing Pages first.';
                $this->addNotice('error', __('The template could not be removed. Because these Landing pages depend on it: ' . $landing . $landingAfter, 'wpmktengine'));
                return;
            }
            // Delete page
            try {
                $this->repositoryPages->deletePage($template_id);
                $this->repositoryPages->flush();
                $this->addNotice('updated', __('Template successfully removed.', 'wpmktengine'));
            } catch (\Exception $e){
                $this->addNotice('error', $e->getMessage());
            }
        }
        // Rename page
        if(isset($_GET) && array_key_exists('genooPagesRename', $_GET) && array_key_exists('genooPagesRenameTitle', $_GET)){
            // If all parameters present
            if(!empty($_GET['genooPagesRename']) && !empty($_GET['genooPagesRenameTitle'])){
                $id = $_GET['genooPagesRename'];
                $name = $_GET['genooPagesRenameTitle'];
                try {
                    $this->repositoryPages->renamePage($id, $name);
                    $this->repositoryPages->flush();
                    $this->addNotice('updated', __('Template successfully renamed.', 'wpmktengine'));
                } catch (\Exception $e){
                    $this->addNotice('error', $e->getMessage());
                }
            }
        }
    }

    /**
     *  Prepares, sorts, delets, all that stuff :)
     */
    public function prepare_items()
    {
        if($this->set == TRUE){ return; }
        try {
            $perPage = 100;
            $allLogs = $this->repositoryPages->getStructuredPagesTable();
            $this->_column_headers = array($this->get_columns(), array(), $this->get_sortable_columns());
            if(!isset($_GET['orderby'])){
                $_GET['orderby'] = $this->repositoryPages::REPO_SORT_NAME;
            }
            usort($allLogs, array(&$this, 'usort_reorder'));
            $this->found_data = array_slice($allLogs,(($this->get_pagenum()-1)* $perPage), $perPage);
            $this->set_pagination_args(array('total_items' => count($allLogs), 'per_page' => $perPage));
            // Append drafts row
            array_unshift($this->found_data, $this->getFirstItem());
            $this->items = $this->found_data;
            $this->set = TRUE;
        } catch (\WPMKTENGINE\ApiException $e){
            $this->addNotice('error', 'Genoo API: ' . $e->getMessage());
        } catch (\Exception $e){
            $this->addNotice('error', $e->getMessage());
        }
    }
}
