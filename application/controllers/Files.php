<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Files extends CI_Controller {

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
		$this->load->model('File_model');
		$this->load->helper(array('form', 'url','url_helper'));
		$this->load->library(array('session','upload'));
		$this->session->set_userdata('userId',1);
	}
	public function getCss(){
		header("Content-type: text/css; charset: UTF-8");
		$userAppData = $this->File_model->getCssModel($this->getUserId());
		$appColor = '#EE4884';
		foreach ($userAppData as $row) {
			echo '.u_'.$row->id.':after{content:"'.substr($row->firstname,0,1).'";}';
			$name = $row->firstname." ".$row->lastname;
			echo '.u_name'.$row->id.':after{content:"'.$name.'"}';
			echo '.u_email'.$row->id.':after{content:"'.$row->email.'"}';
			echo '.u_back'.$row->id.'{background-color:'.$row->color.' !important; }';
		}
	    
		echo ".rightHeaderPanel span.topIconCls:hover,.dropdown.mainUploadBtn,.progressBar,.dropbtn.selectedBtn,.stokeWidth.selected,.stokeWidth.selected:hover,.message-send,.chatbox-icons .fa,.chatIcon,.launch a,.leftNav,.input-group-addon,.mainPanel .heading,.inputJsonDataHeading,.btn-default,.btn-primary,.nextFileBtn:hover,.updatebtn,.filterMenuOption .active,.tabsActiveCls,.postComment, .replyComment{
			background-color: ".$appColor.";	
		}
		.changeView i,ul.tabs li a,span.clearHistory i,footer,.loaderspin.panel i,.breadCrumb,.selected i,.btnSelected i,.btnSelected{
			color:".$appColor.";
		}
		.active,.form-control,.form-control:focus,input[type=text]:focus, input[type=password]:focus,.mainImageView,.btn-primary{
			border-color: ".$appColor.";
		}
		.commentListBorder {
			border-left-color: ".$appColor." !important;
		}
		.chatIcon {
			box-shadow: 0 0 3px 0px ".$appColor.";
		}

		/*canvas css */

		.btnSelected{
			background-color: ".$appColor.";	
		}
		.stokeWidth.btnSelected{
			background-color: ".$appColor." !important;	
		}";
	}
	public function getAccount(){
		$AccountData = $this->File_model->getAccountSize($this->getUserId());
		echo json_encode(array('total_size' =>$this->File_model->formatSizeUnits($AccountData[0]->total_size,''),'total_percentage'=>$this->File_model->formatSizeUnits($AccountData[0]->total_size,2)));
	}
	public function getFiles()
	{
		$imageId = null;
		if(!empty($this->uri->segments[3])){
			$imageId = $this->uri->segments[3];
		}
		$loginId = $this->getUserId();
		$fileData = $this->File_model->getAllFiles($loginId,$imageId);
		if($fileData['status']){
			foreach($fileData['data'] AS $item){
                $data[] = array(
                    'id' => (int)$item->id,
                    'image_name' =>$item->image,
                    'image' => base_url().'uploadImage/'.$item->image,
                    'imgcount' => (int)$item->imgcount,
                    'share' => explode(',',$item->share),
                    'size' => $this->File_model->formatSizeUnits($item->size,''),
                    'ext' => $item->dataUrl,
                    'approve' => (int)$item->approve,
                    'userId' => (int)$item->userId,
                    'uploaded_date' => $item->uploaded_date,
                );
            }
			echo json_encode($data);
		}else{
			echo json_encode(array());
		}
	}
	public function getComments()
	{
		if(!empty($this->uri->segments[3])){
			$imageId = $this->uri->segments[3];
			$fileCommentData = $this->File_model->getAllComments($imageId);
			if($fileCommentData['status']){
				echo json_encode($fileCommentData['data']);
			}else{
				echo json_encode(array());
			}
		}
	}
	public function postcomment()
	{
		$commentData = json_decode(file_get_contents("php://input"), true);
		$fileCommentData = $this->File_model->addComment($commentData,$this->getUserId());
		if($fileCommentData['status']){
			echo json_encode(array('data'=>$fileCommentData['data'],'notification'=>$fileCommentData['notification']));
		}else{
			echo json_encode(array());
		}
	}
	private function getUserId(){
		$sessionRecord = $this->session->all_userdata();
		return $sessionRecord['userId'];
	}
	public function uploadImage(){
		if(!empty($_FILES)){
			$fileName = $_FILES['afile']['name'];
	        $file_size =$_FILES['afile']['size'];
	        $file_tmp =$_FILES['afile']['tmp_name'];
	        $fileType=$_FILES['afile']['type'];
	        $fileContent = file_get_contents($_FILES['afile']['tmp_name']);
	        $dataUrl = 'data:' . $fileType . ';base64,' . base64_encode($fileContent);
	        $imageFileType = strtolower(pathinfo("uploadImage/".$fileName,PATHINFO_EXTENSION));
	        if($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg" && $imageFileType != "gif"/*&& $imageFileType != "pdf"*/) {
	            $json = json_encode(array(
	                'status' => false,
	                'msg' => "Sorry, only JPG, JPEG, PNG & GIF files are allowed."
	            ));
	            echo $json;   
	        }else{
	            move_uploaded_file($file_tmp,"uploadImage/".$fileName);
	            $fileData = array('userId'=>(int)$this->getUserId(),'image'=>$fileName,'uploaded_date'=>date('c'),'dataUrl'=>$imageFileType,'share'=>$this->getUserId(),'size'=>$file_size);
	            $fileResponse = $this->File_model->uploadFiles($fileData);
	            if($fileResponse['status']){
	            	foreach($fileResponse['data'] AS $item){
		                $data[] = array(
		                    'id' => (int)$item->id,
		                    'image_name' =>$item->image,
		                    'image' => base_url().'uploadImage/'.$item->image,
		                    'imgcount' => (int)$item->imgcount,
		                    'share' => explode(',',$item->share),
		                    'size' => $this->File_model->formatSizeUnits($item->size,''),
		                    'ext' => $item->dataUrl,
		                    'approve' => (int)$item->approve,
		                    'userId' => (int)$item->userId,
		                    'uploaded_date' => $item->uploaded_date,
		                );
		            }
					echo json_encode($data);
				}else{
					echo json_encode(array());
				}
			}
		}
		
	}
	public function getNotification(){
		$notificationData = $this->File_model->notificationModel($this->getUserId());
		if($notificationData['status']){
			echo json_encode($notificationData['data']);
		}else{
			echo json_encode(array());
		}
	}
	public function getNotificationCount(){
		$notificationData = $this->File_model->notificationCountModel($this->getUserId());
		echo json_encode(array("count"=>(int)$notificationData['data'][0]->notificationCount));
	}
	public function shareFile(){
		$data = json_decode(file_get_contents("php://input"), true);
		$shareRec = $this->File_model->shareImagemodel($data,$this->getUserId());
		echo json_encode($shareRec);
	}
	public function unlink(){
		$data = json_decode(file_get_contents("php://input"), true);
		$unshareRec = $this->File_model->unsharemodel($data,$this->getUserId());
		echo json_encode($unshareRec);
	}
	public function deleteimage(){
		$imageId = $this->uri->segments[3];
		$deleteRecord = $this->File_model->deleteImagemodel($imageId,$this->getUserId());
		echo json_encode($deleteRecord);
	}
	
}
