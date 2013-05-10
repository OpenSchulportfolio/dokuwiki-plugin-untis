<?php
/**
 * english language file for untis plugin
 *
 * @author Frank Schiebel <frank@linuxmuster.net>
 */

// keys need to match the langig setting name
// $lang['fixme'] = 'FIXME';
$lang['substplanfiles_lehrer'] = "Alle HTML-Dateien, die zur Anzeige der Lehrerversion herangezogen werden sollen. Eine Datei pro Zeile, in aufsteigender zeitlicher Reihenfolge.";
$lang['substplanfiles_aula'] = "Alle HTML-Dateien, die zur Anzeige der Schülerversion herangezogen werden sollen. Eine Datei pro Zeile, in aufsteigender zeitlicher Reihenfolge.";
$lang['invisible_columns_lehrer'] = "Spalten, die in der Lehreranzeige nicht dargestellt werden sollen. Durch Kommata getrennt, die Zählung beginnt bei 1. Grundlage ist die original Untis HTML Datei.";
$lang['invisible_columns_aula'] = "Spalten, die in der Schüleranzeige nicht dargestellt werden sollen. Durch Kommata getrennt, die Zählung beginnt bei 1. Grundlage ist die original Untis HTML Datei.";
$lang['roomplanfile'] = "Raumplandatei. Derzeit nicht verwendet.";
$lang['curl_uploadsecret'] = 'Passwort, das zum Hochladen der Vertretungspläne per CURL nötig ist:<br />curl -k -F secret="geheim" -F filedata=@plans.zip https://SERVER/portfolio/curlupload.php';
$lang['upload_filename'] = 'Der Dateiname mit vollständigen DokuWiki Pfad, als der der Plan hochgeladen werden soll.';
$lang['extract_target'] = 'DokuWiki Namespace, in den das hochgeladene Archiv ausgepackt werden soll.';
$lang['debug'] = 'Ausgaben zur Fehlersuche an/ausschalten';



//Setup VIM: ex: et ts=4 :
