<?php
//========================================================================
function CMD_TERMINATE($service_id, $client_id, $data) {
    global $server;

    SendSeparateLine($service_id, $client_id);
    $server->writeline($service_id, $client_id, 'Perpare to terminate ready, Terminate in 3 second');
    echo NL,'Terminate in 3 SECOND !!!',NL;
    SendSeparateLine($service_id, $client_id);

	sleep(3);
    echo NL,'STOP All Server Connection',NL;
	$server->stop();

    die();
}
//========================================================================
function CMD_QUIT($service_id, $client_id, $data) {
    global $server;

    SendSeparateLine($service_id, $client_id);
    $server->writeline($service_id, $client_id, 'Bye, see you again next cycle');
    SendSeparateLine($service_id, $client_id);
    $server->close($service_id, $client_id);
}
//========================================================================
function CMD_STAT($service_id, $client_id, $data) {
    global $server, $counter_stat;

    SendSeparateLine($service_id, $client_id);
    foreach ($counter_stat as $k=>$v) {
        switch ($k) {
            case StatMemUsage:
            case StatMemPeak:
                if ($v < 1024) $var = number_format($v).' B';
                elseif ($v < 1048576) $var = number_format($v/1024) . ' KB';
                else $var = number_format($v/1048576) . ' MB';
                $server->writeline($service_id, $client_id, $k.' = ' . $var);
                break;
            default:
                $server->writeline($service_id, $client_id, $k.' = ' . $v);
        }
    }
    SendSeparateLine($service_id, $client_id);
}
//========================================================================
function CMD_CLIENT($service_id, $client_id, $data) {
    global $server, $client_to_pos;

    SendSeparateLine($service_id, $client_id);
    $server->writeline($service_id, $client_id, 'Total '.count($server->arrServices[0]['clients']).' clients');
    ksort($server->arrServices[0]['clients']);
    foreach ($server->arrServices[0]['clients'] as $k=>$v) {
        $data = sprintf("%03d", $k).'> '.$v['info']['ADDR'].':'.$v['info']['PORT'].' Buffer('.strlen($v['info']['BUFFER']).') PosID('.@$client_to_pos[$k].')';
        $server->writeline($service_id, $client_id, $data);
    }
    SendSeparateLine($service_id, $client_id);
}
//========================================================================
function CMD_FLUSH($service_id, $client_id, $data) {
    global $server, $client_to_pos;

    SendSeparateLine($service_id, $client_id);
    $server->writeline($service_id, $client_id, 'Total '.count($server->arrServices[0]['clients']).' clients');
    $arrClients = array();
    foreach ($server->arrServices[0]['clients'] as $k=>$v) { $arrClients[] = $k; }
    foreach ($arrClients as $cid) $server->close(0, $cid);
    SendSeparateLine($service_id, $client_id);
}
//========================================================================
function CMD_POS($service_id, $client_id, $data) {
    global $server, $pos_to_client, $pos_profile;
    
    print_r($pos_profile);
    //print_r($pos_to_client);
    //print_r($client_to_pos);
    
    SendSeparateLine($service_id, $client_id);
    $server->writeline($service_id, $client_id, "Current Connected POS ".count($pos_to_client).' clients'.NL);
    ksort($pos_to_client);
    foreach ($pos_to_client as $pid => $cid) {
        $server->writeline($service_id, $client_id,
            'pos#'.sprintf("%03d",$pid).' @CH'.$cid.
            ' Version:'. $pos_profile[$pid]['version'].
            ' Date-Income:'.$pos_profile[$pid]['date-income'].
            ' Date-Running:'.$pos_profile[$pid]['date-running'].
            ' Status:'.$pos_profile[$pid]['status'].
            ' CashierID:'.$pos_profile[$pid]['cashier-id']
            );
    }
    SendSeparateLine($service_id, $client_id);
}
//========================================================================
function CMD_CASHIER($service_id, $client_id, $data) {
    global $server, $pos_to_client, $pos_profile, $pos_to_cashier;

    print_r($pos_to_cashier);
    SendSeparateLine($service_id, $client_id);
    $server->writeline($service_id, $client_id, "Current Cashier in POS ".count($pos_to_cashier).' pos'.NL);
    ksort($pos_to_cashier);
    foreach ($pos_to_cashier as $pid => $cid) {
        $server->writeline($service_id, $client_id,
            'Pos#'.sprintf("%03d",$pid).' @CH'.$cid.
            ' Cashier-ID:'. sprintf("%04d",$cid)
            );
    }
    SendSeparateLine($service_id, $client_id);
}
//========================================================================
function CMD_DATEINCOME_SET($service_id, $client_id, $data) {
    global $server, $dateincome_now;

    SendSeparateLine($service_id, $client_id);
    $arrDumy = explode('/',$data);
    if (count($arrDumy==2)) {
        $d1 = @(int)$arrDumy[0];
        $d2 = trim($arrDumy[1]);
        echo '$d1=',$d1,' and $d2='+$d2,NL;
        if (sprintf("%06d",$d1)==$d2) {
            if ($d1 >= 100901 && $d1 <= (100901+(50*10000))) {
                $dateincome_now = sprintf("%06d",$d1);
                $server->writeline($service_id, $client_id, '/DateIncome_SET/ok/NewDateIncome=' . $dateincome_now);
                return;
            }
            else echo 'CMD_DateIncome_set: DateIncome value OVER RANGE',NL;
        }
        else echo 'CMD_DateIncome_set: Compare $d1 and $d2 not EQUAL',NL;
    }
    else echo 'CMD_DateIncome_set: Cannot separate $d1 and $d2',NL;

    $server->writeline($service_id, $client_id, '/DateIncome_SET/ERR/Invalid P1 or P2');
    SendSeparateLine($service_id, $client_id);
}
//========================================================================
function CMD_DATEINCOME_RESET($service_id, $client_id, $data) {
    global $server, $dateincome_now;

    SendSeparateLine($service_id, $client_id);
    $dateincome_now = "000000";
    $server->writeline($service_id, $client_id, '/dateincome_reset/ok/000000');
    SendSeparateLine($service_id, $client_id);
}
//========================================================================
function _PosListToClientList($data) {
    global $pos_to_client;

    if (trim($data)=='') return(array());

    $arrClientToSend = array();
    if (strtolower($data)=='all') {
        foreach ($pos_to_client as $pid=>$cid) $arrClientToSend[$pid]=$cid;
    }
    else {
        $arr1 = explode(',', $data);
        foreach ($arr1 as $oneId) {
            $pos_id = (int)$oneId;
            if ($pos_id > 0) {
                if (array_key_exists($pos_id, $pos_to_client)) $arrClientToSend[$pos_id] = $pos_to_client[$pos_id];
            }
        }
    }
    return($arrClientToSend);
}
//========================================================================
function CMD_POS_EXITAPP($service_id, $client_id, $data) {
    global $server, $pos_to_client, $pos_profile;

    SendSeparateLine($service_id, $client_id);
    echo 'Got Command [POS_ExitApp] Param[',$data,']',NL;
    $arrClientToSend = _PosListToClientList($data);
    
    if (count($arrClientToSend)<1) {
        $server->writeline($service_id, $client_id, '/Pos_ExitApp/Err/No client condition Match, Client ZERO!!!');
        return;
    }
    $countSend=0;
    foreach ($arrClientToSend as $p=>$c) {
        $countSend++;
        echo 'Send ExitApp command to POS#', $p, ' on Client$',$c,NL;
        $server->writeline(0, $c, '/ExitApp/EmptyParm/');
    }
    $server->writeline($service_id, $client_id, '/Pos_ExitApp/OK/Finish send '.$countSend.' client(s)');
    SendSeparateLine($service_id, $client_id);
}
//========================================================================
function CMD_POS_ENDOFDAY($service_id, $client_id, $data) {
    global $server, $pos_to_client, $pos_profile;

    SendSeparateLine($service_id, $client_id);
    echo 'Got Command [POS_EndOfDay] Param[',$data,']',NL;
    $arrClientToSend = _PosListToClientList($data);

    if (count($arrClientToSend)<1) {
        $server->writeline($service_id, $client_id, '/Pos_EndOfDay/Err/No client condition Match, Client ZERO!!!');
        return;
    }
    $countSend=0;
    foreach ($arrClientToSend as $p=>$c) {
        $countSend++;
        echo 'Send EndOfDay command to POS#', $p, ' on Client$',$c,NL;
        $server->writeline(0, $c, '/EndOfDay/EmptyParm/');
    }
    $server->writeline($service_id, $client_id, '/Pos_EndOfDay/OK/Finish send '.$countSend.' client(s)');
    SendSeparateLine($service_id, $client_id);
}
//========================================================================
function CMD_POS_TRANSREQ($service_id, $client_id, $data) {
    global $server, $pos_to_client, $pos_profile;

    if (strpos($data, '/')===false) {
        $server->writeline($service_id, $client_id, '/Pos_TransREQ/Err/Invalid Param Format');
        return;
    }
    list($client_string, $trans_id) = explode('/', $data, 2);

    SendSeparateLine($service_id, $client_id);
    echo 'Got Command [POS_TransREQ] Param[',$data,']',NL;
    $arrClientToSend = _PosListToClientList($client_string);

    if (count($arrClientToSend)<1) {
        $server->writeline($service_id, $client_id, '/Pos_TransREQ/Err/No client condition Match, Client ZERO!!!');
        return;
    }
    $countSend=0;
    foreach ($arrClientToSend as $p=>$c) {
        $countSend++;
        echo 'Send TransREQ command to POS#', $p, ' on Client$',$c,NL;
        $server->writeline(0, $c, '/TransREQ/'.$trans_id.'/');
    }
    $server->writeline($service_id, $client_id, '/Pos_TransREQ/OK/Finish send '.$countSend.' client(s)');
    SendSeparateLine($service_id, $client_id);
}
//========================================================================
function CMD_SUSPEND_LIST($service_id, $client_id, $data) {
    global $server, $client_to_pos, $suspend_data;

    SendSeparateLine($service_id, $client_id);
    $server->writeline($service_id, $client_id, 'Suspend List Total = '.count($suspend_data).' bill');
    foreach ($suspend_data as $k=>$v) {
        $data = $k .' > ' . strlen($v) . ' byte(s)';
        $server->writeline($service_id, $client_id, $data);
    }
    SendSeparateLine($service_id, $client_id);
}
//========================================================================
?>
