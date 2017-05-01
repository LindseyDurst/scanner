<?php
require_once("header.php");
session_start();
if(isset($_SESSION['auth'])){
	show_user_tools();
}
echo "<div style=\"position:relative;top:100px;left:20%;\">
	<h3>If you like this scanner please donate stars to my carma in order for this project to become finished</h3>
	<br><h4>PayPal: 1d0ntn33dy0urfr3@k1n9m0n3y</h4>
	<br>
	<img src=\"img\\donate.jpeg\">
	</div>";
require_once("footer.php");
?>