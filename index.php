<?php
require_once("header.php");
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
if(isset($_SESSION['auth'])){
	show_user_tools();
}
echo "<center> <div style=\"position:relative;top:100px;\"><h2>Welcome to super-mega-cool-and-awesome web-aplications vulnerability scanner!</h2><br><br><img src=\"img\\vzuh.jpg\" width=\"700px\" height=\"400px\"></div></center>";
require_once("footer.php");
?>