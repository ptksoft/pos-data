#!/usr/bin/php -q
<?php
echo 'VERIFY Config Database/File PATH',"\n";

require('inc/config.inc.php');
echo 'DATABASE Verify ... OK',NL;

require('inc/parser.inc.php');
if (! file_exists(PATH_TO_TRANS_FILE)) {
    echo 'TransactionPath [',PATH_TO_TRANS_FILE,'] doesnot exists',NL;
    die();
}
echo 'PATH_TRANSACTION Verify ... OK',NL;

echo 'CHECK for transaction FILE',NL;
require('inc/general.inc.php');
$arrTransFile = ScanTransactionEsvFile(PATH_TO_TRANS_FILE);
if (empty($arrTransFile)) {
    echo 'NOT FOUND ANY TRANSACTION !!!',NL;
    die();
}

$posSTAT = array();
foreach ($arrTransFile as $oneFile) {
    list($dateIncome, $storeId, $posId, $runNum) = explode('-', $oneFile, 4);
    if (! array_key_exists($posId, $posSTAT)) {
        $posSTAT[$posId] = array(
            'total' => 1,
            'max' => (int)$runNum,
            'status'=>'OK'
        );
    }
    else {
        if ((int)$runNum > $posSTAT[$posId]['max']) $posSTAT[$posId]['max'] = (int)$runNum;
        $posSTAT[$posId]['total']++;
    }
}
foreach ($posSTAT as $posId => $rec) {
    if ($rec['max'] != $rec['total']) $posSTAT[$posId]['status'] = 'WARNING';
}

ksort($posSTAT);
echo NL;
foreach ($posSTAT as $posId => $rec) {
    echo 'POS#',$posId,' TotalFile:',$rec['total'],' MaxRunning:',$rec['max'],' Status:',$rec['status'],NL;
}

echo NL,'END RUNNING',NL;
?>
