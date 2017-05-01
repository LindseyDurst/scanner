<?php
require("header.php");

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
if(isset($_SESSION['auth'])){
	show_user_tools();
}
$user=user_name();
echo "<center><div style=\"position:relative;top:100px;\"><h4><b>Hello, {$user}!</b></h4>
	<br>";
global $mysqli_link;
$num=mysqli_query($mysqli_link,"SELECT count(id) as c from projects where user_id=(select id from users where login='{$user}');");
$num=mysqli_fetch_assoc($num);
echo "<h5> You have scanned <b>{$num['c']}</b> projects. <br><br> Carry on!</h5>
	</div></center>";
require_once("footer.php");
?>