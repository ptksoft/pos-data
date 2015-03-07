<?php
//define('DS', '/');      // directory separater char
//define('NL', "\n");     // new line
//define('CRLF', "\r\n");     // tcp-tream new line
//define('SL', "/");          // slash

define('HOST2', '127.0.0.1');
define('PORT2', '5403');
define('USER2', 'scs2');
define('PASS2', 'password');
define('DB2', 'scs2');

//define('PATH_TO_SAVE_VERSION_FILE', '/home/pos-data/download');
//define('PATH_TO_SAVE_DATA_FILE', '/home/pos-data/download/data');


/* Try To Connect To Database */
$conn_string = "host=".HOST2." port=".PORT2." dbname=".DB2." user=".USER2." password=".PASS2;
$conn = pg_connect ($conn_string);
if (false === $conn) die('ERROR!!! Cannot connec to Database '.NL.$conn_string.NL);
echo 'config_migrate.inc.php : Database Connection Ready',NL;
?>
