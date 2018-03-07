<?php
class Protosocket {
    var $socket; //socket 句柄
    var $is_connect;
	var $serip;
	var $port;

    //var $debug = 1;

    function __construct($params = array('serip'=>'', 'port'=>'')) {
        $this->serip = isset($params['serip']) ? $params['serip'] : $params[0];
        $this->port = isset($params['port']) ? $params['port'] : $params[1];
        $this->is_connect = false;
		$this->do_create_connection();
    }

	private function do_create_connection() {
        $address = gethostbyname ( $this->serip );
        if (($this->socket = socket_create ( AF_INET, SOCK_STREAM, SOL_TCP )) < 0) {
            load_class ( 'Log' )->write_log ( 'ERROR', "Couldn't create socket: " . socket_strerror ( socket_last_error () ) );
            trigger_error ( "Couldn't create socket: " . socket_strerror ( socket_last_error () ) . "\n" );
        }
        socket_set_option ( $this->socket, SOL_SOCKET, SO_SNDTIMEO, array ("sec" => 0, "usec" => 500000 ) );
        try {
            $result = @socket_connect ( $this->socket, $address, $this->port );
        } catch ( Exception $e ) {
            load_class ( 'Log' )->write_log ( 'ERROR', "Couldn't connect socket: " . socket_strerror ( socket_last_error () ) );
            $result = false;
        }
        if ($result === false) {
            load_class ( 'Log' )->write_log ( 'ERROR', $this->serip.":".$this->port." socket_connect fail:" . socket_strerror ( socket_last_error () ) );
            socket_clear_error ();
            $this->close ();
        } else {
            load_class ( 'Log' )->write_log ( 'NOTI', $this->serip.":".$this->port." socket_connect succ:" . socket_strerror ( socket_last_error () ) );
            socket_set_option ( $this->socket, SOL_SOCKET, SO_RCVTIMEO, array ("sec" => 0, "usec" => 500000 ) ); //0.5s
            $this->is_connect = true;
        }
    }

	/* @brief return false 读失败
	 */
    private function read($read_max_len) {
        $buf = socket_read($this->socket, $read_max_len);
		if (!$buf) {
			if ($buf === false) {
				$errcode = socket_last_error($this->socket);
				debug(__FILE__." line:".__LINE__." ".$this->serip.":".$this->port." socket_read err".
						": errcode=".$errcode." errmsg:".socket_strerror($errcode));
				if ($errcode != 11) {//11=>Resource temporarily unavailable
					debug(__FILE__." line:".__LINE__." ".$this->serip.":".$this->port." socket_read closed");
				}
			} else if ($buf === "") {
				debug(__FILE__." line:".__LINE__." ".$this->serip.":".$this->port." socket_read closed");
			}
			$this->close();	
			return false;
		}
        return $buf;
    }

    private function write($msg, $send_len) {
        $ret = socket_write ( $this->socket, $msg, $send_len);
        if ($ret === false) {
            $this->close();
        }
        return $ret;
    }

    private function do_sendmsg($msg) {
        $this->write($msg, strlen($msg));
        $buf = $this->read(4096 );
		if ($buf === false) {//连接断开
			return false;
		}
        $pkg_arr = @unpack ( "Lproto_len", $buf );
        $proto_len = $pkg_arr ["proto_len"];
        while ( $proto_len != strlen ( $buf ) ) {//!=适用场合有限
            $tmp_buf = $this->read(4096 );
			if ($tmp_buf === false) {
				return false;
			}
			$buf .= $tmp_buf;
        }
        return $buf;
    }

    function sendmsg($msg) {
		if ($this->is_connect == false) {
			debug(__FILE__." line:".__LINE__." connect when sendmsg".$this->serip.":".$this->port);
			$this->do_create_connection();
			debug(__FILE__." line:".__LINE__." connect when sendmsg".$this->serip.":".$this->port." ".($this->is_connect?"succ":"fail"));
			if ($this->is_connect == false) {
				return false;//此处如果一直连不上，应该报警
			}
		}
		$buf = $this->do_sendmsg($msg);
		if ($buf === false) {//连接断开
			debug(__FILE__." line:".__LINE__." do_sendmsg fail ".$this->serip.":".$this->port);
			if (!$this->is_connect) {
				usleep(200000);//0.2s
				debug(__FILE__." line:".__LINE__." reconnect ".$this->serip.":".$this->port);
				$this->do_create_connection();
				debug(__FILE__." line:".__LINE__." reconnect ".$this->serip.":".$this->port." ".($this->is_connect?"succ":"fail"));
				if (!$this->is_connect) {
					return false;
				}
			}
			$buf = $this->do_sendmsg($msg);
		}
		return $buf;
    }

    function sendmsg_without_returnmsg($msg) {
        $result = $this->write($msg, strlen ( $msg ) );
        if ($result) {
            return array ("result" => 0 );
        } else
            return array ("result" => 1003 );
    }

    function close() {
        $this->is_connect = false;
        socket_close ( $this->socket );
    }

    function is_connect() {
        return $this->is_connect;
    }

    function __destruct() {
    }
}
