<?php
// dokuwiki init
if(!defined('DOKU_INC')) define('DOKU_INC', dirname(__FILE__).'/');
// load and initialize the core system
require_once(DOKU_INC.'inc/init.php');

if ( ! isset($_REQUEST['secret']) ) {
    exit;
}
// curl upload mit
// curl -k -F filedata=@<DATEINAME> http://<SERVERNAME>/portfolio/curlupload.php
// debugging mit html header:
// curl -i -k -F filedata=@<DATEINAME> http://<SERVERNAME>/portfolio/curlupload.php
if ( $_REQUEST['secret'] == $conf['plugin']['untis']['curl_uploadsecret'] ) {
    move_uploaded_file ($_FILES['filedata'] ['tmp_name'],
            $conf['savedir']."/media/curlupload/{$_FILES['filedata'] ['name']}");
} else {
    echo "Upload failed - wrong upload secret";
}
?>
