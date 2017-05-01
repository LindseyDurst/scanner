<?php
# test scan class
define("domain","ruseller.com");
require_once("./scan.class.php");
$target="http://ruseller.com/";
$proj_id=39;
$scan= new Scan($target,$proj_id,10);

?>