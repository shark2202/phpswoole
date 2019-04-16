<?php 

//fork1.php

function timer1(){
	$s = 5;
    $now = time();
    $next = $now + $s;
    while(1){
    	$t = time();
    	if($t >= $next){
    		$next = $t + $s;

    		echo 'PID:'.getmypid().",timer:".time(),PHP_EOL;
    	}else{
    		usleep(800);
    	}
    }
}

function runWorker(){
	cli_set_process_title("phpswoole worker");

	//timer1();
}


function runMaster(){
	cli_set_process_title("phpswoole master");

	//timer1();
	//主进程监听是否有进程退出了??
	while(true){
		pcntl_signal_dispatch();

		$status = 0;
        $pid    = pcntl_wait($status, WUNTRACED);
            // Calls signal handlers for pending signals again.
        pcntl_signal_dispatch();
        if($pid > 0){
        	echo "child exit:".$pid,PHP_EOL;
        }else{

        }
	}
}

//新的链接来了
function newConn($socket){
	$new_socket = @stream_socket_accept($socket, 0, $remote_address);

	global $reatorRead;
	$reatorRead[] = $new_socket;

	echo "new conn:",PHP_EOL;
}

//新的数据来了
function newData($socket){

	echo "new data:",PHP_EOL;
}

function runReactor($sockfile){
	cli_set_process_title("phpswoole reactor");

	//innser server
	$server = stream_socket_server("unix://$sockfile", $errno, $errstr);
	 
	if (!$server)
	{
	        die("创建unix domain socket fail: $errno - $errstr");
	}
	 
	//这个就是内部的监听网络的
	$conn = stream_socket_accept($server, 5);
	stream_set_blocking($conn,0);
	//$server = stream_socket_accept($server, 5);

	pcntl_signal_dispatch();

	global $reatorRead,$reatorWrite,$reatorExcept;
	$reatorRead = [$conn];

	//
	while(true){
		$read  = $reatorRead;
	    $write = $reatorWrite;
	    $except = $reatorExcept;
	    $_selectTimeout = 100000000;

            // Waiting read/write/signal/timeout events.
        $ret = @stream_select($read, $write, $except, 0, $_selectTimeout);
        if(!$ret){
        	continue;
        }

        if($read){
        	forech($read as $r){
        		if($r == $conn){
        			newConn($r);
        		}else{
        			newData($r);
        		}
        	}
        }
	}

	}
	 
	fclose($server);
	
	//timer1();
}

$sockfile = '/dev/shm/unix.sock';
// 如果sock文件已存在，先尝试删除
if (file_exists($sockfile))
{
    unlink($sockfile);
}

$pid = $pid2 = null;

$workerIds = [];

$pid = pcntl_fork();

if($pid > 0){
	$pid2 = pcntl_fork();
	if($pid2 > 0){

	}elseif($pid2 === 0){
		runWorker($sockfile);

		$e =  new Exception("worker fail");
		echo $e->getTraceAsString(),PHP_EOL;

		exit(250);
	}else{
		throw new Exception("fork2 fail");
	}

}elseif($pid === 0){
	runReactor($sockfile);

	$e =  new Exception("reactor fail");
	echo $e->getTraceAsString(),PHP_EOL;

	exit(250);
}else{
	throw new Exception("fork fail");
}

runMaster();