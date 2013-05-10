<?php
/**
 * DokuWiki Plugin untis (Helper Component)
 *
 * @license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author  Frank Schiebel <frank@linuxmuster.net>
 */

// must be run within Dokuwiki
if (!defined('DOKU_INC')) die();

if (!defined('DOKU_LF')) define('DOKU_LF', "\n");
if (!defined('DOKU_TAB')) define('DOKU_TAB', "\t");
if (!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');

// simple html dom parser für die untis html seiten
require(DOKU_PLUGIN."/untis/simple_html_dom.php");

class  helper_plugin_untis extends DokuWiki_Plugin {

function getMethods(){
    $result = array();
    $result[] = array(
      'name'   => 'untis',
      'desc'   => 'Untis',
      'params' => array(
      'infile' => 'string',
      'outfile' => 'string',
      'number (optional)' => 'integer'),
      'return' => array('pages' => 'array'),
    );
    // and more supported methods...
    return $result;
  }

/**
  * Wrapper funktion for displaying the plan page
  *
  * This function checks the given configuration, builds an array of
  * verified plan files and creates the html output which is returned to
  * the plugins sysntax component.
  *
  * @author Frank Schiebel <frank@linuxmuster.net>
  * @param in $untisday daynumber to decide wich plan to display 0,1,2,3...
  * @return string
  */
function displayUntis($untisday,$displaytarget) {
    global $conf;

    // Aktualisiere die paene aus dem zip
    $this->_unZipArchive();

    if ( $displaytarget == "lehrer" ) {
        $planfileIDs = explode("\n",$this->getConf('substplanfiles_lehrer'));
    } else {
        $planfileIDs = explode("\n",$this->getConf('substplanfiles_aula'));
    }

    $planfilesTested = array();
    foreach ($planfileIDs as $planfile) {
    $planfile = mediaFN($planfile);
        if(file_exists($planfile) && !is_dir($planfile)) {
            $planfilesTested[] = $planfile;
        }
    }

    $html = $this->_untisCreateMenu($planfilesTested);

    if(!isset($planfilesTested[$untisday])) {
        msg("Für den angegebenen Tag ist kein Plan hinterlegt.");
        return;
    }

    if(!file_exists($planfilesTested[$untisday])) {
        msg("Datei existiert nicht:" . $planfilesTested[$untisday] .". Passen Sie die Konfiguration an");
        return;
    }
    // statische kopfinfos lesen
    $html .= $this->_untisReadHtmlHeader($planfilesTested[$untisday],$displaytarget);
    // vertretungstabelle lesen

    if ( $displaytarget == "lehrer" ) {
        $html .= $this->_untisReadHtmlSubstsLehrer($planfilesTested[$untisday]);
    } else {
        $html .= $this->_untisReadHtmlSubstsAula($planfilesTested[$untisday]);
    }


    return $html;

}

/**
  * Reads untis html files: Headersection
  *
  * This function reads the output of untis info-modul html files
  * and creates a userfriendly table for displaying in dokuwiki
  *
  * @author Frank Schiebel <frank@linuxmuster.net>
  * @param string $infile filename to read html from
  * @return string
  */
function _untisReadHtmlHeader ($infile,$displaytarget) {
    global $conf;

    // lesen des html files
    $html = file_get_html("$infile");
    $html_output = "";

    // überschrift
    $res = $html->find("div.mon_title");
    $tagestitel = $res[0]->plaintext;
    $tagestitel = explode(" ",$tagestitel);
    $tagestitel = $tagestitel[1] . " " . $tagestitel[0];
    $html_output .= "<h1>Vertretungen für $tagestitel</h1>";

    // aktualisierungszeit
    $res = $html->find("table.mon_head tr td[align=right] p");
    $last_updated = $res[0]->plaintext;
    $last_updated = explode("and:", $last_updated);
    $last_updated = $last_updated[1];
    $html_output .= "<div class=\"untishead versioninfo\">Stand: " . $last_updated . "/" . $this->getLang("$displaytarget") . "</div>";

    // tagesnachrichten
    foreach ($html->find("table.info") as $table) {
        $html_output .= "<table class=\"substinfo\">";
        foreach ($table->find("tr") as $row ) {
                $html_output .= "<tr>";
                foreach ($row->find("td") as $tdata ) {
                    if ($tdata->colspan) {
                        $colspan = " colspan=\"" . $tdata->colspan."\" class=\"headerinfo\" ";
                    } else {
                        $colspan = "";
                    }
                    $html_output .= "<td" . $colspan . ">" . $tdata->plaintext . "</td>";
                }
            }
            $html_output .= "</tr>";
        }
        $html_output .= "</table>";

    //speicher freigeben
    $html->clear();

    return $html_output;
}


/**
  * Reads untis html files
  *
  * This function reads the output of untis info-modul html files
  * and creates a userfriendly table for displaying in dokuwiki
  *
  * @author Frank Schiebel <frank@linuxmuster.net>
  * @param string $infile filename to read html from
  * @return string
  */
function _untisReadHtmlSubstsAula ($infile) {
    global $conf;

    // welche spalten sollen ausgelasen werden?
    $nodisplaycolumns = explode(",",$this->getConf('invisible_columns_aula'));

    // lesen des html files
    $html = file_get_html("$infile");
    $html_output = "";
    // lesen der vertretungsliste
    $lehrer = "";
    $trclass = "header";
    foreach ($html->find("table.mon_list") as $table) {
        $html_output .= "<table class=\"sublist\">";
        foreach ($table->find("tr") as $row ) {
            $html_output .= "<tr class=\"$trclass\">";
            if ( $res = $row->find('td[colspan=9]')) {
                $lehrer = $res[0]->plaintext;
                $lehrer = explode(" ", $lehrer);
                $lehrer = $lehrer[0];
                $trclass = $trclass == "eins" ? "zwei" : "eins";
            } else {
                if ($row->find("td") ){
                    $html_output .= "<td>$lehrer</td>";
                }
                if ($row->find("th") ){
                    $html_output .= "<th>$lehrer</th>";
                }

                $column = 1;
                foreach ($row->find("th") as $tdata ) {
                    if (!in_array($column,$nodisplaycolumns)) {
                        $html_output .= "<th>" . $tdata->plaintext . "</th>";
                    }
                    $column++;
                }

                $column = 1;
                foreach ($row->find("td") as $tdata ) {
                    if (!in_array($column,$nodisplaycolumns)) {
                        $data_text = $tdata->plaintext;
                        $html_output .= "<td>". $this->_makeReplacements($data_text,$column) . "</td>";
                    }
                    $column++;
                }
            }
            $html_output .= "</tr>";
        }
        $html_output .= "</table>";
    }

    //speicher freigeben
    $html->clear();

    return $html_output;
}

/**
  * Reads untis html files
  *
  * This function reads the output of untis info-modul html files
  * and creates a userfriendly table for displaying in dokuwiki
  *
  * @author Frank Schiebel <frank@linuxmuster.net>
  * @param string $infile filename to read html from
  * @return string
  */
function _untisReadHtmlSubstsLehrer ($infile) {
    global $conf;

    // welche spalten sollen ausgelassen werden?
    $nodisplaycolumns = explode(",",$this->getConf('invisible_columns_lehrer'));
    // lesen des html files
    $html = file_get_html("$infile");
    $html_output = "";
    // lesen der vertretungsliste
    $lehrer = "";
    $trclass = "header";
    foreach ($html->find("table.mon_list") as $table) {
        $html_output .= "<table class=\"sublist\">";
        foreach ($table->find("tr") as $row ) {
            $html_output .= "<tr class=\"$trclass\">";
            // zwischenzeilen auslassen und stattdessen
            // die lehrer in die 0. spalte voranstellen
            if ( $res = $row->find('td[colspan=11]')) {
                $lehrer = $res[0]->plaintext;
                $lehrer = explode(" ", $lehrer);
                $lehrer = $lehrer[0];
                $trclass = $trclass == "eins" ? "zwei" : "eins";
            } else {
                if ($row->find("td") ){
                    $html_output .= "<td>$lehrer</td>";
                }
                if ($row->find("th") ){
                    $html_output .= "<th>$lehrer</th>";
                }

                $column = 1;
                foreach ($row->find("th") as $tdata ) {
                    if (!in_array($column,$nodisplaycolumns)) {
                        $html_output .= "<th>" . $tdata->plaintext . "</th>";
                    }
                    $column++;
                }
                $column = 1;
                foreach ($row->find("td") as $tdata ) {
                    if (!in_array($column,$nodisplaycolumns)) {
                        $data_text = $tdata->plaintext;
                        $html_output .= $this->_makeReplacements($data_text,$column,"td");
                    }
                    $column++;
                }
            }
            $html_output .= "</tr>";
        }
        $html_output .= "</table>";
    }

    //speicher freigeben
    $html->clear();

    return $html_output;
}

/**
  * Create navigation menu to all plans
  *
  * This function reads every given planfile to determine the
  * real date of the plan and creates a menu with the dates to
  * click on, linked to the corresponding plan files
  *
  * @author Frank Schiebel <frank@linuxmuster.net>
  * @param array $infiles array with verified filenames to read
  * @return string
  */
function _untisCreateMenu($infiles) {
    global $conf;
    global $ID;

    // get real dates out of html
    $datelinks = array();
    $returnhtml = "<div class=\"untismenu\">";
    foreach($infiles as $key=>$infile) {
        // lesen des html files
        $html = file_get_html("$infile");
        $res = $html->find("div.mon_title");
        $daylink= $res[0]->plaintext;
        $daylink = explode(" ",$daylink);
        $returnhtml .=  "<a href=\"".wl($ID,"untisday=$key")."\">".$daylink[0]."</a>";
    }
    $returnhtml .= "</div>";
    return $returnhtml;

}

/**
  * Unzip uploaded archive file
  *
  * The plans have to be uploaded to the server as a zip file. This function
  * extracts the plan file according to the plugin configuration, so that 
  * later on the plans can be displayed.
  *
  * @author Frank Schiebel <frank@linuxmuster.net>
  * @return boolean
  */
function _unZipArchive() {

    global $conf;

    $upload_file = cleanID($this->getConf('upload_filename'));
    $upload_filepath = str_replace(":","/",$upload_file);
    $upload_filepath = str_replace("//","/", $conf['savedir'] . "/media/" . $upload_filepath);
    $zip_file = $upload_filepath;

    $directory = cleanID($this->getConf('extract_target'));;
    $directory = str_replace(":","/",$directory);
    $directory = str_replace("//","/", $conf['savedir'] . "/media/" . $directory);
    if ($this->getConf('debug')) {
        msg("Trying to extract zip-file: $zip_file");
        msg("Destination directory: $directory");
    }

    $dir = io_mktmpdir();
    if($dir) {
        $this->tmpdir = $dir;
    } else {
        msg('Failed to create tmp dir, check permissions of cache/ directory', -1);
        return false;
    }

    // failed to create tmp dir stop here
    if(!$this->tmpdir) return false;

    // include ZipLib
    require_once(DOKU_INC."inc/ZipLib.class.php");
    //create a new ZipLib class
    $zip = new ZipLib;

    //attempt to open the archive file
    $result = $zip->Extract($zip_file,$this->tmpdir);

    if($result) {
        $files = $zip->get_List($zip_file);
        $this->_postProcessFiles($directory, $files);
        return true;
    } else {
        return false;
    }
}

/**
 * Checks the mime type and fixes the permission and filenames of the
 * extracted files. Taken from Michel Kliers archiveupload plugin.
 *
 * @author Michael Klier <chi@chimeric.de>
 * @author Frank Schiebel <frank@linuxmuster.net>
 */
function _postProcessFiles($dir, $files) {
    global $conf;
    global $lang;

    require_once(DOKU_INC.'inc/media.php');

    $dirs     = array();
    $tmp_dirs = array();

    foreach($files as $file) {
        $fn_old = $file['filename'];            // original filename
        $fn_new = str_replace('/',':',$fn_old); // target filename
        $fn_new = str_replace(':', '/', cleanID($fn_new));

        if(substr($fn_old, -1) == '/') { 
            // given file is a directory
            io_mkdir_p($dir.'/'.$fn_new);
            chmod($dir.'/'.$fn_new, $conf['dmode']);
            array_push($dirs, $dir.'/'.$fn_new);
            array_push($tmp_dirs, $this->tmpdir.'/'.$fn_old);
        } else {
            // move the file
            // FIXME check for success ??
            rename($this->tmpdir.'/'.$fn_old, $dir.'/'.$fn_new);
            chmod($dir.'/'.$fn_new, $conf['fmode']);
            if ($this->getConf('debug')) {
                msg("Extracted: $dir/$fn_new", 1);
            }

        }
    }
}

/**
* Replaces strings in the substtable according to config settings
*
* @author Frank Schiebel <frank@linuxmuster.net>
*
* @param string $data string
* @return string html tagged replaced string
*/
function _makeReplacements($data,$column) {

    $replacements = confToHash($this->_getsavedir().'/untis-replacements.conf');
    $lastclass = "";
    foreach($replacements as $replacement) {
        list($needle, $rtext,$cssclass, $targetcolumn) = explode("|",$replacement);
        $needle = trim($needle);
        $rtext = trim($rtext);
        $cssclass = trim($cssclass);
        $targetcolumn = trim($targetcolumn);
        if (!$targetcolumn && strstr($data,$needle)) {
            $lastclass = $cssclass;
            $data = str_replace($needle,$rtext,$data);
        } elseif ( $targetcolumn == $column && strstr($data,$needle)) {
            $lastclass = $cssclass;
            $data = str_replace($needle,$rtext,$data);
        }
        unset($needle,$rtext,$cssclass,$targetcolumn);
    }
    if ($lastclass != "" ) {
        $html = $this->_htmlTagText($data,"span",$lastclass);
    } else {
        $html = $data;
    }
    return $html;

}

/**
 *
 **/
function _htmlTagText($text,$htmltag,$cssclasses) {
    if ( $cssclasses == "" ) {
    #    print "$text NOCLASS <br>";
        $html = "<".$htmltag.">" . $text ."</$htmltag>";
    } else {
     #   print "$text CLASS $cssclasses<br>";
        $html = "<".$htmltag . " class=\"".$cssclasses."\">" . $text ."</$htmltag>";
    }
    return $html;

}

/**
* get savedir
*/
function _getsavedir() {
    global $conf;
    if ( $this->getConf('saveconftocachedir') ) {
        return rtrim($conf['savedir'],"/") . "/cache";
    } else {
        return dirname(__FILE__);
    }
}



}

// vim:ts=4:sw=4:et:
