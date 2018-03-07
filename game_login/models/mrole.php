<?php
class MRole extends CI_Model{
	
	public function __construct(){
		parent::__construct();
	}

	public function getMInform(){
		if(!isset($this->mInform)){
			$this->load->model('MInform');
			$this->mInform = new MInform($this);
		}
		return $this->mInform;
	}
}
