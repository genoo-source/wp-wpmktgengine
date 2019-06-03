<?php
/**
 * This file is part of the WPMKTGENGINE plugin.
 *
 * Copyright (c) 2014 WPMKTGENGINE, LLC (http://wpmktgengine.com/)
 *
 * For the full copyright and license information, please view
 * the WPMKTENGINE.php file in root directory of this plugin.
 *
 * WPMKTENGINE TinyMCE plugin - CTA
 *
 * @version 1
 * @author latorante.name
 */

// Include parent class
if(file_exists(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'TinyMCEHanlder.php')){
    require_once 'TinyMCEHanlder.php';
}

/**
 * Class GenooTinyMCECTA
 */

class GenooTinyMCELumens extends TinyMCEHanlder
{
    /** @var array */
    public $lumens;


    /**
     * Constructor
     */

    public function __construct()
    {
        parent::__construct('genooLumens');
    }


    /**
     * Resolve additional variables
     */

    public function resolveSecond()
    {
        $this->lumens = !empty($_GET['lumens']) ? $_GET['lumens'] : array();
    }


    /**
     * WPMKTENGINE CTA pop-up javascript
     */

    public function renderJavascript()
    {
        ?>
        // Output
        var output = '';
        // get vals
        var form = document.getElementById("form");
        var formVal = form.options[form.selectedIndex].value;
        // output
        output += '[<?php echo $this->shortcode; ?>';
        if(formVal){ output += ' id=\''+formVal+'\''; }
        output += ']';        // bam
        tinyMCEPopup.execCommand('mceReplaceContent', false, output);
        tinyMCEPopup.execCommand('<?php echo $this->refresh; ?>');
        tinyMCEPopup.close();
    <?php
    }


    /**
     * WPMKTENGINE CTA pop-up form
     */

    public function renderForm()
    {
        ?>
        <p>
            <label for="form">Lumen Class:</label><br/>
            <select name="form" id="form">
                <?php
                if(isset($this->lumens) && !empty($this->lumens)){
                    foreach($this->lumens as $key => $value){
                        $selectedVal = in_array($key, $this->selected) ? ' selected' : '';
                        echo '<option value="'. $key .'" '. $selectedVal .'>'. $value .'</option>';
                    }
                }
                ?>
            </select>
        </p>
    <?php
    }
}

new GenooTinyMCELumens();

?>