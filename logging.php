<?php
	session_start();
	include "config.php";
	global $mysqli_link;
	$user=user_name();
	if(isset($_POST['id']) && is_numeric($_POST['id'])){
		$proj_name=mysqli_query($mysqli_link,"Select date,proj_name from projects where id=".intval($_POST['id'])." and user_id=(select id from users where login='".$user."' limit 1);");
		$proj_name=mysqli_fetch_assoc($proj_name);			
		$proj_name['date']=explode(" ",$proj_name['date']);
		$path="logs/".$proj_name['proj_name']."_".$proj_name['date'][0].".log";
		#echo $path;
	} else if(isset($_POST['id']) && !is_numeric($_POST['id'])){
		$proj_name=mysqli_query($mysqli_link,"SELECT if( (select user_id from projects where proj_name='".mysqli_real_escape_string($mysqli_link,$_POST['id'])."') = (select id from users where login='".$user."'), (select date from projects where proj_name='".mysqli_real_escape_string($mysqli_link,$_POST['id'])."'), 0) as date");
		$proj_name=mysqli_fetch_assoc($proj_name);
		if($proj_name['date']==0){
			echo "Hacking attempt!";
			die();
		}
		else {
			$proj_name['date']=explode(" ",$proj_name['date']);
			$path="logs/".mysqli_real_escape_string($mysqli_link,$_POST['id'])."_".$proj_name['date'][0].".log";
			#echo $path;
		}
	}
	//$path="logs/test.txt";
	//echo intval($_POST['pos']);
	//echo 11111;
	$log = file_get_contents($path,NULL,NULL, intval($_POST['pos']));
	$log = preg_replace( "/\r|\n/", "", $log);
	echo json_encode(['path'=>$path,"text"=>$log]);
	//echo json_encode(["text"=>$log]);



	#echo file_get_contents($path);
	// if(isset($_COOKIE['str_count'])){
	// 	$pos=intval($_COOKIE['str_count']);
	// 	$log=file_get_contents($path,NULL,NULL, $pos);
	// 	$log = preg_replace( "/\r|\n/", "<br>", $log);
	// 	unset($_COOKIE['str_count']);
	// 	setcookie("str_count",$pos+count($log),time() +1);
	// 	echo json_encode($log);
	// }
	// else{
	// 	setcookie("str_count",'1',time() +1);
	// 	$pos=0;
	// 	$log = preg_replace( "/\r|\n/", "<br>", $pos);
	// 	$log=file_get_contents($path,NULL,NULL, $pos);
	// 	//echo json_encode($log);
	// 	//echo "bla";
	// 	echo $log;
	// 	die();
	// }
?>