#!/usr/bin/php -q
<?php
// Update 2012-12-04 : Fixed barcode len from barcode_masters
echo 'PRICE-CHANGE (version 121204.2253) run @ ',date('Y-m-d H:i:s'),"\n";
require('config2.inc.php');

define('PORT_SERVICE', 7700);
define('PORT_CONSOLE', 7769);
define('PORT_HTTP', 7780);

//========================================================================
function GetDateIncome() {
    $fp = @fsockopen('tcp://127.0.0.1', PORT_SERVICE);
    if (false==$fp) return(false);
    fputs($fp, "/dateincome/\r\n");
    $return = fgets($fp);
    fclose($fp);
    $parts = explode('/', $return);
    if (count($parts)<4) return(false);
    if (
            $parts[1] != 'dateincome' ||
            $parts[2] != 'ok'
            )
        return(false);
    $iDateIncome = (int)$parts[3];
    return($iDateIncome);
}
//========================================================================



//========================= MAIN PROGRAM =================================
/// Get current dateIncome
$dateIncome = GetDateIncome();


if (false === $dateIncome)
    die('Cannot get DateIncome from POS_SERVER'.NL.NL);
echo 'Current dateIncome=',$dateIncome,NL;


/// Get Last ID from download/log/price-change-{$dateIncome}.log
$file_log = dirname(__FILE__).'/download/log/price-change-'.$dateIncome.'.log';
$file_version =     dirname(__FILE__).'/download/price-change.version.txt';
$lines = @file($file_log);
if (false===$lines) {
    // Maybe this is first time of this dateIncome
    $lastRecId = 0;     // Init lastRecId to Zero
    if (file_exists($file_version)) {
        if (! @unlink($file_version))
            die('Cannot delete old file_version ['.$file_version.']'.NL.NL);
    }
}
else {
    $lastRecId = (int)@rtrim($lines[0]);
}
if ($lastRecId < 0) $lastRecId = 0;
echo 'Current LastPriceChangeSerial=',$lastRecId,NL;

echo 'Query for New Changes', PHP_EOL;
$table = @pg_query("
    select count(*) as total
    from core.price_changes
    where
        income_date={$dateIncome} and
        price_change_serial > {$lastRecId} and
        is_apply = 'Y'
");
if ($table===false) die('Error in query'.PHP_EOL);
$record = pg_fetch_assoc($table);
if ($record===false) die('Error in fetch record'.PHP_EOL);
$count_new = (int)$record['total'];
if ($count_new < 1) die('Not found any new price change!'.PHP_EOL);
echo 'Found new change = ', $count_new, ' record(s)', PHP_EOL;

//die(0);
/// Select new record from scs.scs_price_change_detail


$q = "
select 
    price_changes.id ,
    barcode_masters.barcode as product_barcode,
    price_changes.price as new_price ,
    price_changes.price_change_serial,
    barcode_masters.len
from 
    core.price_changes
        left join core.barcode_masters
            on price_changes.style = barcode_masters.style
where 
    price_changes.income_date = {$dateIncome} and
    price_changes.is_apply = 'Y'
order by 
    price_change_serial
";
$table = @pg_query($q);
if (false === $table)
    die('Cannot query, q='.$q.NL.NL);
if (@pg_num_rows($table)<1)
    die('No new rows, for dateIncome='.$dateIncome.' lastRecId='.$lastRecId.NL.NL);
$arrData = array();
while ($row = pg_fetch_assoc($table)) {
    if ($row['price_change_serial']>$lastRecId) $lastRecId = (int)$row['price_change_serial'];
    $len = (int)$row['len'];
    $arrData[] =
        'product_barcode='.str_pad($row['product_barcode'], $len, '0', STR_PAD_LEFT).chr(27).
        'new_price='.$row['new_price'];
}
//echo "$lastRecId".NL;

//die(0);
if (count($arrData)<1)
    die('N data row to write, zero record'.NL.NL);
echo 'Got from database ',count($arrData),' record(s)',NL;

/// Write data esv to file_data
$His = date('His');
if (substr(date('Ymd'), 2)!=$dateIncome) $His = '000000';   // fix but PreGEN
$file_data =
    dirname(__FILE__).
    '/download/data/price-change.'.
    $dateIncome.'.'.
    $His.'.'.
    count($arrData).
    '.esv';
if (false===file_put_contents($file_data, implode("\n",$arrData)))
        die('Cannot write file_data ['.$file_data.']'.NL.NL);
echo 'Write file_data [',basename($file_data),'] Success',NL;

/// Write version information to file_version
$arrDumy[] = basename($file_data);
$arrDumy[] = md5($file_data);
if (false===file_put_contents($file_version, implode("\n",$arrDumy)))
        die('Cannot write file_version ['.$file_version.']'.NL.NL);
echo 'Write file_verson [',basename($file_version),'] Success',NL;

/// Write lastRecId to file_log
$arrLog[] = sprintf("%d", $lastRecId);
$arrLog[] = 'Generate @ '.date('Y-m-d H:i:s');
if (false===file_put_contents($file_log, implode("\n",$arrLog)))
        die('Cannot write file_log ['.$file_log.']'.NL.NL);
echo 'Write lastRecID=',$lastRecId,' to [',basename($file_log),']',NL,NL;

/// End PROGRAM ///
?>
