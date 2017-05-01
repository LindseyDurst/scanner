<?php
# projects php
require_once("header.php");
include_once("config.php");
ini_set("display_errors",false);
if(isset($_SESSION['auth']))show_user_tools();

if(check_auth()==1) {
	$mysqli_link=db_connect();
	$query=mysqli_query($mysqli_link,"Select * from projects where user_id=(select id from users where str='".md5($_SESSION['auth'])."' limit 1) order by date DESC;");
	
		
		echo "<div style=\"position:relative;top:100px;\" class=\"container\">
		<div>";

		echo '<a class="btn btn-sm btn-default pull-left" href="#modal1" data-toggle="modal" data-target="#basicModal">New Project</a>
		<div class="modal fade" id="basicModal" tabindex="-1" role="dialog" id="#modal1">
		     <form class="modal-dialog" method="post">
		       <div class="modal-content">
		          <div class="modal-header"><button class="close" type="button" data-dismiss="modal">x</button>
		             <h4 class="modal-title" id="myModalLabel">New Project</h4>
		          </div>
		        <div class="modal-body">
		           	<label for="ProjName">Project Name</label>
    				<input class="form-control" name="projName" placeholder="test">
		        </div>
		       <div class="modal-footer">
		       		<button class="btn btn-default" type="button" data-dismiss="modal">Close</button>
		        	<button class="btn btn-primary" type="Submit">Submit</button></div>
		    </div>
		  </form>
		</div>

		<a class="btn btn-sm btn-default pull-right" href="#basicModal1" data-toggle="modal" data-target="#basicModal1">Delete All</a>
		<div class="modal fade" id="basicModal1" tabindex="-1" role="dialog" >
		     <form class="modal-dialog" method="post">
		     	<div class="modal-content">
		     		<div class="modal-header"><button class="close" type="button" data-dismiss="modal">x</button>
		             <h4 class="modal-title" id="myModalLabel">Delete Projects</h4>
		          	</div>
		          	<div class="modal-body">
		           	<label for="ProjName" name="delete_all">Are you sure you want to proceed?</label>
		           	<input type="hidden"name="delete_all" value="1">
			        </div>
			       	<div class="modal-footer">
			       		<button class="btn btn-default" type="button" data-dismiss="modal">Close</button>
			        	<button class="btn btn-primary" type="Submit">Yes</button></div>
			   		</div>
		     
		     </form>
		</div>
		';
		echo "</div><br><br>
		<h3>CREATED PROJECTS</h3>
		<br>
		<center><table class=\"table\"  class=\"table\" style=\"text-align:center;\" class=\"col-md-offset-3\">
			<tr>
				<td><b>Project Name</b></td><td><b>Date of creation</b></td>
			</tr>
		";
		while($row=mysqli_fetch_assoc($query)){
			echo 
			"<tr>
				<td><a href=\"proj.php?id={$row['id']}\">{$row['proj_name']}</a></td>
				<td>{$row['date']}</td>
			</tr>	
			";
		}

		echo '</table></center>
		</div>';
	
	$res=mysqli_fetch_assoc($query);
	print_r($res);
}

if(isset($_POST['projName'])){
	if(isset($_SESSION['auth'])) header("Location:login.php");
	$proj_name=mysqli_real_escape_string($mysqli_link,$_POST['projName']);
	$date=date('d.m.Y G:i:s');
	$query=mysqli_query($mysqli_link,"select if( exists(select id from projects where proj_name='{$proj_name}' and user_id=(select id from users where str='".md5($_SESSION['auth'])."' limit 1)),1,0) as ch;");
	$query=mysqli_fetch_assoc($query);
	if($query['ch']==1) {
		echo "<script>alert('Project with the same name already exists!');</script>";
		exit();
	}else{
		$query=mysqli_query($mysqli_link,"insert into projects values (null,(select id from users where str='".md5($_SESSION['auth'])."' limit 1),'{$proj_name}','{$date}')");
		echo "<script>location.href='projects.php';</script>";
	}
}
if(isset($_POST['delete_all']) && $_POST['delete_all']==1) {
	$projects=mysqli_query($mysqli_link,"select proj_name from projects where user_id=(select id from users where str='".md5($_SESSION['auth'])."' limit 1);");
	while($row=mysqli_fetch_assoc($projects)){
		mysqli_query($mysqli_link,"DELETE from projects where id={$row['proj_name']}");
		mysqli_query($mysqli_link,"DELETE from craw_pages where proj_id={$row['proj_name']}");
		mysqli_query($mysqli_link,"DELETE from parameters where proj_id={$row['proj_name']}");
		mysqli_query($mysqli_link,"DELETE from param_set where proj_id={$row['proj_name']}");
		mysqli_query($mysqli_link,"DELETE from pages where proj_id={$row['proj_name']}");
		mysqli_query($mysqli_link,"DELETE from vulns where proj_id={$row['proj_name']}");
	}
	mysqli_query($mysqli_link,"delete from projects where user_id=(select id from users where str='".md5($_SESSION['auth'])."' limit 1);");
	echo "<script>location.href='projects.php';</script>";
}
require_once("footer.php");

?>