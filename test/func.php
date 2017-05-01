<?php
/* FUNCTIONS */

// $( ".div_id" ).prepend( "text that goes before divs inner text" ); // USE TO CUSTOMIZE OUTPUT http://api.jquery.com/prepend/
// OR
// var div=getElementById('divid');
// div.insertBefore


require_once("config.php");
$depth=0;
$mysqli_link=db_connect();
global $mysqli_link;
global $proj_id;

function fuzzing_light(){
	global $mysqli_link;
	global $proj_id;
	$fuzz=array('\'','"',';','\\');
	$p_get=mysqli_query($mysqli_link,"SELECT * from parameters where proj_id={$proj_id['id']};");
	//echo (mysqli_error($mysqli_link));
	$link_arr=[];
	while($row=mysqli_fetch_assoc($p_get)){
		if($row['type']=='GET'){
			$page4par=mysqli_query($mysqli_link,"SELECT page_name from pages where id=(select page_id from param_set where id=(select set_id from parameters where proj_id={$proj_id['id']} and type='GET' and param_name='{$row['param_name']}'  limit 1)  limit 1)");
			$page4par=mysqli_fetch_assoc($page4par);
			//echo (mysqli_error($mysqli_link));
			//if(intval($row['value'])!=0) -> string
			$link_arr[]="http://".domain."/".$page4par['page_name']."?".$row['param_name']."=VECTOR";
		}
	}
	$link_arr=array_unique($link_arr);
	print_r($link_arr);
	die();
}

function get_links($target){ // get links from submitted page
	global $depth;
	if(is_array($target)){ // gets an array 
		//if array is too big for curl
		$res = multiCurl($target);
		$i=0;
		foreach($res as $el){
			$k=dom_doc_links($target[$i]['link'],$el['page']); // 0 - out of depth range
			if($k==0){
				echo("<br>done<br>");
				return 1;
			}
		}
		$data=get_data_from_db();
		$depth++;
		get_links($data);	
	}
	else if(is_string($target)){ // for the first initial page
		//echo $target."<br>";
		global $mysqli_link;global $proj_id;
		$query=mysqli_query($mysqli_link,"select if( exists(select page_name from pages where page_name like '/' and proj_id={$proj_id['id']}),1,0) as ch;");
		$query=mysqli_fetch_assoc($query);
		if($query['ch']==0)	mysqli_query($mysqli_link,"insert into pages values (null, {$proj_id['id']},'/');");// root
		$page=request($target); // get page
		dom_doc_links($target,$page); // get links and write to db
		$data=get_data_from_db(); // get data from db
		$depth++;
		if(is_array($data))get_links($data);	// recursion			
	}else return 0;
}

function post_params($page,$link){
	global $proj_id;
	global $mysqli_link;
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
	//$url=parse_url($link);
	//print_r($arr); // в массиве form action file и имена параметров
	//die();	// WHAT TO DO WITH ARR[$I][0]?? PAGE ACTION
	if(count($arr)>0){
		for($i=0;$i<count($arr);$i++){
			$query_set="";
			for ($k=1;$k<count($arr[$i]);$k++){
				$query_set.="post_".$arr[$i][$k];
				if(isset($arr[$i][$k+1]))$query_set.="&";
			}
			
				$query_res=mysqli_query($mysqli_link,"SELECT IF( EXISTS(SELECT * FROM param_set WHERE page_id=(select id from pages where page_name='".$arr[$i][0]."' and proj_id={$proj_id['id']} limit 1)), 1, 0) as ch");
				$q=mysqli_fetch_assoc($query_res);
				if($q['ch']==0){
					$q=mysqli_query($mysqli_link,"insert into param_set values(null,{$proj_id['id']},(select id from pages where page_name='".$arr[$i][0]."' and proj_id={$proj_id['id']} limit 1),'{$query_set}');");
					echo "<br>WRITING POST SET {$query_set}: <br>";
					echo (mysqli_error($mysqli_link));
					if($q==false){
						mysqli_query($mysqli_link,"insert into pages values(null,{$proj_id['id']},'".$arr[$i][0]."');");
						mysqli_query($mysqli_link,"insert into param_set values(null,{$proj_id['id']},(select id from pages where page_name='".$arr[$i][0]."' and proj_id={$proj_id['id']} limit 1),'{$query_set}');");
					}	
					echo (mysqli_error($mysqli_link));				
				} else{
					$set=mysqli_query($mysqli_link,"SELECT * FROM param_set WHERE page_id=(select id from pages where page_name='".$arr[$i][0]."' and proj_id={$proj_id['id']} limit 1)");
					$set=mysqli_fetch_assoc($set);
					if(stristr($set['par_set'],$query_set)) continue;
					$new_set=$set['par_set']."|".$query_set;
					$q=mysqli_query($mysqli_link,"update param_set set par_set = '{$new_set}' where par_set = '{$set['par_set']}';");
					echo "<br>UPDATING PAR SET WITH POST {$new_set}: <br>";
					echo (mysqli_error($mysqli_link));
				}
				for($j=1;$j<(count($arr[$i]));$j++){
					$q=mysqli_query($mysqli_link,"insert into parameters values(null,{$proj_id['id']},(select id from param_set where page_id=(select id from pages where page_name='".$arr[$i][0]."' and proj_id={$proj_id['id']} limit 1) limit 1),'POST','{$arr[$i][$j]}','');");
# ВРОДЕ ТУТ ВСЕ ОК, НО ВЫЛЕТАЮТ НОТИСЫ.

					//echo "insert into parameters values(null,{$proj_id['id']},(select id from param_set where page_id=(select id from pages where page_name='".$arr[$i][0]."' and proj_id={$proj_id['id']} limit 1) limit 1),'POST','{$arr[$i][$j]}','');";
					echo "<br>WRITING POST PARAMETERS : <br>";
					echo(mysqli_error($mysqli_link));
				}
			
		}
	} else echo "<BR>NO POST PARAMS ON THE PAGE <BR>";
	//die();
	//return $arr;
}
function  dom_doc_links($target,$page){ // func to extract links from page
	global $proj_id;
	$dom = new DOMDocument;
	@$dom->loadHTML($page);
	$xpath = new DOMXPath($dom);
	$hrefs = $xpath->evaluate("/html/body//@href");
	global $mysqli_link;
	global $def_depth;
	global $depth;
	
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
				if(isset($url['query']))$link=$url['host'].$url['path']."?".$url['query'];
				else $link=$url['host'].$url['path'];
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
			$query_res=mysqli_query($mysqli_link,"SELECT IF( EXISTS(SELECT * FROM craw_pages WHERE full_link='".$link."' and proj_id={$proj_id['id']} ), 1, 0) as ch");
			$q=mysqli_fetch_assoc($query_res);
			
			if($q['ch']==0 ){//|| $link==domain."/index.php?".$url['query']){
				echo $link."<br>";
/* WRITE TO PAGES TABLE */
				$query_res=mysqli_query($mysqli_link,"insert into `craw_pages` values(null,{$proj_id['id']},'".$link."',".$depth.",0)");
				if(!isset($url['path'])) break;
				preg_match_all("|^\/(.*)|i",$url['path'],$preg);
				if(isset($preg[1][0])){
					$url['path'] = $preg[1][0];
				}
				$page_test=mysqli_query($mysqli_link,"select if(exists(select * from `pages` where page_name='{$url['path']}' and proj_id={$proj_id['id']}),1,0) as pg;");
				$page_test=mysqli_fetch_assoc($page_test);
				if($page_test['pg']==0){
					$path=addslashes($url['path']);
					$query_res=mysqli_query($mysqli_link,"insert into `pages` values(null,{$proj_id['id']},'{$path}');");
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
					$par_test=mysqli_query($mysqli_link,"select if(exists(select * from `param_set` where page_id = (select id from pages where page_name='{$url['path']}' and proj_id={$proj_id['id']} limit 1) and par_set='{$query_pars}'),1,0) as pt;");
					$par_test=mysqli_fetch_assoc($par_test);
					if($par_test['pt']==0){
						$query_res=mysqli_query($mysqli_link,"insert into `param_set` values(null,{$proj_id['id']},(select id from pages where page_name='".$url['path']."' and proj_id={$proj_id['id']}),'".$query_pars."')");
					}
/* CHECK IF LESS THEN 3 PARS IN DB */
					$query=mysqli_query($mysqli_link,"select count(param_name) as c from parameters where set_id=(select id from param_set where page_id = (select id from pages where page_name='".$url['path']."' and proj_id={$proj_id['id']} limit 1) limit 1)");
					$c=mysqli_fetch_assoc($query);
					if($c['c']<=3){ 
/* GET ALL PARAMS TO THIS LINK FROM DB */
						$query_test=mysqli_query($mysqli_link,"select * from parameters where set_id=(select id from param_set where page_id = (select id from pages where page_name='".$url['path']."' and proj_id={$proj_id['id']} limit 1));");
						//if($query_test->num_rows!=0) {
/* WRITE PARAMS TO DB */
							$arr=[];
							while($q=mysqli_fetch_assoc($query)){
								$arr[]=array($q['param_name'],$q['value']);
							}
							foreach ($query_set_arr as $el) {
								if(!in_array($el[1],$arr)){
									$query_res=mysqli_query($mysqli_link,"insert into `parameters` values(null,{$proj_id['id']},(select id from param_set where page_id=(select id from pages where page_name='".$url['path']."' and proj_id={$proj_id['id']})),'GET','".$el[0]."','".$el[1]."')"); // if unique value insert it
								}
							}
						//}
					}
				}
			} #endif is link in table
		}# endif isset link
	}
	$post_arr=post_params($page,$target); // get post params from page
	$res=mysqli_query($mysqli_link,"update `craw_pages` set visited=1 where full_link='".$target."' and proj_id={$proj_id['id']};");
	if($depth>=$def_depth) return 0;
	return 1;
}

function get_data_from_db(){ // get list of links from db
	global $mysqli_link;
	global $proj_id;
	$query_res=mysqli_query($mysqli_link,"select full_link,visited,count(full_link) as c from `scanner`.`craw_pages` where visited=0 and proj_id={$proj_id['id']} limit 1000;");
	if(mysqli_error($mysqli_link)) {
		//echo mysqli_error($mysqli_link);
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
function multiCurl($data) // multi requests 
{
	$curls = array();
	$result = array();
	$mh = curl_multi_init();
	for($i=0,$j=0;$i<count($data);$i++){
  		$curls[$j] = curl_init();
		curl_setopt_array($curls[$j], array(
			CURLOPT_URL => $data[$i]['link'],
			CURLOPT_HEADER => false,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_CONNECTTIMEOUT => 30,
			CURLOPT_PROXY => "localhost",
			CURLOPT_PROXYPORT => 8080, 
			CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:47.0) Gecko/20100101 Firefox/47.0',
			CURLOPT_SSL_VERIFYPEER => false,
			CURLOPT_SSL_VERIFYHOST => false,
			CURLOPT_NOSIGNAL=> 1
			)); 
		curl_multi_add_handle($mh, $curls[$j]);
	    $j++;	
  	}
	$running = null;
	do{
		curl_multi_exec($mh, $running); 
	} while($running > 0);
	for($i=0;$i<count($curls);$i++){
		$result[$i]['page'] = curl_multi_getcontent($curls[$i]);	
		$header = curl_getinfo($curls[$i], CURLINFO_HTTP_CODE);
        ///проверка целостности страницы
       	preg_match('#<html>.*?<\/html>#is',$result[$i]['page'],$content) ? $totality=1 : $totality=0;
        if($header!=200)	$result[$i]['status']=$header;
        else{
            if($totality==0)
                $result[$i]['status']="not-totaly";
                $result[$i]['status']="ok";
            }
		//echo $data[$i]['link']." - ".$result[$i]['status']."\n";
	    curl_multi_remove_handle($mh, $curls[$i]);
	}
	curl_multi_close($mh);
	return $result;
}

function unique_multidim_array($array, $key) { // func for unique links
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
function request($link){ // функция отправки запросов
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $link);
	curl_setopt($ch, CURLOPT_HEADER, false);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
	curl_setopt($ch, CURLOPT_PROXY, "localhost"); //PROXY| IF VPN COMMENT IT
	curl_setopt($ch, CURLOPT_PROXYPORT, 8080); 
	curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:47.0) Gecko/20100101 Firefox/47.0');
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
	$data = curl_exec($ch);
	curl_close($ch);
	return $data;
}
?>