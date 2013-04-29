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
      'name'   => 'untis2timesub',
      'desc'   => 'Converts untis csv to timesub',
      'params' => array(
        'infile' => 'string',
        'outfile' => 'string',
        'number (optional)' => 'integer'),
      'return' => array('pages' => 'array'),
    );
    // and more supported methods...
    return $result;
  }

/*
 * Reads Untis HTML Output
 */
function untisReadHtml ($displaytype ) {
    global $conf;

    if ($displaytype == "today") {
        $infile = $this->getConf('UriToday');
    }
    if ($displaytype == "tomorrow") {
        $infile = $this->getConf('UriTomorrow');
    }

    $infile = str_replace(":","/",$infile);
    $infile = str_replace("//","/", $conf['savedir'] . "/media/" . $infile);

    // lesen des html files
    $html = file_get_html("$infile");
    #$html =  str_get_html($this->_getHtmlCurl("https://www.dropbox.com/sh/1n0k2wecvhe21w3/KJBcnLVqZT/lehrer_morgen/subst_001.htm"));
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
    $html_output .= "<div class=\"untishead\">Stand: " . $last_updated . "</div>";

    // tagesnachrichten
    foreach ($html->find("table.info") as $table) {
        $html_output .= "<table class=\"substinfo\">";
        foreach ($table->find("tr") as $row ) {
                $html_output .= "<tr>";
                foreach ($row->find("td") as $tdata ) {
                    $html_output .= "<td>" . $tdata->plaintext . "</td>";
                }
            }
            $html_output .= "</tr>";
        }
        $html_output .= "</table>";

    // lesen der vertretungsliste
    $lehrer = "";
    $trclass = "header";
    foreach ($html->find("table.mon_list") as $table) {
        $html_output .= "<table class=\"sublist\">";
        foreach ($table->find("tr") as $row ) {
            $html_output .= "<tr class=\"$trclass\">";
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
                foreach ($row->find("th") as $tdata ) {
                    $html_output .= "<th>" . $tdata->plaintext . "</th>";
                }
                foreach ($row->find("td") as $tdata ) {
                    $data_text = $tdata->plaintext;
                    // FIXME to config
                    $todos="Betreuung Aufsicht Vertretung Verlegung Absenz Entfall";
                    if (strstr($todos, $data_text )) {
                        $cssclass=strtolower($data_text);
                        $tdclass=" class=\"$cssclass\"";
                    } else {
                        $tdclass="";
                    }
                    $html_output .= "<td$tdclass>" . $tdata->plaintext . "</td>";
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


function untisCreateMenu($optiondata) {
    global $conf;

    // split options into parts
    $configparts = explode("|" , $optiondata);
    $pagetoday = $configparts[0];
    $pagetomorrow = $configparts[1];
    $pageroomplan = $configparts[2];

    // get real dates out of html
    $infiles = array();
    $datelinks = array();
    $infiles[] = $this->getConf('UriToday');
    $infiles[] = $this->getConf('UriTomorrow');
    foreach($infiles as $infile) {
        $infile = str_replace(":","/",$infile);
        $infile = str_replace("//","/", $conf['savedir'] . "/media/" . $infile);
        // lesen des html files
        $html = file_get_html("$infile");
        $res = $html->find("div.mon_title");
        $daylink= $res[0]->plaintext;
        $daylink = explode(" ",$daylink);
        $daylinks[] = $daylink[1] . " " . $daylink[0];
    }


    $html =  "<div class=\"untismenu\"><a href=\"".wl($pagetoday)."\">".$daylinks[0]."</a>";
    $html .= "<a href=\"".wl($pagetomorrow)."\">".$daylinks[1]."</a></div>";
    return $html;


}


function _getHtmlCurl($uri) {

    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, "$uri");
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_USERAGENT, "Wget/1.13.4 (linux-gnu)");
    curl_setopt($curl, CURLOPT_AUTOREFERER, 1);
    curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 10);
    $str = curl_exec($curl);
    curl_close($curl);

    // Dropbox redirect?
    $html =  str_get_html($str);
    $res = $html->find("title");
    if ( $res[0]->plaintext == "Found" ) {
        $trueuri = $html->find("a",0)->plaintext;
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, "$trueuri");
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_USERAGENT, "Wget/1.13.4 (linux-gnu)");
        curl_setopt($curl, CURLOPT_AUTOREFERER, 1);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 10);
        $str = curl_exec($curl);
        curl_close($curl);
    }
    $html->clear();

    return $str;
}

function _unZipArchive($zip_file,$directory)
{
    //create a new ZipArchive class
    $zip_archve = new ZipArchive();

    //attempt to open the archive file
    $results = $zip_archive->open($zip_file);

    switch($results)
    {
        case TRUE:
            //format the directory properly
            $directory = str_replace("\\","/",$directory);
            //extract the files
            $zip_arvhive->extractTo($directory);
            //close the ZipArchive
            $zip_archive->close();
            //Return True
            return true;
            break;
        case FALSE:
            return false;
    }
}

}

// vim:ts=4:sw=4:et:
