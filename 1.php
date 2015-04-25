<?php
error_reporting(E_ALL);

/* Allow the script to hang around waiting for connections. */
set_time_limit(0);

/* Turn on implicit output flushing so we see what we're getting
 * as it comes in. */
ob_implicit_flush();

$address = '127.0.0.1';
$port = 10000;

$sock = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
socket_set_option($sock, SOL_SOCKET, SO_REUSEADDR, 1);
socket_set_option($sock, SOL_SOCKET, SO_KEEPALIVE, 1);
//echo socket_get_option($sock, SOL_SOCKET, SO_KEEPALIVE);exit;
socket_bind($sock, $address, $port);
socket_listen($sock);
echo "listen socket $address:$port\n";

$keepAlive = 0;

$clients = array($sock);

while (true) {
    $read = $clients;
    foreach ($read as $k=>$v) {
       if (empty($v)) unset($read[$k]);
    }
    $write = NULL;
    $except = NULL;
    if (socket_select($read, $write, $except, 0) < 1) continue;

    if (in_array($sock, $read)) {
	$clients[] = socket_accept($sock);
	echo "socket open #".(count($clients)-1)."-- ".countClients()." sockets currently opened\r\n";
	$key = array_search($sock, $read);
	unset($read[$key]);
    }
    
    foreach ($read as $msgsock)
    {
        //socket_set_block($msgsock);
	$k = array_search($msgsock, $clients);
	echo "* Read socket #$k\n";
        if (!isset($conbuf[$k])) $conbuf[$k] = '';

        $buf = @socket_read($msgsock, 2048, PHP_BINARY_READ);

        if (empty($buf)) {
	  closeSocket($k, 'empty buf');
          continue;
        }

        echo "Request: ".trim(preg_replace('`\n.+$`s', '', $buf))."\n";
        $conbuf[$k] .= $buf;
        
        if (preg_match('`^GET\s+(\S+)\s+HTTP`', $conbuf[$k], $reqfile)) {
          if (preg_match('`\r?\n\r?\n`', $conbuf[$k])) {
            $finish[$k] = true;
          }
        } elseif (preg_match('`^POST \s+(\S+)\s+HTTP`', $conbuf[$k], $reqfile)) {
          if (preg_match('`Content\-Length\: (\d+)`', $conbuf[$k], $m)) {
            $leng = $m[1];
            preg_match('`\r?\n\r?\n(.+)`s', $conbuf[$k], $m);
            if (strlen(trim($m[1])) >= $leng) $finish[$k] = true;
          } elseif (preg_match('`\r?\n\r?\n`', $conbuf[$k])) {
            $finish[$k] = true;
          }
        }
        
        if (!empty($finish[$k])) {
	  if (empty($reqfile[1])) {
	      $content = "<h1>404 error</h1>";
	      $headers = array(
		'HTTP/1.1 404 Not Found',
		'Date: Wed, 11 Feb 2009 11:20:59 GMT',
		'Server: Apache',
		'X-Powered-By: PHP/5.2.4-2ubuntu5wm1',
		'Last-Modified: Wed, 11 Feb 2009 11:20:59 GMT',
		'Content-Language: ru',
		'Content-Type: text/html; charset=utf-8',
		'Connection: Keep-Alive',
		'Content-Length: '.strlen($content),
		'Connection: close'
	      );
          } else {
            $file = $reqfile[1] == '/' ? 'index.html' : preg_replace('`^/`', '', $reqfile[1]);
            $content = file_get_contents($file);
	    $headers = array(
		'HTTP/1.1 200 OK',
		'Date: Wed, 11 Feb 2009 11:20:59 GMT',
		'Server: Apache',
		'X-Powered-By: PHP/5.2.4-2ubuntu5wm1',
		'Last-Modified: Wed, 11 Feb 2009 11:20:59 GMT',
		'Content-Length: '.strlen($content),
		'Connection: Keep-Alive'
	    );
	    if (preg_match('`\.html$`', $file)) {
                $headers[] = 'Content-Type: text/html; charset=utf-8';
            } elseif (preg_match('`\.gif$`', $file)) {
	        $headers[] = 'Content-Type: image/gif';
	    }
	  }
 
          echo 'Response: ' . trim(preg_replace('`\n.+`s','',implode("\n", $headers))) . "\n";
	  $res = implode("\n", $headers) . "\n\n" . $content;
          socket_write($msgsock, $res, strlen($res));
	  $finish[$k] = false;
	  $conbuf[$k] = '';

          if (!$keepAlive) {
	     closeSocket($k);
	  }
	 
        } // end finish
        
    }

   
}

socket_close($sock);

function closeSocket($k, $reason='')
{
    global $clients;
    socket_close($clients[$k]);
    $clients[$k] = '';
    echo "close socket #".$k." -- ".countClients()." sockets opened left. " . (!empty($reason) ? "Reason: ".$reason : "") . "\n";
}
function countClients()
{
    global $clients;
    $res = 0;
    foreach ($clients as $v)
    {
        if (!empty($v)) $res++;
    }
    return $res;
}
?> 
