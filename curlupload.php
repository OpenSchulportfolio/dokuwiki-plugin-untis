<?php
// dokuwiki init
if(!defined('DOKU_INC')) define('DOKU_INC', dirname(__FILE__).'/');
// load and initialize the core system
require_once(DOKU_INC.'inc/init.php');


// some sanity checks
//if ( ! isset($_REQUEST['secret']) ) {
//    exit;
//}

// determine upload filename and create incoming
// directory if it does not exist
if (! isset($conf['plugin']['untis']['upload_filename'])) {
    $upload_file = "untis:incoming:untis.zip";
} else {
    $upload_file = $conf['plugin']['untis']['upload_filename'];
}
$upload_file = cleanID($upload_file);
io_createNamespace($upload_file, $ns_type='media');
$upload_filepath = str_replace(":","/",$upload_file);
$upload_filepath = str_replace("//","/", $conf['savedir'] . "/media/" . $upload_filepath);

// curl upload mit
// curl -k -F filedata=@<DATEINAME> http://<SERVERNAME>/portfolio/curlupload.php
// debugging mit html header:
// curl -i -k -F filedata=@<DATEINAME> http://<SERVERNAME>/portfolio/curlupload.php
if ( $_REQUEST['secret'] == $conf['plugin']['untis']['curl_uploadsecret'] ) {
    move_uploaded_file ($_FILES['filedata'] ['tmp_name'],$upload_filepath);
} else {
    echo "Upload failed - wrong upload secret";
}
?>
