<?php
#crawler
#gets data from scan.php and starts scanner
require_once("scan.class.php");
require("config.php");
require 'vendor/autoload.php';
if(isset($_POST['aim'])&& isset($_POST['dep']) && isset($_POST['proj']) && isset($_POST['proxy']) && isset($_POST['sqli']) && isset($_POST['xss'])){
	if($_POST['dep']=="") $def_depth=5; // DEFAULT
	else $def_depth=$_POST['dep'];

	preg_match_all("~(http.?://)([^/]+)(?:\/.*?\.php|\/|)~is",$_POST['aim'],$res);
	if(isset($res[1][0])){
		define("domain", $res[2][0]);
		$target=$res[1][0].domain."/";
	} else{
		preg_match_all("~([^/]+\.[^/]+)~is",$target,$res);
		if(isset($res[1][0])){
			define("domain", $res[1][0]);
			$target="http://".domain."/";
		}
	}
	# CALLING SCAN CLASS
	$data= new Scan($target,$_POST['proj'],$def_depth,intval($_POST['proxy']));
	#CHECKING FOR SQLI
	#DEBUG REASONS
	// echo "<pre>";
	// $data->check_for_xss();
	#SQLI
	if(isset($_POST['sqli']) && intval($_POST['sqli'])==1){
		$data->check_for_sqli();
	}
	# XSS
	if(isset($_POST['xss']) && intval($_POST['xss'])==1){
		$data->check_for_xss();
	}
	# PASTEBIN AND STUFF
	if(isset($_POST['adds']) && intval($_POST['adds'])==1){
		$data->search_additional_data();
	}
	# Display results
	$res=$data->get_vulns();
	if(is_array($res)){
		echo "<b><h3>Vulnerabilities found</h3><br><table id=\"return\" class=\"table\"><tr><td>Type</td><td>Link</td><td>Parameter</td><td>Exploit</td></tr></b>";
		foreach ($res as $vuln) {
			echo "<tr>
					<td>".htmlspecialchars($vuln['vuln'])."</td>
					<td>".htmlspecialchars($vuln['info'])."</td>
					<td>".htmlspecialchars($vuln['parameter'])."</td>
					<td>".htmlspecialchars($vuln['exploit'])."</td>
				</tr>";
		}
		echo "</table>";
	} else{
		echo "<h3>Unfortunally, no vulnerabilities were found :C</h3><p>To view all of the results of vulnerability search go to Projects, select the one you want to see information about and open scaning log.</p>";
	}
}
else {
	echo "<script>alert(\"Something went terribly wrong!\");document.location=\"scan.php\";";
	die();
}
// echo "</pre>";
?>