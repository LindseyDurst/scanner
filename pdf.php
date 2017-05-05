<?php
require 'vendor/autoload.php';
use Dompdf\Dompdf;
require 'config.php';
session_start();
// check_auth();

class buildPDF
{	
	private $dompdf;
	private $proj_id;
	private $mysqli_link;
	
	function __construct($proj_id)
	{
		$this->proj_id=$proj_id;
		$this->dompdf= new Dompdf();
		$this->mysqli_link=db_connect();
		$html = $this->buildHtml($this->proj_id);
		// echo $html; die();
		$this->dompdf($html);
	}
	private function buildHtml($proj){
		$vulns=mysqli_query($this->mysqli_link,"Select * from vulns  where proj_id={$this->proj_id} and (select id from users where login='".user_name()."' limit 1)=(select user_id from projects where id={$this->proj_id})");
		if($vulns){
			$vuln_table="<center><h3>Vulnerabilities Found</h3></center><table style=\"page-break-after:always;\"><thead><tr><th>Impact</th><th>Type</th><th>Link</th><th>Parameter</th><th>Exploit</th></tr></thead><tbody>";
			while($vuln=mysqli_fetch_assoc($vulns)){
				// echo $vuln;
				if($vuln['vuln']=="XSS")
					$impact="Medium";
				else $impact="High";
				$tr_style="";
				if ($impact=="High"){
					$tr_style="style=\"background-color:#ff9999\"";
				} else{
					$tr_style="style=\"background-color:#ffe8cc\"";
				}
				$new_row="<tr {$tr_style}><td>{$impact}</td><td>".htmlspecialchars($vuln['vuln'])."</td><td>".htmlspecialchars($vuln['info'])."</td><td>".htmlspecialchars($vuln['parameter'])."</td><td>".htmlspecialchars($vuln['exploit']).".</td></tr>";
				$vuln_table.=$new_row;
			}
			$vuln_table.="</tbody></table><br><br>";

		}
		$pages=mysqli_query($this->mysqli_link,"Select * from pages where proj_id={$this->proj_id} and (select id from users where login='".user_name()."' limit 1)=(select user_id from projects where id={$this->proj_id})");
		if($pages){
			$page_table="<center><h3>Pages Found</h3></center><table style=\"page-break-after:always;\"><thead><tr><th>Page name</th></thead><tbody>";
			while($page=mysqli_fetch_assoc($pages)){
				// echo $vuln;
				$new_row="<tr><td>".htmlspecialchars($page['page_name'])."</td></tr>";
				$page_table.=$new_row;
			}
			$page_table.="</tbody></table><br><br>";

		}
		$params=mysqli_query($this->mysqli_link,"Select * from parameters where proj_id={$this->proj_id} and (select id from users where login='".user_name()."' limit 1)=(select user_id from projects where id={$this->proj_id})");
		if($this->mysqli_link->error){
			echo $this->mysqli_link->error;
			die();
		}
		if($params){
			$param_table="<center><h3>Parameters Found</h3></center><table style=\"page-break-after:always;\"><thead><tr><th>Type</th><th>Name</th> <th>Value</th></thead><tbody>";
			while($param=mysqli_fetch_assoc($params)){
				// echo $vuln;
				$new_row="<tr><td>".htmlspecialchars($param['type'])."</td><td>".htmlspecialchars($param['param_name'])."</td><td>".htmlspecialchars($param['value'])."</td></tr>";
				$param_table.=$new_row;
			}
			$param_table.="</tbody></table><br><br>";

		}
		$pastes=mysqli_query($this->mysqli_link,"Select * from additional_info where proj_id={$this->proj_id} and (select id from users where login='".user_name()."' limit 1)=(select user_id from projects where id={$this->proj_id})");
		if($this->mysqli_link->error){
			echo $this->mysqli_link->error;
			die();
		}
		if($pastes){
			$paste_table="<center><h3>Additional info Found</h3></center><table style=\"page-break-after:always;\"><thead><tr><th>Link</th><th>Short review</th></thead><tbody>";
			while($paste=mysqli_fetch_assoc($pastes)){
				// echo $vuln;
				$new_row="<tr><td>".htmlspecialchars($paste['paste_link'])."</td><td>".htmlspecialchars($paste['review'])."</td></tr>";
				$paste_table.=$new_row;
			}
			$paste_table.="</tbody></table><br><br>";

		}
		$template_header=file_get_contents("templates/template_header.html");
		$template_footer=file_get_contents("templates/template_footer.html");

		$html=$template_header.$page_table.$param_table.$vuln_table.$paste_table.$template_footer;

		return $html;
	}
	private function dompdf($html){
		$this->dompdf->loadHtml($html);
		// $this->dompdf->setPaper('A4', 'landscape');
		$this->dompdf->render();
		$this->dompdf->stream("dompdf_out.pdf", array("Attachment" => false));
	}
}
if(isset($_GET['id'])){
	$pdf=new buildPDF(intval($_GET['id']));
}
?>