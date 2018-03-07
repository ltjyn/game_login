<?php
class mInform extends CI_Model{
	
	private $mRole;
	public function __construct($mRole){
		parent::__construct();
		if(isset($mRole)){
			$this->mRole = $mRole;
		}
		$this->dbrLog = $this->load->database('dbrLog');
	}

}
