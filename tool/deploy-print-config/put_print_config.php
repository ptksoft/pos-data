#!/usr/bin/php -q
<?php
define('FTP_SERVER_PREFIX', '201.4.1.');
define('FTP_PORT', 21);
define('FTP_USER', 'trans');
define('FTP_PASS', 'trans@password');
define('FTP_DIR', '/@Module.Printer/CONFIGs/');

define('FILE1','returnslip.ini');
define('FILE2','slip.ini');

$PWD = dirname(__FILE__);
$pos_num = @(int)$argv[1];
if ($pos_num <1 || $pos_num >999) die('Invalid Pos Number'.PHP_EOL);
echo 'Begin put config to POS#',$pos_num,PHP_EOL;

$pos_ip =
    ($pos_num < 100)?
    (FTP_SERVER_PREFIX.(10+$pos_num)):
    (FTP_SERVER_PREFIX.$pos_num);
$ftp_stream = @ftp_connect($pos_ip, 21);
if (false===$ftp_stream) die('Cannot connecting to ['.$pos_ip.']'.PHP_EOL);
echo 'Connect to [',$pos_ip,'] Success',PHP_EOL;

if (!@ftp_login($ftp_stream, 'trans', 'trans@password')) die('USR/PWD Fail'.PHP_EOL);
echo 'Authen USR/PWD success',PHP_EOL;

if (!@ftp_pasv($ftp_stream, true)) die('Set PASV fail'.PHP_EOL);
echo 'Passive mode OK',PHP_EOL;

if (!@ftp_chdir($ftp_stream, FTP_DIR)) die('Cannot change dir to ['.FTP_DIR.']'.PHP_EOL);
echo 'Change dir to [',FTP_DIR,'] success',PHP_EOL;

if (!@ftp_put($ftp_stream, FILE1, $PWD.'/'.FILE1, FTP_BINARY)) die('Cannot put file ['.FILE1.']'.PHP_EOL);
echo 'Put file1 [',FILE1,'] success',PHP_EOL;
if (!@ftp_put($ftp_stream, FILE2, $PWD.'/'.FILE2, FTP_BINARY)) die('Cannot put file ['.FILE2.']'.PHP_EOL);
echo 'Put file2 [',FILE2,'] success',PHP_EOL;

@ftp_close($ftp_stream);
echo 'Close connection',PHP_EOL;
?>
