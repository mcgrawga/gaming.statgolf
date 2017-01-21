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
        <br><h3>Change Password</h3>
        <?

	extract($_POST);
	if (isset($changePassword))
	{
		$sql = "update user set password = '$password' where id = $user";
		//printf("%s", $sql);
		mysql_query($sql) or die("Could not update password: " . mysql_error());
		print("That password was changed so successfully you wouldn't even understand it bro!<br>");
	}
	else
	{		
		$result = mysql_query("select * from user order by email") or die("Could not get users: " . mysql_error());
		?>
		<form action="resetpassword.php" method="POST">
		<div class="form-group">
                        <label>New Password</label>
			<select name="user" class="form-control">
				<?
				while ($row = mysql_fetch_array($result))
                		{
                        		extract($row);
					printf("<option value=\"$id\">$email</option>");
                		}
				?>
			</select>
                </div>
		<div class="form-group">
                        <label>New Password</label>
                        <input type="text" class="form-control" name="password">
                </div>

		<input class="btn btn-primary" type="submit" name="changePassword" value="Submit"></form><br>
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














