<?php
#proj res page
require_once("header.php");
include_once("config.php");
ini_set("display_errors",false);
if(check_auth()==1) {
	if(!isset($_GET['id'])) header("Location= \"projects.php\"");
	$mysqli_link=db_connect();
	$query=mysqli_query($mysqli_link,"Select * from parameters where proj_id=".intval($_GET['id']).";");
	$proj_name=mysqli_query($mysqli_link,"Select date,proj_name from projects where id=".intval($_GET['id']).";");
	#echo mysqli_error($mysqli_link);
	$proj_name=mysqli_fetch_assoc($proj_name);
	$proj_name['date']=explode(" ",$proj_name['date']);
	//$query=mysqli_fetch_assoc($query);
	//if(mysqli_num_rows($query)!=0 && mysqli_num_rows($proj_name)!=0){
		echo "
		<div style=\"position:relative;top:100px;\" class=\"container\">
			<h3><b>{$proj_name['proj_name']}</b></h3>
			<b>\t<a target=\"_blank\" href=\"logs/".$proj_name['proj_name']."_".$proj_name['date'][0].".log\">scaning log</a></b><br><br>
			<a href=\"pdf.php?id=".intval($_GET['id'])."\"><button type=\"button\" class=\"btn btn-default\">Report</button></a>
			<a class=\"btn btn-sm btn-default pull-right\" href=\"#basicModal1\" data-toggle=\"modal\" data-target=\"#basicModal1\">Delete Project</a>
		<div class=\"modal fade\" id=\"basicModal1\" tabindex=\"-1\" role=\"dialog\" >
		     <form class=\"modal-dialog\" method=\"post\">
		     	<div class=\"modal-content\">
		     		<div class=\"modal-header\"><button class=\"close\" type=\"button\" data-dismiss=\"modal\">x</button>
		             <h4 class=\"modal-title\" id=\"myModalLabel\">Delete Projects</h4>
		          	</div>
		          	<div class=\"modal-body\">
		           	<label for=\"ProjName\" name=\"yes\">Are you sure you want to proceed?</label>
		           	<input type=\"hidden\"name=\"yes\" value=\"1\">
			        </div>
			       	<div class=\"modal-footer\">
			       		<button class=\"btn btn-default\" type=\"button\" data-dismiss=\"modal\">Close</button>
			        	<button class=\"btn btn-primary\" type=\"Submit\">Yes</button></div>
			   		</div>
		     
		     </form>
		</div>
		<br>
		<br>";
		$vulns=mysqli_query($mysqli_link,"select * from vulns where proj_id='".intval($_GET['id'])."';");
		if(mysqli_num_rows($vulns)>0){
			echo 	"<br><br>
					<h4>Vulnerabilities</h4>
					<center>
					<table class=\"table\"   style=\"text-align:center;\" class=\"col-md-offset-3\">
						<tr>
							<td><b>Type</b></td><td><b>Link</b></td><td><b>Parameter</b></td><td><b>Exploit</b></td>
						</tr>";
			while($row=mysqli_fetch_assoc($vulns)){
				echo 
				"<tr>
					<td>".htmlspecialchars($row['vuln'])."</td>
					<td>".htmlspecialchars($row['info'])."</td>
					<td>".htmlspecialchars($row['parameter'])."</td>
					<td>".htmlspecialchars($row['exploit'])."</td>
				</tr>	
				";
			}
			echo '</table><BR><BR></center>';
		}
		echo "<h4>Parameters</h4>
		<center>
		<table class=\"table\"  class=\"table\" style=\"text-align:center;\" class=\"col-md-offset-3\">
			<tr>
				<td><b>Page</b></td><td><b>Type</b></td><td><b>Name</b></td><td><b>Value</b></td>
			</tr>
		";
		while($row=mysqli_fetch_assoc($query)){
			$page=mysqli_query($mysqli_link,"Select page_name from pages where id=(select page_id from param_set where id={$row['set_id']})");
			$page=mysqli_fetch_assoc($page);
			echo 
			"<tr>
				<td>{$page['page_name']}</td>
				<td>{$row['type']}</td>
				<td>{$row['param_name']}</td>
				<td>{$row['value']}</td>
			</tr>	
			";
		}
		echo '</table></center>';
		$adds=mysqli_query($mysqli_link,"select * from additional_info where proj_id='".intval($_GET['id'])."';");
		if(mysqli_num_rows($adds)>0){
			echo 	"<br><br>
					<h4>Additional info</h4>
					<center>
					<table class=\"table\"   style=\"text-align:center;\" class=\"col-md-offset-3\">
						<tr>
							<td><b>Link</b></td><td><b>Short review</b></td>
						</tr>";
			while($row=mysqli_fetch_assoc($adds)){
				echo 
				"<tr>
					<td>".htmlspecialchars($row['paste_link'])."</td>
					<td>".htmlspecialchars($row['review'])."</td>
				</tr>	
				";
			}
			echo '</table><BR><BR>';
		}

		echo '</center></div>';

	//}
}
//echo "YAY";
if(isset($_POST['yes']) && $_POST['yes']==1){
	$test=mysqli_query($mysqli_link,"SELECT if(EXISTS(select proj_name from projects where user_id=(select id from users where str='".md5($_SESSION['auth'])."' limit 1) and id=".intval($_GET['id'])."),1,0) as c");
	$test=mysqli_fetch_assoc($test);
	if($test['c']==1){
		mysqli_query($mysqli_link,"DELETE from projects where id=".intval($_GET['id']));
		mysqli_query($mysqli_link,"DELETE from craw_pages where proj_id=".intval($_GET['id']));
		mysqli_query($mysqli_link,"DELETE from parameters where proj_id=".intval($_GET['id']));
		mysqli_query($mysqli_link,"DELETE from param_set where proj_id=".intval($_GET['id']));
		mysqli_query($mysqli_link,"DELETE from pages where proj_id=".intval($_GET['id']));
		mysqli_query($mysqli_link,"DELETE from vulns where proj_id=".intval($_GET['id']));
		//echo mysqli_error($mysqli_link);
	}
	echo "<script>location.href='projects.php';</script>";
}
require_once("footer.php");

?>