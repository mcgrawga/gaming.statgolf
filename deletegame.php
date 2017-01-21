<?php 
session_start(); 
header("Cache-control: private"); // IE 6 Fix. 

include 'functions.php';

function DisplayPage()
{
	DisplayCommonHeader();
	ConnectToDB();
	if (!isAdmin($_SESSION['userid']))
		exit("You are not an admin.....");
        ?>
        <br><h3>Delete a Game</h3>
        <?

	extract($_GET);
	$sql = "delete from game where id = $id";
	//printf("%s", $sql);
	mysql_query($sql) or die("Could not delete game: " . mysql_error());
	print("You deleted that game so bad, it doesn't even know it ever existed.<br><br>");
}




	if (isset($_POST['LoginUser']))		// Check to see if we should login user
	{
		if ( ValidateCredentials($_POST['Email'], $_POST['Password']) )
			DisplayPage();
	}
	else if (isset($_SESSION['userid']) && isset($_SESSION['paidup']))	// Already logged in and account current?
	{
		//print("Got here 1");
		DisplayPage();
	}
	else		// Make them login
	{
		//print("Got here 2");
		/*
		?>
		<meta http-equiv="Refresh" content="0; URL=./index.php">
		<?
		*/
	}
?>














