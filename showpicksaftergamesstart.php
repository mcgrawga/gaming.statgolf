<?php 
session_start(); 
header("Cache-control: private"); // IE 6 Fix. 

include 'functions.php';

function DisplayPage()
{
	DisplayCommonHeader();
	ConnectToDB();
	
	print("<br><h3>All Saved Picks <span>Well, not all... Only picks for games that have started or been played.</span></h3>");

	$sql = "select * from pick p, game g, user u where p.gameid = g.id and p.userid = u.id and g.gametime < date_sub(now(), interval 6 hour) order by u.email asc, g.round asc, g.gametime asc;";
	//printf("%s", $sql);
	$result = mysql_query($sql) or die("Could not get all results: " . mysql_error());

	?><table class="table table-striped">
	<tr><th>USER</th><th>ROUND</th><th>GAME</th><th>OVER UNDER</th><th>GAME PICK</th><th>OVER UNDER PICK</th><th>POINTS</th></tr><?
	while ($row = mysql_fetch_array($result))
	{
		extract($row);
		printf("<tr><td>$email</td><td>$Round</td><td>$Team1 $Team1Spread vs $Team2 $Team2Spread</td><td>$OverUnder</td>");
		if ($choice == "Team1")
			printf("<td>$Team1</td>");
		else if ($choice == "Team2")
			printf("<td>$Team2</td>");
		else
			printf("<td></td>");
			
		/*
		if ($overunder == "Team1")
			printf("<td>$Team1</td>");
		else if ($choice == "Team2")
			printf("<td>$Team2</td>");
		else
			printf("<td></td>");
	*/
		print("<td>$overunder</td>");
		printf("<td>%s</td></tr>", getPointsForThisGame($gameid, $userid));
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














