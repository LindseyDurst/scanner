<?php
include '../libs/class.diff.php';
$str1="<!DOCTYPE>
<html>
	<head>
		<meta charset=\"utf-8\">
		<link rel=\"stylesheet\" type=\"text/css\" href=\"theme.css\">
		<link rel=\"stylesheet\" type=\"text/css\" href=\"bootstrap.css\">
	</head>
	<body>
		<center>
			<div id=\"header\">
			<div class=\"navbar-header\">
			  <a class=\"navbar-brand\" href=\"index.php\">Breaking News</a>
			</div>
			<div class=\"navbar-collapse collapse\">
				  <ul class=\"navbar-nav nav\">
				<li><a href=\"index.php\" >Home</a></li>
				<li><a href=\"theme.php?theme=IT\">IT</a></li>
				<li><a href=\"theme.php?theme=Politics\">Politics</a></li>
				<li><a href=\"theme.php?theme=Culture\">Culture</a></li>
			  </ul>
			</div>
				<form method=\"post\" id=\"search_bar\" action=\"search.php\">
					<input type=\"submit\" value=\"Search\"><input type=\"text\" name=\"search_str\" id=\"search\">
				</form>
			</div></center><div id=\"articles\"><div class='layer1'>
					<img class='img1' src='/img/nike.jpg' width='100%'><br>
				</div>
				<div class='layer2'>
					<h3>Nike restricts self-lacing trainers to app users</h3><br>
					<a class='url4' href='news.php?id=1'>read more</a>
				</div><div class='layer1'>
					<img class='img1' src='img/1.jpg' width='100%'><br>
				</div>
				<div class='layer2'>
					<h3>Could hackers turn the lights out?</h3><br>
					<a class='url4' href='news.php?id=2'>read more</a>
				</div><div class='layer1'>
					<img class='img1' src='img/2.jpg' width='100%'><br>
				</div>
				<div class='layer2'>
					<h3>PlayStation VR is cheaper than Oculus Rift and HTC Vive</h3><br>
					<a class='url4' href='news.php?id=3'>read more</a>
				</div><div class='layer1'>
					<img class='img1' src='img/4.jpg' width='100%'><br>
				</div>
				<div class='layer2'>
					<h3>Something amazing happened!</h3><br>
					<a class='url4' href='news.php?id=12'>read more</a>
				</div><div class='layer1'>
					<img class='img1' src='img/6.jpg' width='100%'><br>
				</div>
				<div class='layer2'>
					<h3>Maps of Google and other services could require security clearance in India</h3><br>
					<a class='url4' href='news.php?id=25'>read more</a>
				</div><div class='layer1'>
					<img class='img1' src='img/7.jpg' width='100%'><br>
				</div>
				<div class='layer2'>
					<h3>Businesses can now buy apps in bulk from the Windows Store</h3><br>
					<a class='url4' href='news.php?id=31'>read more</a>
				</div><div class='layer1'>
					<img class='img1' src='img/8.jpg' width='100%'><br>
				</div>
				<div class='layer2'>
					<h3>Businesses can now buy apps in bulk from the Windows Store</h3><br>
					<a class='url4' href='news.php?id=32'>read more</a>
				</div><div class='layer1'>
					<img class='img1' src='img/pasha.jpg' width='100%'><br>
				</div>
				<div class='layer2'>
					<h3>admin</h3><br>
					<a class='url4' href='news.php?id=33'>read more</a>
				</div></div>		
		</body>
		</html>";
		$str2="<!DOCTYPE>
<html>
	<head>
		<meta charset=\"utf-8\">
		<link rel=\"stylesheet\" type=\"text/css\" href=\"theme.css\">
		<link rel=\"stylesheet\" type=\"text/css\" href=\"bootstrap.css\">
	</head>
	<body>
		<center>
			<div id=\"header\">
			<div class=\"navbar-header\">
			  <a class=\"navbar-brand\" href=\"index.php\">Breaking News</a>
			</div>
			<div class=\"navbar-collapse collapse\">
				  <ul class=\"navbar-nav nav\">
				<li><a href=\"index.php\" >Home</a></li>
				<li><a href=\"theme.php?theme=IT\">IT</a></li>
				<li><a href=\"theme.php?theme=Politics\">Politics</a></li>
				<li><a href=\"theme.php?theme=Culture\">Culture</a></li>
			  </ul>
			</div>
				<form method=\"post\" id=\"search_bar\" action=\"search.php\">
					<input type=\"submit\" value=\"Search\"><input type=\"text\" name=\"search_str\" id=\"search\">
				</form>
			</div></center><div id=\"articles\"><div class='layer1'>
					<img class='img1' src='/img/nike.jpg' width='100%'><br>
				</div>
				<div class='layer2'>
					<h3>Nike restricts self-lacing trainers to app users</h3><br>
					<a class='url4' href='news.php?id=1'>read more</a>
				</div><div class='layer1'>
					<img class='img1' src='img/1.jpg' width='100%'><br>
				</div>
				<div class='layer2'>
					<h3>Could hackers turn the lights out?</h3><br>
					<a class='url4' href='news.php?id=2'>read more</a>
				</div><div class='layer1'>
					<img class='img1' src='img/2.jpg' width='100%'><br>
				</div>
				<div class='layer2'>
					<h3>PlayStation VR is cheaper than Oculus Rift and HTC Vive</h3><br>
					<a class='url4' href='news.php?id=3'>read more</a>
				</div><div class='layer1'>
					<img class='img1' src='img/3.jpg' width='100%'><br>
				</div>
				<div class='layer2'>
					<h3>Rocking News</h3><br>
					<a class='url4' href='news.php?id=4'>read more</a>
				</div><div class='layer1'>
					<img class='img1' src='img/4.jpg' width='100%'><br>
				</div>
				<div class='layer2'>
					<h3>Something amazing happened!</h3><br>
					<a class='url4' href='news.php?id=12'>read more</a>
				</div><div class='layer1'>
					<img class='img1' src='img/5.jpg' width='100%'><br>
				</div>
				<div class='layer2'>
					<h3>How I Met SQLi</h3><br>
					<a class='url4' href='news.php?id=14'>read more</a>
				</div><div class='layer1'>
					<img class='img1' src='img/6.jpg' width='100%'><br>
				</div>
				<div class='layer2'>
					<h3>Maps of Google and other services could require security clearance in India</h3><br>
					<a class='url4' href='news.php?id=25'>read more</a>
				</div><div class='layer1'>
					<img class='img1' src='img/7.jpg' width='100%'><br>
				</div>
				<div class='layer2'>
					<h3>Businesses can now buy apps in bulk from the Windows Store</h3><br>
					<a class='url4' href='news.php?id=31'>read more</a>
				</div><div class='layer1'>
					<img class='img1' src='img/8.jpg' width='100%'><br>
				</div>
				<div class='layer2'>
					<h3>Businesses can now buy apps in bulk from the Windows Store</h3><br>
					<a class='url4' href='news.php?id=32'>read more</a>
				</div><div class='layer1'>
					<img class='img1' src='img/pasha.jpg' width='100%'><br>
				</div>
				<div class='layer2'>
					<h3>admin</h3><br>
					<a class='url4' href='news.php?id=33'>read more</a>
				</div></div>		
		</body>
		</html>";
$tes=new Diff($str1,$str2);
$diffs=$tes->returnDifferent();
$template=$tes->returnDifferent();
#echo ($template);
print_r($template);
#echo preg_match($template,$str2);
?>