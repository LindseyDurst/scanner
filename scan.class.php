<?php
#scan class
set_time_limit(0);
include("libs/class.diff.php");
require 'vendor/autoload.php';
session_start();
use Psr\Log\LogLevel;
class Scan{
/*
 *
 * bla bla
 * explanations to do
 *
 *
 *
 *
 */
	private $target;
	private $mysqli_link;
	private $depth=0;
	private $proj_id;
	private $def_depth;
	private $proxy=1;
	public $logger;
	public function __construct($target,$proj,$conf_depth,$proxy) {
		$this->mysqli_link=db_connect();
		$proj_name=mysqli_real_escape_string($this->mysqli_link,$proj);
		$proj_id=mysqli_query($this->mysqli_link,"SELECT date,id from projects where proj_name='{$proj_name}' and user_id=(select id from users where login='".user_name()."' limit 1) limit 1");
		$proj_id=mysqli_fetch_assoc($proj_id);
		$this->proj_id=$proj_id['id'];
		$date_of_creation=explode(" ",$proj_id['date']);
		$this->def_depth=$conf_depth;
		$this->proxy=$proxy;
		$this->target=$target;
		#$this->logger = new Katzgrau\KLogger\Logger(__DIR__.'/logs');
		# if you want logs in a specific file
		$this->logger = new Katzgrau\KLogger\Logger(__DIR__.'/logs',LogLevel::DEBUG,[
			'extension'      => 'txt',
			'dateFormat'     => 'Y-m-d G:i:s.u',
			'filename'       => $proj_name."_".$date_of_creation[0].".log",
			'flushFrequency' => false,
			'prefix'         => 'log_',
			'logFormat'      => false,
			'appendContext'  => true]);
	}
	public function search_additional_data(){
		#imitating work
	}
	public function check_for_sqli(){
		$res=$this->get_links($this->target);
		if($res == 1){
			$this->logger->info('Reached finall depth');
			$this->logger->info('Starting fuzzing');
			$this->sql_fuzzing();
		} else if($res == 2){
			$this->logger->info('Scan has been done before. Skipping to fuzzing');
			$this->sql_fuzzing();
		} else{
			$this->logger->info('Starting fuzzing');
			$this->sql_fuzzing();
		}
	}public function check_for_xss(){
		# XSS CHECK
		//$this->logger->warning('XSS CHECK DOESNT WORK YET');
		$this->xss_fuzzing();

	}
	protected function xss_fuzzing(){
		# XSS CHECK
		# look if vector is inserted in the page in initial stage. meaning no special chars etc
		$par="";
		
		$pages=mysqli_query($this->mysqli_link,"Select DISTINCT t1.page_hex, t1.page_id,t2.id as set_id, t4.page_name, t2.par_set,t3.type,t3.param_name from page_bank as t1, param_set as t2, parameters as t3, pages as t4 where t1.page_id = t2.page_id and t1.proj_id = {$this->proj_id} and t2.id=t3.set_id and t1.page_id=t4.id");
		$get_links=[];
		$post_links=[];
		while($page=mysqli_fetch_assoc($pages)){
			$par=$page['param_name'];
			$vectors=["<script>alert('{$par}');</script>","\"><script>alert('{$par}')</script>","\'><script>alert('{$par}')</script>"]; # more?
			// print_r($page);
			$link="https://".domain."/".$page['page_name'];
			if($page['type']=='GET'){
				foreach ($vectors as $vector) {
					$get_links[]=array('link'=>$link."?".$page['param_name']."=".$vector,'page'=>$page['page_hex']);
				}
				//print_r($get_links);
				// приклеить разные xss вектора ко всем параметрам. придется тоже с чередованием. ну или к разным векторам разный текст добавлять, чтобы потом можно было идентифицировать, какой параметр все-таки уязвим. ПРЕДЛАГАЮ цеплять к стандартному вектору вместо alert(1) - название параметра, к которому он клеется.
				// забили. просто по 1 параметру буду отправлять. :D

			}
			else if($page['type']=="POST"){
				foreach ($vectors as $vector) {
					$post_links[]=array('link'=>$link,'page'=>$page['page_hex'],'post_data'=>$page['param_name']."=".$vector);
				}
			}
		}
		$get_pages=$this->multiCurl($get_links,0);
		$post_pages=$this->multiCurl($post_links,1);

		foreach($get_pages as $page){
			if(preg_match("~>.*?<script>alert.*?</script>.*?<~ism",$page['page'])){
				$this->logger->info("XSS FOUND on {$page['link']}");
				$this->logger->info("Writing found vuln to DB");
				$pars = parse_url($page['link']);
				$pars=explode("=",$pars['query']);
				$ch=mysqli_query($this->mysqli_link,"SELECT if( exists(select id from vulns where vuln=\"XSS\" and exploit = \"{$pars[1]}\" and proj_id={$this->proj_id} and info=\"{$page['link']}\" limit 1),1,0) as ch");
				if($ch){
					$ch = mysqli_fetch_assoc($ch);
					if($ch['ch']==0){
						mysqli_query($this->mysqli_link,"INSERT INTO vulns values(null,{$this->proj_id},'XSS','".mysqli_real_escape_string($this->mysqli_link,$pars[0])."','".mysqli_real_escape_string($this->mysqli_link,urldecode($pars[1]))."','".mysqli_real_escape_string($this->mysqli_link,$page['link'])."')");
					} else{
						$this->logger->error("XSS already in database! Skipping to next page");
					}
				}

			}
		}
		// die();
		foreach($post_pages as $page){
			if(preg_match("~>.*?<script>alert.*?</script>.*?<~ism",$page['page'])){
				$this->logger->info("XSS FOUND on {$page['link']}");
				$this->logger->info("Writing found vuln to DB");
				$pars=explode("=",$page['post']);

				$ch=mysqli_query($this->mysqli_link,"SELECT if( exists(select id from vulns where vuln=\"XSS\" and exploit = \"{$pars[1]}\" and proj_id={$this->proj_id} and info=\"{$page['link']}\" limit 1),1,0) as ch");
				$ch = mysqli_fetch_assoc($ch);
				if($ch['ch']==0){
					mysqli_query($this->mysqli_link,"INSERT INTO vulns values(null,{$this->proj_id},'XSS','".mysqli_real_escape_string($this->mysqli_link,$pars[0])."','".mysqli_real_escape_string($this->mysqli_link,urldecode($pars[1]))."','".mysqli_real_escape_string($this->mysqli_link,$page['link'])."')");
				} else{
					$this->logger->error("XSS already in database! Skipping to next page");
				}
			}
		}
	}
	protected function sql_fuzzing(){
		#VECTORS
		$fuzz=array('\'','"','\'#','\\',"\\'","%'",'%"','\'-- 1');
		$vectors = array(array("true" => " and 3=3","false" => " and 3=4"),array("true" => "' and '3'='3","false" => "' and '3'='4"),array("true" => "\" and \"3\"=\"3","false" => "\" and \"3\"=\"4"),array("true" => "%' and \"3\"=\"3","false" => "%' and \"3\"=\"4"));
		#array("true" => "'and(\'3\'=\'3')#","false" => "'and(\'3\'=\'3#")
		#GETTING PAGES FROM DBS
		$data_arr=[];
		$param_sets=mysqli_query($this->mysqli_link,"SELECT * from param_set where proj_id={$this->proj_id};");
		$i=0;
		while($set_row=mysqli_fetch_assoc($param_sets)){
			# GET PAGE THAT RECIEVES THIS PARAMETERS
			$page=mysqli_query($this->mysqli_link,"Select page_name from pages where id={$set_row['page_id']};");
			$page=mysqli_fetch_assoc($page);
			$data_arr[$i]=array("link"=>"http://".domain."/".$page['page_name'],"get_pars"=>[],"post_pars"=>[],'combinations'=>[]);
			# NOW GET ALL PARAMETERS FROM PARTICULAR SET
			$params_4_set=mysqli_query($this->mysqli_link,"Select * from parameters where set_id={$set_row['id']} group by param_name;");
			while($par_row=mysqli_fetch_assoc($params_4_set)){				
				if($par_row['type']=="GET"){
					$data_arr[$i]['get_pars'][]=$par_row['param_name']."=".$par_row['value'];
				}
				else if($par_row['type']=="POST"){
					$data_arr[$i]['post_pars'][]=$par_row['param_name'];
				}
			}
			$i++;
		}
		$data_arr=$this->unique_multidim_array($data_arr,'link');
		$data_arr=array_values($data_arr);
		$this->logger->info("Got ".count($data_arr)." parameters from the DB");
		#print_r($data_arr);
		#$this->logger->debug("Parameters from DB:",$data_arr);
		
		# PREPARING LINKS FOR THE INITIAL PAGE GRAP AKA SAMPLE FOR COMPARING
		$this->logger->info("Preparing links for multicurl");
		$count1=0;$count2=0;
		for($i=0;$i<count($data_arr);$i++){
			for($j=0;$j<count($data_arr[$i]['get_pars']);$j++){
				if($j==0){
					$initial_get_links[$count1]['link']=$data_arr[$i]['link']."?".$data_arr[$i]['get_pars'][$j];
				} 
				else{
					$initial_get_links[$count1]['link'].="&".$data_arr[$i]['get_pars'][$j];
				}
			}
			for($j=0;$j<count($data_arr[$i]['post_pars']);$j++){
				if($j==0){
					$initial_post_links[$count2]['link']=$data_arr[$i]['link'];
					$initial_post_links[$count2]["post_data"]=$data_arr[$i]['post_pars'][$j]."=a";
				} 
				else{
					$initial_post_links[$count2]['post_data'].="&".$data_arr[$i]['post_pars'][$j]."=a";
				}
			}
			$count1++;
			$count2++;
		}
		$initial_get_links=array_values($initial_get_links);
		$initial_post_links=array_values($initial_post_links);
		$this->logger->info("Getting pages with GET parameters");
		#GETTING INITIAL PAGES
		$initial_get_pages=$this->multiCurl($initial_get_links,0);
		$this->logger->info("Got ".count($initial_get_pages). " pages with GET parameters");
		$this->logger->info("Getting pages with POST parameters");
		$initial_post_pages=$this->multiCurl($initial_post_links,1);
		$this->logger->info("Got ".count($initial_post_pages). " pages with POST parameters");
		$this->save_pages_to_db($initial_get_pages);
		$this->save_pages_to_db($initial_post_pages);
		# INITIAL FUZZING
		# JUST TESTING THE GROUNDS
		$this->logger->info("Preparing links for first stage fuzzing");
		$count1=0; $count2=0; 
		for($i=0;$i<count($data_arr);$i++){
			# GET PARAMS
			for($j=0;$j<count($data_arr[$i]['get_pars']);$j++){
				foreach ($fuzz as $v) {
					$links_get[$count1]['link']=$data_arr[$i]['link']."?".$data_arr[$i]['get_pars'][$j].urlencode($v);
					for($k=0;$k<count($data_arr[$i]['get_pars']);$k++){
						if($k!=($j)){
							$links_get[$count1]['link'].="&".$data_arr[$i]['get_pars'][$k];
						}
					}
					$count1++;
				}
			}
			# POST PARAMS
			for($l=0;$l<count($data_arr[$i]['post_pars']);$l++){
				foreach ($fuzz as $v) {
					$links_post[$count2]['link']=$data_arr[$i]['link'];
					$links_post[$count2]['post_data']=$data_arr[$i]['post_pars'][$l]."=a".urlencode($v);
					for($k=0;$k<count($data_arr[$i]['post_pars']);$k++){
						if($k!=($j)){
							$links_post[$count2]['post_data'].="&".$data_arr[$i]['post_pars'][$k];
						}
					}
					$count2++;
				}
			}
		}
		$links_get=array_values($links_get);
		$links_post=array_values($links_post);
		#$this->logger->debug("Links with fuzzed GET parameters:",$links_get);
		#$this->logger->debug("Links with fuzzed POST parameters:",$links_post);
		# GETTING PAGES WITH FUZZED GET PARAMETERS
		$this->logger->info("Getting pages with GET parameters");
		$first_stage_get=$this->multiCurl($links_get,0);
		$this->logger->info("Got ".count($first_stage_get). " pages with GET parameters");
		# GETTING PAGES WITH FUZZED POST PARAMETERS
		$this->logger->info("Getting pages with POST parameters");
		$first_stage_post=$this->multiCurl($links_post,1);
		$this->logger->info("Got ".count($first_stage_post). " pages with POST parameters");
		unset($links_get);
		unset($links_post);
		# ANALISE PAGES
		# GET
		$this->logger->info("Comparing pages with GET parameters");
		$first_fuzz_get=[];
		$count=0;
		for($i=0;$i<count($initial_get_pages);$i++){
			for($j=$count;$j<($count+count($fuzz));$j++){
				if($first_stage_get[$j]['status']!='404'){
					$this->logger->info("STEP ".$j);
					$this->logger->info("Initial link: ".$initial_get_pages[$i]['link']);
					$this->logger->info("Fuzzing link: ".$first_stage_get[$j]['link']);
					$diff= new Diff($initial_get_pages[$i]['page'],$first_stage_get[$j]['page']);
					$diffs=$diff->returnDifferent();
					if(count($diffs)){
						$first_fuzz_get[]=array('link'=>$initial_get_pages[$i]['link'],'page'=>$initial_get_pages[$i]['page'],'template'=>$diff->returnTemplate());
					}
					unset($diff);
				} else $this->logger->error("404 at link: ".$first_stage_get[$j]['link']);
			} 
			$count+=count($fuzz);
		}
		unset($initial_get_pages);
		unset($first_stage_get);
		$first_fuzz_get=$this->unique_multidim_array($first_fuzz_get,"link");
		$this->logger->info("Vulnerable pages with GET parameters:".count($first_fuzz_get));
		#$this->logger->debug("Vulnerable pages with GET parameters:",$first_fuzz_get);
		#POST
		$this->logger->info("Comparing pages with POST parameters ".$j);
		$count=0;
		$first_fuzz_post=[];
		for($i=0;$i<count($initial_post_pages);$i++){
			for($j=$count;$j<($count+count($fuzz));$j++){
				if($first_stage_post[$j]['status']!='404'){
					$this->logger->info("STEP ".$j);
					$this->logger->info("Page with POST parameters: ".$initial_post_pages[$i]['link']);
					$diff= new Diff($initial_post_pages[$i]['page'],$first_stage_post[$j]['page']);
					$diffs=$diff->returnDifferent();
					if(count($diffs)){
						$first_fuzz_post[]=array('link'=>$initial_post_pages[$i]['link'],'page'=>$initial_post_pages[$i]['page'],'template'=>$diff->returnTemplate());
					}
					unset($diff);
				} else $this->logger->error("404 at link: ".$first_stage_post[$j]['link']);
			} 
			$count+=count($fuzz);
		}
		$first_fuzz_post=$this->unique_multidim_array($first_fuzz_post,"link");
		$this->logger->info("Vulnerable pages with POST parameters:".count($first_fuzz_post));
		$first_fuzz_get=array_values($first_fuzz_get);
		$first_fuzz_post=array_values($first_fuzz_post);
		#$this->logger->debug("Vulnerable pages with POST parameters:",$first_fuzz_post);
		unset($initial_post_pages);
		unset($first_stage_post);
		# IF THERE WERE SOME CHANGES ON PAGES DURING FUZZING THEN WE DO THIS ->
		$this->logger->info("Starting next level fuzzing");
		if(count($first_fuzz_get)>0 ){ # GET PARS
			$this->logger->info("Fuzzing GET parameters");
			$links_get=[];
			# PREPEARING NEW FUZZING ARRAY WITH LOGICAL EQUATIONS
			$count=0;
			#$this->logger->info("DATA_ARR - ".count($data_arr));
			for($i=0;$i<count($data_arr);$i++){
				#if($this->in_array_rec($data_arr[$i]['link'],$first_fuzz_get)){
					for($j=0;$j<count($data_arr[$i]['get_pars']);$j++){
						foreach ($vectors as $v) {
							$links_get[$count]['link']=$data_arr[$i]['link']."?".$data_arr[$i]['get_pars'][$j].urlencode($v["true"]);
							$links_get[$count+1]['link']=$data_arr[$i]['link']."?".$data_arr[$i]['get_pars'][$j].urlencode($v["false"]);
							for($k=0;$k<count($data_arr[$i]['get_pars']);$k++){
								if($k!=($j)){
									$links_get[$count]['link'].="&".$data_arr[$i]['get_pars'][$k];
									$links_get[$count+1]['link'].="&".$data_arr[$i]['get_pars'][$k];
								}
							}
							$count+=2;
						}
					}
				#}
			}
			#$this->logger->debug("New GET links",$links_get);
			#GETTING PAGES
			$this->logger->info("Getting pages");
			#$this->logger->debug("Links:",$links_get);
			$second_stage_get=$this->multicurl($links_get,0);
			#$this->logger->debug("New pages:",$second_stage_get);
			#SEARCHING FOR DIFF CONTENT
			$count=0;
			$this->logger->info("Analysing pages with GET parameters");
			for($i=0;$i<count($first_fuzz_get);$i++){
				#$this->logger->info("Init link: ".$first_fuzz_get[$i]['link']);
				for($j=$count;$j<($count+count($second_stage_get)/2);$j+=2){
					#if($second_stage_get[$j]['status']!='no'){
						 // $this->logger->info("TEMPLATE: ".$second_stage_get[$j]['template']);
						 // $this->logger->info("LINK 1:".$links_get[$j]['link']." LINK 1: ".$links_get[$j]['link']);
						 // $this->logger->info("LINK 2:".$links_get[$j+1]['link']." LINK2: ".$links_get[$j+1]['link']);
						 // $this->logger->info("PAGE 1: ".$second_stage_get[$j]['page']);
						 // $this->logger->info("PAGE 2: ".$second_stage_get[$j+1]['page']);
						 // $this->logger->info("Preg_match TRUE:".preg_match($first_fuzz_get[$i]['template'], $second_stage_get[$j]['page']));
						 // $this->logger->info("Preg_match FALSE:".preg_match($first_fuzz_get[$i]['template'], $second_stage_get[$j+1]['page']));
						if(preg_match($first_fuzz_get[$i]['template'], $second_stage_get[$j]['page']) && !preg_match($first_fuzz_get[$i]['template'], $second_stage_get[$j+1]['page'])) {
						    $this->logger->warning("SQLI CONFIRMED!");
						    $this->logger->INFO("SQLI CONFIRMED!");
						    $arr=explode("?",$second_stage_get[$j]['link']);
							$arr=explode("&",$arr[1]);
							$par=[];
							foreach ($arr as $val) {
								if(preg_match("~\+and\+~i",$val)){
									$par=explode("=",$val);
									break;
								}
							}
							$this->logger->info("SQLi at: ".$first_fuzz_get[$i]['link']."\n with parameter: ".$par[0]." \n and exploit: ".urldecode($par[1]));
							#WRITING RESULT TO DB
							$link=mysqli_real_escape_string($this->mysqli_link,$first_fuzz_get[$i]['link']);
							mysqli_query($this->mysqli_link,"INSERT INTO vulns values(null,{$this->proj_id},'SQL injection','".mysqli_real_escape_string($this->mysqli_link,$par[0])."','".mysqli_real_escape_string($this->mysqli_link,urldecode($par[1]))."','".mysqli_real_escape_string($this->mysqli_link,$first_fuzz_get[$i]['link'])."')");
							if($this->mysqli_link->error){
								$this->logger->error($this->mysqli_link->error);
							}
						}
					#}
				}
				$count+=count($vectors)*2;
			}
			unset($links_get);
		} else $this->logger->WARNING("SQLi in GET parameters was not found!");
		#die();
		if(count($first_fuzz_post)>0){ # POST PARS
			$this->logger->info("Fuzzing POST parameters");
			$links_post=[];
			# PREPEARING NEW FUZZING ARRAY WITH LOGICAL EQUATIONS
			$count=0;
			for($i=0;$i<count($data_arr);$i++){
				if($this->in_array_rec($data_arr[$i]['link'],$first_fuzz_post)){
					for($j=0;$j<count($data_arr[$i]['post_pars']);$j++){
						foreach ($vectors as $v) {
							$links_post[$count]['link']=$data_arr[$i]['link'];
							$links_post[$count+1]['link']=$data_arr[$i]['link'];
							$links_post[$count]['post_data']=$data_arr[$i]['post_pars'][$j]."=a".urlencode($v["true"]);
							$links_post[$count+1]['post_data']=$data_arr[$i]['post_pars'][$j]."=a".urlencode($v["false"]);
							for($k=0;$k<count($data_arr[$i]['post_pars']);$k++){
								if($k!=($j)){
									$links_post[$count]['post_data'].="&".$data_arr[$i]['post_pars'][$k];
									$links_post[$count+1]['post_data'].="&".$data_arr[$i]['post_pars'][$k];
								}
							}
							$count+=2;
						}
					}
				}
			}
			#GETTING PAGES
			$this->logger->info("Getting pages");
			$second_stage_post=$this->multicurl($links_post,1);
			#SEARCHING FOR DIFF CONTENT
			$count=0;
			$this->logger->info("Analysing pages with POST parameters");
			for($i=0;$i<count($first_fuzz_post);$i++){
				$this->logger->info("Init link: ".$first_fuzz_post[$i]['link']);
				for($j=$count;$j<($count+count($second_stage_post));$j+=2){
					if($second_stage_post[$j]['status']!='no'){
						 // $this->logger->info("TEMPLATE: ".$first_fuzz_post[$i]['template']);
						 // $this->logger->info("LINK 1:".$links_post[$j]['link']." PARS PAGE 1: ".$links_post[$j]['post_data']);
						 // $this->logger->info("LINK 2:".$links_post[$j+1]['link']." PARS PAGE 2: ".$links_post[$j+1]['post_data']);
						 // $this->logger->info("PAGE 1: ".$second_stage_post[$j]['page']);
						 // $this->logger->info("PAGE 2: ".$second_stage_post[$j+1]['page']);
						 // $this->logger->info("Preg_match TRUE:".preg_match($first_fuzz_post[$i]['template'], $second_stage_post[$j]['page']));
						 // $this->logger->info("Preg_match FALSE:".preg_match($first_fuzz_post[$i]['template'], $second_stage_post[$j+1]['page']));
						if(preg_match($first_fuzz_post[$i]['template'], $second_stage_post[$j]['page']) && !preg_match($first_fuzz_post[$i]['template'], $second_stage_post[$j+1]['page'])) {
						    $this->logger->warning("SQLI CONFIRMED!");
							$arr=explode("&",$links_post[$j]['post_data']);
							$par=[];
							foreach ($arr as $val) {
								if(preg_match("~\+and\+~i",$val) || preg_match("~\'and\(~i",$val)){
									$par=explode("=",$val);
									break;
								}
							}
							$this->logger->info("SQLi at: ".$first_fuzz_post[$i]['link']."\n with parameter: ".$par[0]." \n and exploit: ".urldecode($par[1]));
							#WRITING RESULT TO DB
							$link=mysqli_real_escape_string($this->mysqli_link,$first_fuzz_get[$i]['link']);
							mysqli_query($this->mysqli_link,"INSERT INTO vulns values(null,{$this->proj_id},'SQL injection','{$par[0]}','".mysqli_real_escape_string($this->mysqli_link,urldecode($par[1]))."','".mysqli_real_escape_string($this->mysqli_link,$first_fuzz_get[$i]['link'])."')");
							if($this->mysqli_link->error){
								$this->logger->error($this->mysqli_link->error);
							}
						}
					}
				}
				$count+=count($vectors)*2;
			}
			unset($links_post);
		} else $this->logger->WARNING("SQLi in POST parameters was not found!");
		return;
	}
	protected function save_pages_to_db($pages){
		foreach ($pages as $page) {
			//echo $page['link']; 
			$page_name=parse_url($page['link']);
			$page_name=substr($page_name['path'],1);
			//echo $page_name;
			//die();
			$saving=mysqli_query($this->mysqli_link,"Insert into `page_bank` values(NULL,{$this->proj_id},(Select id from pages where page_name='{$page_name}' and proj_id={$this->proj_id}),'".bin2hex($page['page'])."');");
			//echo "Insert into `page_bank` values(NULL,{$this->proj_id},(Select id from pages where page_name='{$page_name}'),'".bin2hex($page['page'])."')<BR><BR>";
			// var_dump($saving);
			if(mysqli_error($this->mysqli_link)) {
				echo mysqli_error($this->mysqli_link);
				die();
			}
			//die();
		}
	}
	public function get_vulns(){
		$res=mysqli_query($this->mysqli_link,"Select * from  vulns where proj_id={$this->proj_id};");
		if($this->mysqli_link->error){
			$this->logger->error($this->mysqli_link->error);
			return;
		}
		$arr=[];
		while($row=mysqli_fetch_assoc($res)){
			$arr[]=['vuln'=>$row['vuln'],"parameter"=>$row['parameter'],"exploit"=>$row['exploit'],"info"=>$row['info']];
		}
		return $arr;
	}
		
	protected function in_array_rec($needle, $haystack, $strict = false){
	    foreach ($haystack as $item) {
	        if (($strict ? $item === $needle : $item == $needle) || (is_array($item) && $this->in_array_rec($needle, $item, $strict))) {
	            return true;
	        }
	    }
	    return false;
	}
	protected function unique_multidim_array($array, $key) { // func for unique links
	    $temp_array = array(); 
	    $i = 0; 
	    $key_array = array(); 
	    
	    foreach($array as $val) { 
	        if (!in_array($val[$key], $key_array)) { 
	            $key_array[$i] = $val[$key]; 
	            $temp_array[$i] = $val; 
	        } 
	        $i++; 
	    } 
	    return $temp_array; 
	} 
	public function get_links($target = "http://".domain."/"){ // get links from submitted page
		if(is_array($target)){ // gets an array 
			//if array is too big for curl
			$res = $this->multiCurl($target,0);
			$i=0;
			foreach($res as $el){
				$k=$this->dom_doc_links($target[$i]['link'],$el['page']); // 0 - out of depth range
				if($k==0){
					#$this->sql_fuzzing();
					return 1; #Reached finall depth
				}
			}
			$data=$this->get_pars_from_db();
			$this->depth++;
			$this->logger->info('DEPTH: '.$this->depth);
			$this->get_links($data);	
		}
		else if(is_string($target)){ // for the first initial page
			$query=mysqli_query($this->mysqli_link,"select count(full_link) as c from craw_pages where proj_id={$this->proj_id}");
			$c=mysqli_fetch_assoc($query);
			if($c['c']>2) {
				return 2; #Scan has been done before. Skipping to fuzzing
				// $this->logger->info('Scan has been done before. Skipping to fuzzing');
				// $this->sql_fuzzing();
			}
			$this->logger->info('Collecting pages');
			$query=mysqli_query($this->mysqli_link,"select if(exists(select page_name from pages where page_name like '/' and proj_id={$this->proj_id}),1,0) as ch;");
			$query=mysqli_fetch_assoc($query);
			//echo $this->mysqli_link->error;
			// TRY CATCH TO DO
			if($query['ch']==0)	mysqli_query($this->mysqli_link,"insert into pages values (null, {$this->proj_id},'/');");// root
			$page=$this->request($target); // get page
			$this->dom_doc_links($target,$page); // get links and write to db
			$data=$this->get_pars_from_db(); // get data from db
			$this->depth++;
			$this->logger->info('DEPTH: '.$this->depth);
			if(is_array($data))$this->get_links($data);	// recursion	
		}else return 0;
	}
	protected function post_params($page,$link){
		$dom = new DOMDocument;
		@$dom->loadHTML($page);
		$xpath = new DOMXPath($dom);
		$url=parse_url($link);
		$forms = $xpath->query("//form");
		$arr=[]; 
		$arr1=[]; $i=0;
		foreach ($forms as $child){
			$test=$xpath->query("@action",$child);
			$t=$test->item(0);
			$arr[$i]=[];
			if(isset($t->nodeValue))$arr[$i][]=$t->nodeValue; 
			else $arr[$i][]=$url['path'];
		   	$name = $xpath->query("descendant::input[@type='text' or @type='password' or @type='file']/@name",$child);
		    foreach ($name as $n) {
		        $arr[$i][]=$n->nodeValue;
		    }
		    $i++;
		}
		if(count($arr)>0){
			for($i=0;$i<count($arr);$i++){
				$query_set="";
				for ($k=1;$k<count($arr[$i]);$k++){
					$query_set.="post_".$arr[$i][$k];
					if(isset($arr[$i][$k+1]))$query_set.="&";
				}				
				@$query_res=mysqli_query($this->mysqli_link,"SELECT IF( EXISTS(SELECT * FROM param_set WHERE page_id=(select id from pages where page_name='".$arr[$i][0]."' and proj_id={$this->proj_id} limit 1)), 1, 0) as ch");
				@$q=mysqli_fetch_assoc($query_res);
				if($q['ch']==0){
					@$q=mysqli_query($this->mysqli_link,"insert into param_set values(null,{$this->proj_id},(select id from pages where page_name='".$arr[$i][0]."' and proj_id={$this->proj_id} limit 1),'{$query_set}');");
					$this->logger->info("Writing POST set: {$query_set}");
					//echo (mysqli_error($this->mysqli_link));
					if($q==false){
						mysqli_query($this->mysqli_link,"insert into pages values(null,{$this->proj_id},'".$arr[$i][0]."');");
						mysqli_query($this->mysqli_link,"insert into param_set values(null,{$this->proj_id},(select id from pages where page_name='".$arr[$i][0]."' and proj_id={$this->proj_id} limit 1),'{$query_set}');");
					}	
						//echo (mysqli_error($this->mysqli_link));				
				} else{
					$set=mysqli_query($this->mysqli_link,"SELECT * FROM param_set WHERE page_id=(select id from pages where page_name='".$arr[$i][0]."' and proj_id={$this->proj_id} limit 1)");
					$set=mysqli_fetch_assoc($set);
					if(stristr($set['par_set'],$query_set)) continue;
					$new_set=$set['par_set']."|".$query_set;
					$q=mysqli_query($this->mysqli_link,"update param_set set par_set = '{$new_set}' where par_set = '{$set['par_set']}';");
					$this->logger->info("Updating POST set {$set['par_set']} with {$new_set}");
					//echo (mysqli_error($this->mysqli_link));
				}
				for($j=1;$j<(count($arr[$i]));$j++){
					$q=mysqli_query($this->mysqli_link,"insert into parameters values(null,{$this->proj_id},(select id from param_set where page_id=(select id from pages where page_name='".$arr[$i][0]."' and proj_id={$this->proj_id} limit 1) limit 1),'POST','{$arr[$i][$j]}','');");
	# ВРОДЕ ТУТ ВСЕ ОК, НО ВЫЛЕТАЮТ НОТИСЫ.
					$this->logger->info("Writing POST parameters");
					//echo(mysqli_error($this->mysqli_link));
				}
				
			}
		} 
		else $this->logger->info("No POST parameters on the page: {$link}");
		//die();
		//return $arr;
	}
	protected function dom_doc_links($target,$page){ // func to extract links from page
		$dom = new DOMDocument;
		@$dom->loadHTML($page);
		$xpath = new DOMXPath($dom);
		$hrefs = $xpath->evaluate("/html/body//@href");		
		for ($i = 0; $i < $hrefs->length; $i++) { // getting all links on the submitted page
			$href = $hrefs->item($i);
			$url=parse_url($hrefs[$i]->value);
			// target - текущая страница, с которой собираем ссылки
			if(isset($url['host'])){
				if(domain==$url['host']){
					if(isset($url['path'])){
		              	if(isset($url['query']))$link=$url['host'].$url['path']."?".$url['query'];
		                else 
		                	$link=$url['host'].$url['path'];
		            }
		            else{
		              	if(isset($url['query']))$link=domain."/index.php?".$url['query'];
		                else $link=domain;
		            }
				}else continue 1;
			}else if(isset($url['path'])){
				if(preg_match("~^[/]~",$url['path'])){
					preg_match_all("~(.*)\/~", $target, $arr);
					if(isset($url['query']))$link=$arr[1][0].$url['path']."?".$url['query'];
					else $link=$arr[1][0].$url['path'];
				} else{				
					if(isset($url['query']))$link=domain.'/'.$url['path']."?".$url['query'];
					else $link=domain.'/'.$url['path'];
				}
			} else if(isset($url['query'])){
				preg_match_all("~(.*?)\?~", $target,$temp);
				if(isset($temp[1][0])){
					$link=$temp[1][0].$url['query'];
				}else{
					$link=$target."?".$url['query'];
				}
				
			}
			if(!isset($link)) continue;
			preg_match_all("~^(?:http.?:\/\/)(.*)~",$link,$arr); // if http doesnt go away =(
			if(isset($arr[1][0])) $link=$arr[1][0];
			preg_match_all("~(.*?)[/]$~",$link,$arr);
			if(isset($arr[1][0])) $link=$arr[1][0];
			
			if(isset($link)){
	/* CHECK IF THIS LINK EXISTS IN TABLE */
	//echo $link."<br>";
				$query_res=mysqli_query($this->mysqli_link,"SELECT IF( EXISTS(SELECT * FROM craw_pages WHERE full_link='".$link."' and proj_id={$this->proj_id} ), 1, 0) as ch");
				$q=mysqli_fetch_assoc($query_res);
				
				if($q['ch']==0 ){//|| $link==domain."/index.php?".$url['query']){
					#$this->logger->info("Found new link: {$link}");
					#echo $link."<br>";
	/* WRITE TO PAGES TABLE */
					$query_res=mysqli_query($this->mysqli_link,"insert into `craw_pages` values(null,{$this->proj_id},'".$link."',".$this->depth.",0)");
					if(!isset($url['path'])) break;
					preg_match_all("|^\/(.*)|i",$url['path'],$preg);
					if(isset($preg[1][0])){
						$url['path'] = $preg[1][0];
					}
					$page_test=mysqli_query($this->mysqli_link,"select if(exists(select * from `pages` where page_name='{$url['path']}' and proj_id={$this->proj_id}),1,0) as pg;");
					$page_test=mysqli_fetch_assoc($page_test);
					if($page_test['pg']==0){
						$path=addslashes($url['path']);
						$this->logger->info('Found new page: '.$path);
						$query_res=mysqli_query($this->mysqli_link,"insert into `pages` values(null,{$this->proj_id},'{$path}');");
					}
	/* IF THERE ARE GET PARAMETERS IN THE LINK */
					if(isset($url['query'])){ 
	/* SPLIT PARAMETERS */					
						$query_set_arr=preg_split("~&~", $url['query']);
						$query_pars="";
						for($i=0;$i<count($query_set_arr);$i++){
							$query_set_arr[$i]=preg_split("~=~", $query_set_arr[$i]);
							$query_pars.="get_".$query_set_arr[$i][0];
							if(isset($query_set_arr[$i+1])) $query_pars.="&";
						}
	/* WRITE TO PARAM_SET */
						$par_test=mysqli_query($this->mysqli_link,"select if(exists(select * from `param_set` where page_id = (select id from pages where page_name='{$url['path']}' and proj_id={$this->proj_id} limit 1) and par_set='{$query_pars}'),1,0) as pt;");
						$par_test=mysqli_fetch_assoc($par_test);
						if($par_test['pt']==0){
							$this->logger->info('Formed parameter set: '.$query_pars);
							$query_res=mysqli_query($this->mysqli_link,"insert into `param_set` values(null,{$this->proj_id},(select id from pages where page_name='".$url['path']."' and proj_id={$this->proj_id}),'".$query_pars."')");
						}
	/* CHECK IF LESS THEN 3 PARS IN DB */
						$query=mysqli_query($this->mysqli_link,"select count(param_name) as c from parameters where set_id=(select id from param_set where page_id = (select id from pages where page_name='".$url['path']."' and proj_id={$this->proj_id} limit 1) limit 1)");
						$c=mysqli_fetch_assoc($query);
						if($c['c']<=3){ 
	/* GET ALL PARAMS TO THIS LINK FROM DB */
							$query_test=mysqli_query($this->mysqli_link,"select * from parameters where set_id=(select id from param_set where page_id = (select id from pages where page_name='".$url['path']."' and proj_id={$this->proj_id} limit 1));");
							//if($query_test->num_rows!=0) {
	/* WRITE PARAMS TO DB */
								$arr=[];
								while($q=mysqli_fetch_assoc($query)){
									$arr[]=array($q['param_name'],$q['value']);
								}
								foreach ($query_set_arr as $el) {
									if(!in_array($el[1],$arr)){
										$query_res=mysqli_query($this->mysqli_link,"insert into `parameters` values(null,{$this->proj_id},(select id from param_set where page_id=(select id from pages where page_name='".$url['path']."' and proj_id={$this->proj_id})),'GET','".$el[0]."','".$el[1]."')"); // if unique value insert it
									}
								}
							//}
						}
					}
				} #endif is link in table
			}# endif isset link
		}
		$post_arr=$this->post_params($page,$target); // get post params from page
		$res=mysqli_query($this->mysqli_link,"update `craw_pages` set visited=1 where full_link='".$target."' and proj_id={$this->proj_id};");
		$this->logger->info('UPDATE: link '.$target.' is set to `visited`');
		if($this->depth>=$this->def_depth) return 0;
		return 1;
	}
	protected function get_pars_from_db(){ // get list of links from db
		$query_res=mysqli_query($this->mysqli_link,"select full_link,visited,count(full_link) as c from `scanner`.`craw_pages` where visited=0 and proj_id={$this->proj_id} limit 1000;");
		if(mysqli_error($this->mysqli_link)) {
			//echo mysqli_error($this->mysqli_link);
			die();
		}
		if(mysqli_num_rows($query_res)!=0){
			$data=[];$c=0;
			while($row=mysqli_fetch_assoc($query_res)){
				$c=$row['c'];
				$data[]=array("link"=>$row['full_link'],"visited"=>$row['visited']);
			}
			return $data;
		}
		 else return 0;
	}
	protected function multiCurl($data,$post) // multi requests 
	{
		$curls = array();
		$result = array();
		$mh = curl_multi_init();
		for($i=0,$j=0;$i<count($data);$i++){
	  		$curls[$j] = curl_init();
			curl_setopt($curls[$j], CURLOPT_URL,$data[$i]['link']);
			curl_setopt($curls[$j], CURLOPT_HEADER, false);
			curl_setopt($curls[$j], CURLOPT_RETURNTRANSFER, true);
			curl_setopt($curls[$j], CURLOPT_CONNECTTIMEOUT, 30);
			if($this->proxy==1){
				curl_setopt($curls[$j], CURLOPT_PROXY, "localhost");
				curl_setopt($curls[$j], CURLOPT_PROXYPORT, 8080);
			}
			curl_setopt($curls[$j], CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:47.0) Gecko/20100101 Firefox/47.0');
			curl_setopt($curls[$j], CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($curls[$j], CURLOPT_SSL_VERIFYHOST, false);
			curl_setopt($curls[$j], CURLOPT_NOSIGNAL,1);
			if($post==1){
				curl_setopt($curls[$j], CURLOPT_POST, true);
		    	curl_setopt($curls[$j], CURLOPT_POSTFIELDS, "{$data[$i]['post_data']}");
		    }
			curl_multi_add_handle($mh, $curls[$j]);
		    $j++;	
	  	}
		$running = null;
		do{
			curl_multi_exec($mh, $running); 
		} while($running > 0);
		for($i=0;$i<count($curls);$i++){
			$result[$i]['link']=$data[$i]['link'];
			$result[$i]['page'] = curl_multi_getcontent($curls[$i]);
			if($post==1){
				$result[$i]['post']=$data[$i]['post_data'];
			}
			$header = curl_getinfo($curls[$i], CURLINFO_HTTP_CODE);
	        ///проверка целостности страницы
	       	preg_match('#<html>.*?<\/html>#is',$result[$i]['page'],$content) ? $totality=1 : $totality=0;
	        if($header!=200)	$result[$i]['status']=$header;
	        else{
	            if($totality==0)
	                $result[$i]['status']="no";
	                $result[$i]['status']="ok";
	            }
			//echo $data[$i]['link']." - ".$result[$i]['status']."\n";

		    curl_multi_remove_handle($mh, $curls[$i]);
		}
		curl_multi_close($mh);
		return $result;
	}
	protected function request($link){ // функция отправки запросов
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $link);
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
		if($this->proxy==1){
			curl_setopt($ch, CURLOPT_PROXY, "localhost");
			curl_setopt($ch, CURLOPT_PROXYPORT, 8080); 
		}
		curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:47.0) Gecko/20100101 Firefox/47.0');
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
		$data = curl_exec($ch);
		curl_close($ch);
		return $data;
	}
}
?>