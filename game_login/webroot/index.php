<?php
/*---------------------------------------------------------------
 * APPLICATION ENVIRONMENT
 *---------------------------------------------------------------
 *
 * You can load different configurations depending on your
 * current environment. Setting the environment also influences
 * things like logging and error reporting.
 *
 * This can be set to anything, but default usage is:
 *
 *     development
 *     testing
 *     production
 *
 * NOTE: If you change these, also change the error_reporting() code below
 *
 */
	define('ENVIRONMENT', 'development');
/*
 *---------------------------------------------------------------
 * SYSTEM FOLDER NAME
 *---------------------------------------------------------------
 *
 * This variable must contain the name of your "system" folder.
 * Include the path if the folder is not in the same  directory
 * as this file.
 *
 */
	if(!file_exists('../config/path_config.php')){
		echo('not find path_config file!please create and configure it!');
		exit;
	}

	define('PATH_BASE',dirname(dirname(dirname(__FILE__))));
	require_once '../config/path_config.php';
	$system_path = rtrim($path_config['system'],'/').'/';
define('BASEPATH', str_replace("\\", "/", $system_path));
/*
 *---------------------------------------------------------------
 * APPLICATION FOLDER NAME
 *---------------------------------------------------------------
 *
 * If you want this front controller to use a different "application"
 * folder then the default one you can set its name here. The folder
 * can also be renamed or relocated anywhere on your server.  If
 * you do, use a full server path. For more info please see the user guide:
 * http://codeigniter.com/user_guide/general/managing_apps.html
 *
 * NO TRAILING SLASH!
 *
 */
	$application_folder = rtrim($path_config['application'],'/').'/';
require_once BASEPATH.'interface/web.php';

/* End of file index.php */
/* Location: ./index.php */
