#!/usr/bin/php -q
<?php
define('NL', "\n");
define('DS', '/');

define('HOST', '127.0.0.1');
define('PORT', '5432');
define('USER', 'admin');
define('PASS', 'dbadmin');
define('DB', 'cp');

define('PATH_TO_SAVE_VERSION_FILE', '/home/pos-data/download');
define('PATH_TO_SAVE_DATA_FILE', '/home/pos-data/download/data');


/* Try To Connect To Database */
$conn_string = "host=".HOST." port=".PORT." dbname=".DB." user=".USER." password=".PASS;
$conn = pg_connect ($conn_string);
if (false === $conn) die('ERROR!!! Cannot connec to Database '.NL.$conn_string.NL);
echo 'config.inc.php: Database Connection Ready',NL;

$q = "
    select *
    from crm.temp_member_price
    ";
$result = pg_query($q);
if (false===$result) die('Cannot Query'.NL);

$fileNameTmp = PATH_TO_SAVE_DATA_FILE.DS.'member-price.'.substr(date('Ymd'),2).'.'.date('His').'.tmp';
$fp = fopen($fileNameTmp, 'w');
if ($fp===false) die('Cannot open file['.$fileNameTmp.'] for writer'.NL);
$countLine = 0;
$hashCheck = date('Ymd');
// Fetch eash line as assocate array and Pack as ESV format
while ($row = pg_fetch_assoc($result)) {
    if ($countLine % 1000 == 0) {
        echo 'Finish ', number_format($countLine), ' line(s)',NL;
    }

    $arrDumy = array();
    $arrDumy[] = 'barcode='.$row['barcode'];
    $arrDumy[] = 'price=0>'.$row['price'];

    $esvLine = implode(chr(27),$arrDumy);
    if (false!==fputs($fp, $esvLine."\n")) {
        $countLine++;
        $hashCheck = md5($hashCheck.$esvLine);      // Build Line Hashing Check
    }
}
fclose($fp);
echo 'Finish ', number_format($countLine), ' line(s)',NL;

// Rename To Corrent File name
$fileNameReal = PATH_TO_SAVE_DATA_FILE.DS.'member-price.'.substr(date('Ymd'),2).'.'.date('His').'.'.$countLine.'.esv';
if (! rename($fileNameTmp, $fileNameReal)) die('Error! cannot rename ['.$fileNameTmp.'] -> ['.$fileNameReal.']');

// Compressing file
echo 'Compress Files',NL;
shell_exec('gzip '.$fileNameReal);

?>
