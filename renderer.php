<?php
/**
 * DokuWiki Plugin untis (Renderer Component)
 *
 * @license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author  Frank Schiebel <frank@linuxmuster.net>
 */

// must be run within Dokuwiki
if (!defined('DOKU_INC')) die();

if (!defined('DOKU_LF')) define('DOKU_LF', "\n");
if (!defined('DOKU_TAB')) define('DOKU_TAB', "\t");
require_once DOKU_INC.'inc/parser/xhtml.php';

class renderer_plugin_untis extends Doku_Renderer_xhtml {

    /**
     * Make available as XHTML replacement renderer
     */
    public function canRender($format){
        if($format == 'xhtml') return true;
        return false;
    }

    // FIXME override any methods of Doku_Renderer_xhtml here
}

