<?php
require_once 'Zend/Controller/Action.php';
require_once 'Zend/Json.php';
require_once 'lib/choice.class.php';
/**
 * 
 * 教师控制类,包含普通教师、系主任和管理员
 * @author Jac,wepeng
 * 
 */
class TeacherController extends Zend_Controller_Action
{   
 
	function init()
	{
		Zend_Loader::loadClass('Teacher',realpath(dirname(__FILE__) . '/../models'));
		Zend_Loader::loadClass('System',realpath(dirname(__FILE__) . '/../models'));
		Zend_Loader::loadClass('Examination',realpath(dirname(__FILE__) . '/../models'));
		$this->sys = new System();
		$this->teacher = new Teacher();
		$this->examSession = new Zend_Session_Namespace('examSession');
		$this->examination = new Examination();
		$showlist = file_get_contents("../application/views/scripts/teacher/teacher".$this->examSession->level_id.".phtml");
		$this->quickmenuAction($showlist);

	}
	
	function indexAction()
	{
		$this->sys->checkLogined();
	}
	
	function loginAction()
	{
		if(!($this->examSession->username && $this->examSession->password))
		{
			if(isset($_POST['username']) && isset($_POST['password']))
			{
				$result = $this->sys->login($_POST['username'], $_POST['password'], 'teacher');
				if($result)
				{
					$this->examSession->username = $_POST['username'];
					$this->examSession->password = $_POST['password'];
					$this->examSession->teacher_id = $result[0]['id'];
					$this->examSession->name = $result[0]['name'];
					$this->examSession->level_id = $result[0]['level_id'];
					$this->examSession->action = 'teacher';
				}	
			}
		}
		
		header('Location: ./');
	}
	
	/**
	 * 
	 * 设置考试
	 */
	function setexamAction()
	{	
		$this->sys->checkLogined();
		

		//显示试卷所有分类名
		$result = $this->examination->getPaperCategory();
		$categoryList = "";
		if(count($result)>0)
		{
			$this->view->categoryList = $result;
		}
		//查看试卷分类
		if(isset($_POST['category_id']))
		{
			$paperList = $this->examination->getPaperListByCategory($_POST['category_id']);
			if(is_array($paperList))
			{
				foreach($paperList as $value)
				{
					echo "<a href=\"JavaScript:setPaperName('".$value['title']."','".$value['id']."')\">".$value['title']."</a><br/>";
				}
			}
			exit;
		}
		//搜索试卷
		if(isset($_POST['searchByName']))
		{
			$searchPaperList = $this->examination->searchPaper($_POST['searchByName']);
			if(!is_array($searchPaperList))
			{
				echo  "没有找到!";
			}
			else 
			{
				foreach($searchPaperList as $value) 
				{
					echo "<a href=\"JavaScript:setPaperName('".$value['title']."','".$value['id']."')\">".$value['title']."</a><br/>";
				}
			}
			exit;
		}
	
		//显示教师管理的班级
		$classList = $this->examination->getClassOfTeacher($this->examSession->teacher_id);
		$this->view->showClassList = $classList;
		
		//提交考试设置数据
		if(isset($_POST['paperId']) && isset($_POST['paperCategoryID']) )
		{
			$paper_name = $_POST['paperName'];
			$paper_id = $_POST['paperId'];
			$category_id = $_POST['paperCategoryID'];
			$rejectStus = "";
			if(isset($_POST['rejectStus']))
				$rejectStus = $_POST['rejectStus'];
			$otherStus = "";
			if(isset($_POST['otherStus']))
				$otherStus = $_POST['otherStus'];
			$startTime = $_POST['startTime'];
			$endTime = $_POST['endTime'];
			$class_ids = "";
			if(isset($_POST['class_id']))
			{
				$class_ids = $_POST['class_id'];
			}
			echo "<div>";
			echo "试卷名：".$paper_name."<br/>";
			echo "类别：".$category_id."<br/>";
			if(isset($_POST['class_id']))
			{
				foreach ($_POST['class_id'] as $value)
				{
					echo "班级：".$value."<br/>";
				}
			}
			echo "排除的学生：".$rejectStus."<br/>";
			echo "另外允许的学生：".$otherStus."<br/>";
			echo "开始时间：".$startTime."<br/>";
			echo "结束时间：".$endTime."<br/>";
			echo "</div>";
			
			$this->examination->saveExamSetData($paper_id, $paper_name, $category_id, $startTime, $endTime, $class_ids, $rejectStus, $otherStus);
			
		}
	}
	
	/**
	 * 
	 * 设置重考
	 */
	function setreexamAction()
	{	
		$this->sys->checkLogined();
		
		$result = $this->examination->getRecentlyExam();
		$this->view->getRecentlyExamList = $result;
		
		if(isset($_GET['exam_id']))
		{
			$paperParts = $this->examination->getPaperParts($_GET['exam_id']);
			if(is_array($paperParts))
			{
				foreach($paperParts as $value)
				{
					echo "&nbsp;&nbsp;<input name='parts[]' type=\"checkbox\" value='".$value['id']."' />".$value['name'];
				}
			}
			exit;
		}
		
		if(isset($_POST['studentNums']) && isset($_POST['examId']) && isset($_POST['parts']))
		{
			$exam_id = $_POST['examId'];
			$parts = "";
			foreach($_POST['parts'] as $value)
			{
				$parts .= $value."#";
			}
			$parts = substr($parts, 0, strlen($parts)-1);
			$studentNums = $_POST['studentNums'];
			$this->examination->saveReExamSetData($exam_id, $parts, $studentNums, $this->examSession->teacher_id);
			
			echo "添加成功。";
		}

		print_r($_POST);
	}
	
	/**
	 * 
	 * 管理系主任
	 */
	function manageleaderAction()
	{
		if(isset($_POST['leaderName']))
		{
			$result = $this->teacher->searchLeader($_POST['leaderName']);
			
			if(count($result)>0)
			{
				$this->view->leaderList = $result;
			}
			else
			{
				$this->view->leaderList = "没有找到。";
			}
		}
		/*elseif(isset($_POST['addLeader'])){
			
		}*/
		else 
		{
			$result = $this->teacher->getLeaderList();
			if(count($result)>0)
			{
				$this->view->leaderList = $result;
			}
			else
			{
				$this->view->leaderList = "没有系主任！";
			}
		}
		
		
	}
	
	/**
	 * 
	 * 上传试题
	 */
	function addexamAction()
	{
		if(isset($_POST['partType']))
		{
			switch ($_POST['partType'])
			{
				//写作
				case 'writing':
					if(isset($_POST['writing']) && isset($_POST['writing_answer']) )
					{				
						$this->examination->insertSelOrFill('fillblank', $this->examination->addFlag('writing', 
						$_POST['writing']), $_POST['writing_answer'], false, 'writing', $this->examSession->teacher_id);
					}
					break;
				
				//快速阅读
				case 'fastReading':
					if(isset($_POST['editor1']) && isset($_POST['startNum']) && isset($_POST['endNum']) 
						&& isset($_POST['answers']))
					{		
						$content = $this->examination->readingPaper($_POST['editor1'], $_POST['fendNum'], $_POST['startNum']);
						$content = addslashes($content);  //转义
						$answers = $this->examination->linkAnswer($_POST['answers']);
						$this->examination->insertSelOrFill('selection', $this->examination->addFlag('fastReading', $content), $answers, false, 'fastReading', $this->examSession->teacher_id);
					}
					break;

				//听力听写
				case 'dictation':
					if(isset($_POST['editor1']) && isset($_POST['startNum']) && isset($_POST['endNum']) 
						&& isset($_POST['answers']))
					{		
						$content = $this->examination->fillblankPaper($_POST['editor1']);
						$content = addslashes($content);  //转义
						$answers = $this->examination->linkAnswer($_POST['answers']);
						$this->examination->insertSelOrFill('fillblank', $this->examination->addFlag('dictation', $content), $answers, false, 'dictation', $this->examSession->teacher_id);
					}
					break;
				
				//听力短对话	
				case 'shortListening':
					if(isset($_POST['editor1']) && isset($_POST['startNum']) && isset($_POST['endNum']) 
						&& isset($_POST['answers']) && isset($_FILES['audio']) )
					{
						$content = $this->examination->readingPaper($_POST['editor1'], $_POST['endNum'], $_POST['startNum']);
						$content = addslashes($content);  //转义
						$answers = $this->examination->linkAnswer($_POST['answers']);
						$newAudioName = $this->examination->saveAudioFile($_FILES['audio'], $this->examSession->teacher_id);
						$this->examination->insertSelOrFill('selection', $this->examination->addFlag('shortListening', $content), $answers, TRUE, 'shortListening', $this->examSession->teacher_id,$newAudioName);
					}
					break;
					
				//听力长对话或短文	
				case 'longListening':
					if(isset($_POST['editor1']) && isset($_POST['startNum']) && isset($_POST['endNum']) 
						&& isset($_POST['answers']) && isset($_FILES['audio']) )
					{
						$content = $this->examination->readingPaper($_POST['editor1'], $_POST['endNum'], $_POST['startNum']);
						$content = addslashes($content);  //转义
						$answers = $this->examination->linkAnswer($_POST['answers']);
						$newAudioName = $this->examination->saveAudioFile($_FILES['audio'], $this->examSession->teacher_id);
						$this->examination->insertSelOrFill('selection', $this->examination->addFlag('longListening', $content), $answers, TRUE, 'longListening', $this->examSession->teacher_id,$newAudioName);
					}
					break;
				
				//阅读
				case 'reading':
					if(isset($_POST['editor1']) && isset($_POST['startNum']) && isset($_POST['endNum']) 
						&& isset($_POST['answers']))
					{		
						$content = $this->examination->readingPaper($_POST['editor1'], $_POST['endNum'], $_POST['startNum']);		
						$content = addslashes($content);  //转义
						$answers = $this->examination->linkAnswer($_POST['answers']);
						$this->examination->insertSelOrFill('selection', $this->examination->addFlag('reading', $content), $answers, false, 'reading', $this->examSession->teacher_id);
					}
					break;
					
				//完形填空
				case 'cloze':
					if(isset($_POST['editor1']) && isset($_POST['startNum']) && isset($_POST['endNum']) 
						&& isset($_POST['answers']))
					{		
						$content = $this->examination->clozePaper($_POST['editor1'], $_POST['endNum'], $_POST['startNum']);
						$content = addslashes($content);  //转义
						$answers = $this->examination->linkAnswer($_POST['answers']);
						$this->examination->insertSelOrFill('selection', $this->examination->addFlag('cloze', $content), $answers, false, 'cloze', $this->examSession->teacher_id);
					}
					break;

				//翻译
				case 'translation':
					if(isset($_POST['editor1']) && isset($_POST['startNum']) && isset($_POST['endNum']) 
						&& isset($_POST['answers']))
					{		
						$content = $this->examination->fillblankPaper($_POST['editor1']);
						$content = addslashes($content);  //转义
						$answers = $this->examination->linkAnswer($_POST['answers']);
						$this->examination->insertSelOrFill('selection', $this->examination->addFlag('translation', $content), $answers, false, 'translation', $this->examSession->teacher_id);
					}
					break;
			}
			echo "上传成功";
		}
		//更新显示已上传部分的名称
		$temporaryRart = $this->examination->getTemporaryRart($this->examSession->teacher_id); 			
		if(count($temporaryRart)>0)
		{
			$this->view->temporaryRartList = $temporaryRart;
		}
	}
	
	/**
	 * 
	 * 上传试题预览
	 */
	function previewAction()
	{	
		exit;
	}
	
	/**
	 * 
	 * 编辑上传的试题分页
	 */
	function editupexamAction()
	{
		$this->view->showPaperCategory = $this->examination->getPaperCategory();  //试卷类别
		
		$temporaryPart = $this->examination->getTemporaryRart($this->examSession->teacher_id);
		$this->view->temporaryPartList = $temporaryPart;
		
		if(isset($_POST['pageIndex']))
		{
			$paper_id = $this->examination->insertPaper($_POST['title'], $_POST['paperCategoryId']);
			foreach ($_POST['pageIndex'] as $value)
			{
				$this->examination->insertPart($value, $paper_id, NULL,NULL, NULL);
			}
			echo "上传试卷成功";
		}
	}
	
	/**
	 * 
	 * 搜索学生
	 */
	function searchstudentAction()
	{
		if(isset($_POST['value']) && isset($_POST['key']))
		{
			$result = $this->teacher->searchStudentInfo($_POST['value'], $_POST['key']);
			if (!$result)
			{
				echo "没有搜索到您输入的关键字,请确认关键字是有效的";
			}
			else
			{
			//	foreach($result as $value){
			//	 	echo "学号：".$value['username']." 姓名：".$value['name']." 性别："
			//	 		.$value['sex']." 密码：".$value['password'];
			//		}
				$this->view->result = $result;
				$this->view->allclass = $this->teacher->getAllClass();
				echo $this->view->render('teacher/showstudenttablelist.phtml');
				
			}
			exit;
		}
	}
	
	/**
	 * 
	 * 添加学生
	 */
	function addstudentAction()
	{
			
		if(isset($_POST['username']) && isset($_POST['name']) 
				&& isset($_POST['sex']) && isset($_POST['password']))
		{
			$result = $this->teacher->addstudent
									($_POST['username'], $_POST['name'], $_POST['sex'], $_POST['password']);
			if(!$result)
			{
				echo $_POST['name']."添加失败";
			}
			else echo $_POST['name']."添加成功";
		exit;
		}	
	}
	
	/**
	 * 
	 * 退出
	 */
	function quitAction()
	{
		$this->examSession->unsetAll();
		header('Location: ../');
	}
	/*
	 *	编辑教师
	 * login-qin
	 */
	function manageteacherAction()
	{
		$this->sys->checkLogined();
	}

	function managestudentAction() 
	{
		$students = array();
		if(!isset($this->examSession->level_id) && !isset($this->examSession->teacher_id)) 
		{
			header('Loacation:../');
			exit();
		}
		$classes = $this->teacher->getAllClass();
		if(empty($classes)) {
			$this->view->allclassselect = '<br/>
				<span style="color:#900;font-weight:bold">没有班级提供选择<br/>需要管理员或系主任创建班级后才能添加学生。</span>';
		}else{
			$c = new Choice($classes);
			$c->id('select_class')->valueByDataKey('id')->textByDataKey('class_name')->name('class_id');
			$c->setShowType('select');
			$this->view->allclassselect = $c->getHtml(); //生成select html
		}
		//$this->showstudenttablelistAction();//加载showstudenttablelist

	}

	function showstudenttablelistAction() 
	{
	
	}

	function resetpwAction() {
		$msg_ok =	'成功设置'.$_POST['username'].'密码为123。';
		$msg_false = '密码已经是默认的123，你丫，想修改多少次？';
		switch ($_POST['type'])
		{ 
		case 's': //修改学生密码为123
			if($this->sys->resetPassword($_POST['username'], "123", 'student'))
			{
				$this->view->msg = $msg_ok;
			}
			else 
			{
				$this->view->msg = $msg_false;
			}
			break;
		case 't'://修改教师密码为123
			if($this->sys->resetPassword($_POST['username'], "123", 'teacher'))
			{
				$this->view->msg = $msg_ok;
			}
			else
			{
				$this->view->msg = $msg_false;
			}

			break;
		}
	}
	function deletepersonAction() {
		$msg_ok =  '成功删除'.$_POST['username'];
		$msg_false =  '删除失败！';
		switch ($_POST['type'])
		{ 
		case 's': 
			if($this->sys->deleteperson($_POST['username'], 'student'))
			{
				$this->view->msg = $msg_ok;
			}
			else 
			{
				$this->view->msg = $msg_false;
			}
			break;
		case 't':
			if($this->sys->deleteperson($_POST['username'], 'teacher'))
			{
				$this->view->msg = $msg_ok;
			}
			else
			{
				$this->view->msg = $msg_false;
			}

			break;
		}
	}

	function addorupdateteacherAction()
	{
		$table = 'teacher';
		$username = isset($_POST['username']) ? trim($_POST['username']) : '';
		$name = isset($_POST['name']) ? trim($_POST['name']) : '';
		if(!$username || !$name )  //if username or name empty, exit;
		{
			echo 'no';
			exit();
		}
		if(!empty($_POST['id'])) //if get the 'id'，means to update
		{
			$data = array(
				'username' => trim($_POST['username']),
				'name'     => trim($_POST['name']),
				'sex'      => $_POST['sex']
			);
			$where = "WHERE `".$table."`.`id` = ".$_POST['id']."";
			$result = $this->teacher->update($data, $where, $table);
			if($result) {
				$this->view->msg = "yes";
			} else {
				$this->view->msg = 'no';
			}
		}
		else                   //if not get 'id', means to add
		{
			$data = array(
				'username' => trim($_POST['username']),
				'password' => '123',
				'name'     => trim($_POST['name']),
				'sex'      => $_POST['sex'],
				'level_id' => '1'
			);
			$result = $this->teacher->insert($data, $table);
			if($result === '0' || $result) {
				$this->view->msg = 'yes';
			} else {
				$this->view->msg = 'no';
			}
		}
	}

	function addorupdateleaderAction()
	{
		$table = 'teacher';
		$username = isset($_POST['username']) ? trim($_POST['username']) : '';
		$name = isset($_POST['name']) ? trim($_POST['name']) : '';
		if(!$username || !$name ) { //if username or name empty, exit;
		echo 'no';
		exit();
		}	
		if(!empty($_POST['id'])) //if get the 'id'，means to update
		{
			$data = array(
				'username' => trim($_POST['username']),
				'name'     => trim($_POST['name']),
				'sex'      => $_POST['sex']
			);
			$where = "WHERE `".$table."`.`id` = ".$_POST['id']."";
			$result = $this->teacher->update($data, $where, $table);
			if($result) {
				$this->view->msg = "yes";
			} else {
				$this->view->msg = 'no';
			}
		}
		else                   //if not get 'id', means to add
		{
			$data = array(
				'username' => trim($_POST['username']),
				'password' => '123',
				'name'     => trim($_POST['name']),
				'sex'      => $_POST['sex'],
				'level_id' => '2'
			);
			$result = $this->teacher->insert($data, $table);
			if($result === '0' || $result) {
				$this->view->msg = 'yes';
			} else {
				$this->view->msg = 'no';
			}
		}
	}


	function addorupdatestudentAction()
	{
		$table = 'student';

		if(!isset($_POST['class_id']) || empty($_POST['class_id']))  //if have no class , exit
		{
			echo 'no';
			exit();
		} 
		$username = isset($_POST['username']) ? trim($_POST['username']) : ''; 
		$name = isset($_POST['name']) ? trim($_POST['name']) : '';
		if(!$username || !$name )  //if username or name empty, exit;
		{
			echo 'no';
			exit();
		}
		if(!empty($_POST['id'])) //if get the 'id'，means to update
		{
			$data = array(
				'username' => trim($_POST['username']),
				'name'     => trim($_POST['name']),
				'sex'      => $_POST['sex']
			);
			$class = array(
				'class_id' => $_POST['class_id']
			);
			$where = "WHERE `".$table."`.`id` = ".$_POST['id']."";
			$class_where = "WHERE `student_id` = ".$_POST['id']."";
			$result = $this->teacher->update($data, $where, $table);
			$class = $this->teacher->update($class, $class_where, 'class_student');
			if($result && $class) echo 'yes';
			else echo 'no';
		}
		else                   //if not get 'id', means to add
		{
			$data = array(
				'username' => trim($_POST['username']),
				'password' => '123',
				'name'     => trim($_POST['name']),
				'sex'      => $_POST['sex']
			);
			$lastid = $this->teacher->insert($data, $table); //return the last insert id;

			if($lastid === '0' || $lastid)
			{
				$class_data = array(
					'class_id' => $_POST['class_id'],
					'student_id' => $lastid
				);
				$result = $this->teacher->insert($class_data, 'class_student');
				if($result === '0' || $result ) $this->view->msg =  "yes";
			}
			else
			{
				$this->view->msg = 'no';
			}
		}


	}

	/**
	 * HTML head frame
	 */
	function headerAction() {
	}
	/**
	 * HTML footer frame
	 */
	function footerAction(){
	}
	/**
	 * HTML footer frame
	 */
	function quickmenuAction(){
		if(func_num_args() > 0) $this->view->showlist = func_get_arg(0);
	}

	/**
	 * gete data for
	 * flexigird 
	 */ 
	function getstudentjsondataAction()
	{
		error_reporting(0);
		if(isset($this->examSession->level_id) && isset($this->examSession->teacher_id)) 
		{ 
			$level_id = $this->examSession->level_id;
			$teacher_id = $this->examSession->teacher_id;
		}
		else 
		{
			header('Loacation:../');
			exit();
		}
		$controlclasses =  $this->teacher->getClass($teacher_id, $level_id); //根据等级获取教师可控班级
		if(empty($controlclasses)) $controlclasses  = array(
			array( 'id' => '-1' )  //if controlclass is empty , init it;
		);
		$page = isset($_POST['page']) ? $_POST['page'] : '';
		$rp = isset($_POST['rp']) ? $_POST['rp'] : '';
		$sortname = isset($_POST['sortname']) ? $_POST['sortname'] : '';
		$sortorder = isset($_POST['sortorder']) ? $_POST['sortorder'] : '';
		if (!$sortname) $sortname = 'student.name';
		if (!$sortorder) $sortorder = 'desc';
		if($_POST['query'] != '') //to search
		{
			$where = "WHERE `".$_POST['qtype']."` LIKE '%".$_POST['query']."%' AND";
		} 
		else if($_POST['class_id'] != '' && $_POST['query'] == '') //get ajax value to switch class
		{
			$where = "WHERE `class_student`.`class_id`=".$_POST['class_id']." AND ";
		}
		else if($_POST['class_id'] == '' && $_POST['query'] == '') //no search and no class_id post ,show the first default class student
		{
			$where = "WHERE `class_student`.`class_id`=".$controlclasses[0]['id']." AND";
		}
		else 
		{
			$where ='WHERE';
		}
		$sort = "ORDER BY $sortname $sortorder";
		if (!$page) $page = 1;
		if (!$rp) $rp = 60;
		$start = (($page-1) * $rp);
		$limit = "LIMIT $start, $rp";
		$result = $this->teacher->getStudent($where, $sort, $limit);
		$total = count($result);
		header("Expires: Mon, 26 Jul 1997 05:00:00 GMT" );
		header("Last-Modified: " . gmdate( "D, d M Y H:i:s" ) . "GMT" );
		header("Cache-Control: no-cache, must-revalidate" );
		header("Pragma: no-cache" );
		header("Content-type: text/x-json");
		$json = "";
		$json .= "{\n";
		$json .= "page: $page,\n";
		$json .= "total: $total,\n";
		$json .= "rows: [";
		$rc = false;
		foreach ($result as $row ) {
			if ($rc) $json .= ",";
			$json .= "\n{";
			$json .= "id:'".$row['id']."',";
			$json .= "cell:['".$row['id']."','".$row['username']."'";
			$json .= ",'".addslashes($row['name'])."'";
			$json .= ",'".addslashes($row['sex'])."'";
			$json .= ",'".addslashes($row['class_name'])."']";
			$json .= "}";
			$rc = true;
		}
		$json .= "]\n";
		$json .= "}";
		echo $json;
	}

	function getteacherjsondataAction()
	{
		error_reporting(0);
		if(isset($this->examSession->level_id) && isset($this->examSession->teacher_id)) 
		{ 
			$level_id = $this->examSession->level_id;
			$teacher_id = $this->examSession->teacher_id;
		}
		else 
		{
			header('Loacation:../');
			exit();
		}
		$page = isset($_POST['page']) ? $_POST['page'] : '';
		$rp = isset($_POST['rp']) ? $_POST['rp'] : '';
		$sortname = isset($_POST['sortname']) ? $_POST['sortname'] : '';
		$sortorder = isset($_POST['sortorder']) ? $_POST['sortorder'] : '';
		if (!$sortname) $sortname = 't1.username';
		if (!$sortorder) $sortorder = 'desc';
		if($_POST['query'] != '') //to search
		{
			$where = "WHERE `".$_POST['qtype']."` LIKE '%".$_POST['query']."%' ";
		} 
		else 
		{
			$where ="WHERE t1.level_id=1 AND NOT EXISTS
				(SELECT * FROM teacher t2 WHERE t2.id = $teacher_id AND t2.id = t1.id )";
		}
		$sort = "ORDER BY $sortname $sortorder";
		if (!$page) $page = 1;
		if (!$rp) $rp = 60;
		$start = (($page-1) * $rp);
		$limit = "LIMIT $start, $rp";
		$sql = "SELECT t1.id, t1.username, t1.name, t1.sex FROM teacher t1 $where $sort $limit";
		$data = $this->teacher->runSQL($sql);
		if($data) $result = $data->fetchAll();
		$total = count($result);
		header("Expires: Mon, 26 Jul 1997 05:00:00 GMT" );
		header("Last-Modified: " . gmdate( "D, d M Y H:i:s" ) . "GMT" );
		header("Cache-Control: no-cache, must-revalidate" );
		header("Pragma: no-cache" );
		header("Content-type: text/x-json");
		$json = "";
		$json .= "{\n";
		$json .= "page: $page,\n";
		$json .= "total: $total,\n";
		$json .= "rows: [";
		$rc = false;
		foreach ($result as $row ) {
			if ($rc) $json .= ",";
			$json .= "\n{";
			$json .= "id:'".$row['id']."',";
			$json .= "cell:['".$row['id']."','".$row['username']."'";
			$json .= ",'".addslashes($row['name'])."'";
			$json .= ",'".addslashes($row['sex'])."']";
			$json .= "}";
			$rc = true;
		}
		$json .= "]\n";
		$json .= "}";
		echo $json;
	}

	function getleaderjsondataAction()
	{
		error_reporting(0);
		if(isset($this->examSession->level_id) && isset($this->examSession->teacher_id)) 
		{ 
			$level_id = $this->examSession->level_id;
			$teacher_id = $this->examSession->teacher_id;
		}
		else 
		{
			header('Loacation:../');
			exit();
		}
		$page = isset($_POST['page']) ? $_POST['page'] : '';
		$rp = isset($_POST['rp']) ? $_POST['rp'] : '';
		$sortname = isset($_POST['sortname']) ? $_POST['sortname'] : '';
		$sortorder = isset($_POST['sortorder']) ? $_POST['sortorder'] : '';
		if (!$sortname) $sortname = 't1.username';
		if (!$sortorder) $sortorder = 'desc';
		if($_POST['query'] != '') //to search
		{
			$where = "WHERE `".$_POST['qtype']."` LIKE '%".$_POST['query']."%' ";
		} 
		else 
		{
			$where ="WHERE t1.level_id=2 AND NOT EXISTS
				(SELECT * FROM teacher t2 WHERE t2.id = $teacher_id AND t2.id = t1.id )";
		}
		$sort = "ORDER BY $sortname $sortorder";
		if (!$page) $page = 1;
		if (!$rp) $rp = 60;
		$start = (($page-1) * $rp);
		$limit = "LIMIT $start, $rp";
		$sql = "SELECT t1.id, t1.username, t1.name, t1.sex FROM teacher t1 $where $sort $limit";
		$data = $this->teacher->runSQL($sql);
		if($data) $result = $data->fetchAll();
		$total = count($result);
		header("Expires: Mon, 26 Jul 1997 05:00:00 GMT" );
		header("Last-Modified: " . gmdate( "D, d M Y H:i:s" ) . "GMT" );
		header("Cache-Control: no-cache, must-revalidate" );
		header("Pragma: no-cache" );
		header("Content-type: text/x-json");
		$json = "";
		$json .= "{\n";
		$json .= "page: $page,\n";
		$json .= "total: $total,\n";
		$json .= "rows: [";
		$rc = false;
		foreach ($result as $row ) {
			if ($rc) $json .= ",";
			$json .= "\n{";
			$json .= "id:'".$row['id']."',";
			$json .= "cell:['".$row['id']."','".$row['username']."'";
			$json .= ",'".addslashes($row['name'])."'";
			$json .= ",'".addslashes($row['sex'])."']";
			$json .= "}";
			$rc = true;
		}
		$json .= "]\n";
		$json .= "}";
		echo $json;
	}

	/**
	 * Get the class by the teacher level id
	 */ 
	function getcontrolclassjsonAction() //push class json to view 
	{
		if(isset($this->examSession->level_id) && isset($this->examSession->teacher_id)) 
		{ 
			$level_id = $this->examSession->level_id;
			$teacher_id = $this->examSession->teacher_id;
		}
		else 
		{
			header('Loacation:../');
			exit();
		}
		$controlclasses =  $this->teacher->getClass($teacher_id, $level_id); //根据等级获取教师可控班级
		$json = Zend_Json :: encode($controlclasses);
		$this->view->json = $json;
	}

	function manageclassAction(){
		$data = $this->teacher->getALLTeacher();
		$select = new Choice($data);
		$select->valueByDataKey('id')->textByDataKey('name')->name('teacher_id');
		$select->setShowType('select');
		$this->view->selectteacher = $select->getHtml();

	}

	function getclassjsondataAction() {
		error_reporting(0);
		if(isset($this->examSession->level_id) && isset($this->examSession->teacher_id)) 
		{ 
			$level_id = $this->examSession->level_id;
			$teacher_id = $this->examSession->teacher_id;
		}
		else 
		{
			header('Loacation:../');
			exit();
		}
		$page = isset($_POST['page']) ? $_POST['page'] : '';
		$rp = isset($_POST['rp']) ? $_POST['rp'] : '';
		$sortname = isset($_POST['sortname']) ? $_POST['sortname'] : '';
		$sortorder = isset($_POST['sortorder']) ? $_POST['sortorder'] : '';
		if (!$sortname) $sortname = 'id';
		if (!$sortorder) $sortorder = 'desc';
		if($_POST['query'] != '') //to search
		{
			$on = "on `".$_POST['qtype']."` LIKE '%".$_POST['query']."%' AND ";
		} 
		else 
		{
			$on ="on ";
		}
		$sort = "ORDER BY $sortname $sortorder";
		if (!$page) $page = 1;
		if (!$rp) $rp = 60;
		$start = (($page-1) * $rp);
		$limit = "LIMIT $start, $rp";
		$sql = "SELECT class.id,class.class_name,teacher.id teacher_id , teacher.username,
			teacher.name from class left join teacher $on class.teacher_id = teacher.id $sort $limit";
		$data = $this->teacher->runSQL($sql);
		if($data) $result = $data->fetchAll();
		$total = count($result);
		header("Expires: Mon, 26 Jul 1997 05:00:00 GMT" );
		header("Last-Modified: " . gmdate( "D, d M Y H:i:s" ) . "GMT" );
		header("Cache-Control: no-cache, must-revalidate" );
		header("Pragma: no-cache" );
		header("Content-type: text/x-json");
		$json = "";
		$json .= "{\n";
		$json .= "page: $page,\n";
		$json .= "total: $total,\n";
		$json .= "rows: [";
		$rc = false;
		foreach ($result as $row ) {
			if ($rc) $json .= ",";
			$json .= "\n{";
			$json .= "id:'".$row['id']."',";
			$json .= "cell:['".$row['id']."','".$row['class_name']."'";
			$json .= ",'".addslashes($row['teacher_id'])."'";
			$json .= ",'".addslashes($row['username'])."'";
			$json .= ",'".addslashes($row['name'])."']";
			$json .= "}";
			$rc = true;
		}
		$json .= "]\n";
		$json .= "}";
		echo $json;
	}

	function addorupdateclassAction()
	{
		$table = 'class';
		if(!trim($_POST['class_name'])) {
			echo 'no';
			exit();
		}
		if(!empty($_POST['id'])) //if get the 'id'，means to update
		{

			$data = array(
				'id'          => trim($_POST['id']),
				'class_name'  => trim($_POST['class_name']),
				'teacher_id'  => $_POST['teacher_id']
			);

			$where = "WHERE `".$table."`.`id` = ".$_POST['id']."";
			$result = $this->teacher->update($data, $where, $table);
			if($result)	$this->view->msg = 'yes';
			else 	$this->view->msg = 'no';
		}
		else                   //if not get 'id', means to add
		{
			$data = array(
				'class_name' => trim($_POST['class_name']),
				'teacher_id' => $_POST['teacher_id']
			);
			$result = $this->teacher->insert($data, $table); //return the last insert id;
			if($result === '0' || $result ) { $this->view->msg =  "yes";
			}
			else
			{
				$this->view->msg = 'no';
			}
		}

	}
	function deleteclassAction()
	{
		if(!empty($_POST['class_id'])) {
			$result = $this->teacher->deleteclass($_POST['class_id']);
			if($result) echo 'yes';
			else echo 'no';
		}else {
			echo 'no';
		}
	}

}
