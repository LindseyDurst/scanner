<?php
require("test\\scanner\\func backup.php");

$target='http://somethingelse.comli.com/index.php';
//$target='http://ruseller.com/'; // со слэшем на хвосте

preg_match_all("~(?:http.?://)([^/]+)(?:\/.*?\.php|)~is",$target,$res);

define("domain", $res[1][0]);
get_links($target); // get first links

?>