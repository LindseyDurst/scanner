<?php
echo "hi";
//echo file_get_contents("../logs/test.txt");
//die();
  for($i=0;$i<50;$i++){
    file_put_contents("../logs/logs_test_22.03.2017.log","[2017-03-13 16:05:27.351062] [info] Getting pages with GET parameters {$i}[2017-03-13 16:05:27.351062] [info] Getting pages with GET parameters {$i}[2017-03-13 16:05:27.351062] [info] Getting pages with GET parameters {$i}[2017-03-13 16:05:27.351062] [info] Getting pages with GET parameters {$i}[2017-03-13 16:05:27.351062] [info] Getting pages with GET parameters {$i}\n",FILE_APPEND);
    echo $i."<br>";
    flush();
    sleep(2);
  }
?>