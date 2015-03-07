#!/usr/bin/php -q
<?php
require('config.inc.php');

echo 'begin PROMOTION-PRICE generating...',NL;
// Query data and Write TO ESV Files
$query = "
    select
        vms_product_id as style, new_price
    from
        scs.scs_hq_price_change
    where
        is_active = true
    ";
$table = pg_query($query);
$rowcount = pg_num_rows($table);
if (false===$table) die('Error!!! query error'.NL.$query);
$fileNameTmp = PATH_TO_SAVE_DATA_FILE.DS.'promotion-price.'.substr(date('Ymd'),2).'.'.date('His').'.tmp';
$fp = fopen($fileNameTmp, 'w');
if ($fp===false) die('Cannot open file['.$fileNameTmp.'] for writer'.NL);
$countLine = 0;
$hashCheck = date('Ymd');
// Fetch eash line as assocate array and Pack as ESV format
while ($row = pg_fetch_assoc($table)) {
    if ($countLine % 1000 == 0) {
        echo 'Finish ', number_format($countLine), ' line(s)',NL;
    }

    $arrDumy = array();
    foreach ($row as $k=>$v) $arrDumy[] = $k.'='.$v;
    $esvLine = implode(chr(27),$arrDumy);
    if (false!==fputs($fp, $esvLine."\n")) {
        $countLine++;
        $hashCheck = md5($hashCheck.$esvLine);      // Build Line Hashing Check
    }
}
fclose($fp);

// Rename To Corrent File name
$fileNameReal = PATH_TO_SAVE_DATA_FILE.DS.'promotion-price.'.substr(date('Ymd'),2).'.'.date('His').'.'.$countLine.'.esv';
if (! rename($fileNameTmp, $fileNameReal)) die('Error! cannot rename ['.$fileNameTmp.'] -> ['.$fileNameReal.']');
echo 'Compress Files',NL;
shell_exec('gzip '.$fileNameReal);

// SAVE VERSION FILE
echo 'Begin Save FileVersion',NL;
$fileVersion = PATH_TO_SAVE_VERSION_FILE.DS.'promotion-price.version.txt';
$fp = fopen($fileVersion, 'w');
if (false===$fp) die('Error! Cannot open file['.$fileVersion.'] for write');
fputs($fp, basename($fileNameReal)."\n");
fputs($fp, $hashCheck."\n");
fclose($fp);
echo 'Finish Save FileVersion',NL;
?>
