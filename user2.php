#!/usr/bin/php -q
<?php
require('config2.inc.php');

echo 'begin USER v2 generating...',NL;
// Query data and Write TO ESV Files
$query = "
select
    to_char(code, 'FM0009') as id,
    to_char(code, 'FM0009') as username,
    pwd_md5x2 as password,
    (case when is_supervisor='Y' then 2 else 1 end) as scs_user_type_id,
    first_name as firstname,
    last_name as lastname,
    to_char(code, 'FM0009') as cashier_no
from
    pos.cashiers
where
    is_delete = 'N'
order by
    code
";
$table = pg_query($query);
if (false===$table) die('Error!!! query error'.NL.$query);
$rowcount = pg_num_rows($table);
$fileNameTmp = PATH_TO_SAVE_DATA_FILE.DS.'user.'.substr(date('Ymd'),2).'.'.date('His').'.tmp';
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
    $row['firstname'] = base64_encode($row['firstname']);
    $row['lastname'] = base64_encode($row['lastname']);
    foreach ($row as $k=>$v) $arrDumy[] = $k.'='.$v;
    $esvLine = implode(chr(27),$arrDumy);
    if (false!==fputs($fp, $esvLine."\n")) {
        $countLine++;
        $hashCheck = md5($hashCheck.$esvLine);      // Build Line Hashing Check
    }
}
fclose($fp);
echo 'Finish ', number_format($countLine), ' line(s)',NL;

// Rename To Corrent File name
$fileNameReal = PATH_TO_SAVE_DATA_FILE.DS.'user.'.substr(date('Ymd'),2).'.'.date('His').'.'.$countLine.'.esv';
if (! rename($fileNameTmp, $fileNameReal)) die('Error! cannot rename ['.$fileNameTmp.'] -> ['.$fileNameReal.']');
echo 'Compress Files',NL;
shell_exec('gzip '.$fileNameReal);

// SAVE VERSION FILE
echo 'Begin Save FileVersion',NL;
$fileVersion = PATH_TO_SAVE_VERSION_FILE.DS.'user.version.txt';
$fp = fopen($fileVersion, 'w');
if (false===$fp) die('Error! Cannot open file['.$fileVersion.'] for write');
fputs($fp, basename($fileNameReal)."\n");
fputs($fp, $hashCheck."\n");
fclose($fp);
echo 'Finish Save FileVersion',NL;
?>
