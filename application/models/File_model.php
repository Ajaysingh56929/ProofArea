<?php
if(!defined('BASEPATH')) exit('No direct script access allowed');

class File_model extends CI_Model{

	public function __construct()
	{
		parent::__construct();
		$this->load->database();
		$this->tb_user_images=$this->db->dbprefix('tb_user_images');
		$this->tb_comment=$this->db->dbprefix('tb_comment');
		$this->tb_user=$this->db->dbprefix('tb_user');
		$this->tb_notification=$this->db->dbprefix('tb_notification');
	}
	public function getCssModel($loginId){
        if(!empty($loginId)){
            $sql = 'SELECT * FROM '.$this->tb_user.' where id="'.$loginId.'"';
        }else{
            $sql = 'SELECT * FROM '.$this->tb_user;
        }
		$query=$this->db->query($sql);
        if($query->num_rows()>0){
        	return $query->result();
        }
	}
	public function getAccountSize($loginId){
		$totalSizeSql = 'SELECT sum(size) as total_size FROM '.$this->tb_user_images.' WHERE userId='.$loginId;
		return $this->db->query($totalSizeSql)->result();
	}
	public function getAllFiles($loginId,$imageId){
		if(!empty($imageId)){
			$where = 'WHERE find_in_set('.$loginId.',share) <> 0 AND u.id='.$imageId.' group by u.id DESC';
		}else{
			$where = 'WHERE find_in_set('.$loginId.',share) <> 0 group by u.id DESC';
		}
		$sql = 'SELECT u.*, count(c.id) as imgcount FROM '.$this->tb_user_images.' as u LEFT JOIN tb_comment as c on u.id=c.imageId '.$where;
        $query=$this->db->query($sql);
        if($query->num_rows()>0){
        	return array('status' => true,'data'=>$query->result());
		}else{
			return array('status' => false);
		}
	}
	public function getAllComments($imageId){
		$sql = 'SELECT *
		 FROM tb_comment WHERE imageId='.$imageId;
		$query=$this->db->query($sql);
        if($query->num_rows()>0){
        	return array('status' => true,'data'=>$query->result());
		}else{
			return array('status' => false);
		}
	}
	public function addComment($commentRec,$loginId){
		
		if(!empty($commentRec['replyId'])){
	      $replyId = $commentRec['replyId'];
	    }else{
	      $replyId = 0;
	    }
	    $data = array_merge($commentRec,array('userId' =>(int)$loginId,'replyId'=>(int)$replyId,'posted_date'=>date('c')));
	    $file = 'uploadingFiles/user_'.$_SESSION['userId'].'_'.uniqid().'.txt';
	    $handle = fopen($file, 'w') or die('Cannot open file:  '.$file);
	    fwrite($handle, $commentRec['dataUrl']);
	    fclose($handle);
		$this->db->insert($this->tb_comment,$data);
	    $sql = 'SELECT * FROM tb_comment WHERE id='.$this->db->insert_id();
		$query=$this->db->query($sql);
        if($query->num_rows()>0){
        	$notifi_to = $this->setNotification("addComment",$query->result(),$loginId);
        	return array('status' => true,'data'=>$query->result(),'notification' => array('notifi_record' =>$notifi_to));
		}else{
			return array('status' => false);
		}
	}
	public function uploadFiles($data){
		$this->db->insert($this->tb_user_images,$data);
		$sql = 'SELECT u.*, count(c.id) as imgcount FROM tb_user_images as u LEFT JOIN tb_comment as c on u.id=c.imageId WHERE u.id='.$this->db->insert_id().' group by u.id DESC';
		$query=$this->db->query($sql);
        if($query->num_rows()>0){
        	return array('status' => true,'data'=>$query->result());
		}else{
			return array('status' => false);
		}
	}
	public function unsharemodel($data,$loginId){

		$sql = 'SELECT * FROM '.$this->tb_user_images.' WHERE id='.$data['imageId'];
		$notificationData = $this->db->query($sql)->result();
        $notificationData = array('id'=>(int)$notificationData[0]->id,'userId'=>(int)$notificationData[0]->userId,'ext'=>$notificationData[0]->dataUrl,'image_name'=>$notificationData[0]->image,'dataUrl'=>$notificationData[0]->dataUrl,'share'=>$notificationData[0]->share,'image'=>base_url().'uploadImage/'.$notificationData[0]->image,'size'=>$this->formatSizeUnits($notificationData[0]->size,''),'approve'=>(int)$notificationData[0]->approve,'uploaded_date'=>$notificationData[0]->uploaded_date,'unshare'=>(int)$data['userId']);
        $notifi_to = $this->setNotification("unshare",$notificationData,$loginId);
        $notificationData['share'] = explode(',',$notificationData['share']);
        $this->db->where('id', $data['imageId']);
		$this->db->update($this->tb_user_images, array('share' => implode(',',$data['share'])));
        return array("status"=>true,'share' => $data['share'],'userId'=>(int)$notificationData['userId'],'notification' => array('notifi_record' =>$notifi_to,'data'=>$notificationData));

	}
	public function deleteImagemodel($imageId,$loginId){

		$sql = 'SELECT * FROM '.$this->tb_user_images.' WHERE id='.$imageId;
        $notificationData = $this->db->query($sql)->result();
        $notificationData = array('id'=>(int)$notificationData[0]->id,'userId'=>(int)$notificationData[0]->userId,'ext'=>$notificationData[0]->dataUrl,'image_name'=>$notificationData[0]->image,'dataUrl'=>$notificationData[0]->dataUrl,'share'=>$notificationData[0]->share,'image'=>base_url().'uploadImage/'.$notificationData[0]->image,'size'=>$this->formatSizeUnits($notificationData[0]->size,''),'approve'=>(int)$notificationData[0]->approve,'uploaded_date'=>$notificationData[0]->uploaded_date);
        
        $notifi_to = $this->setNotification("delete",$notificationData,$loginId);
        
        $notificationData['share'] = explode(',',$notificationData['share']);

		$this->db->where('id', $imageId);
		$this->db->delete($this->tb_user_images);

        $sql = 'SELECT * FROM tb_comment WHERE imageId='.$imageId;
        $query=$this->db->query($sql);
        if($query->num_rows()>0){
        	foreach ($query->result() as $Item) {
        		unlink($Item->dataUrl);
        	}
        }
        $this->db->where('imageId', $imageId);
		$this->db->delete($this->tb_comment);
        return array("status"=>true,'notification' => array('notifi_record' =>$notifi_to,'data'=>$notificationData));
	}
	public function shareImagemodel($data,$loginId){
		$sql = 'SELECT * FROM tb_user WHERE email="'.$data['share'].'"';
        $query=$this->db->query($sql);
		if($query->num_rows()>0){
			$row = $query->result();
            $getImageSqlQuery = 'SELECT * from '.$this->tb_user_images.' WHERE id='.$data['id'];
            $query = $this->db->query($getImageSqlQuery);
            if($query->num_rows()>0){
                $imageRow = $query->result();
				$shareIds = explode(',',$imageRow[0]->share);
                if(!in_array($row[0]->id, $shareIds)){
                	$userId = array($row[0]->id);
                    $arrayMerge = array_merge($shareIds,$userId);
                    $finalArray = implode(',',$arrayMerge);
                    $this->db->where('id',(int)$data['id']);
                    $this->db->update($this->tb_user_images, array('share' => $finalArray));
                    $sql = 'SELECT * FROM '.$this->tb_user_images.' WHERE id='.$data['id'];
					$notificationData = $this->db->query($sql)->result();
                    $notificationData = array('id'=>(int)$notificationData[0]->id,'ext'=>$notificationData[0]->dataUrl,'image_name'=>$notificationData[0]->image,'userId'=>(int)$notificationData[0]->userId,'dataUrl'=>$notificationData[0]->dataUrl,'share'=>$notificationData[0]->share,'image'=>base_url().'uploadImage/'.$notificationData[0]->image,'size'=>$this->formatSizeUnits($notificationData[0]->size,''),'approve'=>(int)$notificationData[0]->approve,'uploaded_date'=>$notificationData[0]->uploaded_date);
                    $notifi_to = $this->setNotification("share",$notificationData,$loginId);
					$notificationData['share'] = explode(',',$notificationData['share']);
                    return array("status"=>true,'share' => $arrayMerge,'userId'=>(int)$notificationData['userId'],'notification' => array('notifi_record' =>$notifi_to,'data'=>$notificationData));
                }else{
                    return array("status"=>false,"msg"=>"Files already shared with this user");
                }
            }
		}else{
            return array("status"=>false,"msg"=>$post_vars['share']." emailId doesn't exist");
        }
	}
	/* Notication Sql*/
	public function notificationCountModel($loginId){
		$sql = 'SELECT count(*) as notificationCount FROM '.$this->tb_notification.' where updated_by <> '.$loginId.' AND find_in_set('.$loginId.',notifi_to) AND !find_in_set('.$loginId.',count_show)';
		$query=$this->db->query($sql);
		if($query->num_rows()>0){
			if($query->num_rows()>0){
	        	return array('status' => true,'data'=>$query->result());
			}else{
				return array('status' => false);
			}
		}
    }
	public function notificationModel($loginId){
		$sql = 'SELECT * FROM '.$this->tb_notification.' where updated_by <> '.$loginId.' AND find_in_set('.$loginId.',notifi_to) order by id desc';
		$query=$this->db->query($sql);
		if($query->num_rows()>0){
            foreach ($query->result() as $Item) {
                $countUpdateArray = explode(',',$Item->count_show);
                $viewStaus = true;
                if(!in_array((int)$loginId,$countUpdateArray)){
                    $userId = array($loginId);
                    $arrayMerge = array_merge($countUpdateArray,$userId);
                    $finalArray = implode(',',$arrayMerge);
                    $this->db->where('id',(int)$Item->id);
                    $this->db->update($this->tb_notification, array('count_show' => $finalArray));
                    $viewStaus = false;
                }
                $data[] = array(
                    'id' => (int)$Item->id,
                    'status' => $Item->status,
                    'action' => $Item->action,
                    'content' => json_decode($Item->content),
                    'updated_by' => (int)$Item->updated_by,
                    'notifi_to' => $Item->notifi_to,
                    'count_show' => $Item->count_show,
                    'updated_date' =>$Item->updated_date,
                    'view_status' =>$viewStaus
                );
            }
            return array('status' => true,'data'=>$data);
		}else{
			return array('status' => false);
		}
    }

    public function setNotification($action,$data,$userId){
    	if($action=="delete"){
    		$this->db->insert($this->tb_notification,array('status' => 'delete', 'action' => 'file_delete', 'content' => (string)json_encode($data), 'updated_by' => $userId, 'notifi_to' => $data['share'], 'count_show' => $userId, 'updated_date' => date('c') ));
            return $this->getNotificationRec($this->db->insert_id());
    	}
        if($action=="approved"){
            $notiSql = "INSERT INTO ".$this->tb_notification." (status,action,content,updated_by,notifi_to,count_show,updated_date) VALUES ('update','file_approve','".(string)json_encode($data)."',".(int)$_SESSION['userId'].",'".$data['share']."','".$userId."','".date('c')."')";
            return $this->getNotificationRec($this->db->insert_id(),$prevconn);
        }
        if($action=="share"){
        	$this->db->insert($this->tb_notification,array('status' => 'update', 'action' => 'file_share', 'content' => (string)json_encode($data), 'updated_by' => $userId, 'notifi_to' => $data['share'], 'count_show' => $userId, 'updated_date' => date('c') ));
            return $this->getNotificationRec($this->db->insert_id());
        }
        if($action=="unshare"){
        	$this->db->insert($this->tb_notification,array('status' => 'update', 'action' => 'file_unshare', 'content' => (string)json_encode($data), 'updated_by' => $userId, 'notifi_to' => $data['share'], 'count_show' => $userId, 'updated_date' => date('c') ));
            return $this->getNotificationRec($this->db->insert_id());
        }
        if($action=="addComment"){
        	$sql = 'SELECT * FROM '.$this->tb_user_images.' WHERE id='.$data[0]->imageId;
            $notificationData = $this->db->query($sql)->result();
            $arrayMerge = array_merge($notificationData,array('comment'=>htmlentities($data[0]->comment)));
            $this->db->insert($this->tb_notification,array('status' => 'add', 'action' => 'comment_add', 'content' => (string)json_encode($arrayMerge[0]), 'updated_by' => $userId, 'notifi_to' => $notificationData[0]->share, 'count_show' => $userId, 'updated_date' => date('c') ));
            $returnRec = $this->getNotificationRec($this->db->insert_id());
            return array_merge($returnRec,array('image'=>base_url().'uploadImage/'.$notificationData[0]->image));
        }
        if($action=="deletecomment"){
            $notiSql = "INSERT INTO ".$this->tb_notification." (status,action,content,updated_by,notifi_to,count_show,updated_date) VALUES ('deletecomment','comment_delete','".(string)json_encode($data)."',".(int)$_SESSION['userId'].",'".$data['share']."','".$userId."','".date('c')."')";
            return $this->getNotificationRec($this->db->insert_id(),$prevconn);
        }
    }
    public function getNotificationRec($insertId){
        $sql = 'SELECT * FROM '.$this->tb_notification.' where id='.$insertId;
        $row = $this->db->query($sql)->result();
        return array(
        	'id' =>(int)$row[0]->id,
        	'status' =>$row[0]->status,
        	'action' =>$row[0]->action,
        	'updated_by' =>(int)$row[0]->updated_by,
        	'notifi_to' =>$row[0]->notifi_to,
        	'count_show' =>$row[0]->count_show,
        	'updated_date' =>$row[0]->updated_date,
        );
    }
    public function formatSizeUnits($bytes,$percent){
		if(!empty($percent)){
			$totalBytes = $percent*1024*1024*1024;
			return ($bytes/$totalBytes)*100;
		}
        if ($bytes >= 1073741824)
        {
            $bytes = number_format($bytes / 1073741824, 2) . ' GB';
        }
        elseif ($bytes >= 1048576)
        {
            $bytes = number_format($bytes / 1048576, 2) . ' MB';
        }
        elseif ($bytes >= 1024)
        {
            $bytes = number_format($bytes / 1024, 2) . ' KB';
        }
        elseif ($bytes > 1)
        {
            $bytes = $bytes . ' bytes';
        }
        elseif ($bytes == 1)
        {
            $bytes = $bytes . ' byte';
        }
        else
        {
            $bytes = '0 bytes';
        }
        return $bytes;
	}
}