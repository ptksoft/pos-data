<?php
//===========================================================
// Check Instant Running by bind UDP Port
$_instant_socket_ = @socket_create(AF_INET,SOCK_DGRAM, SOL_UDP);
if (false===$_instant_socket_)
    die("Cannot create UPD Socket\n");
if (false===@socket_bind($_instant_socket_, '127.0.0.1', 7700))
    die("Cannot bind UDP://127.0.0.1:7700, Another Process Is Running\n");
//===========================================================

// Define Alway Use Constant
define('X', false);       // Full Debug Or NOT
define('NL',"\n");      // New Line
define('CRLF',"\r\n");  // Carrage/Return
define('DS', "/");  // Directory Separator
define('SQ', "'");  // Single Quote

define('PATH_TO_SAVE_LOG_FILE', '/home/pos-data/download/log');
define('PORT_SERVICE', 7700);
define('PORT_CONSOLE', 7769);
define('PORT_HTTP', 7780);

define('SITE_ID', 43);     // Current Handle Store

// Socket Server
$service_port = array(
    '*:'.PORT_SERVICE,          // Service Channel ID=0
    '*:'.PORT_CONSOLE,       // Console Channel ID=1
    '*:'.PORT_HTTP               // Http Channel ID=2
);
$server = new SuperSocketV3($service_port);
$tm5sec = time()+5;
$tm60sec = time() + 60;
$tm3600sec = time() + 3600;

$app_path = realpath(dirname($argv[0]));

$history_cmd = '';
$history_data = '';

$client_http_header = array();

// POS Information
$dateincome_now = '000000';
$pos_to_client = array();   // Matching pos_id with cient_id
$client_to_pos = array();   // Matching client_id to pos_id
$pos_profile = array();     // Profile if POS
$pos_to_cashier = array();

// Product not found log
$product_not_found = array();   // Record that report from client that code not found
?>
