<?php
/**
 * DokuWiki select plugin (Syntax Component)
 *
 * @license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author  Ikuo Obataya <I.Obataya@gmail.com>
 *
 */
if(!defined('DOKU_INC')) die();
if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
require_once(DOKU_PLUGIN.'syntax.php');

class syntax_plugin_select extends DokuWiki_Syntax_Plugin {
    function getType() { return 'substition';}
    function getPType() { return 'block';}
    function getSort(){return 168;}
    function connectTo($mode) {
        $this->Lexer->addSpecialPattern('<select.+?</select>', $mode, 'plugin_select');
    }

    /**
     * Handle the match
     */
    function handle($match, $state, $pos) {
        global $ID;

        $match = substr($match, 7, -9);  // strip markup
        list($title, $match) = explode('>', $match, 2);
        $items = explode("\n", $match);

        return array($title,$items);
    }

    /**
     * Create output
     */
    function render($mode, &$renderer, $data) {
        global $conf;
        if($mode == 'xhtml'){
            list($title,$items) = $data;
            $s  = '<form class="dw_pl_select">'.NL;
            $s .= '<select onChange="location.href = this.options[this.selectedIndex].value">'.NL;
            $s .= '<option selected>'.hsc($title).'</option>'.NL;
            $s .=$this->get_select_xhtml($items);
            $s.='</form>';

            $renderer->doc.=$s;
            return true;
        }
        return false;
    }

    /**
     * Render XHTMLs
     */
    function get_enter_xhtml($match){
        $s  = '<form class="dw_pl_select">'.NL;
        $s .= '<select onChange="location.href = this.options[this.selectedIndex].value">'.NL;
        $s .= '<option selected>'.hsc($match).'</option>'.NL;
        return $s;
    }

    // Render options
    function get_select_xhtml($items){
        $link_items = array();
        foreach($items as $link){
            $url_name      = $this->getSelectUrlAndName($link);
            if($url_name==NULL) continue;

            $s .= $this->_get_option($url_name);
            $link_items[]  = $this->_get_nj_option($url_name);
        }
        $s .= '</select>'.NL;
        $s .= $this->_get_nonjava_list($link_items);
        return $s;
    }

    function get_exit_xhtml(){
        return '</form>'.NL;
    }

    function _get_option($args){
        return '<option value="'.hsc($args['url']).'">'.hsc($args['name']).'</option>'.NL;
    }

    function _get_nj_option($args){
        return '<a href="'.hsc($args['url']).'" title="'.hsc($args['name']).'">'.hsc($args['name']).'</a>';
    }

    function _get_nonjava_list($link_items){
        $s = '<noscript>|';
        foreach ($link_items as $value){
            $s .= $value.'|';
        }
        $s .= '</noscript>'.NL;
        return $s;
    }


    /**
     * Get name and url from a line
     */
    function getSelectUrlAndName($line){
        $link = preg_split('/\|/u',$line,2);
        $arg1 = trim($link[0]);
        if (empty($arg1)){return NULL;}
        if (empty($arg1)){continue;}
        $name = trim($link[1]);
        if (empty($name)){$name = $arg1;}

        // The following code is partly identical to
        // internallink function in /inc/parser/handler.php

        //decide which kind of link it is
        if ( preg_match('/^[a-zA-Z\.]+>{1}.*$/u',$arg1) ) {
        // Interwiki
            $interwiki = preg_split('/>/u',$arg1);
            $renderer = new Doku_Renderer();
            $url = $renderer->_resolveInterWiki($interwiki[0],$interwiki[1]);
        }elseif ( preg_match('/^\\\\\\\\[\w.:?\-;,]+?\\\\/u',$arg1) ) {
        // Windows Share
            $url = $arg1;
        }elseif ( preg_match('#^([a-z0-9\-\.+]+?)://#i',$arg1) ) {
        // External
            $url = $arg1;
        }elseif ( preg_match('!^#.+!',$arg1) ){
        // local link
            $url = substr($arg1,1);
        }else{
        // internal link
            $url = wl($arg1);
        }
        return array('url'=>$url,'name'=>$name);
    }
}