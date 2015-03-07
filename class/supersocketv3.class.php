<?php
/*********

SuperSocket Class
SuperSocket Class V2 (PTKSOFT Additional Modify)
SuperSocket Class V3 (PTKSOFT BUG Fixed for reference pass)

----------------------

Unlike other solutions that can be as simple as a single-client server and as advanced as a multiclient single
listener server, SuperSocket takes it to the next level for operation usage.

SuperSocket is a multi-socket (different ports and IPs), multiclient automated socket server that passes
actions through callbacks and automates a TCP server.

-> EVENT HANDLERS
	* ON_ClientConnected ($service_id, $client_id, &$obj)
		- Every new connection will call on the assigned callback function within this event handler.
			- $service_id is the socket id.
			- $client_id is the client id.
			- $obj is the SuperSocket object.

	* ON_ClientDisconnected ($service_id, $client_id, &$obj)
		- Every lost connection will call on the assigned callback function within this event handler.
			- $service_id is the socket id.
			- $client_id is the client id.
			- $obj is the SuperSocket object.

	* ON_DataArrival ($service_id, $client_id, $buffer, &$obj)
		- Every new buffer chunk will call on the assigned callback function within this event handler.
			- $service_id is the socket id.
			- $client_id is the client id.
			- $buffer is the recieved data.
			- $obj is the SuperSocket object.

	* ON_MainLoopFinish (&$obj)
		- Every end loop of socket listening will call on the assigned callback function within this event handler. Place any periodic tick functions, etc., within the callback function.
			- $obj is the SuperSocket object.

	* ON_AllServiceStop (&$obj)
		- Once the server stops, we will call on the assigned callback function within this event handler.
			- $obj is the SuperSocket object.

-----------------------------------------------------------------------------------------------------------
Events that add by PTKSOFT

	* ON_DataLineArrival ($service_id, $client_id, $line, &$obj)
		- Occur when info['BUFFER'] got data and detected that found \r\n or Line Complete
			- $service_id is the socket id.
			- $client_id is the client id.
			- $line is the complete data line (exclude \r\n)
			- $obj is the SuperSocket object.

        2007-07-30
            -   rename CHANNEL to CLIENT
-----------------------------------------------------------------------------------------------------------

* Methods
	SuperSocket($listen = array('127.0.0.1:6667'))
		- Assign each listener within an array (string, ADDR:PORT)... ADDR may be IP address, or a wildcard ('*')
		  character
	start()
		- Start the listeners.
	stop()
		- Stop the listeners, loop, and current connections.
	loop()
		- Start the server.
	closeall($service_id = NULL)
		- Close all (optionally, to a specific socket)
	close($service_id, $client_id)
		- Close a single channel
	write($service_id, $client_id, $buffer)
		- Write to a channel
	get_socket_info($service_id)
		- Get information about a specific socket
	remote_address($channel_socket, &$ipaddress, &$port)
		- Get the remote address of a channel socket.
	get_raw_channel_socket($service_id, $client_id)
		- Get the raw socket of a channel
	new_socket_loop(&$socket)
		- Loop privately used by loop()
	recv_socket_loop(&$socket)
		- Loop privately used by loop()
	event($name)
		- Event relay
	add_event_handle($name, $function_name)
		- Event callback handler

// BY PTKSOFT
	writeline($service_id, $client_id, $buffer)
		- Write to a channel with attach \r\n to the end of data

*********/

define('ON_ClientConnected','CLIENT_CONNECTED');
define('ON_ClientDisconnected','CLIENT_DISCONNECTED');
define('ON_DataArrival','DATA_ARRIVAL');
define('ON_DataLineArrival','LINE_ARRIVAL');
define('ON_MainLoopFinish','MAIN_LOOP_FINISH');
define('ON_AllServiceStop','SERVICE_STOP');

Class SuperSocketV3
	{
		var $arrListens = array();		
		var $arrServices = array();
		var $arrEventHandles = array();
                var $is_listening = FALSE;
		var $recvq = 2;
                
		// change this, so it work on PHP5 and later
		function __construct($listen = array('127.0.0.1:6969'))
                {
                    $listen = array_unique($listen);
                    foreach ($listen as $address)
                            {
                                    list($address, $port) = explode(":", $address, 2);
                                    $this->arrListens[] = array("ADDR" => trim($address), "PORT" => trim($port));
                            };
                }

		function start()
			{
				if ($this->is_listening)
					{
						return FALSE;
					};
				$this->arrServices = array();
				$curID = 0;
				foreach ($this->arrListens as $listen)
					{
						if ($listen['ADDR'] == "*")
							{
								$this->arrServices[$curID]['socket'] = @socket_create_listen($listen['PORT']);
								$listen['ADDR'] = FALSE;
                                                                if ($this->arrServices[$curID]['socket']===false) return(false);
							}
						else
							{
								$this->arrServices[$curID]['socket'] = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
							};
						if ($this->arrServices[$curID]['socket'] < 0)
							{
								return FALSE;
							};
						if (@socket_bind($this->arrServices[$curID]['socket'], $listen['ADDR'], $listen['PORT']) < 0)
							{
								return FALSE;
							};
						if (socket_listen($this->arrServices[$curID]['socket']) < 0)
							{
								return FALSE;
							};
						if (!socket_set_option($this->arrServices[$curID]['socket'], SOL_SOCKET, SO_REUSEADDR, 1))
							{
								return FALSE;
							};
						if (!socket_set_nonblock($this->arrServices[$curID]['socket']))
							{
								return FALSE;
							};
						$this->arrServices[$curID]['info'] = array("ADDR" => $listen['ADDR'], "PORT" => $listen['PORT']);
						$this->arrServices[$curID]['clients'] = array();
						$this->arrServices[$curID]['id'] = $curID;
						$curID++;
					};
				$this->is_listening = TRUE;
                                return(true);
			}

		function new_socket_loop(&$service)
			{
				$service =& $this->arrServices[$service['id']];
				if ($newClientSock = @socket_accept($service['socket']))
					{
						socket_set_nonblock($newClientSock);
						$service['clients'][]['socket'] = $newClientSock;
						$client_id = array_pop(array_keys($service['clients']));
						$this->remote_address($newClientSock, $remote_addr, $remote_port);
						$service['clients'][$client_id]['info'] = array('ADDR' => $remote_addr, 'PORT' => $remote_port, 'BUFFER' => '');
						
                                                // Call Event
                                                $event = $this->event("CLIENT_CONNECTED");
						if ($event) $event($service['id'], $client_id, $this);
					};
			}

		function recv_socket_loop(&$service)
			{
				$service =& $this->arrServices[$service['id']];
				foreach ($service['clients'] as $client_id => $client)
					{
						$status = @socket_recv($client['socket'], $buffer, $this->recvq, 0);
						if ($status === 0 && $buffer === NULL)
							{
								$this->close($service['id'], $client_id);
							}
						elseif (!($status === FALSE && $buffer === NULL))
							{
                                                                // Call Event
								$event = $this->event("DATA_ARRIVAL");
								if ($event) $event($service['id'], $client_id, $buffer, $this);

								// check if line complete
								$service['clients'][$client_id]['info']['BUFFER'] .= $buffer;
								$dumy = $service['clients'][$client_id]['info']['BUFFER'];
								if (!(strstr($dumy, "\r\n")===false)) {
									$aDumy = explode("\r\n", $dumy, 2);
									$service['clients'][$client_id]['info']['BUFFER'] = $aDumy[1];
									
                                                                        // Call Event
                                                                        $event = $this->event("LINE_ARRIVAL");
									if ($event) $event($service['id'], $client_id, $aDumy[0], $this);
								}
							}
					}
			}

		function stop()
			{
				$this->closeall();
				$this->is_listening = FALSE;
				foreach ($this->arrServices as $service_id => $service)
					{
						@socket_shutdown($service['socket']);
						@socket_close($service['socket']);
					};

                                // Call Event
				$event = $this->event("SERVICE_STOP");
				if ($event) $event($this);
			}

		function closeall($service_id = NULL)
			{
				if ($service_id === NULL)
					{
						foreach ($this->arrServices as $service_id => $service)
							{
								foreach ($service['clients'] as $client_id => $client)
									{
										$this->close($service_id, $client_id);
									}
							}
					}
				else
					{
						foreach ($this->arrServices[$service_id]['clients'] as $client_id => $client)
							{
								$this->close($service_id, $client_id);
							};
					};
			}

		function close($service_id, $client_id)
			{
				$arrOpt = array('l_onoff' => 1, 'l_linger' => 1);
				@socket_shutdown($this->arrServices[$service_id]['clients'][$client_id]['socket']);
				@socket_close($this->arrServices[$service_id]['clients'][$client_id]['socket']);

                                // Call Event
				$event = $this->event("CLIENT_DISCONNECTED");
				if ($event) $event($service_id, $client_id, $this);

                                // Clear client that already disconnect
                                unset($this->arrServices[$service_id]['clients'][$client_id]);
			}

		function loop()
			{
				while ($this->is_listening)
					{
						foreach ($this->arrServices as $service)
							{
								$this->new_socket_loop($service);
								$this->recv_socket_loop($service);
							};

                                                // Call Event
						$event = $this->event("MAIN_LOOP_FINISH");
						if ($event) $event($this);
                                                
						usleep(0);
					};
			}

		function write($service_id, $client_id, $buffer)
			{
				@socket_write($this->arrServices[$service_id]['clients'][$client_id]['socket'], $buffer);
			}

		function writeline($service_id, $client_id, $buffer)
			{
				@socket_write($this->arrServices[$service_id]['clients'][$client_id]['socket'], "{$buffer}\r\n");
			}

		function get_client_info($service_id, $client_id)
			{
				return $this->arrServices[$service_id]['clients'][$client_id]['info'];
			}

		function get_service_info($service_id)
			{
				$service_info = $this->arrServices[$service_id]['info'];
				if (empty($service_info['ADDR']))
					{
						$service_info['ADDR'] = "*";
					};
				return $service_info;
			}

		function get_raw_client_socket($service_id, $client_id)
			{
				return $this->arrServices[$service_id]['clients'][$client_id]['socket'];
			}

		function remote_address($channel_socket, &$ipaddress, &$port)
			{
				socket_getpeername($channel_socket, $ipaddress, $port);
			}

		function event($name)
			{
				if (isset($this->arrEventHandles[$name]))
				return $this->arrEventHandles[$name];
			}

		function add_event_handle($name, $function_name)
			{
				$this->arrEventHandles[$name] = $function_name;
			}
	};

?>