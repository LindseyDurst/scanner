<html>
<head>
	<title>Log</title>
</head>
<body>
	<pre>
	<div style="vertical-align:bottom;line-height:0.7" id="log_id" class="row-fluid">
	<?php
	session_start();
	include "config.php";

	global $mysqli_link;
	$user=user_name();
		if(isset($_GET['id'])){
			$proj_name=mysqli_query($mysqli_link,"Select date,proj_name from projects where id=".intval($_GET['id'])." and user_id=(select id from users where login='".$user."' limit 1);");
			$proj_name=mysqli_fetch_assoc($proj_name);			
			$proj_name['date']=explode(" ",$proj_name['date']);
			$path="logs/".$proj_name['proj_name']."_".$proj_name['date'][0].".log";
			$log=1;
			#echo $path;
		} else if(isset($_GET['proj'])){
			$proj_name=mysqli_query($mysqli_link,"SELECT if( (select user_id from projects where proj_name='".mysqli_real_escape_string($mysqli_link,$_GET['proj'])."') = (select id from users where login='".$user."'), (select date from projects where proj_name='".mysqli_real_escape_string($mysqli_link,$_GET['proj'])."'), 0) as date");
			$proj_name=mysqli_fetch_assoc($proj_name);
			if($proj_name['date']==0){
				echo "Hacking attempt!";
				die();
			}
			else {
				$proj_name['date']=explode(" ",$proj_name['date']);
				$path="logs/".mysqli_real_escape_string($mysqli_link,$_GET['proj'])."_".$proj_name['date'][0].".log";
				$log=1;
				#echo $path;
			}
		}
		sleep(1);

		$out=file_get_contents($path,NULL, NULL,$log);
		echo $out;
		while(1){
			// $out=file_get_contents($path,NULL, NULL,$log);

			// if(strlen($out)>1){
			// 	// if(strlen($temp)>strlen($log)){
			// 	// 	$out=substr($temp,strlen($log));
			// 	#echo $out;
			// 	$log+=strlen($out);
			// 	$out=preg_replace( "/\r|\n/", "<br>", $out);
			// 	echo "<script>var parent=document.getElementById('log_id');var div_child = document.createElement('div');div_child.innerHTML='".$out."';parent.insertBefore(div_child,parent.firstChild);</script>";
			// 	#echo "<script>var parent=document.getElementById('log_id');var div_child = document.createElement('div');div_child.innerHTML='".$out."';parent.appendChild(div_child);</script>";
			// 	#die();
			// 	#unset($temp);
			// 	if(stristr($out,"complete")) break;
			// 	//}
			// }
			#file_put_contents($path, $i,FILE_APPEND);
			echo 1;
			ob_flush();
			flush();
			sleep(1);
			#$i++;
		}
	?>
	</div>
	</pre>
</body>
</html>