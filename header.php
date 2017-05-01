<?php
#page header
require("config.php");
if(isset($_SESSION['auth']))show_user_tools();
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="">
    <meta name="author" content="">
    <link rel="icon" href="../bootstrap/favicon.ico">
    <title>SCANSCAN</title>
    <link href="../bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="css/main.css" rel="stylesheet">
    <link href="css/dino.css" rel="stylesheet">
    <link href="css/winball.css" rel="stylesheet">
    <script src="jquery-3.1.1.min.js"></script>
    <script src="../bootstrap/js/bootstrap.min.js"></script>
  </head>

  <body>

    <!-- Fixed navbar -->
    <nav class="navbar navbar-default navbar-fixed-top">
      <div class="container">
        <div class="navbar-header">
          <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#navbar" aria-expanded="false" aria-controls="navbar">
            <span class="sr-only">Toggle navigation</span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
          </button>
          <a class="navbar-brand" href="index.php">Scan</a>
        </div>
        <div id="navbar" class="navbar-collapse collapse">
          <ul class="nav navbar-nav">
            <li class="active"><a href="index.php">Home</a></li>
            <li><a href="about.php">About</a></li>
            <li><a href="contact.php">Contact</a></li>
          </ul>
          <ul class="nav navbar-nav navbar-right">
         	<li id="reg" class="no_user"><a href="register.php">Register</a></li>
            <li id="log" class="no_user"><a href="login.php">Login <span class="sr-only">(current)</span></a></li>
            <li class="user_tools" style="visibility:hidden;"><a href="scan.php">Scan</a></li>
            <li class="user_tools" style="visibility:hidden;"><a href="projects.php" >Projects</a></li>
            <li class="user_tools" style="visibility:hidden;"><a href="user.php"></a></li>
            <li class="user_tools" style="visibility:hidden;"><a href="logout.php">Logout</a></li>
          </ul>
        </div>
      </div>
    </nav>