<?php
require_once('library/System.php');
System::init();
require_once("includes.php");
$controller = new Login_Controller();
$controller();
