<?php
/**
 * DokuWiki Plugin untis (Syntax Component)
 *
 * @license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author  Frank Schiebel <frank@linuxmuster.net>
 */

// must be run within Dokuwiki
if (!defined('DOKU_INC')) die();

if (!defined('DOKU_LF')) define('DOKU_LF', "\n");
if (!defined('DOKU_TAB')) define('DOKU_TAB', "\t");
if (!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');

require_once DOKU_PLUGIN.'syntax.php';

class syntax_plugin_untis extends DokuWiki_Syntax_Plugin {
    public function getType() {
        return 'substition';
    }

    public function getPType() {
        return 'block';
    }

    public function getSort() {
        return 222;
    }


    public function connectTo($mode) {
        $this->Lexer->addSpecialPattern('\{\{untis>.+?\}\}',$mode,'plugin_untis');
        $this->Lexer->addSpecialPattern('\{\{untismenu>.+?\}\}',$mode,'plugin_untis');
//        $this->Lexer->addEntryPattern('<FIXME>',$mode,'plugin_untis');
    }

//    public function postConnect() {
//        $this->Lexer->addExitPattern('</FIXME>','plugin_untis');
//    }

    public function handle($match, $state, $pos, &$handler){

        $match = substr($match, 2, -2);
        list($type, $match) = split('>', $match, 2);
        list($input, $options) = split('#', $match, 2);
        return array($type, $input, $options);

    }

    public function render($mode, &$renderer, $data) {
        if($mode != 'xhtml') return false;
        if (!$myhf =& plugin_load('helper', 'untis')) return false;

        // disable caching
        $renderer->info['cache'] = false;

        $type = $data[0];
        $optiondata= $data[1];

        if ($type == "untis" ) {
            $renderer->doc .= $myhf->untisReadHtml($optiondata);
        }
        if ($type == "untismenu") {
            $renderer->doc .= $myhf->untisCreateMenu($optiondata);
        }

        return true;
    }
}

// vim:ts=4:sw=4:et:
