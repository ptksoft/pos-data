#!/usr/bin/php -q
<?php
echo 'PRICE-CHANGE (version 120429.1101) run @ ',date('Y-m-d H:i:s'),"\n";
require('config.inc.php');

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
echo 'Current lastRecId=',$lastRecId,NL;


/// Select new record from scs.scs_price_change_detail
$q = "
    select id, product_barcode, new_price
    from scs.scs_price_change_detail
    where income_date = {$dateIncome} and id > {$lastRecId}
    order by id
    ";
$table = @pg_query($q);
if (false === $table)
    die('Cannot query, q='.$q.NL.NL);
if (@pg_num_rows($table)<1)
    die('No new rows, for dateIncome='.$dateIncome.' lastRecId='.$lastRecId.NL.NL);

/// Select all record for current dateIncome
$q = "
    select id, product_barcode, new_price
    from scs.scs_price_change_detail
    where income_date = {$dateIncome}
    order by id
    ";
$table = @pg_query($q);
if (false === $table)
    die('Cannot query, q='.$q.NL.NL);
if (@pg_num_rows($table)<1)
    die('No new rows, for dateIncome='.$dateIncome.' lastRecId='.$lastRecId.NL.NL);
$arrData = array();
while ($row = pg_fetch_assoc($table)) {
    if ($row['id']>$lastRecId) $lastRecId = (int)$row['id'];
    $arrData[] =
        'product_barcode='.$row['product_barcode'].chr(27).
        'new_price='.$row['new_price'];
}
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
