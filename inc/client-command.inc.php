<?php
//========================================================================
function CMD_REGISTER($service_id, $client_id, $data) {
    global
        $server,
        $pos_to_client, $pos_profile,
        $client_to_pos,
        $pos_to_cashier;

    $arrParts = explode(chr(27),$data);
    if (count($arrParts)<1) {
        $server->writeline($service_id, $client_id, '/register/err/Invalid Pamameter Parts!!!');
    }
    else {
        $site_id = 0;
        $pos_id = 0;
        $pos_version = '000000.000000';
        $pos_status = 'Unknow';
        $pos_dateincome = '000000';
        $pos_daterunning = -1;
        $pos_cashier_id = 0;

        foreach ($arrParts as $onePart) {
            if (false===strpos($onePart, '=')) continue;
            list($k,$v) = explode('=',$onePart, 2);
            $k = strtolower($k);
            switch ($k) {
                case 'site-id': $site_id = @(int)$v; break;
                case 'pos-id': $pos_id = @(int)$v; break;
                case 'version': $pos_version = trim($v); break;
                case 'status': $pos_status = trim($v); break;
                case 'date-income': $pos_dateincome = trim($v); break;
                case 'date-running': $pos_daterunning = trim($v); break;
                case 'cashier-id': $pos_cashier_id = @(int)$v; break;
            }
        }
        if ($site_id != SITE_ID) {
            $server->writeline($service_id, $client_id, '/register/err/Unsupport STORE_ID='.$site_id);
            return;
        }
        if ($pos_id < 1) {
            $server->writeline($service_id, $client_id, '/register/err/Unsupport POS_ID='.$pos_id);
            return;
        }
        $pos_to_client[$pos_id] = $client_id;
        $client_to_pos[$client_id] = $pos_id;
        $pos_profile[$pos_id] = array(
            'client'=>$client_id,
            'version'=>$pos_version,
            'status'=>$pos_status,
            'date-income'=>$pos_dateincome,
            'date-running'=>$pos_daterunning,
            'cashier-id'=>$pos_cashier_id
        );
        $pos_to_cashier[$pos_id] = $pos_cashier_id;
        $server->writeline($service_id, $client_id, '/register/ok/client_id='.$client_id);
    }
}
//========================================================================
function CMD_BOJ($service_id, $client_id, $data) {
    global
        $server,
        $pos_to_client, $pos_profile,
        $client_to_pos,
        $pos_to_cashier;

    $arrParam = explode(chr(27), $data);
    if (count($arrParam)<1) {
        $server->writeline($service_id, $client_id, '/boj/err/Invalid Pamameter Parts!!!');
    }
    else {
        $pos_num = @(int)$client_to_pos[$client_id];
        $cashier_id = trim($arrParam[0]);
        if ($pos_num < 1) {
            $server->writeline($service_id, $client_id, '/boj/err/Unknow POS-ID');
        }
        else {
            if (in_array($cashier_id, $pos_to_cashier)) {
                $pos_have_cashier = 0;
                foreach ($pos_to_cashier as $p => $c) {
                    if ($c == $cashier_id) {
                        $pos_have_cashier = $p;
                        break;
                    }
                }
                if ($pos_have_cashier != $pos_num) {
                    $server->writeline($service_id, $client_id, '/boj/err/'.$pos_have_cashier);
                }
                else {
                    $server->writeline($service_id, $client_id, '/boj/ok/0');
                }
            }
            else {
                $server->writeline($service_id, $client_id, '/boj/ok/0');
            }
        }
    }
}
//========================================================================
function CMD_CLOCK($service_id, $client_id, $data) {
    global $server;

    $server->writeline($service_id, $client_id, '/clock/ok/'.date('Y-m-d H:i:s'));
}
//========================================================================
function CMD_CLIENT_QUIT($service_id, $client_id, $data) {
    global $server;

    $server->writeline($service_id, $client_id, '/quit/ok/See you again next time');
    $server->close($service_id, $client_id);
}
//========================================================================
function CMD_DATEINCOME($service_id, $client_id, $data) {
    global $server, $dateincome_now;

    if ($dateincome_now == '000000') $dateincome_now = substr(date('Ymd'),2);
    $server->writeline($service_id, $client_id, '/dateincome/ok/'.$dateincome_now);;
}
//========================================================================
function CMD_STATUS($service_id, $client_id, $data) {
    global $server, $client_to_pos, $pos_profile;

    if (array_key_exists($client_id, $client_to_pos)) {
        $pos_id = (int)$client_to_pos[$client_id];
        $pos_profile[$pos_id]['status'] = $data;
        $server->writeline($service_id, $client_id, '/status/ok/');
    }
    else {
        $server->writeline($service_id, $client_id, '/status/err/Not found client_id in $client_to_pos');
    }
}
//========================================================================
function CMD_RUNNING($service_id, $client_id, $data) {
    global $server, $client_to_pos, $pos_profile;

    if (array_key_exists($client_id, $client_to_pos)) {
        $pos_id = (int)$client_to_pos[$client_id];
        $pos_profile[$pos_id]['date-running'] = $data;
        $server->writeline($service_id, $client_id, '/running/ok/');
    }
    else {
        $server->writeline($service_id, $client_id, '/running/err/Not found client_id in $client_to_pos');
    }
}
//========================================================================
function CMD_PRODUCT_NOT_FOUND($service_id, $client_id, $data) {
    global $server, $client_to_pos, $product_not_found;

    if (!array_key_exists($client_id, $client_to_pos)) {
        $server->writeline($service_id, $client_id, '/product_not_found/err/Not found client_id in $client_to_pos');
        return;
    }
    $data = trim($data);
    if (strlen($data)>25) {
        $server->writeline($service_id, $client_id, '/product_not_found/err/Your data code is');
        return;
    }

    $pos_id = (int)$client_to_pos[$client_id];
    if (array_key_exists($data, $product_not_found)) {
        if (! in_array($pos_id, $product_not_found[$data])) {
            $product_not_found[$data][] = $pos_id;
            _SaveProductNotFoundReport($pos_id, $data);
        }
    }
    else {
        $product_not_found[$data] = array($pos_id);
        _SaveProductNotFoundReport($pos_id, $data);
    }
    $server->writeline($service_id, $client_id, '/product_not_found/OK/');
}
//========================================================================
function CMD_SUSPEND($service_id, $client_id, $data) {
    global $server, $client_to_pos, $pos_profile, $suspend_data;

    if (!array_key_exists($client_id, $client_to_pos)) {
        $server->writeline($service_id, $client_id, '/product_not_found/err/Not found client_id in $client_to_pos');
        return;
    }

    $arrPart = explode(chr(27), $data, 2);
    if (count($arrPart)!=2) {
        $server->writeline($service_id, $client_id, '/suspend/err/Data Parts <> 2');
        return;
    }
    
    $suspend_data[$arrPart[0]] = $arrPart[1];
    $server->writeline($service_id, $client_id, '/suspend/Success/Total suspend data='.count($suspend_data));
    echo 'client-command: /suspend/ data len=', strlen($arrPart[1]), ' total data=', count($suspend_data), PHP_EOL;
}
//========================================================================
function CMD_RESUME($service_id, $client_id, $data) {
    global $server, $client_to_pos, $pos_profile, $suspend_data;

    // fileSuspend format is: 120303-123458-008
    $key = trim($data);
    if (strlen($key)!= 17) {
        $server->writeline($service_id, $client_id, '/resume/err/Invalid resume-key length <> 17');
        return;
    }
    if (!array_key_exists($key, $suspend_data)) {
        $server->writeline($service_id, $client_id, '/resume/err/Not Found! resume-key ['.$key .']');
        return;
    }

    // Found suspend data and send it back to client
    $dataB64 = $suspend_data[$key];
    $server->writeline($service_id, $client_id, '/resume/ok/'.$dataB64);

    echo 'Send resume data of key [',$key,'] back to client', PHP_EOL;
}
//========================================================================
function _SaveProductNotFoundReport($pos_id, $pro_code) {
    global $dateincome_now;

    if (! file_exists(PATH_TO_SAVE_LOG_FILE)) {
        if (! mkdir(PATH_TO_SAVE_LOG_FILE)) {
            echo 'Cannot create directory [', PATH_TO_SAVE_LOG_FILE, ']',CRLF;
            return(false);
        }
    }

    $fileLog = PATH_TO_SAVE_LOG_FILE.DS.'product_not_found-'.$dateincome_now.'.log';
    $fp = @fopen($fileLog, 'a');
    if ($fp===false) {
        echo 'Cannot create file product_not_found log [',$fileLog,']',CRLF;
        return(false);
    }
    @fputs($fp, $pos_id.'=>'.$pro_code.CRLF);
    @fclose($fp);
    return(true);
}
//========================================================================
//========================================================================
//========================================================================
?>
