<?php 
session_start(); 
header("Cache-control: private"); // IE 6 Fix. 

include 'functions.php';

function DisplayPage()
{
	DisplayCommonHeader();
	ConnectToDB();
	$sql = "select distinct round from game order by round asc";
	printf("<br>");
	$result = mysql_query($sql) or die("Could not get a list of rounds: " . mysql_error());
        
        $RowCount = 0;		
	while ($row = mysql_fetch_array($result))
	{
		$classname = ($RowCount % 2) ? 'ScoreHistoryTDScores2' : 'ScoreHistoryTDScores1';
		extract($row);
		printf("<h2><a href=\"round.php?round=$round\">Round $round</a><small><------- Click here to enter picks</small></h2>");
		$RowCount++;
	}
	
	?>


	<?
	$np = GetTotalNumPicks();
	$wp = GetTotalNumWinningPicks();
	if ($np > 0)
	{
		print("<h2>Some Stats...</h2>");
		$pb = round(($wp/$np)*100, 0);
		printf("Total number of picks against the spread:  %s<br>", $np);
		printf("Total number of winning picks against the spread:  %s<br>", $wp);
		printf("Probability you will pick a winner against the spread:  %s%%<br><br>", $pb);
	
		$ou = GetTotalNumWinningPicksOverUnder();
		$pb = round(($ou/$np)*100, 0);
		printf("Total number of picks against the over under:  %s<br>", $np);
		printf("Total number of winning picks against the over under:  %s<br>", $ou);
		printf("Probability you will pick a winner against the over under:  %s%%<br><br>", $pb);
	}
	
}




	if (isset($_POST['LoginUser']))		// Check to see if we should login user
	{
		if ( ValidateCredentials($_POST['Email'], $_POST['Password']) )
			DisplayPage();
	}
	else if (isset($_SESSION['userid']) && isset($_SESSION['paidup']))	// Already logged in and account current?
	{
		DisplayPage();
	}
	else		// Make them login
	{
		?>
		<meta http-equiv="Refresh" content="0; URL=index.php">
		<?
	}
?>














