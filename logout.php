<?php 
session_start(); 
header("Cache-control: private"); // IE 6 Fix. 

include 'functions.php';

$_SESSION = array(); 
session_destroy(); 
if(isset($_SESSION['userid']))
{ 
	DisplayCommonHeader();
    	printf("Your session is still active."); 
} 
else 
{ 
	DisplayGeneralPublicHeader();	
		?>
		<meta http-equiv="Refresh" content="0; URL=./index.php">
		<?
} 
DisplayCommonFooter();
?>














