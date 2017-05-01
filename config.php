<?php
$config_arr=array('host'=>'localhost','user'=>'admin','password'=>'MYBGpTGpZx3pZMpb','db'=>'scanner');
//MYBGpTGpZx3pZMpb
$mysqli_link=db_connect();

function proj_id($proj_name){
	global $mysqli_link;
	$proj_id=mysqli_query($mysqli_link,"SELECT date,id from projects where proj_name='{$proj_name}' and user_id=(select id from users where login='".user_name()."' limit 1) limit 1");
	$proj_id=mysqli_fetch_assoc($proj_id);
	return $proj_id;
}
function check_auth(){
	if (session_status() == PHP_SESSION_NONE) {
	    session_start();
	}
	if(!isset($_SESSION['auth'])){
		header("Location: login.php");
	} else {
		global $mysqli_link;
		//echo $_SESSION['auth'];
		$query=mysqli_query($mysqli_link,"Select * from users where str='".md5($_SESSION['auth'])."';");
		$res=mysqli_fetch_assoc($query);
		if(isset($res['id'])){
			show_user_tools();
			return 1;
		} else header("Location: login.php");
	}
 }
function user_name(){
	
	if(!isset($_SESSION['auth'])){
		header("Location: login.php");
	} else {
		$mysqli_link=db_connect();
		$query=mysqli_query($mysqli_link,"Select login from users where str='".md5($_SESSION['auth'])."';");
		$res=mysqli_fetch_assoc($query);
		if(isset($res['login'])){
			return $res['login'];
		} else return 0;
	}
}
function db_connect(){
	global $config_arr;
	$link = mysqli_connect($config_arr['host'], $config_arr['user'], $config_arr['password'], $config_arr['db']);
	if (mysqli_connect_errno()){
		printf("Не удалось подключиться: %s\n", mysqli_connect_error());
		exit();
	} else return $link;
}
function genStr($length){
  $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ23456789';
  $numChars = strlen($chars);
  $string = '';
  for ($i = 0; $i < $length; $i++) {
    $string .= substr($chars, rand(1, $numChars) - 1, 1);
  }
  return $string;
}
function show_user_tools(){
	echo "
	<script>
		document.getElementsByClassName(\"user_tools\")[0].style.visibility=\"visible\";
		document.getElementsByClassName(\"user_tools\")[1].style.visibility=\"visible\";
		document.getElementsByClassName(\"user_tools\")[2].style.visibility=\"visible\";
		document.getElementsByClassName(\"user_tools\")[3].style.visibility=\"visible\";
		document.getElementsByClassName(\"user_tools\")[2].firstChild.innerHTML=\"".user_name()."\";
		document.getElementsByClassName(\"no_user\")[0].style.visibility=\"hidden\";
		document.getElementsByClassName(\"no_user\")[1].style.visibility=\"hidden\";
	</script>
	";
}

?>