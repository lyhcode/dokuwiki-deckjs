<?php
/**
 * Renderer for XHTML output
 *
 * @author lyhcode <lyhcode@gmail.com>
 */
// must be run within Dokuwiki
if(!defined('DOKU_INC')) die();

// we inherit from the XHTML renderer instead directly of the base renderer
require_once DOKU_INC.'inc/parser/xhtml.php';

/**
 * The Renderer
 */
class renderer_plugin_deckjs extends Doku_Renderer_xhtml {
    var $slideopen = false;
    var $notesopen = false;
    var $base = '';
	var $theme = '';
	var $transition = '';

    /**
     * the format we produce
     */
    function getFormat(){
        // this should be 's5' usally, but we inherit from the xhtml renderer
        // and produce XHTML as well, so we can gain magically compatibility
        // by saying we're the 'xhtml' renderer here.
        return 'xhtml';
    }


    /**
     * Initialize the rendering
     */
    function document_start() {
        global $ID;

        // call the parent
        parent::document_start();

        // store the content type headers in metadata
        $headers = array(
            'Content-Type' => 'text/html; charset=utf-8'
        );
        p_set_metadata($ID,array('format' => array('deckjs' => $headers) ));
        $this->base = DOKU_BASE.'lib/plugins/deckjs';
        $this->theme = isset($_GET['theme'])?$_GET['theme']:$this->getConf('theme');
        $this->transition = isset($_GET['transition'])?$_GET['transition']:$this->getConf('transition');
    }

    /**
     * Print the header of the page
     */
    function deckjs_init($title){
        global $conf;
        global $lang;
        global $INFO;
        global $ID;

		$clear_title = hsc($title);
		$clear_description = $conf['description'];

		if (count($tokens = explode('(', $clear_title)) > 1) {
			$clear_title = trim($tokens[0]);
			$clear_description = trim(str_replace(')', '', $tokens[1]));
		}

        //throw away any previous content
        $this->doc = '<!DOCTYPE html>
<!--[if lt IE 7]> <html class="no-js ie6" lang="'.$conf['lang'].'"> <![endif]-->
<!--[if IE 7]>    <html class="no-js ie7" lang="'.$conf['lang'].'"> <![endif]-->
<!--[if IE 8]>    <html class="no-js ie8" lang="'.$conf['lang'].'"> <![endif]-->
<!--[if gt IE 8]><!-->  <html class="no-js" lang="'.$conf['lang'].'"> <!--<![endif]-->
<head>
<meta charset="utf-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">

<title>'.$clear_title.' - '.$conf['title'].'</title>

<meta name="description" content="'.$clear_description.'">
<meta name="author" content="'.$conf['title'].'">
<meta name="viewport" content="width=1024, user-scalable=no">

<!-- Core and extension CSS files -->
<link rel="stylesheet" href="'.$this->base.'/deckjs/core/deck.core.css">
<link rel="stylesheet" href="'.$this->base.'/deckjs/extensions/goto/deck.goto.css">
<link rel="stylesheet" href="'.$this->base.'/deckjs/extensions/menu/deck.menu.css">
<link rel="stylesheet" href="'.$this->base.'/deckjs/extensions/navigation/deck.navigation.css">
<link rel="stylesheet" href="'.$this->base.'/deckjs/extensions/status/deck.status.css">
<link rel="stylesheet" href="'.$this->base.'/deckjs/extensions/hash/deck.hash.css">
<link rel="stylesheet" href="'.$this->base.'/deckjs/extensions/scale/deck.scale.css">

<!-- Style theme. More available in /themes/style/ or create your own. -->
<link rel="stylesheet" href="'.$this->base.'/deckjs/themes/style/'.$this->theme.'.css">

<!-- Transition theme. More available in /themes/transition/ or create your own. -->
<link rel="stylesheet" href="'.$this->base.'/deckjs/themes/transition/'.$this->transition.'.css">

<script src="'.$this->base.'/deckjs/modernizr.custom.js"></script>
</head>

<body class="deck-container">

<!-- Begin slides -->
<section class="slide" id="title-slide">
	<h1>'.$clear_title.'</h1>
</section>

';
    }

    /**
     * Closes the document
     */
    function document_end(){
        // we don't care for footnotes and toc
        // but cleanup is nice
        $this->doc = preg_replace('#<p>\s*</p>#','',$this->doc);

        if($this->slideopen){
            $this->doc .= '</section>'.DOKU_LF; //close previous slide
        }
        if($this->notesopen){
            $this->doc .= '</div>'.DOKU_LF; //close notes
            $this->notesopen = false;
        }
        $this->doc .= '
<!-- deck.navigation snippet -->
<a href="#" class="deck-prev-link" title="Previous">&#8592;</a>
<a href="#" class="deck-next-link" title="Next">&#8594;</a>

<!-- deck.status snippet -->
<p class="deck-status">
	<span class="deck-status-current"></span>
	/
	<span class="deck-status-total"></span>
</p>

<!-- deck.goto snippet -->
<form action="." method="get" class="goto-form">
	<label for="goto-slide">Go to slide:</label>
	<input type="text" name="slidenum" id="goto-slide" list="goto-datalist">
	<datalist id="goto-datalist"></datalist>
	<input type="submit" value="Go">
</form>

<!-- deck.hash snippet -->
<a href="." title="Permalink to this slide" class="deck-permalink">#</a>

<!-- Grab CDN jQuery, with a protocol relative URL; fall back to local if offline -->
<script src="//ajax.aspnetcdn.com/ajax/jQuery/jquery-1.7.min.js"></script>
<script>window.jQuery || document.write(\'<script src="'.$this->base.'/deckjs/jquery-1.7.min.js"><\/script>\')</script>

<!-- Deck Core and extensions -->
<script src="'.$this->base.'/deckjs/core/deck.core.js"></script>
<script src="'.$this->base.'/deckjs/extensions/hash/deck.hash.js"></script>
<script src="'.$this->base.'/deckjs/extensions/menu/deck.menu.js"></script>
<script src="'.$this->base.'/deckjs/extensions/goto/deck.goto.js"></script>
<script src="'.$this->base.'/deckjs/extensions/status/deck.status.js"></script>
<script src="'.$this->base.'/deckjs/extensions/navigation/deck.navigation.js"></script>
<script src="'.$this->base.'/deckjs/extensions/scale/deck.scale.js"></script>

<!-- Initialize the deck -->
<script>
$(function() {
	$.deck(\'.slide\');
});
</script>

</body>
</html>';
    }

    /**
     * This is what creates new slides
     *
     * A new slide is started for each H2 header
     */
    function header($text, $level, $pos) {
        if($level == 1){
            if(!$this->slideopen){
                $this->deckjs_init($text); // this is the first slide
            }else{
                return;
            }
        }
        else if($level == 2){
            if($this->notesopen){
                $this->doc .= '</div>'.DOKU_LF; //close notes
                $this->notesopen = false;
			}
			if ($this->slideopen){
				$this->doc .= DOKU_LF.'</section>'.DOKU_LF;
			}
            $this->doc .= '<section class="slide">'.DOKU_LF;
            $this->slideopen = true;
        }
		else if($level == 3){
		}
		
		if ($level >= 2){
			$this->doc .= '<h'.($level).'>';
			$this->doc .= $this->_xmlEntities($text);
			$this->doc .= '</h'.($level).'>'.DOKU_LF;
		}
	}

	var $section_stack = array();

    /**
     * Top-Level Sections are slides
     */
    function section_open($level) {
//        if($level < 3){
//            $this->doc .= '<section class="slidecontent">'.DOKU_LF;
//        }else{
//            $this->doc .= '<section>'.DOKU_LF;
//        }
        // we don't use it 
    }

    function section_close() {
    }

    /**
     * Throw away footnote
     */
    function footnote_close() {
        // recover footnote into the stack and restore old content
        $footnote = $this->doc;
        $this->doc = $this->store;
        $this->store = '';
    }

    /**
     * No acronyms in a presentation
     */
    function acronym($acronym){
        $this->doc .= $this->_xmlEntities($acronym);
    }

	function p_open() {
        $this->doc .= '<p>';
    }

    function p_close() {
        $this->doc .= '</p>'.DOKU_LF;
    }

    /**
     * A line stops the slide and start the handout section
     */
    function hr() {
        $this->doc .= '<div class="notes" style="display:none">'.DOKU_LF;
        $this->notesopen = true;
    }

    /**
     * Renders internal and external media
     */
    function _media ($src, $title=NULL, $align=NULL, $width=NULL,
                      $height=NULL, $cache=NULL, $render = true) {

        $ret = '';

        list($ext,$mime,$dl) = mimetype($src);
        if(substr($mime,0,5) == 'image'){
            // first get the $title
            if (!is_null($title)) {
                $title  = $this->_xmlEntities($title);
            }elseif($ext == 'jpg' || $ext == 'jpeg'){
                //try to use the caption from IPTC/EXIF
                require_once(DOKU_INC.'inc/JpegMeta.php');
                $jpeg =& new JpegMeta(mediaFN($src));
                if($jpeg !== false) $cap = $jpeg->getTitle();
                if($cap){
                    $title = $this->_xmlEntities($cap);
                }
            }
            if (!$render) {
                // if the picture is not supposed to be rendered
                // return the title of the picture
                if (!$title) {
                    // just show the sourcename
                    $title = $this->_xmlEntities(basename(noNS($src)));
                }
                return $title;
            }
            //add image tag
            $ret .= '<img src="'.ml($src,array('w'=>$width,'h'=>$height,'cache'=>$cache)).'"';
            $ret .= ' class="scale"';

            // make left/right alignment for no-CSS view work (feeds)
            if($align == 'right') $ret .= ' align="right"';
            if($align == 'left')  $ret .= ' align="left"';

            if ($title) {
                $ret .= ' title="' . $title . '"';
                $ret .= ' alt="'   . $title .'"';
            }else{
                $ret .= ' alt=""';
            }

            if ( !is_null($width) )
                $ret .= ' width="'.$this->_xmlEntities($width).'"';

            if ( !is_null($height) )
                $ret .= ' height="'.$this->_xmlEntities($height).'"';

            $ret .= ' />';

        }elseif($mime == 'application/x-shockwave-flash'){
            if (!$render) {
                // if the flash is not supposed to be rendered
                // return the title of the flash
                if (!$title) {
                    // just show the sourcename
                    $title = basename(noNS($src));
                }
                return $this->_xmlEntities($title);
            }

            $att = array();
            $att['class'] = "media$align";
            if($align == 'right') $att['align'] = 'right';
            if($align == 'left')  $att['align'] = 'left';
            $ret .= html_flashobject(ml($src,array('cache'=>$cache)),$width,$height,
                                     array('quality' => 'high'),
                                     null,
                                     $att,
                                     $this->_xmlEntities($title));
        }elseif($title){
            // well at least we have a title to display
            $ret .= $this->_xmlEntities($title);
        }else{
            // just show the sourcename
            $ret .= $this->_xmlEntities(basename(noNS($src)));
        }

        return $ret;
    }

}

//Setup VIM: ex: et ts=4 enc=utf-8 :
