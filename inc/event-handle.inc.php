<?php
//==============================================================================
function ON_MainLoopFinish (&$obj) {
    global 
            $counter_stat,
            $counter_5sec,
            $tm5sec, $tm60sec, $tm3600sec
            ;

    $counter_5sec[StatLoopCycle]++;
    if ($tm5sec <= time()) { // GenStat & Reset Counter
        echo 'Process tm5 second schedule @ ', date('Y-m-d H:i:s'),NL;
        $tm5sec = time()+5;
        foreach($counter_5sec as $k=>$v) {
            $counter_stat[$k] = $counter_5sec[$k]/5;
            if ($counter_stat[$k] > $counter_stat['Max'.$k])
                $counter_stat['Max'.$k] = $counter_stat[$k];
            $counter_5sec[$k] = 0;
        }
        $counter_stat[StatMemUsage] = memory_get_usage(true);
        $counter_stat[StatMemPeak] = memory_get_peak_usage(true);

        // Also Check 60 Second schedule
        if ($tm60sec <= time()) {   // CleanUp TimeTable
            $tm60sec = time()+60;
            echo 'Running tm60 second schedule @ ', date('Y-m-d H:i:s'),NL;
            echo 'Nothing to DO!!!',NL;
            echo 'Finish tm60 second schedule @ ', date('Y-m-d H:i:s'),NL;

            // Also Check 3600 Second schedule
            if ($tm3600sec <= time()) {
                $tm3600sec = time()+3600;

                echo 'Running tm3600 second schedule @ ',date('Y-m-d H:i:s'),NL;
                echo 'Nothing to DO!!!',NL;
                echo 'Finish tm3600 second schedule @ ',date('Y-m-d H:i:s'),NL;
            }
        }
    }
}
//==============================================================================
function ON_ClientConnected ($service_id, $client_id, &$obj) {
    global $counter_5sec, $server;
    $counter_5sec[StatClientConnect]++;

    //echo 'Service#0 Client=',count($obj->arrServices[0]['clients']),NL;
    echo 'Client Connected on ServiceID=',$service_id, ' Client id=',$client_id,NL;
    if ($service_id == 1) {
        $server->writeline($service_id, $client_id, '# ZiCure POS Server Service version ['._VERSION_.'] (Powered By SuperSocketV3)');
        $server->writeline($service_id, $client_id, '# Enter /quit/ command for exit...');
    }
}
//==============================================================================
function ON_ClientDisconnected ($service_id, $client_id, &$obj) {
    global 
            $counter_5sec, $client_http_header,
            $pos_to_client, $pos_profile, $client_to_pos,
            $pos_to_cashier
            ;
    @$counter_5sec[StatClientDisconnect]++;

    echo 'Client Disconnected on ServiceID=',$service_id, ' Client id=',$client_id,NL;
    if (! $service_id < 2) {
        if (isset($client_http_header[$client_id])) unset($client_http_header[$client_id]);
    }
    if ($service_id != 0) return;

    /** Build And Verify Client Information Profile ***/
    $posNum = $client_to_pos[$client_id];
    unset($pos_to_client[$posNum]);
    unset($pos_profile[$posNum]);
    unset($client_to_pos[$client_id]);
    unset($pos_to_cashier[$posNum]);

    print_r($pos_profile);
}
//==============================================================================
function ON_DataLineArrival ($service_id, $client_id, $line, &$obj) {
    global
        $counter_5sec, $client_http_header,
        $server
        ;

    $counter_5sec[StatLineData]++;

    if ($service_id < 2) {
        // SERVICE and CONSOLE port
        $cmd = '';
        $data = '';
        if (false!==ParseCommandAndData($line, $cmd, $data)) {
            if (strlen($data)>80) {
                echo $service_id,':',$client_id,' Command=[',$cmd,'] DataFist80Char=[',  substr($data,0, 80) ,']',NL;
            }
            else {
                echo $service_id,':',$client_id,' Command=[',$cmd,'] Data=[',$data,']',NL;
            }
            ProcessCommand($service_id, $client_id, $cmd, $data);
        }
        else {
            // Uknow what command or data format
            echo '$line=',$line,NL;
            echo $service_id,':',$client_id,' Command=[',$cmd,'] Data=[',$data,']',NL;
            $server->writeline($service_id, $client_id, '! Unknow COMMAND or DATA format???');
        }
    }
    else {
        // HTTP port
        echo 'Incoming HTTP (',$service_id,':',$client_id,') line=',$line,NL;
        if (! isset($client_http_header[$client_id])) { 
            $client_http_header[$client_id] = $line;
            echo 'Found First Line, Save it to $client_http_header[',$client_id, ']',NL;
            return(true);
        }
        if ($line=='') {
            echo 'Found Blank Line, Begin To ProcessHttpGet',NL;
            ProcessHttpGet($service_id, $client_id);
        }
    }
}
//==============================================================================
?>
