<?php
	require_once("connect.inc");
	$test_table_name = 'test_mysqli_pconn_kill_table_1'; require_once("table.inc");

	$host = 'p:' . $host;
	if (!$plink = my_mysqli_connect($host, $user, $passwd, $db, $port, $socket))
		printf("[001] Cannot connect to the server using host=%s, user=%s, passwd=***, dbname=%s, port=%s, socket=%s\n",
			$host, $user, $db, $port, $socket);

	// get the thread ids of the two connections...
	$thread_id = mysqli_thread_id($link);
	$pthread_id = mysqli_thread_id($plink);

	if (!$res = mysqli_query($link, 'SHOW FULL PROCESSLIST'))
		printf("[002] Cannot get processlist, [%d] %s\n", mysqli_errno($link), mysqli_error($link));

	$running_threads = array();
	while ($row = mysqli_fetch_assoc($res))
		$running_threads[$row['Id']] = $row;
	mysqli_free_result($res);

	if (count($running_threads) < 2)
		printf("[003] Processlist is too short, [%d] %s\n", mysqli_errno($link), mysqli_error($link));

	if (!isset($running_threads[$thread_id]))
		printf("[004] Cannot find thread id of the regular link, [%d] %s\n", mysqli_errno($link), mysqli_error($link));

	if (!isset($running_threads[$pthread_id]))
		printf("[005] Cannot find thread id of the persistent link, [%d] %s\n", mysqli_errno($link), mysqli_error($link));

	// Kill the persistent connection - don't use mysqli_kill, mysqlnd will catch that...
	if (!mysqli_query($link, sprintf('KILL %d', $pthread_id)))
		printf("[006] Cannot kill persistent connection, [%d] %s\n", mysqli_errno($link), mysqli_error($link));
	mysqli_close($plink);
	// Give the server think-time to kill the pthread
	sleep(1);

	if (!$res = mysqli_query($link, 'SHOW FULL PROCESSLIST'))
		printf("[007] Cannot get processlist, [%d] %s\n", mysqli_errno($link), mysqli_error($link));

	$running_threads2 = array();
	while ($row = mysqli_fetch_assoc($res))
		$running_threads2[$row['Id']] = $row;
	mysqli_free_result($res);

	if (isset($running_threads2[$pthread_id]))
		printf("[008] Thread of the persistent connection should have been gone, [%d] %s\n", mysqli_errno($link), mysqli_error($link));
	if (!isset($running_threads2[$thread_id]))
		printf("[009] Thread of the regular connection should be still there, [%d] %s\n", mysqli_errno($link), mysqli_error($link));

	// On PHP side this should do nothing. PHP should not try to close the connection or something.
	@mysqli_close($plink);

	if (!$plink = @my_mysqli_connect($host, $user, $passwd, $db, $port, $socket))
		printf("[011] Cannot connect to the server using host=%s, user=%s, passwd=***, dbname=%s, port=%s, socket=%s\n",
			$host, $user, $db, $port, $socket);
	if (!$res3 = @mysqli_query($plink, 'SELECT id FROM test_mysqli_pconn_kill_table_1 ORDER BY id LIMIT 1')) {
		printf("[012] New persistent connection cannot execute queries, [%d] %s\n", @mysqli_errno($plink), @mysqli_error($plink));
	}

	@mysqli_free_result($res3);
	@mysqli_close($plink);
	mysqli_close($link);

	// remove the "p:<host>" from the host variable
	$host = substr($host, 2);
	if (!$link = my_mysqli_connect($host, $user, $passwd, $db, $port, $socket))
		printf("[013] Cannot connect to the server using host=%s, user=%s, passwd=***, dbname=%s, port=%s, socket=%s\n",
			$host, $user, $db, $port, $socket);
	if (!$res4 = mysqli_query($link, 'SELECT id FROM test_mysqli_pconn_kill_table_1 ORDER BY id LIMIT 1'))
		printf("[014] New regular connection cannot execute queries, [%d] %s\n", mysqli_errno($link), mysqli_error($link));

	mysqli_free_result($res4);
	mysqli_close($link);
	print "done!";
?>
<?php error_reporting(0); ?>
<?php
	$test_table_name = 'test_mysqli_pconn_kill_table_1'; require_once("clean_table.inc");
?>