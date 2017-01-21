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
	extract($_POST);
	if (isset($addgame))
	{
		$sql = "insert into game (Team1, Team2, Team1Spread, Team2Spread, Round, OverUnder, GameTime) values ('$Team1', '$Team2', $Team1Spread, $Team2Spread, $Round, $OverUnder, '$GameTime')";
		//printf("%s", $sql);
		mysql_query($sql) or die("Could not add game: " . mysql_error());
		print("Holy Tomato, you added that game like a champ.<br>");
	}
	else
	{		
		?>
		<h2>Add a Game</h2>
		<form action="addgame.php" method="POST">
		<div class="form-group">
    			<label for="Team1">Team1</label>
			<input type="text" class="form-control" name="Team1" id="Team1">
  		</div>
		<div class="form-group">
    			<label for="Team2">Team2</label>
			<input type="text" class="form-control" name="Team2" id="Team2">
  		</div>
		<div class="form-group">
    			<label for="Team1Spread">Team1 Spread</label>
			<input type="text" class="form-control" name="Team1Spread" id="Team1Spread">
  		</div>
		<div class="form-group">
    			<label for="Team2Spread">Team2 Spread</label>
			<input type="text" class="form-control" name="Team2Spread" id="Team2Spread">
  		</div>
		<div class="form-group">
    			<label for="Round">Round</label>
			<input type="text" class="form-control" name="Round" id="Round">
  		</div>
		<div class="form-group">
    			<label for="OverUnder">Over Under</label>
			<input type="text" class="form-control" name="OverUnder" id="OverUnder">
  		</div>
		<div class="form-group">
    			<label for="GameTime">Game Time (CST) Format: YYYY-MM-DD HH:MM military time: 2011-01-15 13:3</label>
			<input type="text" class="form-control" name="GameTime" id="GameTime">
  		</div>
		<input class="btn btn-primary" type="submit" name="addgame" value="Submit">
		
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














