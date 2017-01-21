<?php 
session_start(); 
header("Cache-control: private"); // IE 6 Fix. 

include 'functions.php';

function DisplayPage()
{
	DisplayCommonHeader();
	ConnectToDB();

	print("<br><h3>Standings</h3>");
	
	
	$sql = "select distinct(round) from game;";
	//printf("%s", $sql);
	$resultRounds = mysql_query($sql) or die("Could not get a list of rounds: " . mysql_error());

	?><table class="table table-striped">
	<tr><th>PLACE</th><th>USER</th><th>TOTAL SCORE</th><?
	while ($row = mysql_fetch_array($resultRounds))
	{
		extract($row);
		printf("<th>ROUND $round</th>");
	}
	?></tr><?
	
	$sql = "select email, id from user;";
	//printf("%s", $sql);
	
	
	$result = mysql_query($sql) or die("Could not get a list of users: " . mysql_error());
	$TableArray = array();
	while ($row = mysql_fetch_array($result))
	{
		$RowArray = array();
		extract($row);
		$RowArray[] = $email;
		if (mysql_num_rows($resultRounds) > 0)
		{
			$RowArray[] = getPointsForAllRounds($id);
			mysql_data_seek($resultRounds,0);  // reset the result set
			while ($rowRound = mysql_fetch_array($resultRounds))
			{
				extract($rowRound);
				$RowArray[] = getPointsForThisRound($round, $id);
			}
		}
		$TableArray[] = $RowArray;
	}
	//print_r($TableArray);
	//printf("Count:  %s", count($TableArray));
	function cmp($a, $b)
	{
		if ( ! isset($a[1]) )
			$a[1] = null;
		if ( ! isset($b[1]) )
			$b[1] = null;
		if ($a[1] == $b[1])
			return 0;
		if ($a[1] < $b[1])
			return -1;
		else
			return 1;
	}
	usort($TableArray, "cmp");
	$TableArray = array_reverse($TableArray);
	$prevRowPlace = 0;
	$prevRowTotalScore = 1000;  
	$currentRowPlace = 0;
	for ($i=0; $i < count($TableArray); $i++)
	{
		?><tr><?
		$row = $TableArray[$i];
		if ($row[1] < $prevRowTotalScore)
		{
			printf("<td>%s</td>", ($i+1));
			$prevRowPlace = ($i+1);
			$prevRowTotalScore = $row[1];
		}
		else
		{
			printf("<td>%s</td>", $prevRowPlace);
		}
		for ($j=0; $j < count($row); $j++)
		{
			printf("<td>%s</td>", $row[$j]);
		}
		?></tr><?
	}
	
	
	/*
	while ($row = mysql_fetch_array($result))
	{
		extract($row);
		printf("<td>$email</td>");
		if (mysql_num_rows($resultRounds) > 0)
		{
			mysql_data_seek($resultRounds,0);  // reset the result set
			while ($rowRound = mysql_fetch_array($resultRounds))
			{
				extract($rowRound);
				printf("<td>%s</td>", getPointsForThisRound($round, $id));
			}
			printf("<td>%s</td>", getPointsForAllRounds($id));
		}
		?></tr><?
	}
	*/
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
		/*
		?>
		<meta http-equiv="Refresh" content="0; URL=./">
		<?
		*/
	}
?>














