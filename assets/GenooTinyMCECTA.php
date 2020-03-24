<?php
/**
 * This file is part of the WPMKTGENGINE plugin.
 *
 * Copyright (c) 2014 WPMKTGENGINE, LLC (http://wpmktgengine.com/)
 *
 * For the full copyright and license information, please view
 * the WPMKTENGINE.php file in root directory of this plugin.
 *
 * WPMKTGENGINE TinyMCE plugin - CTA
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

class GenooTinyMCECTA extends TinyMCEHanlder
{
    /** @var string */
    public $cta;
    /** @var array */
    public $ctas;


    /**
     * Constructor
     */

    public function __construct()
    {
        parent::__construct('WPMKTENGINECTA');
    }


    /**
     * Resolve additional variables
     */

    public function resolveSecond()
    {
        $this->cta = (isset($_GET['cta']) && $_GET['cta'] == 'true') ? true : false;
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
        var cta = document.getElementById("cta");
        var ctaVal = cta.options[cta.selectedIndex].value;
        var align = document.getElementById("align");
        var alignVal = align.options[align.selectedIndex].value;
        // output
        output += '[<?php echo $this->shortcode; ?>';
        if(ctaVal){ output += ' id=\''+ctaVal+'\''; }
        if(alignVal){ output += ' align=\''+alignVal+'\''; }
        output += ']';
        // bam
        <?php if($this->edit){ ?>
        //tinyMCEPopup.execCommand('WPMKTGENGINERefresh', false, output);
        //tinyMCEPopup.execCommand('mceReplaceContent', false, output);
        tinyMCEPopup.execCommand('<?php echo $this->refresh; ?>Ref', false, output);
        <?php } else { ?>
        tinyMCEPopup.execCommand('mceReplaceContent', false, output);
        <?php } ?>
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
            <label for="cta">CTA:</label><br/>
            <?php if(isset($this->ctas) && !empty($this->ctas)){ ?>
                <select name="cta" id="cta">
                    <?php
                    foreach($this->ctas as $key => $value){
                        $selectedVal = is_array($this->selected) && in_array($key, $this->selected) ? ' selected' : '';
                        echo '<option value="'. $key .'" '. $selectedVal .'>'. $value .'</option>';
                    }
                    ?>
                </select>
            <?php } else { ?>
                <strong>You don't have any CTA's in your WordPress installation.</strong>
            <?php } ?>
        </p>
        <p>
            <label for="align">Align:</label><br/>
            <select name="align" id="align">
                <option value="">None</option>
                <option value="left" <?php echo is_array($this->selected) && in_array('left', $this->selected) ? 'selected' : '' ?>>Left</option>
                <option value="right" <?php echo is_array($this->selected) && in_array('right', $this->selected) ? 'selected' : '' ?>>Right</option>
            </select>
        </p>
        <script type="text/javascript">
            jQuery(function() {
                var data = Popup.data();
                if(data){
                    var dataShort = '[' + data + ']';
                    var atts = window.parent.wp.shortcode.next('WPMKTENGINECTA', dataShort);
                    if(atts.shortcode.attrs.named.id){
                        jQuery('#cta').val(atts.shortcode.attrs.named.id).change();
                    }
                    if(atts.shortcode.attrs.named.align){
                        jQuery('#align').val(atts.shortcode.attrs.named.align).change();
                    }
                }
            });
        </script>
        <?php
    }
}

new GenooTinyMCECTA();

?>
