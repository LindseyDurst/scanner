<?php
require_once("header.php");
session_start();
if(isset($_SESSION['auth'])){
	show_user_tools();
}
echo "<div style=\"position:relative;top:100px;left:20%;\"><h3>This is super-mega-cool-and-awesome web-aplications vulnerability scanner!</h3> <BR><BR>
	<h5 style=\"line-height:30px;\">All you need to do is <b>register</b>,<b> create project</b> and <b>choose web-site</b> you want to scan. <br>
	Easy, beautifull, <b>slow</b>.. Yeah. No good scanner takes 2 seconds to finish its job. So be patient. Okay? :)</h5>
	<br>
	<img src=\"img\\scan.jpg\">
</div>";
require_once("footer.php");
?>