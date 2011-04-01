<?php
/**
 * 
 * 教师类：
 * @author jac,wepeng
 * @access public
 * @copyright 广西大学计网081
 * @version 0.1
 */
class Teacher extends Zend_Db
{
	protected $db;
	
	public function __construct()
	{
		$this->db = Zend_Registry::get('db');
	}
	
	public function isLeader($id)
	{
		$sql = "select level_id from teacher where id='".$id."'";
		$result = $this->db->query($sql)->fetchAll();
		if($result[0]['level_id'] == 2)
		{
			return true;
		}
		else
		{
			return false;
		}
	}
	
	public function isAdmin($id)
	{
		$sql = "select level_id from teacher where id='".$id."'";
		$result = $this->db->query($sql)->fetchAll();
		if($result[0]['level_id'] == 3)
		{
			return true;
		}
		else
		{
			return false;
		}
	}
	
	public function searchLeader($name)
	{
		$sql = "select id,username,password,name,sex  from teacher where level_id='2' and name='".$name."'";
		$result = $this->db->query($sql)->fetchAll();
		if(count($result)>0)
		{	return $result;
			//print_r($result);
		}
		return NULL;
	}
	
	public function getLeaderList()
	{
		$sql = "select *  from teacher where level_id='2'";
		$result = $this->db->query($sql)->fetchAll();
		if(count($result)>0)
			return $result;
		return NULL;
	}
	
	public function searchStudentInfo($value,$key)
	{
		$sql = "select * from student where ".$key."='".$value."'";
		$result = $this->db->query($sql)->fetchAll();
		if(count($result)>0)
			return $result;
		return NULL;
	}
	
	public function addstudent($username,$name,$sex,$password)
	{
		$sql = "insert into student (username,name,sex,password) 
			values ('".$username."','".$name."','".$sex."','".$password."')";
		$result = $this->db->query($sql);
		if(!$result)
			return NULL;
		else
			return 1;
			
	}
//login-qin
	public function getStudentByClassID($class_id)
	{
		$result = array();
		$sql = 'SELECT s.id, s.username, s.name, s.sex, s.password, c.class_name
			FROM Student s, class c, class_student cs
			WHERE s.id = cs.student_id
			AND c.id = cs.class_id
			AND c.id = '.$class_id.''
			;
		$result = $this->db->query($sql)->fetchAll();
		return $result;
	}

	public  function getClass($teacher_id, $level_id) 
	{
		$result = array();
		switch ($level_id)
		{
		case 3: //Admin and Leader return all classes
			$sql = 'SELECT c.id, c.class_name FROM class c ORDER BY c.class_name';
			break;
		case 2: //Admin and Leader return all classes
			$sql = 'SELECT c.id, c.class_name FROM class c ORDER BY c.class_name';
			break;
		case 1: //teacher return his class 
			$sql = 'SELECT c.id, c.class_name FROM class c WHERE c.teacher_id = '.$teacher_id.'';
			break;
		default:
			$result = NULL;
			break;
		}
		$result = $this->db->query($sql)->fetchAll();
		return $result;
	}

	function getTeacher()
	{
		$sql = 'SELECT t.id, t.username, t.name, t.sex, t.level_id 
			FROM teacher t 
			WHERE t.level_id = 1';
		return $this->db->query($sql)->fetchAll();
	}

	function searchTeacher($name)
	{
		$sql = 'SELECT id,username,password,name,sex  
			FROM teacher WHERE level_id=1 AND name like "%'.$name.'%"';
		return $this->db->query($sql)->fetchAll();
	}
}
