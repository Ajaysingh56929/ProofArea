<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Login extends CI_Controller {

	/**
	 * Index Page for this controller.
	 *
	 * Maps to the following URL
	 * 		http://example.com/index.php/welcome
	 *	- or -
	 * 		http://example.com/index.php/welcome/index
	 *	- or -
	 * Since this controller is set as the default controller in
	 * config/routes.php, it's displayed at http://example.com/
	 *
	 * So any other public methods not prefixed with an underscore will
	 * map to /index.php/welcome/<method_name>
	 * @see https://codeigniter.com/user_guide/general/urls.html
	 */
	public function __construct()
	{
		parent::__construct();
		$this->load->model('Login_model');
		$this->load->helper(array('form', 'url','url_helper'));
		$this->load->library(array('session'));
		//$this->session->set_userdata('userId',1);
	}

	public function checkLogin(){
		if(!empty($this->uri->segments[3])){
			$sessionKey = $this->uri->segments[3];
			$data = $this->Login_model->checkLogin($sessionKey);
			if($data){
				$this->session->set_userdata('userId',$data[0]->id);
				echo json_encode($data);
			}
		}
	}
	
}