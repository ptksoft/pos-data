#!/usr/bin/php -q
<?php
require('config.inc.php');

echo 'begin RETURN-REASON generating...',NL;
// Query data and Write TO ESV Files
$query = "
    select
        id, name, eng_name
    from
        pos.pos_trans_return_type
    order by
        id
    ";
$table = pg_query($query);
if (false===$table) die('Error!!! query error'.NL.$query);
$rowcount = pg_num_rows($table);

$fileNameTmp = PATH_TO_SAVE_DATA_FILE.DS.'return-reason.'.substr(date('Ymd'),2).'.'.date('His').'.tmp';
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
    $row['name'] = base64_encode($row['name']);
    $row['eng_name'] = base64_encode($row['eng_name']);
    foreach ($row as $k=>$v) $arrDumy[] = $k.'='.$v;
    $esvLine = implode(chr(27),$arrDumy);
    if (false!==fputs($fp, $esvLine."\n")) {
        $countLine++;
        $hashCheck = md5($hashCheck.$esvLine);      // Build Line Hashing Check
    }
}
fclose($fp);
$fileNameReal = PATH_TO_SAVE_DATA_FILE.DS.'return-reason'.'.esv';
@unlink($fileNameReal);
if (! rename($fileNameTmp, $fileNameReal)) die('Error! cannot rename ['.$fileNameTmp.'] -> ['.$fileNameReal.']');
echo 'Generate RETURN-REASON complete',NL;
?>