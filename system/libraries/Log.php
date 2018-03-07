<?php
if(!defined('BASEPATH'))
	exit('No direct script access allowed');
/**
 * CodeIgniter
 *
 * An open source application development framework for PHP 5.1.6 or newer
 *
 * @package		CodeIgniter
 * @author		ExpressionEngine Dev Team
 * @copyright	Copyright (c) 2008 - 2011, EllisLab, Inc.
 * @license		http://codeigniter.com/user_guide/license.html
 * @link		http://codeigniter.com
 * @since		Version 1.0
 * @filesource
 */

// ------------------------------------------------------------------------


/**
 * Logging Class
 *
 * @package		CodeIgniter
 * @subpackage	Libraries
 * @category	Logging
 * @author		ExpressionEngine Dev Team
 * @link		http://codeigniter.com/user_guide/general/errors.html
 */
class CI_Log {
	
	protected $_log_path;
	protected $_threshold = 1;
	protected $_date_fmt = 'Y-m-d H:i:s';
	protected $_enabled = true;
	protected $_levels = array('ERROR' =>'1', 'NOTI' => '2', 'DEBUG' =>'3', 'INFO' =>'4', 'ALL' =>'5');
	
	/**
	 * Constructor
	 */
	public function __construct() {
		$config = & get_config();
		
		$this->_log_path = ($config['log_path'] != '')?$config['log_path']:APPPATH . 'logs/';
		
		if(!is_dir($this->_log_path) or !is_really_writable($this->_log_path)){
			$this->_enabled = FALSE;
		}
		
		if(is_numeric($config['log_threshold'])){
			$this->_threshold = $config['log_threshold'];
		}
		
		if($config['log_date_format'] != ''){
			$this->_date_fmt = $config['log_date_format'];
		}
	}
	
	// --------------------------------------------------------------------
	

	/**
	 * Write Log File
	 *
	 * Generally this function will be called using the global log_message() function
	 *
	 * @param	string	the error level
	 * @param	string	the error message
	 * @param	bool	whether the error is a native PHP error
	 * @return	bool
	 */
	public function write_log($level = 'error', $msg, $php_error = FALSE) {
		static $lineIndex = 0;
		if($this->_enabled === FALSE){
			return FALSE;
		}
		
		$level = strtoupper($level);
		
		if(!isset($this->_levels[$level]) or ($this->_levels[$level] > $this->_threshold)){
			return FALSE;
		}

		$cur_logpath = $this->_log_path . date('Ymd');
		if (! is_dir($cur_logpath)) {
			$old = umask(0);
			mkdir ($cur_logpath, 0777, true);
			umask ($old);
		}	
		$filepath = $cur_logpath . '/' . 'log-' . date('Y-m-d-H') . '.php';
		$message = '';

		$new_created = false;
		if(!file_exists($filepath)){
			$message .= "<" . "?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed'); ?" . ">\n\n";
			$new_created = true;
		}
/*
		if($lineIndex == 0){
			$message .= "=================================================================================================================\n\n\n";
		}
*/		
		if(!$fp = fopen($filepath, 'a')){
			return FALSE;
		}
		if ($new_created == true) {
			chmod($filepath, 0777);
		}
/*
		$trace = debug_backtrace();
		foreach($trace as $call){
			if($call){
				$message .= sprintf('%06d [%s][%s] %s[%d] ', $lineIndex ++, date($this->_date_fmt),$level, isset($call['file'])?str_replace(array(rtrim(realpath(BASEPATH), '/'), rtrim(realpath(APPPATH), '/')), array('system:', 'app:'), $call['file']):'', isset($call['line'])?$call['line']:'');
				$call['type'] = isset($call['type'])?$call['type']:'x';
				switch($call['type']){
					case '->':
						$message .= $call['class'] . '->' . $call['function'];
					break;
					case '::':
						$message .= $call['class'] . '::' . $call['function'];
					break;
					default:
						$message .= $call['function'];
				}
				$message .= "\n";
			}
		}
*/
		$message .= date($this->_date_fmt).' '.$level.' MSG:'.$msg."\n";
		//$message .= "\n\n";
		

		flock($fp, LOCK_EX);
		fwrite($fp, $message);
		//flush($fp);
		flock($fp, LOCK_UN);
		fclose($fp);
		
		return TRUE;
	}

}
// END Log Class

/* End of file Log.php */
/* Location: ./system/libraries/Log.php */
