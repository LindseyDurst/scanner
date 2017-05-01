<?php
require_once("header.php");
if(isset($_SESSION['auth']))show_user_tools();
echo "<form method=\"post\" class=\"auth\">
  <div class=\"form-group\">
    <label for=\"exampleInputEmail1\">Login or Email</label>
    <input type=\"login\" class=\"form-control\" id=\"exampleInputEmail1\" name=\"login\" placeholder=\"Email\">
  </div>
  <div class=\"form-group\">
    <label for=\"exampleInputPassword1\">Password</label>
    <input type=\"password\" class=\"form-control\" id=\"exampleInputPassword1\" name=\"pass\" placeholder=\"Password\">
  </div>
  <button type=\"submit\" class=\"btn btn-default\">Submit</button>
</form>
";
if(isset($_POST['login']) && isset($_POST['pass'])){
	global $mysqli_link;
	$login=mysqli_real_escape_string($mysqli_link,$_POST['login']);
	$pass=mysqli_real_escape_string($mysqli_link,$_POST['pass']);
	//echo "<h2 style=\"position:relative;top:100px;\">".$email.$pass."</h2>";die();
	$query=mysqli_query($mysqli_link,"select * from users where (login='{$login}' or email='{$login}') limit 1;");
	if(mysqli_num_rows($query)>0){
		$res=mysqli_fetch_assoc($query);
		preg_match_all("~[^\$]+~",$res['password'],$arr);
		$pass=crypt($pass,'$5$rounds=5000$'.$arr[0][2].'$');
		if($pass==$res['password']) {
			session_start([
				'cookie_lifetime' => 86400,
			]);
			if (!isset($_SESSION['auth'])) {
				$auth_str=genStr(8);
				$_SESSION['auth'] =$auth_str;
				$a=md5($auth_str);
				$query=mysqli_query($mysqli_link,"Update users set str='{$a}' where login='{$login}';");
			} 
			header("Location: projects.php");
		} 
	}
}

require_once("footer.php");
?>