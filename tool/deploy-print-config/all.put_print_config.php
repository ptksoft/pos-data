#!/usr/bin/php -q
<?php
$all_pos = array();
for($i=1;$i<=60;$i++) $all_pos[] = $i;
$all_pos[] = 201;

foreach ($all_pos as $pos_num) {
    echo 'POS # ', sprintf('%03d', $pos_num), ' BEGIN ',PHP_EOL;
    passthru(dirname(__FILE__).'/put_print_config.php '.$pos_num);
    echo str_repeat('-', 79),PHP_EOL;
    sleep(3);
}
?>
