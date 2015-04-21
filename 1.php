<?php
error_reporting(E_ALL);

/* Allow the script to hang around waiting for connections. */
set_time_limit(0);

/* Turn on implicit output flushing so we see what we're getting
 * as it comes in. */
ob_implicit_flush();

$address = '127.0.0.1';
$port = 10000;

if (($sock = socket_create(AF_INET, SOCK_STREAM, SOL_TCP)) === false) {
    echo "socket_create() failed: reason: " . socket_strerror(socket_last_error()) . "\n";
}
socket_set_option($sock, SOL_SOCKET, SO_KEEPALIVE, 1);

//echo socket_get_option($sock, SOL_SOCKET, SO_KEEPALIVE);exit;

if (socket_bind($sock, $address, $port) === false) {
    echo "socket_bind() failed: reason: " . socket_strerror(socket_last_error($sock)) . "\n";
}

if (socket_listen($sock, 5) === false) {
    echo "socket_listen() failed: reason: " . socket_strerror(socket_last_error($sock)) . "\n";
}

do {
    if (!isset($msgsock)) {
      echo "socket_accept\r\n";
      if (($msgsock = socket_accept($sock)) === false) {
          echo "socket_accept() failed: reason: " . socket_strerror(socket_last_error($sock)) . "\n";
          break;
      }
    }
    
    $conbuf = '';
    $finish = false;
    do {
        //socket_set_block($msgsock);
        echo "socket_read\r\n";
        if (false === ($buf = socket_read($msgsock, 2048, PHP_BINARY_READ))) {
            echo "socket_read() failed: reason: " . socket_strerror(socket_last_error($msgsock)) . "\n";
            break 2;
        }
        if (empty($buf)) {
          echo "empty buf\r\n";
          //exit('a');
        }
        echo "received: $buf\r\n";
        $conbuf .= $buf;
        
        if (preg_match('`^GET\s+(\S+)\s+HTTP`', $conbuf, $reqfile)) {
          if (preg_match('`\r?\n\r?\n`', $conbuf)) {
            $finish = true;
          }
        } elseif (preg_match('`^POST \s+(\S+)\s+HTTP`', $conbuf, $reqfile)) {
          if (preg_match('`Content\-Length\: (\d+)`', $conbuf, $m)) {
            $leng = $m[1];
            preg_match('`\r?\n\r?\n(.+)`s', $conbuf, $m);
            if (strlen(trim($m[1])) >= $leng) $finish = true;
          } elseif (preg_match('`\r?\n\r?\n`', $conbuf)) {
            $finish = true;
          }
        }
        
        if ($finish) {
          if (empty($reqfile[1])) {
          $aaa = "<h1>404 error</h1>";
$talkback = "HTTP/1.1 404 Not Found
Date: Wed, 11 Feb 2009 11:20:59 GMT
Server: Apache
X-Powered-By: PHP/5.2.4-2ubuntu5wm1
Last-Modified: Wed, 11 Feb 2009 11:20:59 GMT
Content-Language: ru
Content-Type: text/html; charset=utf-8
Connection: Keep-Alive
Content-Length: ".strlen($aaa)."
Connection: close

$aaa";
          } else {
            if ($reqfile[1] == '/') {
              $reqfile[1] = 'index.html';
            }
          $aaa = file_get_contents(preg_replace('`^/`', '', $reqfile[1]));
          //$aaa = '<h1>korvin0</h1>';
$talkback = "HTTP/1.1 200 OK
Date: Wed, 11 Feb 2009 11:20:59 GMT
Server: Apache
X-Powered-By: PHP/5.2.4-2ubuntu5wm1
Last-Modified: Wed, 11 Feb 2009 11:20:59 GMT
Content-Length: ".strlen($aaa)."
Connection: Keep-Alive

$aaa";
          }


          socket_write($msgsock, $talkback, strlen($talkback));
          break;
        }
        

        //socket_write($msgsock, $talkback, strlen($talkback));
        
        //break;
        //$talkback = "PHP: You said '$buf'.\n";
        //echo "$buf\n";
    } while (true);


    //socket_close($msgsock);unset($msgsock);
    
} while (true);

socket_close($sock);
?> 