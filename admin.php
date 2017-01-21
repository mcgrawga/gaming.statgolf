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

	?><br><h3>Admin Home</h3><?
	$sql = "select * from game;";
	//printf("%s", $sql);
	$resultRounds = mysql_query($sql) or die("Could not get a list of games: " . mysql_error());

	?><table class="table table-striped"><tr><th>TEAM1</th><th>TEAM2</th><th>TEAM1SPREAD</th><th>TEAM2SPREAD</td><th>TEAM1SCORE</th><th>TEAM2SCORE</th><th>ROUND</th><th>OVERUNDER</th><th></th><th></th></tr><?
	while ($row = mysql_fetch_array($resultRounds))
	{
		//$classname = ($RowCount % 2) ? 'ScoreHistoryTDScores2' : 'ScoreHistoryTDScores1';
		extract($row);
		printf("<tr><td>$Team1</td><td>$Team2</td><td>$Team1Spread</td><td>$Team2Spread</td><td>$Team1Score</td><td>$Team2Score</td><td>$Round</td><td>$OverUnder</td><td><a href=\"updategame.php?id=$id\">update</a></td><td><a href=\"deletegame.php?id=$id\">delete</a></td></tr>");
	}
	?></table><?
	
	
	
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














