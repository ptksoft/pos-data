#!/usr/bin/php -q
<?php
define('DEBUG1', false);
@error_reporting(E_ALL);

echo "TMP Clean Inventory\n";
require('config.inc.php');

$arrSKU = generate_sku_target();
$i=0;
$fname="./tmp/query-correction.sql";
$fp = fopen($fname, "w");
$countTotalSKU = count($arrSKU);
$countSKU = 0;
foreach ($arrSKU as $sku=>$oneSKU) {
    $countSKU++;
    echo "Correction {$countSKU}/{$countTotalSKU} SKU[{$sku}] Total update Query=";
    $queryUpdate = array();
    process_one_sku($sku, $oneSKU, $queryUpdate, array('1104','1105', '1106'));
    //print_r($queryUpdate);
    foreach ($queryUpdate as $oneQ) fwrite ($fp, $oneQ . "\n");
    echo count($queryUpdate), ' lines',NL;

    $i++;    
    //if ($i>100) break;
}
fclose($fp);

//==============================================================================
function process_one_sku($sku, $data, &$queryUpdate,$arrTable=array()) {
   if (DEBUG1) echo
    sprintf("%-6s|%8s|%8s|%8s|%8s|%8s|%8s|%8s|\n",
           "DateIC",
           "ST_QTY",
           "B_CN_QTY",
           "ADJ_QTY",
           "CNT_QTY",
           "EOD_QTY",
           "MOVING",
           "*ST_QTY"
           );

   foreach ($arrTable as $oneTable) {
        $q = "
select 
    {$oneTable} as month_num, sku, int_sales_date, start_qty, before_count_adjust_qty, adjust_qty, count_qty, eod_qty,
    (
        (po_qty + bol_qty + store_in_qty + spit_pack_in_qty) -
        (sales_qty + store_out_qty + rtv_hq_qty + rtv_dc_qty + rtv_store_qty + destroy_qty + use_qty + spit_pack_out_qty)
    ) as moving_qty
from
    inventory.inv_rpt_onhand_log_{$oneTable}
where
    sku='{$sku}'
order
    by int_sales_date
";
        $table = pg_query($q);
        $datas = pg_fetch_all($table);

        $old_eod_qty = false;
        $is_begin_correction = false;    ## Begin correction process of not

        foreach ($datas as $row) {
            ## Check if begin_correction
            if ($old_eod_qty !== false) {
                if ($old_eod_qty != $row['start_qty']) $is_begin_correction = true;
            }

            ## Correction Start and End QTY
            $old_start_qty = $row['start_qty'];
            if ($is_begin_correction) {
                $row['start_qty'] = $old_eod_qty;
                $row['eod_qty'] = $row['start_qty'] + $row['moving_qty'];

                // have any-one adjust stock by re-count
                if ($row['count_qty'] != 0) {
                    $row['before_count_adjust_qty'] = $row['eod_qty'];
                    $row['adjust_qty'] = $row['count_qty'] - $row['before_count_adjust_qty'];
                    $row['eod_qty'] = $row['count_qty'];
                }
            }

            if (DEBUG1) echo
            sprintf("%-6d|%8.2f|%8.2f|%8.2f|%8.2f|%8.2f|",
                $row['int_sales_date'],
                $row['start_qty'],
                $row['before_count_adjust_qty'],
                $row['adjust_qty'],
                $row['count_qty'],
                $row['eod_qty']
                );

            ## Moving Adjust
            if (DEBUG1) {
                if ($row['moving_qty']!=0) {
                    echo sprintf("%8.2f|", $row['moving_qty']);
                }
                else {
                    echo sprintf("%8.2s|"," ");
                }
            }

            ## Old Start QTY
            if (DEBUG1) {
                if ($old_start_qty != $row['start_qty'])
                    echo sprintf("%8.2s|", $old_start_qty);
                else
                    echo sprintf("%8s|", " ");

                echo sprintf("%8s", $is_begin_correction);
            }

            $old_eod_qty = $row['eod_qty'];
            if (DEBUG1) echo NL;

            ### Generate Query Line if need
            if ($is_begin_correction) {
                $dumy = array(
                    'start_qty='.$row['start_qty'],
                    'before_count_adjust_qty='.$row['before_count_adjust_qty'],
                    'adjust_qty='.$row['adjust_qty'],
                    'count_qty='.$row['count_qty'],
                    'eod_qty='.$row['eod_qty']
                );
                $update_list = implode(',', $dumy);
                $dateIncome = $row['int_sales_date'];
                $qLine = "UPDATE inventory.inv_rpt_onhand_log_{$oneTable} SET {$update_list} WHERE sku='{$sku}' and int_sales_date={$dateIncome};";
                $queryUpdate[] = $qLine;
            }
        }
   }
}
//==============================================================================
function generate_sku_target() {
    $arrSKU = array();
    $arrMonth = array('1104','1105','1106');

    foreach ($arrMonth as $oneMonth) {
        $q = "
select 
    sku, '{$oneMonth}' as month_num, int_sales_date, count_qty
from
    inventory.inv_rpt_onhand_log_{$oneMonth}
where
    count_qty > 0
";
        echo "Fetch data Month[{$oneMonth}]",NL;
        $table = pg_query($q);
        $countRec = 0;
        while ($row = pg_fetch_array($table)) {           
            $sku1 = trim($row['sku']);
            if (! array_key_exists($sku1, $arrSKU)) $arrSKU[$sku1] = array();
            $arrSKU[$sku1][] = $row['month_num'].'-'.$row['int_sales_date'].'-'.$row['count_qty'];
            $countRec++;
        }
        echo "Finish = ", number_format($countRec), " Rows",NL;
    }
    echo "Total Unique SKU = ", number_format(count($arrSKU)), " Rows",NL;
    return($arrSKU);
}
//==============================================================================
//==============================================================================
//==============================================================================
//==============================================================================
?>
