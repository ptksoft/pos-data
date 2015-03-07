<?php
// Counter DATA
define('StatClientConnect','ClientConnect');
define('StatClientDisconnect','ClientDisconnect');
define('StatLineData','LineData');
define('StatLoopCycle','LoopCycle');
define('StatMemUsage','MemUsage');
define('StatMemPeak','MemPeak');

$counter_stat = array(
    StatClientConnect => 0, 'Max'.StatClientConnect => 0,
    StatClientDisconnect => 0, 'Max'.StatClientDisconnect => 0,
    StatLineData => 0, 'Max'.StatLineData => 0,
    StatLoopCycle => 0, 'Max'.StatLoopCycle => 0,
    StatMemUsage => number_format(memory_get_usage(true)),
    StatMemPeak => number_format(memory_get_peak_usage(true))
);

$counter_5sec = array(
    StatClientConnect => 0,
    StatClientDisconnect => 0,
    StatLineData => 0,
    StatLoopCycle => 0
);

// Suspend data base64, clear when change income_date
$suspend_data = array();

//=======================================
//=======================================
//=======================================
?>
