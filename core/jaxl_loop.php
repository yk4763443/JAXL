<?php 
/**
 * Jaxl (Jabber XMPP Library)
 *
 * Copyright (c) 2009-2012, Abhinav Singh <me@abhinavsingh.com>.
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions
 * are met:
 *
 * * Redistributions of source code must retain the above copyright
 * notice, this list of conditions and the following disclaimer.
 *
 * * Redistributions in binary form must reproduce the above copyright
 * notice, this list of conditions and the following disclaimer in
 * the documentation and/or other materials provided with the
 * distribution.
 *
 * * Neither the name of Abhinav Singh nor the names of his
 * contributors may be used to endorse or promote products derived
 * from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS
 * FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
 * COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT,
 * INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING,
 * BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER
 * CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRIC
 * LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN
 * ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 *
 */

/**
 *
 * Enter description here ...
 * @author abhinavsingh
 *
 */
class JAXLLoop {
	
	private static $is_running = false;
	private static $active_fds = 0;
	
	private static $read_fds = array();
	private static $read_cbs = array();
	private static $write_fds = array();
	private static $write_cbs = array();
	
	private static $secs = 1;
	private static $usecs = 0;
	
	private static $clock = 0;
	
	private function __construct() {}
	private function __clone() {}
	
	public static function watch($fd, $opts) {
		if(isset($opts['read'])) {
			$fdid = (int) $fd;
			self::$read_fds[$fdid] = $fd;
			self::$read_cbs[$fdid] = $opts['read'];
			++self::$active_fds;
		}
		
		if(isset($opts['write'])) {
			$fdid = (int) $fd;
			self::$write_fds[$fdid] = $fd;
			self::$write_cbs[$fdid] = $opts['write'];
			++self::$active_fds;
		}
	}
	
	public static function unwatch($fd, $opts) {
		if(isset($opts['read'])) {
			$fdid = (int) $fd;
			unset(self::$read_fds[$fdid]);
			unset(self::$read_cbs[$fdid]);
			--self::$active_fds;
		}
		
		if(isset($opts['write'])) {
			$fdid = (int) $fd;
			unset(self::$write_fds[$fdid]);
			unset(self::$write_cbs[$fdid]);
			--self::$active_fds;
		}
	}
	
	public static function run() {
		if(!self::$is_running) {
			self::$is_running = true;
			while(self::$active_fds > 0)
				self::select();
		}
	}
	
	private static function select() {
		$read = self::$read_fds;
		$write = self::$write_fds;
		$except = null;
		
		$changed = @stream_select($read, $write, $except, self::$secs, self::$usecs);
		
		if($changed === false) {
			_debug("error while selecting stream for read");
			//print_r(stream_get_meta_data(self::fd));
			//self::disconnect();
			//return;
			exit;
		}
		else if($changed === 0) {
			//_debug("nothing changed while selecting for read");
			self::$clock = self::$secs + self::$usecs/pow(10,6);
		}
		else if($changed > 0) {
			// read callback
			foreach($read as $r) {
				$fdid = array_search($r, self::$read_fds);
				_debug("calling read cb");
				call_user_func(self::$read_cbs[$fdid]);
			}
			
			// write callback
			foreach($write as $w) {
				$fdid = array_search($w, self::$write_fds);
				_debug("calling write cb");
				call_user_func(self::$write_cbs[$fdid]);
			}
		}
	}
	
}

?>
