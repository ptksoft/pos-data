<?php
/*
Explain and Description
for SuperSocketV3
 */


$arrServices = array(
    '0' => array(
        'id' => 0,
        'socket'  => listenSocket,
        'info' => array(
                    'ADDR' => '127.0.0.1',
                    'PORT' => 6969
                    ),
        'clients' => array(
            '0' => array(
                        'socket' => acceptSocket,
                        'info' => array(
                                        'ADDR' => '192.168.1.1',
                                        'PORT' => 23556,
                                        'BUFFER' => ''
                                    )
                    )
        )
    )
);
?>
