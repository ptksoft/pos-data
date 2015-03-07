#!/usr/bin/php -q
<?php
/*      ===================================
 *      POS SERVER
 *      ===================================
 *      version: 120327.1606
 *      version: 120919.1844 - Add Suspend/Resume feature
 */
define('_VERSION_', '120919.1844');

require('class/supersocketv3.class.php');
require('inc/server-config.inc.php');
require('inc/mem-record.inc.php');
require('inc/event-handle.inc.php');
require('inc/client-command.inc.php');
require('inc/console-command.inc.php');

//==============================================================================
function SendSeparateLine($service_id, $client_id, $width=40, $char='=') {
    global $server;
    $server->writeline(
            $service_id, $client_id,
            str_repeat($char, $width)
            );
}
//==============================================================================
function ParseCommandAndData($line, &$cmdString, &$dataString) {
    $DL = substr($line, 0, 1);
    if ($DL!='/' && $DL!='#') return(false);
    $arrPart = explode($DL, $line, 3);
    if (count($arrPart)!=3) return(false);
    $cmdString = $arrPart[1];
    $dataString = $arrPart[2];
    return(true);
}
//==============================================================================
function ProcessCommand($service_id, $client_id, $cmd, $data) {
    global $server,$history_cmd,$history_data;

    $cmd = strtoupper($cmd);

    if ($service_id == 0) {
        // ==================================
        // Command From Client
        // ==================================
        switch ($cmd) {
            case 'DATEINCOME':  CMD_DATEINCOME($service_id, $client_id, $data); break;
            case 'CLOCK':   CMD_CLOCK($service_id, $client_id, $data); break;
            case 'REGISTER':    CMD_REGISTER($service_id, $client_id, $data); break;
            case 'BOJ': CMD_BOJ($service_id, $client_id, $data);    break;
            case 'STATUS':  CMD_STATUS($service_id, $client_id, $data); break;
            case 'QUIT':    CMD_CLIENT_QUIT($service_id, $client_id, $data);    break;
            case 'PRODUCT_NOT_FOUND':   CMD_PRODUCT_NOT_FOUND($service_id, $client_id, $data);  break;
            case 'SUSPEND': CMD_SUSPEND($service_id, $client_id, $data); break;
            case 'RESUME': CMD_RESUME($service_id, $client_id, $data); break;
        }
    }
    elseif ($service_id == 1) {
        // ==================================
        // Command From Console
        // ==================================

        // Pre-Process Repeat Command
        if ($cmd=='' && $data=='') {
                $cmd = $history_cmd;
                $data = $history_data;
        }
        else {
                $history_cmd = $cmd;
                $history_data = $data;
        }

        // ==================================
        // Command From Console
        // ==================================
        echo 'Command=[', $cmd, '] Data=[',$data, ']', NL;
        switch ($cmd) {
            case 'CLIENT':  CMD_CLIENT($service_id, $client_id, $data); break;
            case 'DATEINCOME': CMD_DATEINCOME($service_id, $client_id, $data); break;
            case 'DATEINCOME_SET': CMD_DATEINCOME_SET($service_id, $client_id, $data); break;
            case 'DATEINCOME_RESET': CMD_DATEINCOME_RESET($service_id, $client_id, $data); break;
            case 'POS':     CMD_POS($service_id, $client_id, $data); break;
            case 'POS_ENDOFDAY':    CMD_POS_ENDOFDAY($service_id, $client_id, $data);   break;
            case 'POS_EXITAPP': CMD_POS_EXITAPP($service_id, $client_id, $data);    break;
            case 'POS_TRANSREQ':    CMD_POS_TRANSREQ($service_id, $client_id, $data); break;
            case 'CASHIER': CMD_CASHIER($service_id, $client_id, $data);    break;
            case 'STAT':    CMD_STAT($service_id, $client_id, $data);   break;
            case 'SUSPEND_LIST': CMD_SUSPEND_LIST($service_id, $client_id, $data);    break;
            case 'RESUME': CMD_RESUME($service_id, $client_id, $data);  break;
            case 'QUIT':    CMD_QUIT($service_id,$client_id, $data);    break;
            case 'FLUSH':   CMD_FLUSH($service_id, $client_id, $data);    break;
            case 'TERMINATE':   CMD_TERMINATE($service_id, $client_id, $data);  break;
            default:	CMD_UNKNOW($service_id, $client_id, $data);	break;
        }
    }
}
//==============================================================================
function CMD_UNKNOW($service_id, $client_id, $data) {
	global $server;

    SendSeparateLine($service_id, $client_id);
    $server->writeline($service_id, $client_id, 'Unknow command???');
    SendSeparateLine($service_id, $client_id);
}
//==============================================================================
function SendHttpHeader($service_id, $client_id) {
    global $server;
    
    $server->writeline($service_id, $client_id, 'HTTP/1.0 200 OK');
    $server->writeline($service_id, $client_id, 'Content-Type: text/html');
    $server->writeline($service_id, $client_id, '');
}
//==============================================================================
function ProcessHttpGet($service_id, $client_id) {
    global $server, $client_http_header;

    while (true) {
        echo 'ProcessHttpGet, check isset $client_http_header',NL;
        if (! isset($client_http_header[$client_id])) break;

        echo 'ProcessHttpGet, check header line 3 parts',NL;
        $head1 = $client_http_header[$client_id];
        $aDumy = explode(' ', $head1, 3);
        if (count($aDumy)!=3) break;

        echo 'ProcessHttpGet, check GET and HTTP keyword',NL;
        $sType = strtoupper($aDumy[0]); if ($sType != 'GET') break;
        $sHttp = strtoupper(substr($aDumy[2], 0, 4));   if ($sHttp != 'HTTP') break;

        echo 'ProcessHttpGet, check Extract CMD and DATA',NL;
        $parts = explode('/', $aDumy[1], 3);  if (count($parts)!=3) break;
        $cmd = strtoupper($parts[1]);
        $data = $parts[2];

        echo 'ProcessHttpGet, cmd=',$cmd,NL;
        switch ($cmd) {
            case 'CLIENT':
                SendHttpHeader($service_id, $client_id);
                CMD_CLIENT($service_id, $client_id, $data);
                break;
            case 'STAT':
                SendHttpHeader($service_id, $client_id);
                CMD_STAT($service_id, $client_id, $data);
                break;
            case 'RESUME':
                SendHttpHeader($service_id, $client_id);
                CMD_RESUME($service_id, $client_id, $data);
                break;
        }

        break;  // End while (true);
    }
    $server->close($service_id, $client_id);    // Disconnect Client
}
//==============================================================================

//==============================================================================
// main program
//==============================================================================
echo 'ZiCure POS Server version '._VERSION_.' Staring',NL;

$server->add_event_handle(ON_ClientConnected, 'ON_ClientConnected');
$server->add_event_handle(ON_ClientDisconnected, 'ON_ClientDisconnected');
$server->add_event_handle(ON_DataLineArrival, 'ON_DataLineArrival');
$server->add_event_handle(ON_MainLoopFinish, 'ON_MainLoopFinish');
if (! $server->start()) die('Cannot Bind Port'.NL);

echo 'Start Server Success',NL;
$server->loop();

$server->stop();
echo 'Stop Server Success',NL;
//==============================================================================
?>
