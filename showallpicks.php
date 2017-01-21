<?php 
session_start(); 
header("Cache-control: private"); // IE 6 Fix. 

include 'functions.php';

function DisplayPage()
{
	DisplayCommonHeader();
	ConnectToDB();
	$userid = $_SESSION['userid'];
	extract($_GET);

	print("<br><h3>My Saved Picks</h3>");
	
	$sql = "select DATE_FORMAT(GameTime, '%b %D %Y %h:%i %p') as FormatedGameTime, g.* from game g order by round asc";
	//printf("%s", $sql);
	$result = mysql_query($sql) or die("Could not get a list of games: " . mysql_error());

	$RowCount = 1;
	?><table class="table table-striped"><tr><th>GAME</th><th>ROUND</th><th>PICK VS SPREAD</th><th>OVER UNDER</th><th>SCORE</th><th>WINNER AGAINST SPREAD</th><th>WINNER OVER UNDER</th><th>POINTS</th></tr><?
	while ($row = mysql_fetch_array($result))
	{
		?><tr><?
		$classname = ($RowCount % 2) ? 'ScoreHistoryTDScores2' : 'ScoreHistoryTDScores1';
		extract($row);
		?><td><?
		printf("$Team1 $Team1Spread vs. $Team2 $Team2Spread<br>$FormatedGameTime");
		?></td><?
		?><td><?
		printf("$Round");
		?></td><?
		
		?><td><?
		$choice = HasUserAlreadyPickedThisGame($id);
		if ($choice == "Team1")
			printf("$Team1 $Team1Spread");
		else if ($choice == "Team2")
			printf("$Team2 $Team2Spread");
		?></td><?
		
		?><td><?
		$overOrUnder = HasUserAlreadyPickedThisGameOverUnder($id);
		if ($overOrUnder)
			print("$overOrUnder $OverUnder")
		?></td><?
		
		
		$Winner = "Push";
		if ($Team1Score != null && $Team2Score != null)
		{
			$Team1SpreadScore = $Team1Score;
			$Team2SpreadScore = $Team2Score + $Team2Spread;
			if ($Team1SpreadScore > $Team2SpreadScore)
				$Winner = $Team1;
			else if ($Team2SpreadScore > $Team1SpreadScore)
				$Winner = $Team2;
			print("<td>$Team1 $Team1Score, $Team2 $Team2Score</td> <td>$Winner</td>");
		}
		else
			print("<td>$Team1 N/A, $Team2 N/A</td> <td>N/A</td>");
			
			
		
		?><td><?
		$Winner = "Push";
		if ($Team1Score != null && $Team2Score != null)
		{
			$Total = $Team1Score + $Team2Score;
			if ($Total > $OverUnder)
				$Winner = "Over";
			else if ($Total < $OverUnder)
				$Winner = "Under";
			print("$Winner");
		}
		else
			print("N/A");	
		?></td><?
		
		?><td><?
		$points = getPointsForThisGame($id, $userid);
		if (strlen($points) >  0)
			print("$points");
		else
			print("N/A");
		?></td><?	
			
		
		
		$RowCount++;
		?></tr><?
	}
	?></table><br><?
	
	?>
	<br>
	<?
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














