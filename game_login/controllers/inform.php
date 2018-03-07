<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Inform extends CI_Controller {
	
	public function __construct(){
		parent::__construct();
		$this->load->model('MRole');
		$this->mRole = new MRole();
	}

	public function index()
	{
		$data = array();
		return returnResult(CI_Controller::JSON, $data);
	}
	
	public function gethtml()
	{
		$data = array();
		return returnResult(CI_Controller::HTML, $data, 'inform');
	}
}

