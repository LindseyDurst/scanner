<?php
	require_once("header.php");
	require_once("config.php");
	if(isset($_SESSION['auth'])) show_user_tools();
	ini_set("display_errors", true);
	if (session_status() == PHP_SESSION_NONE) {
	    session_start();
	}
	# if isset auth cookie then show scanning page
	//check_auth();
	show_user_tools();
?>
<div id="def_target" class="container" style="position:relative;top:100px;">
	<h2>SCAN THE HELL OUT OF THIS DOMAIN</h2>
	<br>
	<img src="img\icon.png" class="pull-right" class="form-control" id="icon" style="width:30%;height:30%;right:200px;">
	<form class="col-xs-5"  >
		<h4>Select Project</h4>
		<select name="proj" class="selectpicker form-control" >
		<option></option>";
		<?php
		$mysqli_link=db_connect();
		$query=mysqli_query($mysqli_link,"select proj_name from projects where user_id=(select id from users where str='".md5($_SESSION['auth'])."' limit 1);");
		while($row=mysqli_fetch_assoc($query)){
			echo "<option selected=\"selected\">{$row['proj_name']}</option>";
		}
		?>
		</select>
		<h4>Target domain: </h4>
		<input type="text" class="form-control" placeholder="http://domain.com/" name="aim" value="https://super-mega-cool-site.000webhostapp.com/">
		<br>
		<h4 class="col-xl-2" >Depth of search (5 by default): </h4>
		<input type="text" class="form-control" placeholder="5" name="depth_conf"><br>
		<table class="table table-condensed ">
			<tr>
				<td><h5>Proxy (on/off):</h5></td>
				<td><input type="checkbox" name="proxy" id="ch1" checked></td>
			</th>
			<tr>
				<td><h5>SQLi:</h5></td>
				<td><input type="checkbox" name="sqli" id="ch2" checked></td>
			</th>
			<tr>
				<td><h5>XSS:</h5></td>
				<td><input type="checkbox" name="xss" id="ch3" checked></td>
			</th>
			<tr>
				<td><h5>Additional info:</h5></td>
				<td><input type="checkbox" name="adds" id="ch4" ></td>
			</th>
			</table>
		<br><br>
		<button type="button" class="btn btn-default" onclick="ajax_me()">Submit</button>
	</form>
</div>
<div id="load" class="container" style="display:none;position:relative;top:100px;">
	
	<h2>Scanning in progress<div id="blink1" style="display:inline;">.</div><div id="blink2" style="display:inline;">.</div><div id="blink3" style="display:inline;">.</div></h2>
	<br>
	<h4 id="blink1"> Do not refresh this page! </h4>
	<div id="logs"></div>
	<div id="load_img" style="height:500px;">
		<div class='dino'></div>
		<div class='eye'></div>
		<div class='mouth'></div>
		<div class='ground'></div>
		<div class='comets'></div>
	</div>	
</div>
<div id="end" class="container" style="display:none;position:relative;top:100px;">
<h2>Scanning complete!</h2>
</div>
<div id="res_text" class="container" style="display:none;position:relative;top:100px;">
	<h2>RESULTS:</h2>
	
</div>
<script>
	document.body.onkeydown = function(event) { 
		if(event.keyCode==67){
			window.location.reload();
		}
	};
function ajax_me(){
	//<a target="_blank" href="logs.php?id=<?php //if(isset($_POST['proj_id'])){echo proj_id($_POST['proj']);} ?>">OPEN SCANING LOG</a>
	var aim = document.getElementsByName("aim")[0].value;
	var dep = document.getElementsByName("depth_conf")[0].value;
	var proj = document.getElementsByName("proj")[0].value;
	var proxy;
	if ($('#ch1').is(':checked')) proxy=1;
	else proxy=0;
	if ($('#ch2').is(':checked')) sqli=1;
	else sqli=0;
	if ($('#ch3').is(':checked')) xss=1;
	else xss=0;
	if ($('#ch4').is(':checked')) adds=1;
	else adds=0;
	$.ajax({
		type: "POST",
		url: "crawler.php",
		data: { aim: aim, dep: dep, proj: proj, proxy: proxy,sqli:sqli,xss:xss,adds:adds},
		beforeSend: function() {
			document.getElementById("def_target").style.display="none";
			document.getElementById("load").style.display="block";
			var logs_div = document.getElementById('logs');
			var logs_link = document.createElement('a');
			var linkText = document.createTextNode("open scaning log");
			logs_link.setAttribute("href","logs.html?proj="+proj);
			logs_link.appendChild(linkText);
			logs_link.setAttribute("target","_blank");
			logs_div.appendChild(logs_link);
		},
		success: function(data) {
			document.getElementById("res_text").style.display="block";
			document.getElementById('res_text').innerHTML=data;

		},
		complete: function(){
			document.getElementById("load").style.display="none";
			document.getElementById("end").style.display="block";
		}
	})
}
</script>
<?php
require_once("footer.php");
?>