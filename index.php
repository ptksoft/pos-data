#!/usr/bin/php -q
<?php
require('config.inc.php');

echo 'POS-DATA Exportor',NL;
echo 'This Script Just Check Database Connection',NL,NL;
require('config.inc.php');

@pg_close($conn);
?>
