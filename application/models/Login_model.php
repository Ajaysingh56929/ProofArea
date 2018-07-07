<?php
if(!defined('BASEPATH')) exit('No direct script access allowed');

class Login_model extends CI_Model{

	public function __construct()
	{
		parent::__construct();
		$this->load->database();
		$this->tb_user=$this->db->dbprefix('tb_user');
	}
	public function checkLogin($apiKey){
		$sql = 'SELECT * FROM '.$this->tb_user. ' Where api_key ="'.$apiKey.'"';
		$query=$this->db->query($sql);
        if($query->num_rows()>0){
        	return $query->result();
        }
	}
}