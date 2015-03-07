#!/usr/bin/php -q
<?php
/*
 *   2013-02-01  Add Filter for Barcode that SKU=0 or Barcode=0
 */
define('VERSION', '130201.1340');
require('config2.inc.php'); // This config is connect to SCS2
echo 'Script version ['.VERSION.']'.PHP_EOL;

echo 'Load THAI-(0352)- Product STYLE ... [';
$cur_dir = dirname($_SERVER['PHP_SELF']);
$file_style_name = "{$cur_dir}/thai-product-style.txt";
echo $file_style_name;
echo ']',NL;

$arrThaiStyle = file($file_style_name, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
if (count($arrThaiStyle)<1) die('Not found file arrThaiStyle'.NL);
echo 'Got THAI Style = ',count($arrThaiStyle),' Items',NL;
for ($i=0; $i<count($arrThaiStyle); $i++) { $arrThaiStyle[$i] = rtrim($arrThaiStyle[$i]); }
$countThaiStyle = array();
foreach ($arrThaiStyle as $oneStyle) $countThaiStyle[$oneStyle] = 0;

echo 'begin PRODUCT generating...',NL;
// Query data and Write TO ESV Files
//$query = "
//    select
//        vms_product_id as style, sku, barcode, name, price,
//        is_sales_by_weight, vat_rate, is_request_price, is_consignment
//    from
//        pos.pos_product
//    where
//        barcode is not null
//    ";
$query="
Select  
bm.style,
sku,
bm.barcode,
long_name as name,
current_price as price,
(case when is_sales_by_weight ='N' then 'f' else 't' end) as is_sales_by_weight ,
vat_rate,
(case when is_request_price ='N' then 'f' else 't' end) as is_request_price,
(case when is_consignment = 'N' then 'f' else 't' end) as is_consignment,
bm.len
from 
core.barcode_masters bm LEFT JOIN core.product_masters pm on bm.style = pm.style
where bm.barcode > 0 and bm.sku > 0 and bm.style > 0
";
$table = pg_query($query);
if (false===$table) die('Error!!! query error'.NL.$query);
$rowcount = pg_num_rows($table);
$fileNameTmp = PATH_TO_SAVE_DATA_FILE.DS.'product.'.substr(date('Ymd'),2).'.'.date('His').'.tmp';
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
    $len = (int)$row['len'];
    unset($row['len']);

    $row['style'] = str_pad($row['style'], 8, '0', STR_PAD_LEFT);
    $row['sku'] = str_pad($row['sku'], 9, '0', STR_PAD_LEFT);
    $row['barcode'] = str_pad($row['barcode'], $len, '0', STR_PAD_LEFT);    
    
    if (array_key_exists($row['style'], $countThaiStyle)) {
        $countThaiStyle[$row['style']]++;
        $row['name'] = base64_encode('(*)'.$row['name']);
    }
    else {
        $row['name'] = base64_encode($row['name']);
    }
    foreach ($row as $k=>$v) $arrDumy[] = $k.'='.$v;
    $esvLine = implode(chr(27),$arrDumy);
    if (false!==fputs($fp, $esvLine."\n")) {
        $countLine++;
        $hashCheck = md5($hashCheck.$esvLine);      // Build Line Hashing Check
    }
}
fclose($fp);

// Rename To Corrent File name
$fileNameReal = PATH_TO_SAVE_DATA_FILE.DS.'product.'.substr(date('Ymd'),2).'.'.date('His').'.'.$countLine.'.esv';
if (! rename($fileNameTmp, $fileNameReal)) die('Error! cannot rename ['.$fileNameTmp.'] -> ['.$fileNameReal.']');
echo 'Compress Files',NL;
shell_exec('gzip '.$fileNameReal);

// SAVE VERSION FILE
echo 'Begin Save FileVersion',NL;
$fileVersion = PATH_TO_SAVE_VERSION_FILE.DS.'product.version.txt';
$fp = fopen($fileVersion, 'w');
if (false===$fp) die('Error! Cannot open file['.$fileVersion.'] for write');
fputs($fp, basename($fileNameReal)."\n");
fputs($fp, $hashCheck."\n");
fclose($fp);
echo 'Finish Save FileVersion',NL;

// Show Style Match Stat
echo 'MATCH Thai Style STAT',NL;
foreach ($countThaiStyle as $style=>$total) echo $style, ' => ',$total, ' item(s)',NL;
?>
