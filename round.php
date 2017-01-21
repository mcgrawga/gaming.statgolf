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
	print("<br><h3>Round $round</h3>");
	$sql = "select DATE_FORMAT(GameTime, '%b %D %Y %h:%i %p') as FormatedGameTime, g.* from game g where round = $round";
	//printf("%s", $sql);
	$result = mysql_query($sql) or die("Could not get a list of games: " . mysql_error());

	$RowCount = 1;
	printf("<form action=\"savepicks.php\" method=\"POST\">");
	?><table class="table table-striped"><tr><th>GAME</th><th>PICK VS SPREAD</th><th>OVER UNDER</th><th>SCORE</th><th>WINNER AGAINST SPREAD</th><th> WINNER OVER UNDER</th><th>POINTS</th></tr><?
	while ($row = mysql_fetch_array($result))
	{
		?><tr><?
		$classname = ($RowCount % 2) ? 'ScoreHistoryTDScores2' : 'ScoreHistoryTDScores1';
		extract($row);
		printf("<input value=\"$id\" type=\"hidden\" name=\"id$RowCount\">");
		?><td><?
		printf("$Team1 $Team1Spread vs. $Team2 $Team2Spread<br>$FormatedGameTime");
		?></td><?
		
		?><td><?
		// GAME DROP DOWN BOX
		if (GameHasStarted($id))
			printf("<select disabled name=\"choice$RowCount\">");
		else
			printf("<select name=\"choice$RowCount\">");
		printf("<option value=0>choose one...</option>");
		//printf("<option value=\"Team1\">$Team1 $Team1Spread</option>");
		//printf("<option value=\"Team2\">$Team2 $Team2Spread</option>");
		
		// if the user has already picked this game, mark it as selected
		$teamPicked = HasUserAlreadyPickedThisGame($id);
		if ($teamPicked == null)
		{
			printf("<option value=\"Team1\">$Team1 $Team1Spread</option>");
			printf("<option value=\"Team2\">$Team2 $Team2Spread</option>");
		}
		else if ($teamPicked == "Team1")
		{
			printf("<option value=\"Team1\" selected=\"selected\">$Team1 $Team1Spread</option>");
			printf("<option value=\"Team2\">$Team2 $Team2Spread</option>");
		}
		else
		{
			printf("<option value=\"Team1\">$Team1 $Team1Spread</option>");
			printf("<option value=\"Team2\" selected=\"selected\">$Team2 $Team2Spread</option>");
		}
		
		printf("</select>");
		?></td><?
		
		?><td><?
		// OVER UNDER DROP DOWN BOX
		if (GameHasStarted($id))
			printf("<select disabled name=\"choiceOverUnder$RowCount\">");
		else
			printf("<select name=\"choiceOverUnder$RowCount\">");
		
		printf("<option value=0>choose one...</option>");
		//printf("<option value=\"Team1\">$Team1 $Team1Spread</option>");
		//printf("<option value=\"Team2\">$Team2 $Team2Spread</option>");
		
		// if the user has already picked this game, mark it as selected
		$overOrUnder = HasUserAlreadyPickedThisGameOverUnder($id);
		if ($overOrUnder == null)
		{
			printf("<option value=\"over\">Over $OverUnder</option>");
			printf("<option value=\"under\">Under $OverUnder</option>");
		}
		else if ($overOrUnder == "over")
		{
			printf("<option value=\"over\" selected=\"selected\">Over $OverUnder</option>");
			printf("<option value=\"under\">Under $OverUnder</option>");
		}
		else
		{
			printf("<option value=\"over\">Over $OverUnder</option>");
			printf("<option value=\"under\" selected=\"selected\">Under $OverUnder</option>");
		}
		
		printf("</select>");		
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
	printf("<input class=\"btn btn-primary\" type=\"submit\" value=\"Save Picks\" name=\"SubmitPicks\">");
	
	
	//print("Userid:  $userid");
	printf("<br><br>Points for this round:  %s", getPointsForThisRound($round, $userid));
	printf("<br>Points for all rounds:  %s", getPointsForAllRounds($userid));
	
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














