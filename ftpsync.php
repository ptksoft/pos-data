#!/usr/bin/php -q
<?php
/* FTP Syncronize Engine
 * Use for configable ftp syncronize
 * By: PTKSOFT (pitak@pakgon.com)
 */
define('VERSION', '120328.1730');
define('NL',"\n");
define('SCREEN_WIDTH', 71);

define('CONFIG_FOLDER', 'conf.ftpsync');     // folder that store config file
define('PORT_CHECK', 'port_check');     // port check pre-existing duplicate

define('FTP_SERVER', 'ftp_server');
define('FTP_PORT', 'ftp_port');

define('FTP_USER', 'ftp_user');
define('FTP_PASS', 'ftp_pass');

define('FTP_PASSIVE', 'ftp_passive');
define('FILE_TRANSFER_MODE', 'file_transfer_mode');     // MODE ASCII OR BIN

define('SYNC_DIRECTION', 'sync_direction'); // 0 = Download, 1 = Upload
define('SYNC_PATH', 'sync_path');           // which file path to be sync
define('SYNC_PATTERN', 'sync_pattern');     // which file pattern to be sync

define('LOCAL_PATH', 'local_path');         // wich path on local
define('LOCAL_TEMP_EXT', 'local_temp_ext'); // Temp file ext that will use during loading

//==============================================================================
$E_LAST_LEN = 0;    // Last string Length Print by A() function
function A($data) {
    global $E_LAST_LEN;
    echo date('His'), ': ',$data;
    $E_LAST_LEN = strlen($data);
};
function E($data) {
    echo date('His'), ': ', $data, NL;
}
function R($bool) {
    global $E_LAST_LEN;
    $DOT = '';
    $NeedDotLen = SCREEN_WIDTH - $E_LAST_LEN - (($bool)?(strlen('SUCCESS')):(strlen('FAIL')));
    if ($NeedDotLen > 2) $DOT =  ' '.str_repeat('.', $NeedDotLen-2).' ';
    if ($bool) echo $DOT.'SUCCESS'.NL; else die($DOT.'FAIL'.NL.NL);
}
function W($data) {
    global $E_LAST_LEN;
    $DOT = '';
    $NeedDotLen = SCREEN_WIDTH - $E_LAST_LEN - strlen($data);
    if ($NeedDotLen > 2) $DOT =  ' '.str_repeat('.', $NeedDotLen-2).' ';
    echo $DOT.$data.NL;
}
//------------------------------------------------------------------------------
//------------------------------------------------------------------------------
A('FTP-SYNC version['.VERSION.'] Starting');
        R(true);

/* Check Config Name Parameter */
A('Check Config Name');
    $ConfigName = @trim($argv[1]);
        R(strlen($ConfigName)>1);

/* Check Config Path */
A('Check Config Folder');
    $ConfigPath = dirname($argv[0]).'/'.CONFIG_FOLDER.'/';
        R(@file_exists($ConfigPath));
    E('Found ConfigPath == '.$ConfigPath);

/* Check Config File */
A('Check Config File');
    $ConfigFile = $ConfigPath.$ConfigName;
        R(@file_exists($ConfigFile));
    E('Found ConfigFile == '.$ConfigFile);

/* Read Config File */
A('Check ConfigValueCount');
    $lines = @file($ConfigFile);
        R(count($lines)>0);

/* Prepare Configuration */
$CONFIG = array();
for ($i=0; $i<count($lines); $i++)
{
    $line = trim($lines[$i]);
    if (substr($line, 0, 1)=='#') continue;     // Comment
    if (strpos($line, '=')===false) continue;   // Invalid format Key=Value
    list($K,$V) = explode('=', $line);
    $CONFIG[trim(strtolower($K))] = trim($V);
}

/* Check Port-Listen Duplicate */
A('Check Udp Port Range');
    $BindPort = @(int)$CONFIG[PORT_CHECK];
        R($BindPort > 1000 && $BindPort < 65535);
A('Create UDP Port');
    $socket = @socket_create(AF_INET,SOCK_DGRAM, SOL_UDP);
        R(false!==$socket);
A('Bind UDP Port');
        R(false!==@socket_bind($socket, '127.0.0.1', $BindPort));

/* CONNECT SERVER */
A('Check FTP Host/Port config');
    $ftp_server = @trim($CONFIG[FTP_SERVER]);
    $ftp_port = @(int)$CONFIG[FTP_PORT];
        R(strlen($ftp_server)>0 && $ftp_port > 1 && $ftp_port < 65535);
A('Connecting to Host ['.$ftp_server.':'.$ftp_port.']');
    $ftp_stream = @ftp_connect($ftp_server, $ftp_port);
        R(false!==$ftp_stream);

/* LOGIN */
A('Login with user ['.$ftp_user.']');
    $ftp_user = @trim($CONFIG[FTP_USER]);
    $ftp_pass = @$CONFIG[FTP_PASS];
        R(@ftp_login($ftp_stream, $ftp_user, $ftp_pass));

/* PASSIVE */
$ftp_passive = @(int)$CONFIG[FTP_PASSIVE];
if ($ftp_passive > 0) {
    A('Set PASSIVE Mode');
            R(@ftp_pasv($ftp_stream, true));
}

/* File Transfer Mode ( ASCII, BIN) */
$transfer_mode = FTP_ASCII;
switch (strtoupper(trim(@$CONFIG[FILE_TRANSFER_MODE]))) {
    case 'ASCII': $transfer_mode = FTP_ASCII;   break;
    case 'BINARY': $transfer_mode = FTP_BINARY;    break;
    default: $transfer_mode = FTP_ASCII;    break;
}
    E('Transfer Mode == '.$transfer_mode);

/* SYNC */
$sync_direction = @(int)$CONFIG[SYNC_DIRECTION];
    E('Sync direction == '.(($sync_direction==0)?('Down-Sync'):('Up-Sync')));

$sync_path = @trim($CONFIG[SYNC_PATH]);
A('Change PATH to ['.$sync_path.']');
        R(@ftp_chdir($ftp_stream, $sync_path));

if ($sync_direction == 0) {
    /* DOWNLOAD Syncronization */        
    A('Check Local Path Value');
        $local_path = @trim($CONFIG[LOCAL_PATH]);
            R(strlen($local_path)>0);
    A('Check Local Path Existing');
            R(@file_exists($local_path));
    A('Check Local Temp Ext Value');
        $local_temp_ext = @trim($CONFIG[LOCAL_TEMP_EXT]);
            R(strlen($local_temp_ext)>0);

    $sync_pattern = @trim($CONFIG[SYNC_PATTERN]);    
    A('List files ['.$sync_path.'/'.$sync_pattern.']');
        $filesOnFtp = @ftp_nlist($ftp_stream, $sync_path.'/'.$sync_pattern);
            R($filesOnFtp!==false);
    A('Total files found');
        W(count($filesOnFtp).' file(s)');

    $count_new_file = 0;
    $count_exists_file = 0;
    $count_error = 0;
    foreach ($filesOnFtp as $oneFile) {
        $fileWoPath = basename($oneFile);
        $file_on_local = $local_path.'/'.$fileWoPath;
        $file_temp_on_local = $file_on_local.'.'.$local_temp_ext;
        if (file_exists($file_temp_on_local))
            if (!@unlink($file_temp_on_local)) continue;   // having some problem
        if (file_exists($file_on_local)) {
            $count_exists_file++;
            continue;  // file already exists don't load
        }

        A('Load file ['.$fileWoPath.']');
            $step1 = false; $step2 = false;
            $step1 = @ftp_get($ftp_stream, $file_temp_on_local, $oneFile, $transfer_mode);
            if ($step1) $step2 = @rename($file_temp_on_local, $file_on_local);
                W(($step1 && $step2)?("SUCCESS"):("FAIL"));
            if ($step1 && $step2) $count_new_file++; else $count_error++;
    }
    A('Sync Summary');
        W('Existing='.$count_exists_file.' Error='.$count_error.' New='.$count_new_file);
}

A('Close FTP Connection');
        R(@ftp_close($ftp_stream));
//==============================================================================
E('Terminate Script Normaly');
//==============================================================================
?>
