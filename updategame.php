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
        <br><h3>Update a Game</h3>
        <?

	extract($_POST);
	if(isset($doUpdate))
	{
		$t1scr = "null";
		if (strlen($Team1Score) > 0)
			$t1scr = $Team1Score;
		$t2scr = "null";
		if (strlen($Team2Score) > 0)
			$t2scr = $Team2Score;
		$sql = "update game set Team1 ='$Team1', Team2 = '$Team2', Team1Spread = $Team1Spread, Team2Spread = $Team2Spread, Team1Score = $t1scr, Team2Score = $t2scr, Round = $Round, OverUnder = $OverUnder, GameTime = '$GameTime' where id = $id";
		//printf("%s", $sql);
		mysql_query($sql) or die("Could not update game: " . mysql_error());
		print("Man, I like how you sit on the couch all day and update these scores.  Makes it easy for the rest of us degenerate gamblers.<br>");
	}
	else
	{		
		extract($_GET);
		$sql = "select * from game where id = $id";
		//printf("%s", $sql);
		$result = mysql_query($sql) or die("Could not get game to update: " . mysql_error());
		extract(mysql_fetch_array($result))
		?>
		<form action="updategame.php" method="POST">
		<div class="form-group">
                        <label>Team1</label>
                        <input type="text" class="form-control" name="Team1" value="<?print("$Team1");?>">
                </div>
		<div class="form-group">
                        <label>Team2</label>
                        <input type="text" class="form-control" name="Team2" value="<?print("$Team2");?>">
                </div>
		<div class="form-group">
                        <label>Team1 Spread</label>
                        <input type="text" class="form-control" name="Team1Spread" value="<?print("$Team1Spread");?>">
                </div>
		<div class="form-group">
                        <label>Team2 Spread</label>
                        <input type="text" class="form-control" name="Team2Spread" value="<?print("$Team2Spread");?>">
                </div>
		<div class="form-group">
                        <label>Team1 Score</label>
                        <input type="text" class="form-control" name="Team1Score" value="<?print("$Team1Score");?>">
                </div>
		<div class="form-group">
                        <label>Team2 Score</label>
                        <input type="text" class="form-control" name="Team2Score" value="<?print("$Team2Score");?>">
                </div>
		<div class="form-group">
                        <label>Round</label>
                        <input type="text" class="form-control" name="Round" value="<?print("$Round");?>">
                </div>
		<div class="form-group">
                        <label>OverUnder</label>
                        <input type="text" class="form-control" name="OverUnder" value="<?print("$OverUnder");?>">
                </div>
		<div class="form-group">
                        <label>Game Time (CST) Format:  YYYY-MM-DD HH:MM military time: 2011-01-15 13:30</label>
                        <input type="text" class="form-control" name="GameTime" value="<?print("$GameTime");?>">
                </div>
		<input type="hidden" name="id" value="<?print("$id");?>">
		<br><br><input class="btn btn-primary" type="submit" name="doUpdate" value="Submit"></form><br>
		<?
	}
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














