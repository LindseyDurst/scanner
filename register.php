<?php
require_once("header.php");
if(isset($_SESSION['auth']))show_user_tools();
echo "<form method=\"post\" class=\"auth\">
<h2>REGISTER NOW</h2>
  <div class=\"form-group\">
    <label for=\"exampleInputEmail1\">Login</label>
    <input type=\"login\" class=\"form-control\" id=\"exampleInputEmail1\" name=\"login\" placeholder=\"Login\">
  </div>
  <div class=\"form-group\">
    <label for=\"exampleInputEmail1\">Email address</label>
    <input type=\"email\" class=\"form-control\" id=\"exampleInputEmail1\" name=\"email\" placeholder=\"Email\">
  </div>
  <div class=\"form-group\">
    <label for=\"exampleInputPassword1\">Password</label>
    <input type=\"password\" class=\"form-control\" id=\"exampleInputPassword1\" name=\"pass\" placeholder=\"Password\">
  </div>
  <div class=\"form-group\">
    <label for=\"exampleInputPassword1\">Repeat Password</label>
    <input type=\"password\" class=\"form-control\" id=\"exampleInputPassword1\" name=\"pass2\" placeholder=\"Password\">
  </div>
  <button type=\"submit\" class=\"btn btn-default\">Submit</button>
</form>
";
if(isset($_POST['login']) && isset($_POST['pass'])&& isset($_POST['pass2'])&& isset($_POST['email'])){
	//echo "WELCOME!";
	if($_POST['pass']!=$_POST['pass2']){
		echo "<script>alert(\"Paswords don't match!\");</script>"; 
		die();
	}
	if(strlen($_POST['pass'])<4){
		echo "<script>alert(\"Pasword is too short!\");</script>"; 
		die();
	}
	$mysqli_link=db_connect();
# ESCAPE SCARY SCHARACTERS IN INPUT VALUES
	$login=mysqli_real_escape_string($mysqli_link,$_POST['login']);
	$email=mysqli_real_escape_string($mysqli_link,$_POST['email']);
# CHECK IF USER EXIST IN DB
	$query=mysqli_query($mysqli_link,"select if(exists(select * from users where email='{$email}'),1,0) as ch;");
	$ch=mysqli_fetch_assoc($query);
	if($ch==1){
		echo "<script>alert('User with this email already exists'')</script>";
		die();
	} else{
		$query=mysqli_query($mysqli_link,"select if(exists(select * from users where login='{$login}'),1,0) as ch;");
		$ch=mysqli_fetch_assoc($query);
		if($ch==1){
			echo "<script>alert('User with this login already exists')</script>";
		die();
		}
	}
	$rand=genStr(16);
	//die($rand);
	$pass=crypt($_POST['pass'],'$5$rounds=5000$'.$rand.'$');
	$query=mysqli_query($mysqli_link,"INSERT INTO users values(NULL,'{$login}','{$email}','{$pass}','none');");
	//echo $mysqli_link->error;
	//var_dump($query);
	
	if($query==true){

		//echo "<script>alert(\"1\");</script>";
		session_start([
			'cookie_lifetime' => 86400,
		]);
		//if (!isset($_SESSION['auth'])) {
			$auth_str=genStr(8);
			$_SESSION['auth'] =$auth_str;
			$a=md5($auth_str);
			$query=mysqli_query($mysqli_link,"Update users set str='{$a}' where login='{$login}';");
			
		//} 
		header("Location: projects.php");

	}
}
require_once("footer.php");
?>