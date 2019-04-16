<?php 


$gsocks = array();


$pid = pcntl_fork();

switch ($pid) {
    case -1:
        #fail
        break;

    case 0:
        for(;;){
            sleep(1);
            echo "C";
            var_dump($gsocks);###     这里一直输出空数组，那么应该如何共享内存呢？
            if (count($gsocks) == 0) continue;
            
            $gsock = $gsocks[rand(0,count($gsocks)-1)];
            $msg = time()." You are a lucky man \n";
            socket_write($gsock, $msg, strlen($msg));
        }
        #i am child
        break;

    default:
        //确保在连接客户端时不会超时
        set_time_limit(0);
        //设置IP和端口号
        $address = "127.0.0.1";
        $port = 2046; //调试的时候，可以多换端口来测试程序！
        $sock = socket_create(AF_INET, SOCK_STREAM, SOL_TCP) or die("socket_create() 失败的原因是:" . socket_strerror(socket_last_error()) . "/n");
        //阻塞模式
        socket_set_block($sock) or die("socket_set_block() 失败的原因是:" . socket_strerror(socket_last_error()) . "/n");
        //绑定到socket端口
        $result = socket_bind($sock, $address, $port) or die("socket_bind() 失败的原因是:" . socket_strerror(socket_last_error()) . "/n");
        //开始监听
        $result = socket_listen($sock, 4) or die("socket_listen() 失败的原因是:" . socket_strerror(socket_last_error()) . "/n");
        do { // never stop the daemon
            //它接收连接请求并调用一个子连接Socket来处理客户端和服务器间的信息
            $msgsock = socket_accept($sock) or  die("socket_accept() failed: reason: " . socket_strerror(socket_last_error()) . "/n");

            //读取客户端数据
            //socket_read函数会一直读取客户端数据,直到遇见\n,\t或者\0字符.PHP脚本把这写字符看做是输入的结束符.
            $buf = socket_read($msgsock, 8192);
//            echo "Received msg: $buf   \n";

            //数据传送 向客户端写入返回结果
            $msg = "welcome \n";
            socket_write($msgsock, $msg, strlen($msg)) or die("socket_write() failed: reason: " . socket_strerror(socket_last_error()) ."/n");
            //一旦输出被返回到客户端,父/子socket都应通过socket_close($msgsock)函数来终止

            $gsocks[] = $msgsock;

            if ($buf == "stop") {
                break;
            }

        } while (true);

        foreach ($gsocks as $gsock) {
            socket_close($gsock);
        }

        socket_close($sock);
    #parent
}