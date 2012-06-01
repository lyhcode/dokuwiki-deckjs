<?php
/**
 * Deck.js Plugin: Display a Wiki page as Deck.js slideshow presentation
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     lyhcode <lyhcode@gmail.com>
 */
// must be run within Dokuwiki
if(!defined('DOKU_INC')) die();

if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
require_once(DOKU_PLUGIN.'syntax.php');

/**
 * All DokuWiki plugins to extend the parser/rendering mechanism
 * need to inherit from this class
 */
class syntax_plugin_deckjs extends DokuWiki_Syntax_Plugin {

    /**
     * What kind of syntax are we?
     */
    function getType(){
        return 'substition';
    }

    /**
     * What about paragraphs?
     */
    function getPType(){
        return 'normal';
    }

    /**
     * Where to sort in?
     */
    function getSort(){
        return 800;
    }


    /**
     * Connect pattern to lexer
     */
    function connectTo($mode) {
        $this->Lexer->addSpecialPattern('~~DECKJS[^~]*~~', $mode, 'plugin_deckjs');
    }


    /**
     * Handle the match
     */
    function handle($match, $state, $pos, &$handler){
		return ($match != '~~DECKJS~~')?explode(' ', trim(substr($match, 8, -2))):array();
    }

    /**
     * Create output
     */
    function render($format, &$renderer, $data) {
        global $ID;
        if($format != 'xhtml') return false;

		$args = null;
		if (count($data)==1) {
			$args = array('theme'=>$data[0]);
		}
		else if (count($data)>1) {
			$args = array('theme'=>$data[0], 'transition'=>$data[1]);
		}

		$iconhtml = '<a href="'.exportlink($ID, 'deckjs', $args).'" title="'.$this->getLang('view').'" class="deckjs-link">';
        $iconhtml.= '<img src="'.DOKU_BASE.'lib/plugins/deckjs/screen.gif" align="right" alt="'.$this->getLang('view').'" width="48" height="48" />';
		$iconhtml.= '</a>';
        $renderer->doc = $iconhtml . $renderer->doc;
        return true;
    }
}

//Setup VIM: ex: et ts=4 enc=utf-8 :
