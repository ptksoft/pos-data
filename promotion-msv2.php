#!/usr/bin/php -q
<?php
require('config2.inc.php');

echo 'begin PROMOTION-MSV generating...',NL;

// Query data and Write TO ESV Files
$NowDate = date('Y-m-d');
$idate_start_year =  substr($NowDate,2,2);
$idate_start_month =  substr($NowDate,5,2);
$idate_start_day =  substr($NowDate,8,2);
$current_date = $idate_start_year.$idate_start_month.$idate_start_day;
 
$query = "
    select
        event_num as event_no , condition_list, condition_qty, result_list,
        result_qty, discount_amount, discount_type as promotion_type
    from
        pos.msv_promotions
    where
         $current_date between idate_start and idate_stop
    ";
$table = pg_query($query);
if (false===$table) die('Error!!! query error'.NL.$query);
$rowcount = pg_num_rows($table);
$fileNameTmp = PATH_TO_SAVE_DATA_FILE.DS.'promotion-msv.'.substr(date('Ymd'),2).'.'.date('His').'.tmp';
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
    foreach ($row as $k=>$v) {
        if ($k=='event_no') {
            $arrDumy[] = $k.'='. str_pad($v, 6, '0', STR_PAD_LEFT);
        }
        else {
            $arrDumy[] = $k.'='.$v;
        }
    }
    $esvLine = implode(chr(27),$arrDumy);
    if (false!==fputs($fp, $esvLine."\n")) {
        $countLine++;
        $hashCheck = md5($hashCheck.$esvLine);      // Build Line Hashing Check
    }
}
fclose($fp);

// Rename To Corrent File name
$fileNameReal = PATH_TO_SAVE_DATA_FILE.DS.'promotion-msv.'.substr(date('Ymd'),2).'.'.date('His').'.'.$countLine.'.esv';
if (! rename($fileNameTmp, $fileNameReal)) die('Error! cannot rename ['.$fileNameTmp.'] -> ['.$fileNameReal.']');
echo 'Compress Files',NL;
shell_exec('gzip '.$fileNameReal);

// SAVE VERSION FILE
echo 'Begin Save FileVersion',NL;
$fileVersion = PATH_TO_SAVE_VERSION_FILE.DS.'promotion-msv.version.txt';
$fp = fopen($fileVersion, 'w');
if (false===$fp) die('Error! Cannot open file['.$fileVersion.'] for write');
fputs($fp, basename($fileNameReal)."\n");
fputs($fp, $hashCheck."\n");
fclose($fp);
echo $fileNameReal.NL;
echo 'Finish Save FileVersion',NL;
?>
