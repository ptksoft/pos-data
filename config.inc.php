<?php
define('DS', '/');      // directory separater char
define('NL', "\n");     // new line
define('CRLF', "\r\n");     // tcp-tream new line
define('SL', "/");          // slash

define('HOST', '127.0.0.1');
define('PORT', '5432');
define('USER', 'admin');
define('PASS', 'dbadmin');
define('DB', 'scs');

define('PATH_TO_SAVE_VERSION_FILE', '/home/pos-data/download');
define('PATH_TO_SAVE_DATA_FILE', '/home/pos-data/download/data');


/* Try To Connect To Database */
$conn_string = "host=".HOST." port=".PORT." dbname=".DB." user=".USER." password=".PASS;
$conn = pg_connect ($conn_string);
if (false === $conn) die('ERROR!!! Cannot connec to Database '.NL.$conn_string.NL);
echo 'config.inc.php: Database Connection Ready',NL;
?>
