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
 * Initialize the database
 *
 * @category	Database
 * @author		ExpressionEngine Dev Team
 * @link		http://codeigniter.com/user_guide/database/
 * @param 	string
 * @param 	bool	Determines if active record should be used or not
 */
function &DB($param = '', $active_record_override = NULL) {
	static $_handlers = array();
	if(isset($_handlers[$param]) && is_object($_handlers[$param])){
		return $_handlers[$param];
	}
	
	// Load the DB config file if a DSN string wasn't passed
	if(is_string($param) and strpos($param, '://') === FALSE){
		// Is the config file in the environment folder?
		if(!defined('ENVIRONMENT') or !file_exists($file_path = APPPATH . 'config/' . ENVIRONMENT . '/database.php')){
			if(!file_exists($file_path = APPPATH . 'config/database.php')){
				show_error('The configuration file database.php does not exist.');
			}
		}
		
		include ($file_path);
		
		if(!isset($db) or count($db) == 0){
			show_error('No database connection settings were found in the database config file.');
		}
		
		$params = array();
		foreach($db as $handler => $row){
			if(!is_array($row['database'])){
				$kname = strtolower($handler) . ucfirst(strtolower($row['database']));
				$params[$kname] = $row;
			}else{
				foreach($row['database'] as $dbname => $val){
					$kname = strtolower($handler) . ucfirst(strtolower($dbname));
					if(!in_array($kname, $params)){
						$tmp_row = $row;
						unset($tmp_row['database']);
						$tmp_row['database'] = $val;
						$params[$kname] = $tmp_row;
					}
				}
			}
		}
		if(!isset($params[$param])){
			load_class('Log')->write_log('error','can\'t find db_handler['.$param.']');
			exit;
		}
		$params = $params[$param];
	}
	
	// No DB specified yet?  Beat them senseless...
	if(!isset($params['dbdriver']) or $params['dbdriver'] == ''){
		show_error('You have not selected a database type to connect to.');
	}
	
	// Load the DB classes.  Note: Since the active record class is optional
	// we need to dynamically create a class that extends proper parent class
	// based on whether we're using the active record class or not.
	// Kudos to Paul for discovering this clever use of eval()
	

	if($active_record_override !== NULL){
		$active_record = $active_record_override;
	}
	
	require_once (BASEPATH . 'database/DB_driver.php');
	
	if(!isset($active_record) or $active_record == TRUE){
		require_once (BASEPATH . 'database/DB_active_rec.php');
		
		if(!class_exists('CI_DB')){
			eval('class CI_DB extends CI_DB_active_record { }');
		}
	}else{
		if(!class_exists('CI_DB')){
			eval('class CI_DB extends CI_DB_driver { }');
		}
	}
	
	require_once (BASEPATH . 'database/drivers/' . $params['dbdriver'] . '/' . $params['dbdriver'] . '_driver.php');
	
	// Instantiate the DB adapter
	$driver = 'CI_DB_' . $params['dbdriver'] . '_driver';
	$_handlers[$param] = new $driver($params);
	
	if($_handlers[$param]->autoinit == TRUE){
		$_handlers[$param]->initialize();
	}
	
	if(isset($params['stricton']) && $params['stricton'] == TRUE){
		$_handlers[$param]->query('SET SESSION sql_mode="STRICT_ALL_TABLES"');
	}
	
	return $_handlers[$param];
}



/* End of file DB.php */
/* Location: ./system/database/DB.php */