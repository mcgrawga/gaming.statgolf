<?	
function GetTotalNumWinningPicksOverUnder()
{
	
	$sql = "select count(*) as cnt from pick p, game g where
			(
			  p.overunder = 'Over' and
			  p.gameid = g.id and
			  ((g.Team1Score + g.Team2Score) > g.OverUnder)
			)
			or
			(
			  p.overunder = 'Under' and
			  p.gameid = g.id and
			  ((g.Team1Score + g.Team2Score) < g.OverUnder)
			)
			and p.userid in (
			select distinct(id) from user);"; 
	//printf("%s<br>", $sql);
	$result = mysql_query($sql) or die("Could not get total number of winning picks for over under: " . mysql_error());
	$row = mysql_fetch_array($result);
	extract($row);
	return $cnt;
}

function GetTotalNumWinningPicks()
{
	
	$sql = "select count(*) as cnt from pick p, game g where
			(
			  p.choice = 'Team2' and
			  p.gameid = g.id and
			  ((g.Team1Score + g.Team1Spread) < g.Team2Score)
			)
			or
			(
			  p.choice = 'Team1' and
			  p.gameid = g.id and
			  ((g.Team1Score + g.Team1Spread) > g.Team2Score)
			)
			and p.userid in (
			select distinct(id) from user);"; 
	//printf("%s<br>", $sql);
	$result = mysql_query($sql) or die("Could not get total number of winning picks: " . mysql_error());
	$row = mysql_fetch_array($result);
	extract($row);
	return $cnt;
}

function GetTotalNumPicks()
{
	
	$sql = "select count(*) as cnt from pick p where p.userid in (
			select distinct(id) from user) and p.gameid in(
			select distinct(id) from game where Team1Score is not null);"; 
	//printf("%s<br>", $sql);
	$result = mysql_query($sql) or die("Could not get total number of picks: " . mysql_error());
	$row = mysql_fetch_array($result);
	extract($row);
	return $cnt;
}


function GameHasStarted($id)
{
	
	//$sql = "select * from game where id = $id and GameTime < now();";  // is the game in the past?  Commented because this only works on local host.
	$sql = "select * from game where id = $id and GameTime < DATE_SUB(now(), INTERVAL 6 HOUR);";  // Offset for server time
	//printf("%s<br>", $sql);
	$result = mysql_query($sql) or die("Could not get game record: " . mysql_error());
	if ( mysql_num_rows($result) > 0 )
		return 1;  //yes
	else
		return 0; // no
	
}
function isAdmin($userid)
{
	
	//select * from game g, pick p where p.userid = 1 and p.gameid = g.id;
	
	$sql = "select * from user where id = $userid and admin = 1;";
	//printf("%s<br>", $sql);
	$result = mysql_query($sql) or die("Could not get user record: " . mysql_error());
	if ( mysql_num_rows($result) == 0 )
		return 0;
	else
		return 1;
}


function getPointsForAllRounds($userid)
{
	
	//select * from game g, pick p where p.userid = 1 and p.gameid = g.id;
	
	$sql = "select distinct(g.round) from game g, pick p where p.userid = $userid and p.gameid = g.id;";
	//printf("%s<br>", $sql);
	$result = mysql_query($sql) or die("Could not get all the rounds: " . mysql_error());

	$points = 0;	
	if ( mysql_num_rows($result) == 0 )
		return null;
	else
	{
		while ($row = mysql_fetch_array($result))
		{
			extract($row);
			$roundPoints = getPointsForThisRound($round, $userid);
			if (strlen($roundPoints) > 0)
				$points += $roundPoints;
		}
	}
	return $points;
}

function getPointsForThisRound($round, $userid)
{
	//print("Userid:  $userid");
	
	//select * from game g, pick p where p.userid = 1 and p.gameid = g.id;
	$sql = "select g.id as id from game g, pick p where p.userid = $userid and p.gameid = g.id and g.round = $round order by p.dt desc";
	//printf("%s<br>", $sql);
	$result = mysql_query($sql) or die("Could not get games for this round: " . mysql_error());

	$points = 0;
	if ( mysql_num_rows($result) == 0 )
		return null;
	else
	{
		while ($row = mysql_fetch_array($result))
		{
			extract($row);
			$gamePoints = getPointsForThisGame($id, $userid);
			if (strlen($gamePoints) > 0)
				$points += $gamePoints;
		}
	}
	return $points;
}


function getPointsForThisGame($id, $userid)
{
	//
	// 1 point for winning the game against the spread
	// 1 points for winning the over under
	// 0 points for losing... no shit
	//
	
	//select * from game g, pick p where p.userid = 1 and p.gameid = g.id;
	$sql = "select * from game g, pick p where p.userid = $userid and p.gameid = $id and p.gameid = g.id order by p.dt desc";
	//printf("%s<br>", $sql);
	$result = mysql_query($sql) or die("Could not get points for this game: " . mysql_error());

	$points = 0;
	if ( mysql_num_rows($result) == 0 )
		return null;
	else
	{
		extract(mysql_fetch_array($result));
		if ($Team1Score != null && $Team2Score != null)
		{
			// score for the game
			$Team1SpreadScore = $Team1Score;
			$Team2SpreadScore = $Team2Score + $Team2Spread;
			$Winner = "";
			if ($Team1SpreadScore > $Team2SpreadScore)
				$Winner = "Team1";
			else if ($Team2SpreadScore > $Team1SpreadScore)
				$Winner = "Team2";
			if ($Winner == $choice)
				$points += 1;
			
			// score for the over under
			$total = $Team1Score + $Team2Score;
			if ( ($total > $OverUnder) &&($overunder == "over"))
				$points += 1;
			if ( ($total < $OverUnder) &&($overunder == "under"))
				$points += 1;
		}
	}
	return $points;
}



function HasUserAlreadyPickedThisGameOverUnder($id)
{
	$userid = $_SESSION['userid'];
	$sql = "select * from pick where userid = $userid and gameid = $id order by dt desc";
	//printf("%s<br>", $sql);
	$result = mysql_query($sql) or die("Could not get a list of picks for over under: " . mysql_error());
	
	if ( mysql_num_rows($result) == 0 )
		return null;
	else
	{
		extract(mysql_fetch_array($result));		
		return $overunder;
	}
}


function HasUserAlreadyPickedThisGame($id)
{
	$userid = $_SESSION['userid'];
	$sql = "select * from pick where userid = $userid and gameid = $id order by dt desc";
	//printf("%s<br>", $sql);
	$result = mysql_query($sql) or die("Could not get a list of picks for game pick: " . mysql_error());
	
	if ( mysql_num_rows($result) == 0 )
		return null;
	else
	{
		extract(mysql_fetch_array($result));		
		return $choice;
	}
}

function HasThisUserAlreadyPickedThisGameOverUnder($id, $userid)
{
	$sql = "select * from pick where userid = $userid and gameid = $id order by dt desc";
	//printf("%s<br>", $sql);
	$result = mysql_query($sql) or die("Could not get a list of picks for over under: " . mysql_error());
	
	if ( mysql_num_rows($result) == 0 )
		return null;
	else
	{
		extract(mysql_fetch_array($result));		
		return $overunder;
	}
}


function HasThisUserAlreadyPickedThisGame($id, $userid)
{
	$sql = "select * from pick where userid = $userid and gameid = $id order by dt desc";
	//printf("%s<br>", $sql);
	$result = mysql_query($sql) or die("Could not get a list of picks for game pick: " . mysql_error());
	
	if ( mysql_num_rows($result) == 0 )
		return null;
	else
	{
		extract(mysql_fetch_array($result));		
		return $choice;
	}
}

function UserPaidUp()
{

	return 1;
/*
	$SignUpDate = GetSignUpDate();
	$Today = strtotime("today");
	$TrialPeriodEndDate = GetTrialPeriodEndDate();
	$PaidThroughDate = GetPaidThroughDate();
	
	// 1)  USER STILL IN TRIAL PERIOD
	// 2)  USER PAST TRIAL PERIOD AND HAS NOT PAID
	// 3)  USER HAS PAID PREVIOUSLY AND ACCOUNT EXPIRED
	// 4)  USER HAS PAID AND ACCOUNT IS NOT EXPIRED
	
	
	if ($Today <= $TrialPeriodEndDate)
	{
		return 1;
	}
	else if (($Today > $TrialPeriodEndDate) && ($PaidThroughDate == 'NEVER_PAID'))
	{
		DisplayOnlyBanner();
		printf("<div id=\"paragraphtext\">Thank you for signing up for StatGolf.  We are sure you will enjoy the site and that it will be a valuable tool to help improve your golf game.  Your trial period expired on %s.<br><br>Please pay the annual fee of $%s to activate your account through %s</div>", date("F j, Y", $TrialPeriodEndDate), getConfigValue("ANNUAL_FEE"), GetNextPaidThroughDate());
		include 'paymentbutton.php';
		DisplayCommonFooter();
		return 0;
	}
	else if ($Today > strtotime($PaidThroughDate))
	{
		DisplayOnlyBanner();
		printf("<div id=\"paragraphtext\">Your StatGolf account expired on %s.<br>Please pay now to keep all your scores and courses.<br><br>Paying now will extend your StatGolf account through %s</div>", $PaidThroughDate, GetNextPaidThroughDate());
		include 'paymentbutton.php';
		DisplayCommonFooter();
		return 0;
	}
	else
	{
		return 1;
	}
	
	
	/*
	// HAVE SIGNED UP, BUT NEVER PAID
	if ($PaidThroughDate == 'NEVER_PAID')
	{
		DisplayOnlyBanner();
		printf("<div id=\"paragraphtext\">Thank you for signing up for StatGolf.  We are sure you will enjoy the site and that it will be a valuable tool to help improve your golf game.<br><br>  Please pay the annual fee of $35 to activate your account through %s</div><br><br>", GetNextPaidThroughDate());
		include 'paymentbutton.php';
		DisplayCommonFooter();
		return 0;
	}
	
	printf("Today:  %s<br>", date("F j, Y", $Today));	
	printf("SignUpDate:  %s<br>", date("F j, Y", $SignUpDate));	
	printf("TrialPeriodEndDate:  %s<br>", date("F j, Y", $TrialPeriodEndDate));	
	printf("PaidThroughDate:  %s<br>", $PaidThroughDate);	
	
	
	*/
}


// THIS RETURNS THE DATE UNFORMATTED (UNIX TIMESTAMP)
function GetSignUpDate()
{

	$sql = "select createdt as signupdate from user where id = ";
	$sql .= $_SESSION['userid'];
	$result = mysql_query($sql) or die("Could not get sign up date: " . mysql_error());
	$row = mysql_fetch_array($result);

	return strtotime($row['signupdate']);
}

// THIS RETURNS THE DATE UNFORMATTED (UNIX TIMESTAMP)
function GetTrialPeriodEndDate()
{

	$sql = "select ADDDATE(createdt, INTERVAL 30 DAY) as trialperiodenddate from user where id = ";
	$sql .= $_SESSION['userid'];
	$result = mysql_query($sql) or die("Could not get trial period end date: " . mysql_error());
	$row = mysql_fetch_array($result);

	return strtotime($row['trialperiodenddate']);
}
 

//
//  IF USER PAYS TODAY, THIS FUNCTION RETURNS THE DATE HE/SHE WOULD BE PAID THROUGH.
//
function GetNextPaidThroughDate()
{

	$sql = "select max(effectivedate), ADDDATE(max(effectivedate), INTERVAL 2 YEAR) as nextpaidthroughdate from payment_tbl where user_id = ";
	$sql .= $_SESSION['userid'];
	$result = mysql_query($sql) or die("Could not get paid through date: " . mysql_error());
	$row = mysql_fetch_array($result);
	
	// CREATE A DATE ONE YEAR FROM TODAY
	$oneYearFromToday = strtotime("+1 year");
	
	if ( !$row['nextpaidthroughdate'] )
	{
		//return $oneYearFromToday->format("F j, Y");
		return date("F j, Y", $oneYearFromToday);
	}
	else
	{
		$twoYearsFromLastPaidDate = strtotime($row['nextpaidthroughdate']);
		if ($twoYearsFromLastPaidDate > $oneYearFromToday)
			return date("F j, Y", $twoYearsFromLastPaidDate);
		else
			return date("F j, Y", $oneYearFromToday);
	}
}


function GetPaidThroughDate()
{

	$sql = "select max(effectivedate), ADDDATE(max(effectivedate), INTERVAL 1 YEAR) as paidthroughdate from payment_tbl where user_id = ";
	$sql .= $_SESSION['userid'];
	$result = mysql_query($sql) or die("Could not get paid through date: " . mysql_error());
	$row = mysql_fetch_array($result);
	
	// CREATE A DATE ONE YEAR FROM TODAY
	$oneYearFromToday = strtotime("+1 year");
	
	if ( !$row['paidthroughdate'] )
	{
		return 'NEVER_PAID';
	}
	else
	{
		$oneYearFromLastPaidDate = strtotime($row['paidthroughdate']);
		return date("F j, Y", $oneYearFromLastPaidDate);
	}
}


function InsertPayment($userid, $amt, $effectivedate)
{
	$sql = "INSERT INTO payment_tbl (user_id, amt, effectivedate) VALUES($userid, $amt, '$effectivedate')";
	//printf("$sql");
	if (!mysql_query($sql))
		return 0;	// INSERT DIDN'T WORK
	$_SESSION['paidup'] = 1;
	return 1;	// INSERT WORKED
}

function IsRequiredFieldPresent($fieldname, $fieldvalue)	
{
	if (strlen($fieldvalue) == 0)
	{
		printf("%s is a required field", $fieldname);
		return 0;
	}
	return 1;
}

/*
function IsUsernameValid($fieldvalue)	
{
	if (!IsRequiredFieldPresent("Username", $fieldvalue))
		return 0;
	return 1;
}
*/

function IsPasswordValid($fieldvalue)	
{
	if (!IsRequiredFieldPresent("Password", $fieldvalue))
		return 0;
	return 1;
}

function IsCPasswordValid($fieldvalue)	
{
	if (!IsRequiredFieldPresent("Confim Password", $fieldvalue))
		return 0;
	return 1;
}

function IsEmailValid($fieldvalue)	
{
	if (!IsRequiredFieldPresent("Email", $fieldvalue))
		return 0;
	if(!preg_match("/^[a-z0-9\å\ä\ö._-]+@[a-z0-9\å\ä\ö.-]+\.[a-z]{2,6}$/i", $fieldvalue))
	{
		printf("You must enter a valid email");
		return 0;
	}
	return 1;
}

function IsCourseNameValid($fieldvalue)	
{
	if (!IsRequiredFieldPresent("Course Name", $fieldvalue))
		return 0;
	return 1;
}

function IsCityValid($fieldvalue)	
{
	if (!IsRequiredFieldPresent("City", $fieldvalue))
		return 0;
	return 1;
}

function IsStateValid($fieldvalue)	
{
	if (!IsRequiredFieldPresent("State/Province", $fieldvalue))
		return 0;
	return 1;
}

function IsTeeValid($fieldvalue)	
{
	if (!IsRequiredFieldPresent("Tee Name", $fieldvalue))
		return 0;
	return 1;
}

function IsSlopeRatingValid($fieldvalue)	
{
	if (!IsRequiredFieldPresent("Slope Rating", $fieldvalue))
		return 0;
	if (!is_numeric($fieldvalue))
	{
		printf("Slope Rating has to be numeric");
		return 0;
	}
	return 1;
}

function IsCourseRatingValid($fieldvalue)	
{
	if (!IsRequiredFieldPresent("Course Rating", $fieldvalue))
		return 0;
	if (!is_numeric($fieldvalue))
	{
		printf("Course Rating has to be numeric");
		return 0;
	}
	return 1;
}

function IsCourseRatingLessThanSlope($course, $slope)
{
	if ($course < $slope)
		return 1;
	else
	{
		printf("Course rating must be less than slope rating.  You might have them switched around, i.e. entered course for slope rating and slope for course rating");
		return 0;
	}
}

function IsParValueForHoleValid($fieldname, $fieldvalue)	
{
	if ($fieldvalue < 3 || $fieldvalue > 5)
	{
		printf("%s has to be 3, 4 or 5", $fieldname);
		return 0;
	}
	return 1;
}

function IsScoreValueForHoleValid($fieldname, $fieldvalue)	
{
		
	if (!is_numeric($fieldvalue))
	{
		printf("%s has to be numeric and greater than zero", $fieldname);
		return 0;
	}
	
	if ($fieldvalue == 0)
	{
		printf("%s has to be numeric and greater than zero", $fieldname);
		return 0;
	}
	return 1;
}

function IsPuttValueForHoleValid($fieldname, $fieldvalue)	
{
		
	if (!is_numeric($fieldvalue) && (strlen(fieldvalue) > 0))
	{
		printf("%s has to be numeric or null", $fieldname);
		return 0;
	}
	
	return 1;
}

function IsGIRValid($fieldname, $fieldvalue)	
{
	if (strlen($fieldvalue) > 0)
	{
		if (!is_numeric($fieldvalue))
		{
			printf("%s has to be numeric or null", $fieldname);
			return 0;
		}
		
		if ($fieldvalue > 18)
		{
			printf("%s has to be less than 19", $fieldname);
			return 0;
		}
		
		if ($fieldvalue < 0)
		{
			printf("%s can not be less than zero", $fieldname);
			return 0;
		}
	}
	return 1;
}

function IsFairwaysHitValid($fieldname, $fieldvalue)	
{
	if (strlen($fieldvalue) > 0)
	{
		if (!is_numeric($fieldvalue))
		{
			printf("%s has to be numeric or null", $fieldname);
			return 0;
		}
		
		if ($fieldvalue > 18)
		{
			printf("%s has to be less than 19", $fieldname);
			return 0;
		}
		
		if ($fieldvalue < 0)
		{
			printf("%s can not be less than zero", $fieldname);
			return 0;
		}
	}
	return 1;
}

function IsPenaltiesValid($fieldname, $fieldvalue)	
{
	
	if (strlen($fieldvalue) > 0)
	{
		if (!is_numeric($fieldvalue))
		{
			printf("%s has to be numeric or null", $fieldname);
			return 0;
		}
		
		if ($fieldvalue < 0)
		{
			printf("%s can not be less than zero", $fieldname);
			return 0;
		}
	}
	return 1;
}

function ListCourses()
{
	$sql = "select name, id from course_tbl where userid = ";
	$sql .= $_SESSION['userid'];
	$result = mysql_query($sql) or die("Could not get a list of courses: " . mysql_error());
	if ( mysql_num_rows($result) == 0 )
		printf("You have not entered any courses yet.");
	else
	{
	/*
		printf("<table class=\"ScoreCardTable\">");
		$rowCnt = 0;
		while ($row = mysql_fetch_array($result))
		{
			$classname = ($rowCnt % 2) ? 'CourseList2' : 'CourseList1';
			printf("<tr>");
			printf("<td class=\"$classname\">%s</td><td class=\"$classname\"><A HREF=\"courseadmin.php?ShowDetails=1&CourseID=%s\">edit</A></td><td class=\"$classname\"><A HREF=\"courseadmin.php?DeleteCourse=1&courseid=%s\" onclick=\"javascript:return confirm('Are you sure you want to delete?')\">delete</A></td>",$row["name"], $row["id"], $row["id"]);
			printf("</tr>");
			$rowCnt++;
		}
		printf("</table>");
	*/
	
		printf("<table border=\"0\" cellspacing=\"0\" cellpadding=\"5\">");
		$rowCnt = 0;
		while ($row = mysql_fetch_array($result))
		{
			$classname = ($rowCnt % 2) ? 'CourseList2' : 'CourseList1';
			printf("<tr class=\"$classname\">");
			printf("<td>%s</td><td><A HREF=\"courseadmin.php?ShowDetails=1&CourseID=%s\">edit</A>&nbsp&nbsp</td><td><A HREF=\"courseadmin.php?DeleteCourse=1&courseid=%s\" onclick=\"javascript:return confirm('Are you sure you want to delete?')\">delete</A></td>",ucwords(strtolower($row["name"])), $row["id"], $row["id"]);
			printf("</tr>");
			$rowCnt++;
		}
		printf("</table>");
	}
	?>
	<br><a href=addcourse.php>Add New Course</a>&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp
	<a href=searchcourse.php>Search For Course</a>
	<?
	
}


//
//  THIS DELETES THE COURSE PLUS ALL THE TEES AND SCORES ASSOCIATED WITH IT.
//
function DeleteCourse($courseid)
{
	$DeleteScoresSql = "delete from score_tbl where teeid in(select id from tee_tbl where courseid = $courseid);";
	$DeleteTeesSql = "delete from tee_tbl where courseid = $courseid;";
	$DeleteCourseSql = "delete from course_tbl where id = $courseid;";
	//printf("%s", $DeleteScoresSql);
	mysql_query($DeleteScoresSql) or die("Could not delete scores for this course: " . mysql_error());
	mysql_query($DeleteTeesSql) or die("Could not delete tees for this course: " . mysql_error());
	mysql_query($DeleteCourseSql) or die("Could not delete this course: " . mysql_error());
}

//
//  THIS DELETES THE TEE PLUS ALL THE SCORES ASSOCIATED WITH IT.
//
function DeleteTee($teeid)
{
	$DeleteScoresSql = "delete from score_tbl where teeid = $teeid;";
	$DeleteTeesSql = "delete from tee_tbl where id = $teeid;";
	mysql_query($DeleteScoresSql) or die("Could not delete scores for this course: " . mysql_error());
	mysql_query($DeleteTeesSql) or die("Could not delete tees for this course: " . mysql_error());
}

function getNumTeesForCourse($courseId)
{
	$sql = "select * from tee_tbl where courseid = $courseId;";
	$result = mysql_query($sql) or die("Could not select tees for course: " . mysql_error());
	return mysql_num_rows($result);
}

function DeleteScore($scoreid)
{
	$DeleteScoresSql = "delete from score_tbl where id = $scoreid;";
	mysql_query($DeleteScoresSql) or die("Could not delete scores for this course: " . mysql_error());
}




function ResultSetToXML($rs)
{
	$XML = '<?xml version="1.0" encoding="ISO-8859-1"?>';
	$XML .= "<resultset>";

	$NumberOfColumns = mysql_num_fields($rs);
	while ($row = mysql_fetch_array($rs))
	{
		$XML .= "<row>";
		for ($i=0; $i<$NumberOfColumns; $i++)
		{
			if (!is_null($row[$i]))
			{
				$ColumnName = mysql_field_name($rs,$i);
				$ColumnValue = htmlspecialchars($row[$i]);
				$XML .= sprintf("<%s>%s</%s>",$ColumnName, $ColumnValue, $ColumnName );
			}
		}
		$XML .= "</row>";
	}
	$XML .= "</resultset>";
	return $XML;
}

function formatPuttScore ( $a )
{
	if ( $a == "" )
		$a = "null";
	return $a;
	
}

function DisplayCommentForm()
{
	printf("<form action=\"comments.php\" method=\"POST\" name=\"commentForm\">", $postPage);
	printf("<table class=\"InstructionsTable\">");
	printf("<tr><td><textarea class=\"CommentBox\" type=\"text\" name=\"commentText\"></textarea></td></tr>");
	printf("<tr><td align=\"center\"><input type=\"submit\" name=\"SubmitComment\" value=\"Submit Comment\"></td></tr>");
	printf("</table> ");	
}

function ConnectToDB()
{
	$db = mysql_connect("localhost", "statgolf_mcgraw", "blu1duck*");
	mysql_select_db("gaming",$db);
}

function InvalidLogin()
{
		DisplayGeneralPublicHeader();
		printf("<font color=\"red\">Invalid Login</font><br>");
		DisplayCommonFooter();
}

function ValidateCredentials($Email, $Password)
{
        //printf("email: %s<br>password:  %s", $Email, $Password);
	ConnectToDB();
	
	if ($Email == "" || $Password == "")
	{
		InvalidLogin();
		return 0;
	}

	// Verify user name
	$sql = "select * from user where lcase(email) = lcase('$Email')";
	//printf("%s", $sql);
	$result = mysql_query($sql);
	$num_rows = mysql_num_rows($result);
	//printf("num rows:  %s", $num_rows); 
	if ( $num_rows == 0 )
	{
		InvalidLogin();
		return 0;
	}
	else
	{
		// Verify password
		$sql = "select id, email from user where lcase(email) = lcase('$Email') and password = '$Password'";
		$result = mysql_query($sql);
		$num_rows = mysql_num_rows($result);
		if ( $num_rows == 0 )
		{
			InvalidLogin();
			return 0;
		}
		else
		{
			// Get email and id from result set.
			$row = mysql_fetch_array($result);
			$id = $row["id"];
			$email = $row["email"];
			
			// Register session key with the value 
			$_SESSION['userid'] = $id;
			$_SESSION['email'] = $email;
			
			// RECORD THE USER LOGIN			
			$sql = "insert into login_tbl (user_id) values ($id)";
			mysql_query($sql);
			
			if (UserPaidUp())
			{
				$_SESSION['paidup'] = 1;
				return 1;
			}
			else
			{
				return 0;
			}
			
		}
	}
}


function GenerateGiftCode()
{
	$elementArray = array(	'1', 
				'2',
				'3',
				'4',
				'5',
				'6',
				'7',
				'8',
				'9',
				'A',
				'B',
				'C',
				'D',
				'E',
				'F',
				'G',
				'H',
				'I',
				'J',
				'K',
				'L',
				'M',
				'N',
				'P',
				'Q',
				'R',
				'S',
				'T',
				'U',
				'V',
				'W',
				'X',
				'Y',
				'Z'
				);
				
	$giftcode = "";
	$arraySize = count($elementArray);
	for ($i = 0; $i < 10; $i++)
		$giftcode .= $elementArray[rand(0, $arraySize-1)];
	return $giftcode;
}

function InsertGiftCode($email="")
{
	$GiftCode = GenerateGiftCode();
	
	for ($i=0; $i<1000; $i++)
	{
		if (GiftCodeAlreadyExists($GiftCode))
			$GiftCode = GenerateGiftCode();
		else
			break;
	}
	
	if ( strlen($email) > 0 )
		$sql = "INSERT INTO giftcode_tbl (code, used, paid, createdbyemail) VALUES('$GiftCode', 0, 0, '$email')";
	else
		$sql = "INSERT INTO giftcode_tbl (code, used, paid) VALUES('$GiftCode', 0, 0)";
	
	//printf("$sql");
	if (!mysql_query($sql))
		return 0;	// INSERT DIDN'T WORK
	return $GiftCode;	// INSERT WORKED
}

function GiftCodeAlreadyExists($GiftCode)
{
	$sql = "select * from giftcode_tbl where code = '$GiftCode'";
	//printf("%s", $sql);
	$result = mysql_query($sql) or die("Could not check to see if user already exists: " . mysql_error());
	return mysql_num_rows($result);
}

function MarkGiftCodeAsPaid($gc)
{
	$sql = "update giftcode_tbl set paid = 1 where code = '$gc'";
	//printf("%s", $sql);
	if (!mysql_query($sql))
		return 0;	// INSERT DIDN'T WORK
	return 1;	// INSERT WORKED
}

function GetRandomQuoteID()
{
	$sql = "select count(*) num_rows from quote_tbl";
	$result = mysql_query($sql);
	$row = mysql_fetch_array($result);
	$num_rows = $row["num_rows"];
	return rand(1, $num_rows);
}

function GetQuote()
{
	$golfQuoteId = GetRandomQuoteID();
	$sql = "select * from quote_tbl where id = $golfQuoteId";
	$result = mysql_query($sql);
	if ( mysql_num_rows($result) == 0 )
		return "No available golf quotes";
	$row = mysql_fetch_array($result);
	$quote = $row["quote"];
	$quote .= "<br>  ";
	$quote .= $row["author"];
	return $quote;
}


function EscapeData()
{
	//create array to temporarily grab variables
	$input_arr = array();
	//grabs the $_POST variables and adds slashes
	foreach ($_POST as $key => $input_arr) 
	{
		$_POST[$key] = addslashes($input_arr);
	}
	
	//create array to temporarily grab variables
	$input_arr = array();
	//grabs the $_GET variables and adds slashes
	foreach ($_GET as $key => $input_arr) 
	{
		$_GET[$key] = addslashes($input_arr);
	}
}

// THIS IS THE HEADER AND NAV BAR AND LOGIN PROMPT THAT APPEARS
// WHEN A USER IS NOT LOGGED IN OR ON PAYMENT SCREENS.
function DisplayGeneralPublicHeader()
{
	EscapeData();
	?>
	
		
		<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
			"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
		
		<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
		<head>
		<title>NFL Pick'em (2017)</title>
		<link rel="stylesheet" type="text/css" href="style.css" />
		<link rel="stylesheet" type="text/css" href="bootstrap.min.css" />
		<script src="jquery-2.1.4.min.js"></script>
		<script src="bootstrap.min.js"></script>
        
		
		<h1>NFL Playoff Pick'em (2017)</h1>

<form class="form-inline" action="home.php" method="POST" >
  <div class="form-group">
    <label class="sr-only" for="Email">Email address</label>
    <input type="email" class="form-control" id="Email" placeholder="Email" name="Email">
  </div>
  <div class="form-group">
    <label class="sr-only" for="Password">Password</label>
    <input type="password" class="form-control" id="Password" placeholder="Password" name="Password">
  </div>
  <button type="submit" class="btn btn-primary" name="LoginUser">Sign in</button>
</form>
<br>
		<A  HREF="newuser.php">Sign Up</A><br><br>
		

        
		<script language="JavaScript">
			document.getElementById("Email").focus();
		</script>
	<?
}

function DisplayCommonHeader()
{
	EscapeData();
	// Connect to the db.
	ConnectToDB();
?>
	<html>
	<head>
	<title>NFL Playoff Pick'em (2017)</title>
	<link rel="stylesheet" type="text/css" href="style.css" />
	<link rel="stylesheet" type="text/css" href="bootstrap.min.css" />
	<script src="jquery-2.1.4.min.js"></script>
	<script src="bootstrap.min.js"></script>
	<!--[if IE]>
	<link rel="stylesheet" href='ie_fixes.css' type="text/css" media="screen, projection" />
	<![endif]-->
	</head>
	<body>


<nav class="navbar navbar-default">
  <div class="container-fluid">
    <!-- Brand and toggle get grouped for better mobile display -->
    <div class="navbar-header">
      <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#bs-example-navbar-collapse-1" aria-expanded="false">
        <span class="sr-only">Toggle navigation</span>
        <span class="icon-bar"></span>
        <span class="icon-bar"></span>
        <span class="icon-bar"></span>
      </button>
      <a class="navbar-brand" href="home.php">NFL Pick'em (2017)</a>
    </div>

    <!-- Collect the nav links, forms, and other content for toggling -->
    <div class="collapse navbar-collapse" id="bs-example-navbar-collapse-1">
      <ul class="nav navbar-nav">
        <li><a href="showallpicks.php">My Saved Picks </a></li>
        <li><a href="showpicksaftergamesstart.php">All Saved Picks</a></li>
        <li><a href="standings.php">Standings</a></li>

	<?
	if (isAdmin($_SESSION['userid']))
	{?>

	<li class="dropdown">
          <a href="admin.php" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">Admin <span class="caret"></span></a>
          <ul class="dropdown-menu">
            <li><a href="admin.php">Admin home</a></li>
            <li><a href="addgame.php">Add a game</a></li>
            <li><a href="showallpicksallusers.php">Show all picks</a></li>
            <li><a href="resetpassword.php">Reset a password</a></li>
            <li><a href="setpicks.php">Change / Set picks for somebody</a></li>
          </ul>
        </li>
	<?}?>
      </ul>
      <ul class="nav navbar-nav navbar-right">
        <li><a href="logout.php">Logout</a></li>
      </ul>
    </div><!-- /.navbar-collapse -->
  </div><!-- /.container-fluid -->
</nav>
<?
}

function DisplayOnlyBanner()
{

	// Connect to the db.
	ConnectToDB();
?>
	<html>
	<head>
	<title>StatGolf</title>
	<link rel="stylesheet" type="text/css" href="style.css" />
	<script language="JavaScript" src="scorecard_events.js"></script>
	</head>
	<body>
	
	<div id="container">
		<div id="top"></div>
			
		
		<div id="main">
<?
}

function DisplayCommonFooter()
{
?>
		</div>
		<div id="footer">
		</div>
	</div>
	
	
	<script type="text/javascript">
	var gaJsHost = (("https:" == document.location.protocol) ? "https://ssl." : "http://www.");
	document.write(unescape("%3Cscript src='" + gaJsHost + "google-analytics.com/ga.js' type='text/javascript'%3E%3C/script%3E"));
	</script>
	<script type="text/javascript">
	try {
	var pageTracker = _gat._getTracker("UA-12551082-1");
	pageTracker._trackPageview();
	} catch(err) {}</script>

</body>
</html>
<?
}

function calcFront ($row)
{
   $total = $row["hole1"];
   $total += $row["hole2"];
   $total += $row["hole3"];
   $total += $row["hole4"];
   $total += $row["hole5"];
   $total += $row["hole6"];
   $total += $row["hole7"];
   $total += $row["hole8"];
   $total += $row["hole9"];
   return $total;
}

function calcBack ($row)
{
   $total = $row["hole10"];
   $total += $row["hole11"];
   $total += $row["hole12"];
   $total += $row["hole13"];
   $total += $row["hole14"];
   $total += $row["hole15"];
   $total += $row["hole16"];
   $total += $row["hole17"];
   $total += $row["hole18"];
   return $total;
}

function calcFrontPar ($row)
{
   $total = $row["par1"];
   $total += $row["par2"];
   $total += $row["par3"];
   $total += $row["par4"];
   $total += $row["par5"];
   $total += $row["par6"];
   $total += $row["par7"];
   $total += $row["par8"];
   $total += $row["par9"];
   return $total;
}

function calcBackPar ($row)
{
   $total = $row["par10"];
   $total += $row["par11"];
   $total += $row["par12"];
   $total += $row["par13"];
   $total += $row["par14"];
   $total += $row["par15"];
   $total += $row["par16"];
   $total += $row["par17"];
   $total += $row["par18"];

   return $total;
}

function userPreExists ($email)
{
	$sql = "select * from user where lcase(email) = lcase('$email')";
	//printf("%s", $sql);
	$result = mysql_query($sql) or die("Could not check to see if user already exists: " . mysql_error());
	return mysql_num_rows($result);
}


function getESCValue($handi = "")
{
	//printf("Handi:  %s<BR>", $handi);
	if ( $handi == "" || $handi == null )
		return 10;
	if ( $handi <= 9 )
		return "double";
	else if ( $handi <= 19 )
		return "7";
	else if ( $handi <= 29 )
		return "8";
	else if ( $handi <= 39 )
		return "9";
	else 
		return "10";
}


function getMostRecentHandi($id)
{
	$sql = "select dateplayed from score_tbl where id = $id";
	//printf("%s", $sql);
	$result = mysql_query($sql) or die("Could not get date: " . mysql_error());
	$row = mysql_fetch_array($result);
	
	$datePlayed = $row["dateplayed"];
	$userID = $_SESSION["userid"];
	$sql2 = "select handicap from score_tbl where dateplayed <= '$datePlayed' and id != $id and userid = '$userID' order by dateplayed desc";
	//printf("%s", $sql2);
	$result2 = mysql_query($sql2) or die("Could not get handicap: " . mysql_error());
	if ( mysql_num_rows($result) == 0 )
		return "";
	else
	{
		$row2 = mysql_fetch_array($result2);
		return $row2["handicap"];	
	}
}



function getAdjustedGrossScore($id)
{
	$escVal = getESCValue(getMostRecentHandi($id));
	$sql = "select * from score_tbl where id = $id";
	$result = mysql_query($sql) or die("Could not get score: " . mysql_error());
	$row = mysql_fetch_array($result);
	$ags = 0;
	$nineHoleScore = 0;
	if ( escVal == "double" )
	{
		for ( $i = 1; $i <= 18; $i++ )
		{
			$rowName = "hole$i";
			$holeVal = $row[$rowName];
			if ($holeVal == 0 || $holeVal == NULL)
				$nineHoleScore = 1;
			$doubleVal = getDoubleVal($row["courseid"], $i);
			if ( $holeVal <= $doubleVal )
				$ags += $holeVal;
			else
				$ags += $doubleVal;
		} 
	}
	else
	{
		
		for ( $i = 1; $i <= 18; $i++ )
		{
			$rowName = "hole$i";
			$holeVal = $row[$rowName];
			if ($holeVal == 0 || $holeVal == NULL)
				$nineHoleScore = 1;
			if ( $holeVal <= $escVal )
				$ags += $holeVal;
			else
				$ags += $escVal;
		} 
	}
	if ( $nineHoleScore )
		return (2*$ags);
	else
		return $ags;
}

// THIS FUNCTION CALCULATES THE HANDICAP DIFFERENTIAL FOR THE ROW PASSED TO IT.
function calcDifi($id)
{
	$ags = getAdjustedGrossScore($id);
	$sql = "select tee.slope as slope, tee.rating as rating from tee_tbl tee, score_tbl score where tee.id = score.teeid and score.id = $id";
	$result = mysql_query($sql) or die("Could not get slope and rating of course: " . mysql_error());
	if ( mysql_num_rows($result) == 0 )
		die("Could not get score associated to id passed to function.");
	$row = mysql_fetch_array($result);
	$rating = $row["rating"];
	$slope = $row["slope"];
	$difii = (($ags -$rating) * 113) / $slope;
	return $difii;
}

function numDifsScoresToUse($uid, $id, &$numDifs, &$numScores)
{
	$sql = "select id from score_tbl where userid = '$uid' and id <= $id order by dateplayed desc";
	$result = mysql_query($sql) or die("Could not get score history when calculating handicap: " . mysql_error());
	$numAvailScores = mysql_num_rows($result);
	if ( $numAvailScores < 5 )
	{
		$numDifs = 0;
		$numScores = 0;
	}
	else if ( $numAvailScores <= 6 )
	{
		$numDifs = 1;
		$numScores = $numAvailScores;
	}
	else if ( $numAvailScores <= 8 )
	{
		$numDifs = 2;
		$numScores = $numAvailScores;
	}
	else if ( $numAvailScores <= 10 )
	{
		$numDifs = 3;
		$numScores = $numAvailScores;
	}
	else if ( $numAvailScores <= 12 )
	{
		$numDifs = 4;
		$numScores = $numAvailScores;
	}
	else if ( $numAvailScores <= 14 )
	{
		$numDifs = 5;
		$numScores = $numAvailScores;
	}
	else if ( $numAvailScores <= 16 )
	{
		$numDifs = 6;
		$numScores = $numAvailScores;
	}
	else if ( $numAvailScores <= 17 )
	{
		$numDifs = 7;
		$numScores = $numAvailScores;
	}
	else if ( $numAvailScores <= 18 )
	{
		$numDifs = 8;
		$numScores = $numAvailScores;
	}
	else if ( $numAvailScores <= 19 )
	{
		$numDifs = 9;
		$numScores = $numAvailScores;
	}
	else 
	{
		$numDifs = 10;
		$numScores = 20;
	}
	return $result;
}

function getMostRecentScore()
{
	$uid = $_SESSION['userid'];
	//$sql = "select id from score_tbl where userid = '$uid' order by dateplayed desc";
	$sql = "select id from score_tbl where userid = '$uid' order by id desc";
	$result = mysql_query($sql) or die("Could not get most recent score when calculating handicap: " . mysql_error());
	$numAvailScores = mysql_num_rows($result);
	if ( $numAvailScores == 0 )
		return "Not Enough Scores";
	else
	{
		$row = mysql_fetch_array($result);
		return $row["id"];
	}
}

function formatHandi($h)
{
	/*
	if ( $h == 0 )
		$h = "0.0";
	$pos = strpos($h, ".");
	if ( ($pos + 2) > (strlen($h) - 1) )	// make sure we don't substr past the end of the string.
		return $h;
	else
		return substr( $h, 0, ($pos + 2) );
	*/
	
	if ( $h == 0 )
	{
		$h = "";
	}
	else
	{
		$pos = strpos($h, ".");
		if ( ($pos + 2) > (strlen($h) - 1) )	// make sure we don't substr past the end of the string.
			return $h;
		else
			return substr( $h, 0, ($pos + 2) );
	}
}

function formatPuttHandi($h)
{
	/*
	if ( $h == 0 )
		$h = "0.0";
	$pos = strpos($h, ".");
	if ( ($pos + 2) > (strlen($h) - 1) )	// make sure we don't substr past the end of the string.
		return $h;
	else
		return substr( $h, 0, ($pos + 2) );
	*/
	
	if ( $h == 0 )
	{
		return $h;
	}
	else
	{
		$pos = strpos($h, ".");
		if ( ($pos + 2) > (strlen($h) - 1) )	// make sure we don't substr past the end of the string.
			return $h;
		else
			return substr( $h, 0, ($pos + 2) );
	}
}

function calcHandi($id = "")
{
/*
Step 2: Determine Handicap Differential(s); 

Step 3: Average the Handicap Differential(s) being used ( best 10 out of last 20, see the function numDifsScoresToUse() ); 

Step 4: Multiply the average by.96; 

Step 5: Delete all numbers after the tenths' digit (truncate). Do not round to the nearest tenth. 

Handicap Differential = (Adjusted Gross Score - USGA Course Rating) x 113 / Slope Rating
*/






	$uid = $_SESSION['userid'];
	
	if ( $id == "" || $id == null )
	{
		$id = getMostRecentScore();
		if ( $id == "Not Enough Scores" )
			return $id;
	}
	
	// GET NUMBER OF HANDICAP DIFFERENTIALS AND NUMBER OF SCORES TO USE WHEN CALCULATING HANDICAP
	$result = numDifsScoresToUse($uid, $id, $numDifs, $numScores);
	if ( $numDifs == 0 )
		return "Not Enough Scores";
	//printf("Num Difs:  %s    Num Scores:  %s<BR>", $numDifs, $numScores);
	
	// GET AN ARRAY OF ALL DIFFERENTIALS TO USE
	$difArray = array();
	for ( $i = 0; $i < $numScores; $i++ )
	{
		$row = mysql_fetch_array($result);
		$difArray[] = calcDifi($row["id"]);
	}
	
	// SORT THE ARRAY FROM LOWEST TO HIGHEST DIFFERENTIAL
	sort($difArray, SORT_NUMERIC);
	reset($difArray);	// set the array pointer back to the first element
	
	// AVERAGE THE "X" NUMBER OF LOWEST DIFFS AND CALC HANDI
	$total = 0;
	for ( $i = 0; $i < $numDifs; $i++ )
	{
		list($key, $val) = each($difArray);
		$total += $val;
	}
	$h = (($total / $numDifs) * .96);
	
	return formatHandi($h);	
}


function getHandiIDArray()	// returns an array of golfscore ids that are used to calculate current handicap
{
	$uid = $_SESSION['userid'];
	
	$id = getMostRecentScore();
	if ( $id == "Not Enough Scores" )
		return array();

	
	// GET NUMBER OF HANDICAP DIFFERENTIALS AND NUMBER OF SCORES TO USE WHEN CALCULATING HANDICAP
	$result = numDifsScoresToUse($uid, $id, $numDifs, $numScores);
	if ( $numDifs == 0 )
		return array();
	
	// GET AN ARRAY OF ALL IDS TO USE
	$difArray = array();
	for ( $i = 0; $i < $numScores; $i++ )
	{
		$row = mysql_fetch_array($result);
		$difArray[$row["id"]] = calcDifi($row["id"]);
	}
	
	// SORT THE ARRAY FROM LOWEST TO HIGHEST DIFFERENTIAL
	asort($difArray, SORT_NUMERIC);
	reset($difArray);	// set the array pointer back to the first element
	
	$newArray = array();
	for ( $i = 0; $i < $numDifs; $i++ )
	{
		list($key, $val) = each($difArray);
		$newArray[] = $key;
	}	
	return $newArray;
}

function getParArray ( $teeid )
{
	$parArray = array();
	$sql = "select * from tee_tbl where id = $teeid";
	$result = mysql_query($sql) or die("Could not list of par values: " . mysql_error());
	if ( mysql_num_rows($result) == 0 )
		die("Could not get course data for tee id %s" . $courseID);
	$row = mysql_fetch_array($result);
	$parArray[] = $row["par1"];
	$parArray[] = $row["par2"];
	$parArray[] = $row["par3"];
	$parArray[] = $row["par4"];
	$parArray[] = $row["par5"];
	$parArray[] = $row["par6"];
	$parArray[] = $row["par7"];
	$parArray[] = $row["par8"];
	$parArray[] = $row["par9"];
	$parArray[] = $row["par10"];
	$parArray[] = $row["par11"];
	$parArray[] = $row["par12"];
	$parArray[] = $row["par13"];
	$parArray[] = $row["par14"];
	$parArray[] = $row["par15"];
	$parArray[] = $row["par16"];
	$parArray[] = $row["par17"];
	$parArray[] = $row["par18"];
	return $parArray;
}


function getBestToWorstHoleArray ( $courseAverageArray, $teeid, $fromdate, $todate )
{
	$parArray = array();
	$parArray = getParArray($teeid);
	$difArray = array();	// score - par
	for ($i = 0; $i < 18; $i++)
	{
		if ( $courseAverageArray[$i] != "" && $courseAverageArray[$i] != 0 )
			$difArray[$i] = $courseAverageArray[$i] - $parArray[$i];
	}
	
		
	for ($i = 0; $i < count($difArray); $i++)
	{
		$difArray[$i] = formatHandi($difArray[$i]);
	}
	
	
	asort( $difArray, SORT_NUMERIC );
	
	$valKeySortedArray = array();
	$valKeySortedArray = sortArrayByKeyWithinVal($difArray);
	
	
   	$bestToWorstHoleArray = array();
	while (list($key, $val) = each($valKeySortedArray)) 
   		$bestToWorstHoleArray[] = $key + 1;
	
	
	return $bestToWorstHoleArray;
}

function printArray($theArray)
{
	while (list($key, $val) = each($theArray)) 
   		echo "$key = $val<br>";
	echo "<BR><BR>";
}

function sortArrayByKeyWithinVal($theArray)
{
	$masterArray = array();
	$masterArray[] = array();
	$masterArrayCnt = 0;
	$currentVal = current($theArray);
	while (list($key, $val) = each($theArray)) 
	{
		if ( $val != $currentVal )
		{
			$masterArray[] = array();
			$masterArrayCnt++;
			$currentVal = $val;
		}
		$masterArray[$masterArrayCnt][$key] = $val;
	}
	
	$rtnArray = array();
	for ($i = 0; $i < count($masterArray); $i++)
		ksort($masterArray[$i]);	
		
	for ($i = 0; $i < count($masterArray); $i++)
	{
		while (list($key, $val) = each($masterArray[$i])) 
			$rtnArray[$key] = $val;
	}
	
	return $rtnArray;
	//printf("<br>count:  %s<br>", count($masterArray));
}


function getWorstAverageHoleArray ( $courseAverageArray, $teeid, $fromdate, $todate )
{
	$parArray = array();
	$parArray = getParArray($teeid);
	$difArray = array();	// score - par
	for ($i = 0; $i < 18; $i++)
	{
		if ( $courseAverageArray[$i] != "" && $courseAverageArray[$i] != 0 )
			$difArray[$i] = $courseAverageArray[$i] - $parArray[$i];
	}
	
	for ($i = 0; $i < count($difArray); $i++)
	{
		$difArray[$i] = formatHandi($difArray[$i]);
	}
	
	arsort( $difArray, SORT_NUMERIC );
	
   	reset( $difArray );
   	$worstHoleArray = array();
   	$dif = current($difArray);
   	
   	while ($dif == current($difArray)) 
   	{
   		$worstHoleArray[] = (key($difArray) + 1);	// Add one to account for the zero index.
   		next($difArray);
	}

	sort($worstHoleArray, SORT_NUMERIC);	
	return $worstHoleArray;
}

function getBestAverageHoleArray ( $courseAverageArray, $teeid, $fromdate, $todate )
{
	$parArray = array();
	$parArray = getParArray($teeid);
	$difArray = array();	// score - par
	for ($i = 0; $i < 18; $i++)
	{
		if ( $courseAverageArray[$i] != "" && $courseAverageArray[$i] != 0 )
			$difArray[$i] = $courseAverageArray[$i] - $parArray[$i];
	}
	for ($i = 0; $i < count($difArray); $i++)
	{
		$difArray[$i] = formatHandi($difArray[$i]);
	}
	asort( $difArray, SORT_NUMERIC );
	
   	reset( $difArray );
   	$bestHoleArray = array();
   	$dif = current($difArray);
   	
   	while ($dif == current($difArray)) 
   	{
   		$bestHoleArray[] = (key($difArray) + 1);	// Add one to account for the zero index.
   		next($difArray);
	}
	
	sort($bestHoleArray, SORT_NUMERIC);	
	return $bestHoleArray;
	
	/*
	while (list($key, $val) = each($difArray)) 
	{
   		echo "$key = $val<br>";
	}
	echo "<BR>";
	reset( $difArray );
	*/	
}

function getPuttAverageForHole ($teeid, $holeNm, $fromdate, $todate)
{
	$total = 0;
	$avg = 0;
	$sql = "";
	if ( $fromdate != "" && $todate != "" )
		$sql = "select $holeNm as score from score_tbl where teeid = $teeid and $holeNm is not null and dateplayed >= $fromdate and dateplayed <= $todate";
	else if ( $fromdate != "" )
		$sql = "select $holeNm as score from score_tbl where teeid = $teeid and $holeNm is not null and dateplayed >= $fromdate";
	else if ( $todate != "" )
		$sql = "select $holeNm as score from score_tbl where teeid = $teeid and $holeNm is not null and dateplayed <= $todate";
	else
		$sql = "select $holeNm as score from score_tbl where teeid = $teeid and $holeNm is not null";
	//printf("%s<br>", $sql);
	$result = mysql_query($sql) or die("Could not select putt average: " . mysql_error());
	if ( mysql_num_rows($result) == 0 )
	{
		//printf("Got here");
		return "";
	}
	else
	{
		while ($row = mysql_fetch_array($result))
		{
			$total += $row["score"];
		}
		$avg = ( $total / mysql_num_rows($result) );
		return $avg;
	}
}

function getAverageForHole ($teeid, $holeNm, $fromdate, $todate)
{
	$total = 0;
	$avg = 0;
	$sql = "";
	if ( $fromdate != "" && $todate != "" )
		$sql = "select $holeNm as score from score_tbl where teeid = $teeid and $holeNm != 0 and $holeNm is not null and dateplayed >= $fromdate and dateplayed <= $todate";
	else if ( $fromdate != "" )
		$sql = "select $holeNm as score from score_tbl where teeid = $teeid and $holeNm != 0 and $holeNm is not null and dateplayed >= $fromdate";
	else if ( $todate != "" )
		$sql = "select $holeNm as score from score_tbl where teeid = $teeid and $holeNm != 0 and $holeNm is not null and dateplayed <= $todate";
	else
		$sql = "select $holeNm as score from score_tbl where teeid = $teeid and $holeNm != 0 and $holeNm is not null";
	//printf("%s<br>", $sql);
	$result = mysql_query($sql) or die("Could not select average for hole: " . mysql_error());
	if ( mysql_num_rows($result) == 0 )
		return "";
	else
	{
		while ($row = mysql_fetch_array($result))
		{
			$total += $row["score"];
		}
		$avg = ( $total / mysql_num_rows($result) );
		return $avg;
	}
}

function getAverageForFrontNine ($teeid, $fromdate, $todate)
{
	$total = 0;
	$avg = 0;
	$sql = "";
	if ( $fromdate != "" && $todate != "" )
		$sql = "select hole1 + hole2 + hole3 + hole4 + hole5 + hole6 + hole7 + hole8 + hole9 as score from score_tbl where teeid = $teeid and hole1 != 0 and hole1 is not null and hole2 != 0 and hole2 is not null and hole3 != 0 and hole3 is not null and hole4 != 0 and hole4 is not null and hole5 != 0 and hole5 is not null and hole6 != 0 and hole6 is not null and hole7 != 0 and hole7 is not null and hole8 != 0 and hole8 is not null and hole9 != 0 and hole9 is not null and dateplayed >= $fromdate and dateplayed <= $todate";
	else if ( $fromdate != "" )
		$sql = "select hole1 + hole2 + hole3 + hole4 + hole5 + hole6 + hole7 + hole8 + hole9 as score from score_tbl where teeid = $teeid and hole1 != 0 and hole1 is not null and hole2 != 0 and hole2 is not null and hole3 != 0 and hole3 is not null and hole4 != 0 and hole4 is not null and hole5 != 0 and hole5 is not null and hole6 != 0 and hole6 is not null and hole7 != 0 and hole7 is not null and hole8 != 0 and hole8 is not null and hole9 != 0 and hole9 is not null and dateplayed >= $fromdate";
	else if ( $todate != "" )
		$sql = "select hole1 + hole2 + hole3 + hole4 + hole5 + hole6 + hole7 + hole8 + hole9 as score from score_tbl where teeid = $teeid and hole1 != 0 and hole1 is not null and hole2 != 0 and hole2 is not null and hole3 != 0 and hole3 is not null and hole4 != 0 and hole4 is not null and hole5 != 0 and hole5 is not null and hole6 != 0 and hole6 is not null and hole7 != 0 and hole7 is not null and hole8 != 0 and hole8 is not null and hole9 != 0 and hole9 is not null and dateplayed <= $todate";
	else
		$sql = "select hole1 + hole2 + hole3 + hole4 + hole5 + hole6 + hole7 + hole8 + hole9 as score from score_tbl where teeid = $teeid and hole1 != 0 and hole1 is not null and hole2 != 0 and hole2 is not null and hole3 != 0 and hole3 is not null and hole4 != 0 and hole4 is not null and hole5 != 0 and hole5 is not null and hole6 != 0 and hole6 is not null and hole7 != 0 and hole7 is not null and hole8 != 0 and hole8 is not null and hole9 != 0 and hole9 is not null";
	//printf("%s<br>", $sql);
	$result = mysql_query($sql) or die("Could not select score history for front nine: " . mysql_error());
	if ( mysql_num_rows($result) == 0 )
		return "";
	else
	{
		while ($row = mysql_fetch_array($result))
		{
			$total += $row["score"];
		}
		$avg = ( $total / mysql_num_rows($result) );
		return $avg;
	}
}

function getAverageForBackNine ($teeid, $fromdate, $todate)
{
	$total = 0;
	$avg = 0;
	$sql = "";
	if ( $fromdate != "" && $todate != "" )
		$sql = "select hole10 + hole11 + hole12 + hole13 + hole14 + hole15 + hole16 + hole17 + hole18 as score from score_tbl where teeid = $teeid and hole10 != 0 and hole10 is not null and hole11 != 0 and hole11 is not null and hole12 != 0 and hole12 is not null and hole13 != 0 and hole13 is not null and hole14 != 0 and hole14 is not null and hole15 != 0 and hole15 is not null and hole16 != 0 and hole16 is not null and hole17 != 0 and hole17 is not null and hole18 != 0 and hole18 is not null and dateplayed >= $fromdate and dateplayed <= $todate";
	else if ( $fromdate != "" )
		$sql = "select hole10 + hole11 + hole12 + hole13 + hole14 + hole15 + hole16 + hole17 + hole18 as score from score_tbl where teeid = $teeid and hole10 != 0 and hole10 is not null and hole11 != 0 and hole11 is not null and hole12 != 0 and hole12 is not null and hole13 != 0 and hole13 is not null and hole14 != 0 and hole14 is not null and hole15 != 0 and hole15 is not null and hole16 != 0 and hole16 is not null and hole17 != 0 and hole17 is not null and hole18 != 0 and hole18 is not null and dateplayed >= $fromdate";
	else if ( $todate != "" )
		$sql = "select hole10 + hole11 + hole12 + hole13 + hole14 + hole15 + hole16 + hole17 + hole18 as score from score_tbl where teeid = $teeid and hole10 != 0 and hole10 is not null and hole11 != 0 and hole11 is not null and hole12 != 0 and hole12 is not null and hole13 != 0 and hole13 is not null and hole14 != 0 and hole14 is not null and hole15 != 0 and hole15 is not null and hole16 != 0 and hole16 is not null and hole17 != 0 and hole17 is not null and hole18 != 0 and hole18 is not null and dateplayed <= $todate";
	else
		$sql = "select hole10 + hole11 + hole12 + hole13 + hole14 + hole15 + hole16 + hole17 + hole18 as score from score_tbl where teeid = $teeid and hole10 != 0 and hole10 is not null and hole11 != 0 and hole11 is not null and hole12 != 0 and hole12 is not null and hole13 != 0 and hole13 is not null and hole14 != 0 and hole14 is not null and hole15 != 0 and hole15 is not null and hole16 != 0 and hole16 is not null and hole17 != 0 and hole17 is not null and hole18 != 0 and hole18 is not null";
	//printf("%s<br>", $sql);
	$result = mysql_query($sql) or die("Could not select score history for front nine: " . mysql_error());
	if ( mysql_num_rows($result) == 0 )
		return "";
	else
	{
		while ($row = mysql_fetch_array($result))
		{
			$total += $row["score"];
		}
		$avg = ( $total / mysql_num_rows($result) );
		return $avg;
	}
}


function getPuttAverageForFrontNine ($teeid, $fromdate, $todate)
{
	$total = 0;
	$avg = 0;
	$sql = "";
	if ( $fromdate != "" && $todate != "" )
		$sql = "select putt1 + putt2 + putt3 + putt4 + putt5 + putt6 + putt7 + putt8 + putt9 as score from score_tbl where teeid = $teeid and putt1 is not null and putt2 is not null and putt3 is not null and putt4 is not null and putt5 is not null and putt6 is not null and putt7 is not null and putt8 is not null and putt9 is not null and dateplayed >= $fromdate and dateplayed <= $todate";
	else if ( $fromdate != "" )
		$sql = "select putt1 + putt2 + putt3 + putt4 + putt5 + putt6 + putt7 + putt8 + putt9 as score from score_tbl where teeid = $teeid and putt1 is not null and putt2 is not null and putt3 is not null and putt4 is not null and putt5 is not null and putt6 is not null and putt7 is not null and putt8 is not null and putt9 is not null and dateplayed >= $fromdate";
		//$sql = "select putt1 + putt2 + putt3 + putt4 + putt5 + putt6 + putt7 + putt8 + putt9 as score from score_tbl where teeid = $teeid and putt1 != 0 and putt1 is not null and putt2 != 0 and putt2 is not null and putt3 != 0 and putt3 is not null and putt4 != 0 and putt4 is not null and putt5 != 0 and putt5 is not null and putt6 != 0 and putt6 is not null and putt7 != 0 and putt7 is not null and putt8 != 0 and putt8 is not null and putt9 != 0 and putt9 is not null and dateplayed >= $fdate";
	else if ( $todate != "" )
		$sql = "select putt1 + putt2 + putt3 + putt4 + putt5 + putt6 + putt7 + putt8 + putt9 as score from score_tbl where teeid = $teeid and putt1 is not null and putt2 is not null and putt3 is not null and putt4 is not null and putt5 is not null and putt6 is not null and putt7 is not null and putt8 is not null and putt9 is not null and dateplayed <= $todate";
		//$sql = "select putt1 + putt2 + putt3 + putt4 + putt5 + putt6 + putt7 + putt8 + putt9 as score from score_tbl where teeid = $teeid and putt1 != 0 and putt1 is not null and putt2 != 0 and putt2 is not null and putt3 != 0 and putt3 is not null and putt4 != 0 and putt4 is not null and putt5 != 0 and putt5 is not null and putt6 != 0 and putt6 is not null and putt7 != 0 and putt7 is not null and putt8 != 0 and putt8 is not null and putt9 != 0 and putt9 is not null and dateplayed <= $tdate";
	else
		$sql = "select putt1 + putt2 + putt3 + putt4 + putt5 + putt6 + putt7 + putt8 + putt9 as score from score_tbl where teeid = $teeid and putt1 is not null and putt2 is not null and putt3 is not null and putt4 is not null and putt5 is not null and putt6 is not null and putt7 is not null and putt8 is not null and putt9 is not null";
		//$sql = "select putt1 + putt2 + putt3 + putt4 + putt5 + putt6 + putt7 + putt8 + putt9 as score from score_tbl where teeid = $teeid and putt1 != 0 and putt1 is not null and putt2 != 0 and putt2 is not null and putt3 != 0 and putt3 is not null and putt4 != 0 and putt4 is not null and putt5 != 0 and putt5 is not null and putt6 != 0 and putt6 is not null and putt7 != 0 and putt7 is not null and putt8 != 0 and putt8 is not null and putt9 != 0 and putt9 is not null";
	//printf("%s<br>", $sql);
	$result = mysql_query($sql) or die("Could not select score history for front nine: " . mysql_error());
	if ( mysql_num_rows($result) == 0 )
		return "";
	else
	{
		while ($row = mysql_fetch_array($result))
		{
			$total += $row["score"];
		}
		$avg = ( $total / mysql_num_rows($result) );
		return $avg;
	}
}

function getPuttAverageForBackNine ($teeid, $fromdate, $todate)
{
	$total = 0;
	$avg = 0;
	$sql = "";
	if ( $fromdate != "" && $todate != "" )
		$sql = "select putt10 + putt11 + putt12 + putt13 + putt14 + putt15 + putt16 + putt17 + putt18 as score from score_tbl where teeid = $teeid and putt10 != 0 and putt10 is not null and putt11 != 0 and putt11 is not null and putt12 != 0 and putt12 is not null and putt13 != 0 and putt13 is not null and putt14 != 0 and putt14 is not null and putt15 != 0 and putt15 is not null and putt16 != 0 and putt16 is not null and putt17 != 0 and putt17 is not null and putt18 != 0 and putt18 is not null and dateplayed >= $fromdate and dateplayed <= $todate";
	else if ( $fromdate != "" )
		$sql = "select putt10 + putt11 + putt12 + putt13 + putt14 + putt15 + putt16 + putt17 + putt18 as score from score_tbl where teeid = $teeid and putt10 != 0 and putt10 is not null and putt11 != 0 and putt11 is not null and putt12 != 0 and putt12 is not null and putt13 != 0 and putt13 is not null and putt14 != 0 and putt14 is not null and putt15 != 0 and putt15 is not null and putt16 != 0 and putt16 is not null and putt17 != 0 and putt17 is not null and putt18 != 0 and putt18 is not null and dateplayed >= $fromdate";
	else if ( $todate != "" )
		$sql = "select putt10 + putt11 + putt12 + putt13 + putt14 + putt15 + putt16 + putt17 + putt18 as score from score_tbl where teeid = $teeid and putt10 != 0 and putt10 is not null and putt11 != 0 and putt11 is not null and putt12 != 0 and putt12 is not null and putt13 != 0 and putt13 is not null and putt14 != 0 and putt14 is not null and putt15 != 0 and putt15 is not null and putt16 != 0 and putt16 is not null and putt17 != 0 and putt17 is not null and putt18 != 0 and putt18 is not null and dateplayed <= $todate";
	else
		$sql = "select putt10 + putt11 + putt12 + putt13 + putt14 + putt15 + putt16 + putt17 + putt18 as score from score_tbl where teeid = $teeid and putt10 != 0 and putt10 is not null and putt11 != 0 and putt11 is not null and putt12 != 0 and putt12 is not null and putt13 != 0 and putt13 is not null and putt14 != 0 and putt14 is not null and putt15 != 0 and putt15 is not null and putt16 != 0 and putt16 is not null and putt17 != 0 and putt17 is not null and putt18 != 0 and putt18 is not null";
	//printf("%s<br>", $sql);
	$result = mysql_query($sql) or die("Could not select score history for front nine: " . mysql_error());
	if ( mysql_num_rows($result) == 0 )
		return "";
	else
	{
		while ($row = mysql_fetch_array($result))
		{
			$total += $row["score"];
		}
		$avg = ( $total / mysql_num_rows($result) );
		return $avg;
	}
}


function getCoursePuttAverageArray( $teeid, $fromdate, $todate )
{
	$sql = "";
	if ( $fromdate != "" && $todate != "" )
		$sql = "select * from score_tbl where teeid = $teeid and dateplayed >= '$fromdate' and dateplayed <= '$todate'";
	else if ( $fromdate != "" )
		$sql = "select * from score_tbl where teeid = $teeid and dateplayed >= '$fromdate'";
	else if ( $todate != "" )
		$sql = "select * from score_tbl where teeid = $teeid and dateplayed <= '$todate'";
	else
		$sql = "select * from score_tbl where teeid = $teeid";

	//printf("%s<br>", $sql);
	$result = mysql_query($sql) or die("Could not select score history " . mysql_error());
	if (mysql_num_rows($result) == 0)
		die("No scores for that date range or course");
	
	$scoreAvgArray = array();
	for ($i = 1; $i <= 18; $i++)
	{
		$holeNm = "putt";
		$holeNm .= $i;
		$scoreAvgArray[] = getPuttAverageForHole($teeid, $holeNm, $fromdate, $todate);
	}
	$scoreAvgArray[] = getPuttAverageForFrontNine($teeid, $fromdate, $todate);
	$scoreAvgArray[] = getPuttAverageForBackNine($teeid, $fromdate, $todate);
	return $scoreAvgArray;
}

function getCourseAverageArray( $teeid, $fromdate, $todate )
{
	$sql = "";
	if ( $fromdate != "" && $todate != "" )
		$sql = "select * from score_tbl where teeid = $teeid and dateplayed >= '$fromdate' and dateplayed <= '$todate'";
	else if ( $fromdate != "" )
		$sql = "select * from score_tbl where teeid = $teeid and dateplayed >= '$fromdate'";
	else if ( $todate != "" )
		$sql = "select * from score_tbl where teeid = $teeid and dateplayed <= '$todate'";
	else
		$sql = "select * from score_tbl where teeid = $teeid";

	//printf("%s<br>", $sql);
	$result = mysql_query($sql) or die("Could not select score history " . mysql_error());
	if (mysql_num_rows($result) == 0)
		die("No scores for that date range or course");
	
	$scoreAvgArray = array();
	for ($i = 1; $i <= 18; $i++)
	{
		$holeNm = "hole";
		$holeNm .= $i;
		$scoreAvgArray[] = getAverageForHole($teeid, $holeNm, $fromdate, $todate);
	}
	$scoreAvgArray[] = getAverageForFrontNine($teeid, $fromdate, $todate);
	$scoreAvgArray[] = getAverageForBackNine($teeid, $fromdate, $todate);
	return $scoreAvgArray;
}

function getGreensArray( $teeid, $fromdate, $todate )
{
	$sql = "select * from score_tbl where teeid = $teeid";
	if ( strlen($fromdate) > 0 )
		$sql .= " and dateplayed >= '$fromdate' and dateplayed <= '$todate'";

	//printf("%s<br>", $sql);
	$result = mysql_query($sql) or die("Could not select score history " . mysql_error());
	if (mysql_num_rows($result) == 0)
		die("No scores for that date range or course");
	
	$greensArray = array();
	$greensHit = 0;
	$greensPossible = 0;
	while ($row = mysql_fetch_array($result))
	{
		if ($row["greens"] != null)
		{
			if ($row["hole1"] != null)
				$greensPossible += 9;
			if ($row["hole10"] != null)
				$greensPossible += 9;
			$greensHit += $row["greens"];
		}
	}
	$greensArray[] = $greensHit;
	$greensArray[] = $greensPossible;
	return $greensArray;
}

function getPercentFairways( $scoreid )
{
	$sql = "select * from score_tbl where id = $scoreid";
	
	//printf("%s<br>", $sql);
	$result = mysql_query($sql) or die("Could not select score history " . mysql_error());
	if (mysql_num_rows($result) == 0)
		die("No scores for that date range or course");
	$row = mysql_fetch_array($result);
	
	$fairwaysArray = array();
	$fairwaysHit = 0;
	$fairwaysPossible = 0;
	$fairwaysFrontNine = getFairwaysPossible($row["teeid"], "Front");
	$fairwaysBackNine = getFairwaysPossible($row["teeid"], "Back");
	if ($row["fairways"] != null)
	{
		if ($row["hole1"] != null)
			$fairwaysPossible += $fairwaysFrontNine;
		if ($row["hole10"] != null)
			$fairwaysPossible += $fairwaysBackNine;
		$fairwaysHit += $row["fairways"];
		$perFairways = number_format(100*($fairwaysHit / $fairwaysPossible), 1);
		return $perFairways;
	}
	else
		return "N/A";
}



function getFairwaysArray( $teeid, $fromdate, $todate )
{
	$sql = "select * from score_tbl where teeid = $teeid";
	if ( strlen($fromdate) > 0 )
		$sql .= " and dateplayed >= '$fromdate' and dateplayed <= '$todate'";
		

	//printf("%s<br>", $sql);
	$result = mysql_query($sql) or die("Could not select score history " . mysql_error());
	if (mysql_num_rows($result) == 0)
		die("No scores for that date range or course");
	
	$fairwaysArray = array();
	$fairwaysHit = 0;
	$fairwaysPossible = 0;
	$fairwaysFrontNine = getFairwaysPossible($teeid, "Front");
	$fairwaysBackNine = getFairwaysPossible($teeid, "Back");
	while ($row = mysql_fetch_array($result))
	{
		if ($row["fairways"] != null)
		{
			if ($row["hole1"] != null)
				$fairwaysPossible += $fairwaysFrontNine;
			if ($row["hole10"] != null)
				$fairwaysPossible += $fairwaysBackNine;
			$fairwaysHit += $row["fairways"];
		}
	}
	$fairwaysArray[] = $fairwaysHit;
	$fairwaysArray[] = $fairwaysPossible;
	return $fairwaysArray;
}

function getpenaltiesArray( $teeid, $fromdate, $todate )
{
	$sql = "select * from score_tbl where teeid = $teeid";
	if ( strlen($fromdate) > 0 )
		$sql .= " and dateplayed >= '$fromdate' and dateplayed <= '$todate'";
	
	//printf("%s<br>", $sql);
	$result = mysql_query($sql) or die("Could not select score history " . mysql_error());
	if (mysql_num_rows($result) == 0)
		die("No scores for that date range or course");
	
	$penaltiesArray = array();
	$holesPlayed = 0;
	$penalties = 0;
	while ($row = mysql_fetch_array($result))
	{
		if ($row["penalties"] != null)
		{
			if ($row["hole1"] != null)
				$holesPlayed += 9;
			if ($row["hole10"] != null)
				$holesPlayed += 9;
			$penalties += $row["penalties"];
		}
	}
	$penaltiesArray[] = $penalties;
	$penaltiesArray[] = $holesPlayed;
	return $penaltiesArray;
}

function getFairwaysPossible( $teeid, $x )
{
	$sql = "select * from tee_tbl where id = $teeid";
	$result = mysql_query($sql) or die("Could not get course details: " . mysql_error());
	$row = mysql_fetch_array($result);
	$num = 0;
	if ($x == "Front")
	{
		if ($row["par1"] > 3)
			$num++;
		if ($row["par2"] > 3)
			$num++;
		if ($row["par3"] > 3)
			$num++;
		if ($row["par4"] > 3)
			$num++;
		if ($row["par5"] > 3)
			$num++;
		if ($row["par6"] > 3)
			$num++;
		if ($row["par7"] > 3)
			$num++;
		if ($row["par8"] > 3)
			$num++;
		if ($row["par9"] > 3)
			$num++;
	}
	else if ($x == "Back")
	{
		if ($row["par10"] > 3)
			$num++;
		if ($row["par11"] > 3)
			$num++;
		if ($row["par12"] > 3)
			$num++;
		if ($row["par13"] > 3)
			$num++;
		if ($row["par14"] > 3)
			$num++;
		if ($row["par15"] > 3)
			$num++;
		if ($row["par16"] > 3)
			$num++;
		if ($row["par17"] > 3)
			$num++;
		if ($row["par18"] > 3)
			$num++;
	}
	else
		die("Invalid second parm to function getFairwaysPossible()");
	return $num;
}

function isNineHole( $teeid )
{
	$sql = "select * from tee_tbl where id = $teeid";
	//printf("%s", $sql);
	$result = mysql_query($sql) or die("Could not get course details: " . mysql_error());
	$row = mysql_fetch_array($result);
	if ($row["par10"] == null)
		return 1;
	if ($row["par11"] == null)
		return 1;
	if ($row["par12"] == null)
		return 1;
	if ($row["par13"] == null)
		return 1;
	if ($row["par14"] == null)
		return 1;
	if ($row["par15"] == null)
		return 1;
	if ($row["par16"] == null)
		return 1;
	if ($row["par17"] == null)
		return 1;
	if ($row["par18"] == null)
		return 1;
	return 0;
}

function getTimeControl( $theDate="", $suffix="" )
{
	if ( $theDate == "" )
		$my_t=getdate();
	else
		$my_t=$theDate;
		
		printf("<select name=\"TheHour%s\">", $suffix);
		for ($i = 0; $i <= 23; $i++)
		{
			if ($my_t[hours] == $i)
				printf("<option value=\"$i\" selected=\"selected\">$i</option>");
			else
				printf("<option value=\"$i\">$i</option>");
		}
		printf("</select>");
	
	
		printf("<select name=\"TheMinute%s\">", $suffix);
		
		for ($i = 0; $i <= 59; $i++)
		{
			if ($my_t[minutes] == $i)
				printf("<option value=\"$i\" selected=\"selected\">$i</option>");
			else
				printf("<option value=\"$i\">$i</option>");
		}
	printf("</select>");
}

function getDateControl( $theDate="", $suffix="" )
{
	if ( $theDate == "" )
		$my_t=getdate();
	else
		$my_t=$theDate;
		
	$monthArray = array("Dummy", "Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec");
	printf("<select name=\"TheMonth%s\">", $suffix);
		for ($i = 1; $i <= 12; $i++)
		{
			if ($my_t[mon] == $i)
				printf("<option value=\"$i\" selected=\"selected\">$monthArray[$i]</option>");
			else
				printf("<option value=\"$i\">$monthArray[$i]</option>");
		}
	printf("</select>");
	
	printf("<select name=\"TheDay%s\">", $suffix);
		
		for ($i = 1; $i <= 31; $i++)
		{
			if ($my_t[mday] == $i)
				printf("<option value=\"$i\" selected=\"selected\">$i</option>");
			else
				printf("<option value=\"$i\">$i</option>");
		}
	printf("</select>");
	
	printf("<select name=\"TheYear%s\">", $suffix);

		for ($i = 2008; $i <= 2025; $i++)
		{
			if ($my_t[year] == $i)
				printf("<option value=\"$i\" selected=\"selected\">$i</option>");
			else
				printf("<option value=\"$i\">$i</option>");
		}
	printf("</select>");
}

function DisScoreEntryForm( $courseID, $postPage )
{
	$sql = "select ct.name as CourseName, tt.name as TeeName, tt.id as TeeID, tt.* from course_tbl ct, tee_tbl tt where tt.courseid = ct.id and ct.id = ";
	//$sql = "select ct.name as CourseName, tt.name as TeeName, tt.id as TeeID, tt.* from course_tbl ct LEFT JOIN tee_tbl tt on ct.id = tt.courseid where ct.id =";
	//$sql = "select * from course_tbl ct, tee_tbl tt where tt.courseid = ct.id and ct.id = ";
	$sql .= $courseID;
	//printf("%s", $sql);
	$result = mysql_query($sql) or die("Could not get selected course: " . mysql_error());
	$row = mysql_fetch_array($result);
	$teesArray = array();
	$teesArray = $row;
	
	printf("<form action=\"%s\" method=\"POST\" name=\"scorecard\">", $postPage);
	printf("<table class=\"CourseTable\">");
	printf("<td>");
	printf("<table>");
	printf("<tr>");
	printf("<tr><td><b>Course Name:</b> </td><td><input class=\"InputBoxWidth\" disabled type=\"text\" name=\"CourseName\" value=\"%s\"></td></tr>", $row["CourseName"]);
	printf("<tr><td><b>Tees:</b> </td><td>");
	
	printf("<select onchange=\"GetTees(this.value, PopulateScorecardParValues)\" class=\"InputBoxWidth\" name=\"Tees\">");
		while ($teesArray)
		{
			printf("<option value=\"%s\">%s</option>",$teesArray["TeeID"],$teesArray["TeeName"]);
			$teesArray = mysql_fetch_array($result);
		}
	printf("</select>");
	
	
	//printf("<tr><td><b>Slope Rating:</b>  </td><td><input class=\"InputBoxWidth\" disabled type=\"text\" name=\"SlopeRating\" value=\"%s\"></td></tr>", $row["slope"]);
	//printf("<tr><td><b>Course Rating:</b>  </td><td><input class=\"InputBoxWidth\" disabled type=\"text\" name=\"CourseRating\" value=\"%s\"></td></tr> ", $row["rating"]);
	//printf("<tr><td><b>City:</b>  </td><td><input class=\"InputBoxWidth\" disabled type=\"text\" name=\"City\" value=\"%s\"></td></tr> ", $row["city"]);
	//printf("<tr><td><b>State/Province:</b>  </td><td><input class=\"InputBoxWidth\" disabled type=\"text\" name=\"State\" value=\"%s\"></td></tr>", $row["state"]);
	printf("<tr><td><b>Date:</b>  </td><td>");
	getDateControl();
	printf("</td></tr>");
	printf("</table> ");
	
	printf("<table class=\"ScoreCardTable\">");
	?>
	<tr><td><br></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td></tr>
	<?
	printf("<tr><td><b>Hole:</b></td><td class=\"HoleNumberBox\">1</td><td class=\"HoleNumberBox\">2</td><td class=\"HoleNumberBox\">3</td><td class=\"HoleNumberBox\">4</td><td class=\"HoleNumberBox\">5</td><td class=\"HoleNumberBox\">6</td><td class=\"HoleNumberBox\">7</td><td class=\"HoleNumberBox\">8</td><td class=\"HoleNumberBox\">9</td><td class=\"HoleNumberBox\"><b>Out<b></td></tr>");
	printf("<tr><td><b>Par:</b></td>
	<td name=\"ParHole1\" class=\"HoleNumberBox\"><div id=\"ParHole1\">%s</div></td>
	<td name=\"ParHole2\" class=\"HoleNumberBox\"><div id=\"ParHole2\">%s</div></td>
	<td name=\"ParHole3\" class=\"HoleNumberBox\"><div id=\"ParHole3\">%s</div></td>
	<td name=\"ParHole4\" class=\"HoleNumberBox\"><div id=\"ParHole4\">%s</div></td>
	<td name=\"ParHole5\" class=\"HoleNumberBox\"><div id=\"ParHole5\">%s</div></td>
	<td name=\"ParHole6\" class=\"HoleNumberBox\"><div id=\"ParHole6\">%s</div></td>
	<td name=\"ParHole7\" class=\"HoleNumberBox\"><div id=\"ParHole7\">%s</div></td>
	<td name=\"ParHole8\" class=\"HoleNumberBox\"><div id=\"ParHole8\">%s</div></td>
	<td name=\"ParHole9\" class=\"HoleNumberBox\"><div id=\"ParHole9\">%s</div></td>
	<td name=\"Front9Total\" class=\"HoleNumberBox\"><div id=\"Front9Total\">%s</div></td></tr>", $row["par1"], $row["par2"], $row["par3"], $row["par4"], $row["par5"], $row["par6"], $row["par7"], $row["par8"], $row["par9"], calcFrontPar($row));
	?>
	<tr><td><b>Score:</b></td>
	    <td><input class="ScoreInputBox" type="text" name="par1" onKeyUp="HandleKeyUp(this, document.scorecard.par2)" onFocus="HandleFocus(this)"></td>
	    <td><input class="ScoreInputBox" type="text" name="par2" onKeyUp="HandleKeyUp(this, document.scorecard.par3)" onFocus="HandleFocus(this)"></td>
	    <td><input class="ScoreInputBox" type="text" name="par3" onKeyUp="HandleKeyUp(this, document.scorecard.par4)" onFocus="HandleFocus(this)"></td>
	    <td><input class="ScoreInputBox" type="text" name="par4" onKeyUp="HandleKeyUp(this, document.scorecard.par5)" onFocus="HandleFocus(this)"></td>
	    <td><input class="ScoreInputBox" type="text" name="par5" onKeyUp="HandleKeyUp(this, document.scorecard.par6)" onFocus="HandleFocus(this)"></td>
	    <td><input class="ScoreInputBox" type="text" name="par6" onKeyUp="HandleKeyUp(this, document.scorecard.par7)" onFocus="HandleFocus(this)"></td>
	    <td><input class="ScoreInputBox" type="text" name="par7" onKeyUp="HandleKeyUp(this, document.scorecard.par8)" onFocus="HandleFocus(this)"></td>
	    <td><input class="ScoreInputBox" type="text" name="par8" onKeyUp="HandleKeyUp(this, document.scorecard.par9)" onFocus="HandleFocus(this)"></td>
	    <td><input class="ScoreInputBox" type="text" name="par9" onKeyUp="HandleKeyUp(this, document.scorecard.putt1)" onFocus="HandleFocus(this)"></td>
	    <td><input disabled class="ScoreInputBox" type="text" name="out" value="0"></td>
	</tr>
	
	<tr><td><b>Putts:</b></td>
	    <td><input class="ScoreInputBox" type="text" name="putt1" onKeyUp="HandlePuttKeyUp(this, document.scorecard.putt2)" onFocus="HandleFocus(this)"></td>
	    <td><input class="ScoreInputBox" type="text" name="putt2" onKeyUp="HandlePuttKeyUp(this, document.scorecard.putt3)" onFocus="HandleFocus(this)"></td>
	    <td><input class="ScoreInputBox" type="text" name="putt3" onKeyUp="HandlePuttKeyUp(this, document.scorecard.putt4)" onFocus="HandleFocus(this)"></td>
	    <td><input class="ScoreInputBox" type="text" name="putt4" onKeyUp="HandlePuttKeyUp(this, document.scorecard.putt5)" onFocus="HandleFocus(this)"></td>
	    <td><input class="ScoreInputBox" type="text" name="putt5" onKeyUp="HandlePuttKeyUp(this, document.scorecard.putt6)" onFocus="HandleFocus(this)"></td>
	    <td><input class="ScoreInputBox" type="text" name="putt6" onKeyUp="HandlePuttKeyUp(this, document.scorecard.putt7)" onFocus="HandleFocus(this)"></td>
	    <td><input class="ScoreInputBox" type="text" name="putt7" onKeyUp="HandlePuttKeyUp(this, document.scorecard.putt8)" onFocus="HandleFocus(this)"></td>
	    <td><input class="ScoreInputBox" type="text" name="putt8" onKeyUp="HandlePuttKeyUp(this, document.scorecard.putt9)" onFocus="HandleFocus(this)"></td>
	    <td><input class="ScoreInputBox" type="text" name="putt9" onKeyUp="HandlePuttKeyUp(this, document.scorecard.<?if (!isNineHole($row["TeeID"])) printf("par10"); else printf("greensInReg"); ?>)" onFocus="HandleFocus(this)"></td>
	    <td><input disabled class="ScoreInputBox" type="text" name="puttout" value="0"></td>
	</tr>
	
	<tr><td><br></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td></tr>
	</table>
	<div id=secondnine>
	<table>
	<?
	if ( !isNineHole($courseID) )
	{
		printf("<tr><td><b>Hole:</b></td><td class=\"HoleNumberBox\">10</td><td class=\"HoleNumberBox\">11</td><td class=\"HoleNumberBox\">12</td><td class=\"HoleNumberBox\">13</td><td class=\"HoleNumberBox\">14</td><td class=\"HoleNumberBox\">15</td><td class=\"HoleNumberBox\">16</td><td class=\"HoleNumberBox\">17</td><td class=\"HoleNumberBox\">18</td><td class=\"HoleNumberBox\"><b>In</b></td></tr>");
		printf("<tr><td><b>Par:</b></td>
		<td name=\"ParHole10\" class=\"HoleNumberBox\"><div id=\"ParHole10\">%s</div></td>
		<td name=\"ParHole11\" class=\"HoleNumberBox\"><div id=\"ParHole11\">%s</div></td>
		<td name=\"ParHole12\" class=\"HoleNumberBox\"><div id=\"ParHole12\">%s</div></td>
		<td name=\"ParHole13\" class=\"HoleNumberBox\"><div id=\"ParHole13\">%s</div></td>
		<td name=\"ParHole14\" class=\"HoleNumberBox\"><div id=\"ParHole14\">%s</div></td>
		<td name=\"ParHole15\" class=\"HoleNumberBox\"><div id=\"ParHole15\">%s</div></td>
		<td name=\"ParHole16\" class=\"HoleNumberBox\"><div id=\"ParHole16\">%s</div></td>
		<td name=\"ParHole17\" class=\"HoleNumberBox\"><div id=\"ParHole17\">%s</div></td>
		<td name=\"ParHole18\" class=\"HoleNumberBox\"><div id=\"ParHole18\">%s</div></td>
		<td name=\"Back9Total\" class=\"HoleNumberBox\"><div id=\"Back9Total\">%s</div></td></tr>",$row["par10"], $row["par11"], $row["par12"], $row["par13"], $row["par14"], $row["par15"], $row["par16"], $row["par17"], $row["par18"], calcBackPar($row));
	}
	else
	{
		printf("<tr><td><b>Hole:</b></td><td class=\"HoleNumberBox\">10</td><td class=\"HoleNumberBox\">11</td><td class=\"HoleNumberBox\">12</td><td class=\"HoleNumberBox\">13</td><td class=\"HoleNumberBox\">14</td><td class=\"HoleNumberBox\">15</td><td class=\"HoleNumberBox\">16</td><td class=\"HoleNumberBox\">17</td><td class=\"HoleNumberBox\">18</td><td class=\"HoleNumberBox\"><b>In</b></td></tr>");
		printf("<tr><td><b>Par:</b></td>
		<td name=\"ParHole10\" class=\"HoleNumberBox\"><div id=\"ParHole10\">%s</div></td>
		<td name=\"ParHole11\" class=\"HoleNumberBox\"><div id=\"ParHole11\">%s</div></td>
		<td name=\"ParHole12\" class=\"HoleNumberBox\"><div id=\"ParHole12\">%s</div></td>
		<td name=\"ParHole13\" class=\"HoleNumberBox\"><div id=\"ParHole13\">%s</div></td>
		<td name=\"ParHole14\" class=\"HoleNumberBox\"><div id=\"ParHole14\">%s</div></td>
		<td name=\"ParHole15\" class=\"HoleNumberBox\"><div id=\"ParHole15\">%s</div></td>
		<td name=\"ParHole16\" class=\"HoleNumberBox\"><div id=\"ParHole16\">%s</div></td>
		<td name=\"ParHole17\" class=\"HoleNumberBox\"><div id=\"ParHole17\">%s</div></td>
		<td name=\"ParHole18\" class=\"HoleNumberBox\"><div id=\"ParHole18\">%s</div></td>
		<td name=\"Back9Total\" class=\"HoleNumberBox\"><div id=\"Back9Total\">%s</div></td></tr>",$row["par1"], $row["par2"], $row["par3"], $row["par4"], $row["par5"], $row["par6"], $row["par8"], $row["par8"], $row["par9"], calcFrontPar($row));
	}
	?>
		<tr><td><b>Score:</b></td>
		    <td><input class="ScoreInputBox" type="text" name="par10" onKeyUp="HandleKeyUp(this, document.scorecard.par11)" onFocus="HandleFocus(this)"></td>
		    <td><input class="ScoreInputBox" type="text" name="par11" onKeyUp="HandleKeyUp(this, document.scorecard.par12)" onFocus="HandleFocus(this)"></td>
		    <td><input class="ScoreInputBox" type="text" name="par12" onKeyUp="HandleKeyUp(this, document.scorecard.par13)" onFocus="HandleFocus(this)"></td>
		    <td><input class="ScoreInputBox" type="text" name="par13" onKeyUp="HandleKeyUp(this, document.scorecard.par14)" onFocus="HandleFocus(this)"></td>
		    <td><input class="ScoreInputBox" type="text" name="par14" onKeyUp="HandleKeyUp(this, document.scorecard.par15)" onFocus="HandleFocus(this)"></td>
		    <td><input class="ScoreInputBox" type="text" name="par15" onKeyUp="HandleKeyUp(this, document.scorecard.par16)" onFocus="HandleFocus(this)"></td>
		    <td><input class="ScoreInputBox" type="text" name="par16" onKeyUp="HandleKeyUp(this, document.scorecard.par17)" onFocus="HandleFocus(this)"></td>
		    <td><input class="ScoreInputBox" type="text" name="par17" onKeyUp="HandleKeyUp(this, document.scorecard.par18)" onFocus="HandleFocus(this)"></td>
		    <td><input class="ScoreInputBox" type="text" name="par18" onKeyUp="HandleKeyUp(this, document.scorecard.putt10)" onFocus="HandleFocus(this)"></td>
		    <td><input disabled class="ScoreInputBox" type="text" name="inn" value="0"></td>
		</tr>
		
		<tr><td><b>Putts:</b></td>
		    <td><input class="ScoreInputBox" type="text" name="putt10" onKeyUp="HandlePuttKeyUp(this, document.scorecard.putt11)" onFocus="HandleFocus(this)"></td>
		    <td><input class="ScoreInputBox" type="text" name="putt11" onKeyUp="HandlePuttKeyUp(this, document.scorecard.putt12)" onFocus="HandleFocus(this)"></td>
		    <td><input class="ScoreInputBox" type="text" name="putt12" onKeyUp="HandlePuttKeyUp(this, document.scorecard.putt13)" onFocus="HandleFocus(this)"></td>
		    <td><input class="ScoreInputBox" type="text" name="putt13" onKeyUp="HandlePuttKeyUp(this, document.scorecard.putt14)" onFocus="HandleFocus(this)"></td>
		    <td><input class="ScoreInputBox" type="text" name="putt14" onKeyUp="HandlePuttKeyUp(this, document.scorecard.putt15)" onFocus="HandleFocus(this)"></td>
		    <td><input class="ScoreInputBox" type="text" name="putt15" onKeyUp="HandlePuttKeyUp(this, document.scorecard.putt16)" onFocus="HandleFocus(this)"></td>
		    <td><input class="ScoreInputBox" type="text" name="putt16" onKeyUp="HandlePuttKeyUp(this, document.scorecard.putt17)" onFocus="HandleFocus(this)"></td>
		    <td><input class="ScoreInputBox" type="text" name="putt17" onKeyUp="HandlePuttKeyUp(this, document.scorecard.putt18)" onFocus="HandleFocus(this)"></td>
		    <td><input class="ScoreInputBox" type="text" name="putt18" onKeyUp="HandlePuttKeyUp(this, document.scorecard.greensInReg)" onFocus="HandleFocus(this)"></td>
		    <td><input disabled class="ScoreInputBox" type="text" name="puttinn" value="0"></td>
		</tr>
	
		<tr><td><br></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td></tr>
		<tr>
			<td></td>
		 	<td></td>
		 	<td></td>
			<td></td>
			<td></td>
			<td></td>
			<td></td>
			<td COLSPAN=3><b>Total Score:</b></td>
			<td><input disabled class="ScoreInputBox" type="text" name="total" value="0"></td>
		</tr>
	
		<tr>
			<td></td>
		 	<td></td>
		 	<td></td>
			<td></td>
			<td></td>
			<td></td>
			<td></td>
			<td COLSPAN=3><b>Total Putts:</b></td>
			<td><input disabled class="ScoreInputBox" type="text" name="putttotal" value="0"></td>
		</tr>
	</table>
	</div>
	<script language="JavaScript">
	<!--
	//GetTees(document.scorecard.Tees.value, PopulateScorecardParValues);
	//-->
	</script>
	<table>
		<?
	
	printf("<tr><td colspan=\"5\"><b>Num of Greens in Reg:</b>  </td><td colspan=\"6\"><input class=\"ScoreInputBox\" type=\"text\" name=\"greensInReg\" ></td></tr>");
	printf("<tr><td colspan=\"5\"><b>Num of Fairways Hit:</b>  </td><td colspan=\"6\"><input class=\"ScoreInputBox\" type=\"text\" name=\"fairwaysHit\" ></td></tr>");
	printf("<tr><td colspan=\"5\"><b>Num of Penalties:</b>  </td><td colspan=\"6\"><input class=\"ScoreInputBox\" type=\"text\" name=\"penalties\" ></td></tr>");
	printf("</table>");
	printf("<table>");
	printf("<tr><td><b>Comments:</b></td></tr>");
	printf("<tr><td><textarea class=\"ScoreCardCommentBox\" type=\"text\" name=\"commentText\"></textarea></td></tr>");
	printf("</table> ");	
	printf("<br>");
	printf("<table>");
	?>
	<?
	printf("</table>");
	printf("<input type=\"hidden\" value=\"%s\" name=\"CourseID\">", $_GET["CourseID"]);
	printf("<input type=\"hidden\" name=\"Back9HTML\">");
	
	printf("<input type=\"submit\" value=\"Save Score\" name=\"SubmitScore\">");
	printf("<br><br>Holes 1-9 or 10-18 required.");
	printf("</table>");	
	
	printf("</form>");
	?>
<script language="JavaScript">
<!--
//alert(document.scorecard.par10.value);
document.scorecard.Back9HTML.value = document.getElementById("secondnine").innerHTML;
GetTees(document.scorecard.Tees.value, PopulateScorecardParValues);
//alert(document.scorecard.par10.value);
//-->
</script>
<?
}

function DisScore( $scoreID, $postPage )
{
		$scoreSql = "select * from course_tbl, tee_tbl, score_tbl where score_tbl.id = ";
		$scoreSql .= $scoreID;
		$scoreSql .= " and score_tbl.teeid = tee_tbl.id and tee_tbl.courseid = course_tbl.id";
		//printf("%s", $scoreSql);
		$scoreSqlResult = mysql_query($scoreSql) or die("Could not get detailed history of round: " . mysql_error());
		$scoreSqlRow = mysql_fetch_array($scoreSqlResult);

		$coursesSql = "select name, id from course_tbl where userid = ";
		$coursesSql .= $_SESSION['userid'];
		$coursesSqlResult = mysql_query($coursesSql) or die("Could not get a list of courses: " . mysql_error());
?>
		<table class="CourseTable">
		<form action="<?printf("%s", $postPage);?>" name="scorecard" method="POST">
		<td>
		<table class="ScoreCardTable">
		<tr>
		<tr><td><b>Course Name:</b> </td><td><select onChange="GetTeesForCourse(this.value, PopulateEditScorecard)" class="InputBoxWidth" name="courseid">
<?
		while ($coursesRow = mysql_fetch_array($coursesSqlResult))
		{
			if ($scoreSqlRow["courseid"] == $coursesRow["id"])
				printf("<option selected value=\"%s\">%s</option>",$coursesRow["id"],$coursesRow["name"]);
			else
				printf("<option value=\"%s\">%s</option>",$coursesRow["id"],$coursesRow["name"]);
		}
?>
		</select>
		</td></tr> 
<?


	$teeSql = "select * from tee_tbl tt where tt.courseid = ";
	$teeSql .= $scoreSqlRow["courseid"];
	//printf("%s", $teeSql);
	$teeSqlResult = mysql_query($teeSql) or die("Could not get tees: " . mysql_error());
	

	printf("<tr><td><b>Tees:</b> </td><td>");
	printf("<select onchange=\"GetTees(this.value, PopulateScorecardParValues)\" class=\"InputBoxWidth\" name=\"Tees\">");
		while ($teeRow = mysql_fetch_array($teeSqlResult))
		{
			if ($scoreSqlRow["teeid"] == $teeRow["id"])
				printf("<option selected value=\"%s\">%s</option>",$teeRow["id"],$teeRow["name"]);
			else
				printf("<option value=\"%s\">%s</option>",$teeRow["id"],$teeRow["name"]);
		}
	printf("</select>");







		$dbDate = $scoreSqlRow["dateplayed"];
		$formattedDate = substr($dbDate, 5, 2);
		$formattedDate .= "/";
		$formattedDate .= substr($dbDate, 8, 2);
		$formattedDate .= "/";
		$formattedDate .= substr($dbDate, 0, 4);
		printf("<tr><td><b>Date:</b>  </td><td>"); 
		getDateControl(getdate(strtotime($formattedDate)));
		printf("</td></tr>");
?>
		</table> 
		<table class="ScoreCardTable">
		<tr><td><br></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td></tr>
		<tr><td><b>Hole:</b></td><td class="HoleNumberBox">1</td><td class="HoleNumberBox">2</td><td class="HoleNumberBox">3</td><td class="HoleNumberBox">4</td><td class="HoleNumberBox">5</td><td class="HoleNumberBox">6</td><td class="HoleNumberBox">7</td><td class="HoleNumberBox">8</td><td class="HoleNumberBox">9</td><td class="HoleNumberBox">Out</td></tr>
<?
		printf("<input value=\"%s\" type=\"hidden\" name=\"id\">", $scoreID);
		//printf("<tr><td><b>Par:</b></td><td class=\"HoleNumberBox\">%s</td><td class=\"HoleNumberBox\">%s</td><td class=\"HoleNumberBox\">%s</td><td class=\"HoleNumberBox\">%s</td><td class=\"HoleNumberBox\">%s</td><td class=\"HoleNumberBox\">%s</td><td class=\"HoleNumberBox\">%s</td><td class=\"HoleNumberBox\">%s</td><td class=\"HoleNumberBox\">%s</td><td class=\"HoleNumberBox\">%s</td></tr>", $scoreSqlRow["par1"], $scoreSqlRow["par2"], $scoreSqlRow["par3"], $scoreSqlRow["par4"], $scoreSqlRow["par5"], $scoreSqlRow["par6"], $scoreSqlRow["par7"], $scoreSqlRow["par8"], $scoreSqlRow["par9"], calcFrontPar($scoreSqlRow));
		printf("<tr><td><b>Par:</b></td>
		<td name=\"ParHole1\" class=\"HoleNumberBox\"><div id=\"ParHole1\">%s</div></td>
		<td name=\"ParHole2\" class=\"HoleNumberBox\"><div id=\"ParHole2\">%s</div></td>
		<td name=\"ParHole3\" class=\"HoleNumberBox\"><div id=\"ParHole3\">%s</div></td>
		<td name=\"ParHole4\" class=\"HoleNumberBox\"><div id=\"ParHole4\">%s</div></td>
		<td name=\"ParHole5\" class=\"HoleNumberBox\"><div id=\"ParHole5\">%s</div></td>
		<td name=\"ParHole6\" class=\"HoleNumberBox\"><div id=\"ParHole6\">%s</div></td>
		<td name=\"ParHole7\" class=\"HoleNumberBox\"><div id=\"ParHole7\">%s</div></td>
		<td name=\"ParHole8\" class=\"HoleNumberBox\"><div id=\"ParHole8\">%s</div></td>
		<td name=\"ParHole9\" class=\"HoleNumberBox\"><div id=\"ParHole9\">%s</div></td>
		<td name=\"Front9Total\" class=\"HoleNumberBox\"><div id=\"Front9Total\">%s</div></td></tr>", $scoreSqlRow["par1"], $scoreSqlRow["par2"], $scoreSqlRow["par3"], $scoreSqlRow["par4"], $scoreSqlRow["par5"], $scoreSqlRow["par6"], $scoreSqlRow["par7"], $scoreSqlRow["par8"], $scoreSqlRow["par9"], calcFrontPar($scoreSqlRow));
		//printf("<tr><td>Score:</td><td><input value=\"%s\" class=\"ScoreInputBox\" type=\"text\" name=\"par1\"></td><td><input value=\"%s\" class=\"ScoreInputBox\" type=\"text\" name=\"par2\"></td><td><input value=\"%s\" class=\"ScoreInputBox\" type=\"text\" name=\"par3\"></td><td><input value=\"%s\" class=\"ScoreInputBox\" type=\"text\" name=\"par4\"></td><td><input value=\"%s\" class=\"ScoreInputBox\" type=\"text\" name=\"par5\"></td><td><input value=\"%s\" class=\"ScoreInputBox\" type=\"text\" name=\"par6\"></td><td><input value=\"%s\" class=\"ScoreInputBox\" type=\"text\" name=\"par7\"></td><td><input value=\"%s\" class=\"ScoreInputBox\" type=\"text\" name=\"par8\"></td><td><input value=\"%s\" class=\"ScoreInputBox\" type=\"text\" name=\"par9\"></td><td><input disabled value=\"%s\" type=\"text\" class=\"ScoreInputBox\"></td></tr>", $scoreSqlRow["hole1"], $scoreSqlRow["hole2"], $scoreSqlRow["hole3"], $scoreSqlRow["hole4"], $scoreSqlRow["hole5"], $scoreSqlRow["hole6"], $scoreSqlRow["hole7"], $scoreSqlRow["hole8"], $scoreSqlRow["hole9"], calcFront($scoreSqlRow));
		?>
		
		<tr><td><b>Score:</b></td>
		    <td><input value="<?printf("%s", $scoreSqlRow["hole1"]);?>" class="ScoreInputBox" type="text" name="par1" onKeyUp="HandleKeyUp(this, document.scorecard.par2)" onFocus="HandleFocus(this)"></td>
		    <td><input value="<?printf("%s", $scoreSqlRow["hole2"]);?>" class="ScoreInputBox" type="text" name="par2" onKeyUp="HandleKeyUp(this, document.scorecard.par3)" onFocus="HandleFocus(this)"></td>
		    <td><input value="<?printf("%s", $scoreSqlRow["hole3"]);?>" class="ScoreInputBox" type="text" name="par3" onKeyUp="HandleKeyUp(this, document.scorecard.par4)" onFocus="HandleFocus(this)"></td>
		    <td><input value="<?printf("%s", $scoreSqlRow["hole4"]);?>" class="ScoreInputBox" type="text" name="par4" onKeyUp="HandleKeyUp(this, document.scorecard.par5)" onFocus="HandleFocus(this)"></td>
		    <td><input value="<?printf("%s", $scoreSqlRow["hole5"]);?>" class="ScoreInputBox" type="text" name="par5" onKeyUp="HandleKeyUp(this, document.scorecard.par6)" onFocus="HandleFocus(this)"></td>
		    <td><input value="<?printf("%s", $scoreSqlRow["hole6"]);?>" class="ScoreInputBox" type="text" name="par6" onKeyUp="HandleKeyUp(this, document.scorecard.par7)" onFocus="HandleFocus(this)"></td>
		    <td><input value="<?printf("%s", $scoreSqlRow["hole7"]);?>" class="ScoreInputBox" type="text" name="par7" onKeyUp="HandleKeyUp(this, document.scorecard.par8)" onFocus="HandleFocus(this)"></td>
		    <td><input value="<?printf("%s", $scoreSqlRow["hole8"]);?>" class="ScoreInputBox" type="text" name="par8" onKeyUp="HandleKeyUp(this, document.scorecard.par9)" onFocus="HandleFocus(this)"></td>
		    <td><input value="<?printf("%s", $scoreSqlRow["hole9"]);?>" class="ScoreInputBox" type="text" name="par9" onKeyUp="HandleKeyUp(this, document.scorecard.putt1)" onFocus="HandleFocus(this)"></td>
		    <?
			    $total = $scoreSqlRow["hole1"] + $scoreSqlRow["hole2"] + $scoreSqlRow["hole3"] + $scoreSqlRow["hole4"] + $scoreSqlRow["hole5"] + $scoreSqlRow["hole6"] + $scoreSqlRow["hole7"] + $scoreSqlRow["hole8"] + $scoreSqlRow["hole9"];
			    if ( !$total )
			    	$total ="";
		    	    printf("<td><input disabled class=\"ScoreInputBox\" type=\"text\" name=\"out\" value=\"%s\"></td>", $total);
		    ?>
		</tr>
		
		<tr><td><b>Putts:</b></td>
		    <td><input value="<?printf("%s", $scoreSqlRow["putt1"]);?>" class="ScoreInputBox" type="text" name="putt1" onKeyUp="HandlePuttKeyUp(this, document.scorecard.putt2)" onFocus="HandleFocus(this)"></td>
		    <td><input value="<?printf("%s", $scoreSqlRow["putt2"]);?>" class="ScoreInputBox" type="text" name="putt2" onKeyUp="HandlePuttKeyUp(this, document.scorecard.putt3)" onFocus="HandleFocus(this)"></td>
		    <td><input value="<?printf("%s", $scoreSqlRow["putt3"]);?>" class="ScoreInputBox" type="text" name="putt3" onKeyUp="HandlePuttKeyUp(this, document.scorecard.putt4)" onFocus="HandleFocus(this)"></td>
		    <td><input value="<?printf("%s", $scoreSqlRow["putt4"]);?>" class="ScoreInputBox" type="text" name="putt4" onKeyUp="HandlePuttKeyUp(this, document.scorecard.putt5)" onFocus="HandleFocus(this)"></td>
		    <td><input value="<?printf("%s", $scoreSqlRow["putt5"]);?>" class="ScoreInputBox" type="text" name="putt5" onKeyUp="HandlePuttKeyUp(this, document.scorecard.putt6)" onFocus="HandleFocus(this)"></td>
		    <td><input value="<?printf("%s", $scoreSqlRow["putt6"]);?>" class="ScoreInputBox" type="text" name="putt6" onKeyUp="HandlePuttKeyUp(this, document.scorecard.putt7)" onFocus="HandleFocus(this)"></td>
		    <td><input value="<?printf("%s", $scoreSqlRow["putt7"]);?>" class="ScoreInputBox" type="text" name="putt7" onKeyUp="HandlePuttKeyUp(this, document.scorecard.putt8)" onFocus="HandleFocus(this)"></td>
		    <td><input value="<?printf("%s", $scoreSqlRow["putt8"]);?>" class="ScoreInputBox" type="text" name="putt8" onKeyUp="HandlePuttKeyUp(this, document.scorecard.putt9)" onFocus="HandleFocus(this)"></td>
		    <td><input value="<?printf("%s", $scoreSqlRow["putt9"]);?>" class="ScoreInputBox" type="text" name="putt9" onKeyUp="HandlePuttKeyUp(this, document.scorecard.<?if (!isNineHole($scoreSqlRow["courseid"])) printf("par10"); else printf("UpdateScore"); ?>)" onFocus="HandleFocus(this)"></td>
		    <?
			    $total = $scoreSqlRow["putt1"] + $scoreSqlRow["putt2"] + $scoreSqlRow["putt3"] + $scoreSqlRow["putt4"] + $scoreSqlRow["putt5"] + $scoreSqlRow["putt6"] + $scoreSqlRow["putt7"] + $scoreSqlRow["putt8"] + $scoreSqlRow["putt9"];
			    if ( !$total )
			    	$total ="";
		    	    printf("<td><input disabled class=\"ScoreInputBox\" type=\"text\" name=\"puttout\" value=\"%s\"></td>", $total);
		    ?>
		</tr>

		<tr><td><br></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td></tr>
		</table>
		<div id="secondnine">
		<table class="ScoreCardTable">
		<?
		if (!isNineHole($scoreSqlRow["teeid"]))
		{
?>
			<tr><td><b>Hole:</b></td><td class="HoleNumberBox">10</td><td class="HoleNumberBox">11</td><td class="HoleNumberBox">12</td><td class="HoleNumberBox">13</td><td class="HoleNumberBox">14</td><td class="HoleNumberBox">15</td><td class="HoleNumberBox">16</td><td class="HoleNumberBox">17</td><td class="HoleNumberBox">18</td><td class="HoleNumberBox">In</td></tr>
<?
			//printf("<tr><td><b>Par:</b></td><td class=\"HoleNumberBox\">%s</td><td class=\"HoleNumberBox\">%s</td><td class=\"HoleNumberBox\">%s</td><td class=\"HoleNumberBox\">%s</td><td class=\"HoleNumberBox\">%s</td><td class=\"HoleNumberBox\">%s</td><td class=\"HoleNumberBox\">%s</td><td class=\"HoleNumberBox\">%s</td><td class=\"HoleNumberBox\">%s</td><td class=\"HoleNumberBox\">%s</td></tr>", $scoreSqlRow["par10"], $scoreSqlRow["par11"], $scoreSqlRow["par12"], $scoreSqlRow["par13"], $scoreSqlRow["par14"], $scoreSqlRow["par15"], $scoreSqlRow["par16"], $scoreSqlRow["par17"], $scoreSqlRow["par18"], calcBackPar($scoreSqlRow));
			printf("<tr><td><b>Par:</b></td>
			<td name=\"ParHole10\" class=\"HoleNumberBox\"><div id=\"ParHole10\">%s</div></td>
			<td name=\"ParHole11\" class=\"HoleNumberBox\"><div id=\"ParHole11\">%s</div></td>
			<td name=\"ParHole12\" class=\"HoleNumberBox\"><div id=\"ParHole12\">%s</div></td>
			<td name=\"ParHole13\" class=\"HoleNumberBox\"><div id=\"ParHole13\">%s</div></td>
			<td name=\"ParHole14\" class=\"HoleNumberBox\"><div id=\"ParHole14\">%s</div></td>
			<td name=\"ParHole15\" class=\"HoleNumberBox\"><div id=\"ParHole15\">%s</div></td>
			<td name=\"ParHole16\" class=\"HoleNumberBox\"><div id=\"ParHole16\">%s</div></td>
			<td name=\"ParHole17\" class=\"HoleNumberBox\"><div id=\"ParHole17\">%s</div></td>
			<td name=\"ParHole18\" class=\"HoleNumberBox\"><div id=\"ParHole18\">%s</div></td>
			<td name=\"Back9Total\" class=\"HoleNumberBox\"><div id=\"Back9Total\">%s</div></td></tr>",$scoreSqlRow["par10"], $scoreSqlRow["par11"], $scoreSqlRow["par12"], $scoreSqlRow["par13"], $scoreSqlRow["par14"], $scoreSqlRow["par15"], $scoreSqlRow["par16"], $scoreSqlRow["par17"], $scoreSqlRow["par18"], calcBackPar($scoreSqlRow));
			//printf("<tr><td>Score:</td><td><input value=\"%s\" class=\"ScoreInputBox\" type=\"text\" name=\"par10\"></td><td><input value=\"%s\" class=\"ScoreInputBox\" type=\"text\" name=\"par11\"></td><td><input value=\"%s\" class=\"ScoreInputBox\" type=\"text\" name=\"par12\"></td><td><input value=\"%s\" class=\"ScoreInputBox\" type=\"text\" name=\"par13\"></td><td><input value=\"%s\" class=\"ScoreInputBox\" type=\"text\" name=\"par14\"></td><td><input value=\"%s\" class=\"ScoreInputBox\" type=\"text\" name=\"par15\"></td><td><input value=\"%s\" class=\"ScoreInputBox\" type=\"text\" name=\"par16\"></td><td><input value=\"%s\" class=\"ScoreInputBox\" type=\"text\" name=\"par17\"></td><td><input value=\"%s\" class=\"ScoreInputBox\" type=\"text\" name=\"par18\"></td><td><input disabled value=\"%s\" type=\"text\" class=\"ScoreInputBox\"></td></tr>", $scoreSqlRow["hole10"], $scoreSqlRow["hole11"], $scoreSqlRow["hole12"], $scoreSqlRow["hole13"], $scoreSqlRow["hole14"], $scoreSqlRow["hole15"], $scoreSqlRow["hole16"], $scoreSqlRow["hole17"], $scoreSqlRow["hole18"], calcBack($scoreSqlRow));
			?>
			
			<tr><td><b>Score:</b></td>
			    <td><input value="<?printf("%s", $scoreSqlRow["hole10"]);?>" class="ScoreInputBox" type="text" name="par10" onKeyUp="HandleKeyUp(this, document.scorecard.par11)" onFocus="HandleFocus(this)"></td>
			    <td><input value="<?printf("%s", $scoreSqlRow["hole11"]);?>" class="ScoreInputBox" type="text" name="par11" onKeyUp="HandleKeyUp(this, document.scorecard.par12)" onFocus="HandleFocus(this)"></td>
			    <td><input value="<?printf("%s", $scoreSqlRow["hole12"]);?>" class="ScoreInputBox" type="text" name="par12" onKeyUp="HandleKeyUp(this, document.scorecard.par13)" onFocus="HandleFocus(this)"></td>
			    <td><input value="<?printf("%s", $scoreSqlRow["hole13"]);?>" class="ScoreInputBox" type="text" name="par13" onKeyUp="HandleKeyUp(this, document.scorecard.par14)" onFocus="HandleFocus(this)"></td>
			    <td><input value="<?printf("%s", $scoreSqlRow["hole14"]);?>" class="ScoreInputBox" type="text" name="par14" onKeyUp="HandleKeyUp(this, document.scorecard.par15)" onFocus="HandleFocus(this)"></td>
			    <td><input value="<?printf("%s", $scoreSqlRow["hole15"]);?>" class="ScoreInputBox" type="text" name="par15" onKeyUp="HandleKeyUp(this, document.scorecard.par16)" onFocus="HandleFocus(this)"></td>
			    <td><input value="<?printf("%s", $scoreSqlRow["hole16"]);?>" class="ScoreInputBox" type="text" name="par16" onKeyUp="HandleKeyUp(this, document.scorecard.par17)" onFocus="HandleFocus(this)"></td>
			    <td><input value="<?printf("%s", $scoreSqlRow["hole17"]);?>" class="ScoreInputBox" type="text" name="par17" onKeyUp="HandleKeyUp(this, document.scorecard.par18)" onFocus="HandleFocus(this)"></td>
			    <td><input value="<?printf("%s", $scoreSqlRow["hole18"]);?>" class="ScoreInputBox" type="text" name="par18" onKeyUp="HandleKeyUp(this, document.scorecard.putt10)" onFocus="HandleFocus(this)"></td>
			    <?
				    $total = $scoreSqlRow["hole10"] + $scoreSqlRow["hole11"] + $scoreSqlRow["hole12"] + $scoreSqlRow["hole13"] + $scoreSqlRow["hole14"] + $scoreSqlRow["hole15"] + $scoreSqlRow["hole16"] + $scoreSqlRow["hole17"] + $scoreSqlRow["hole18"];
				    if ( !$total )
				    	$total ="";
			    	    printf("<td><input disabled class=\"ScoreInputBox\" type=\"text\" name=\"inn\" value=\"%s\"></td>", $total);
			    ?>
			</tr>
			
			<tr><td><b>Putts:</b></td>
			    <td><input value="<?printf("%s", $scoreSqlRow["putt10"]);?>" class="ScoreInputBox" type="text" name="putt10" onKeyUp="HandlePuttKeyUp(this, document.scorecard.putt11)" onFocus="HandleFocus(this)"></td>
			    <td><input value="<?printf("%s", $scoreSqlRow["putt11"]);?>" class="ScoreInputBox" type="text" name="putt11" onKeyUp="HandlePuttKeyUp(this, document.scorecard.putt12)" onFocus="HandleFocus(this)"></td>
			    <td><input value="<?printf("%s", $scoreSqlRow["putt12"]);?>" class="ScoreInputBox" type="text" name="putt12" onKeyUp="HandlePuttKeyUp(this, document.scorecard.putt13)" onFocus="HandleFocus(this)"></td>
			    <td><input value="<?printf("%s", $scoreSqlRow["putt13"]);?>" class="ScoreInputBox" type="text" name="putt13" onKeyUp="HandlePuttKeyUp(this, document.scorecard.putt14)" onFocus="HandleFocus(this)"></td>
			    <td><input value="<?printf("%s", $scoreSqlRow["putt14"]);?>" class="ScoreInputBox" type="text" name="putt14" onKeyUp="HandlePuttKeyUp(this, document.scorecard.putt15)" onFocus="HandleFocus(this)"></td>
			    <td><input value="<?printf("%s", $scoreSqlRow["putt15"]);?>" class="ScoreInputBox" type="text" name="putt15" onKeyUp="HandlePuttKeyUp(this, document.scorecard.putt16)" onFocus="HandleFocus(this)"></td>
			    <td><input value="<?printf("%s", $scoreSqlRow["putt16"]);?>" class="ScoreInputBox" type="text" name="putt16" onKeyUp="HandlePuttKeyUp(this, document.scorecard.putt17)" onFocus="HandleFocus(this)"></td>
			    <td><input value="<?printf("%s", $scoreSqlRow["putt17"]);?>" class="ScoreInputBox" type="text" name="putt17" onKeyUp="HandlePuttKeyUp(this, document.scorecard.putt18)" onFocus="HandleFocus(this)"></td>
			    <td><input value="<?printf("%s", $scoreSqlRow["putt18"]);?>" class="ScoreInputBox" type="text" name="putt18" onKeyUp="HandlePuttKeyUp(this, document.scorecard.UpdateScore)" onFocus="HandleFocus(this)"></td>
			    <?
				    $total = $scoreSqlRow["putt10"] + $scoreSqlRow["putt11"] + $scoreSqlRow["putt12"] + $scoreSqlRow["putt13"] + $scoreSqlRow["putt14"] + $scoreSqlRow["putt15"] + $scoreSqlRow["putt16"] + $scoreSqlRow["putt17"] + $scoreSqlRow["putt18"];
				    if ( !$total )
				    	$total ="";
			    	    printf("<td><input disabled class=\"ScoreInputBox\" type=\"text\" name=\"puttinn\" value=\"%s\"></td>", $total);
			    ?>
			</tr>

			<tr><td><br></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td></tr>
			<tr>
				<td></td>
			 	<td></td>
			 	<td></td>
				<td></td>
				<td></td>
				<td></td>
				<td></td>
				<td COLSPAN=3><b>Total Score:</b></td>
				<td><input disabled class="ScoreInputBox" type="text" name="total" value="<?printf("%s", $scoreSqlRow["hole1"] + $scoreSqlRow["hole2"] + $scoreSqlRow["hole3"] + $scoreSqlRow["hole4"] + $scoreSqlRow["hole5"] + $scoreSqlRow["hole6"] + $scoreSqlRow["hole7"] + $scoreSqlRow["hole8"] + $scoreSqlRow["hole9"] + $scoreSqlRow["hole10"] + $scoreSqlRow["hole11"] + $scoreSqlRow["hole12"] + $scoreSqlRow["hole13"] + $scoreSqlRow["hole14"] + $scoreSqlRow["hole15"] + $scoreSqlRow["hole16"] + $scoreSqlRow["hole17"] + $scoreSqlRow["hole18"]);?>"></td>
			</tr>
			<tr>
				<td></td>
			 	<td></td>
			 	<td></td>
				<td></td>
				<td></td>
				<td></td>
				<td></td>
				<td COLSPAN=3><b>Total Putts:</b></td>
				<td><input disabled class="ScoreInputBox" type="text" name="putttotal" value="<?printf("%s", $scoreSqlRow["putt1"] + $scoreSqlRow["putt2"] + $scoreSqlRow["putt3"] + $scoreSqlRow["putt4"] + $scoreSqlRow["putt5"] + $scoreSqlRow["putt6"] + $scoreSqlRow["putt7"] + $scoreSqlRow["putt8"] + $scoreSqlRow["putt9"] + $scoreSqlRow["putt10"] + $scoreSqlRow["putt11"] + $scoreSqlRow["putt12"] + $scoreSqlRow["putt13"] + $scoreSqlRow["putt14"] + $scoreSqlRow["putt15"] + $scoreSqlRow["putt16"] + $scoreSqlRow["putt17"] + $scoreSqlRow["putt18"]);?>"></td>
			</tr>
		</table>
		</div>
		<script language="JavaScript">
		<!--
		//GetTees(document.scorecard.Tees.value, PopulateScorecardParValues);
		//-->
		</script>
		<table class="ScoreCardTable">
			<?
		}
		else
		{
		?>
			<tr><td><b>Hole:</b></td><td class="HoleNumberBox">10</td><td class="HoleNumberBox">11</td><td class="HoleNumberBox">12</td><td class="HoleNumberBox">13</td><td class="HoleNumberBox">14</td><td class="HoleNumberBox">15</td><td class="HoleNumberBox">16</td><td class="HoleNumberBox">17</td><td class="HoleNumberBox">18</td><td class="HoleNumberBox">In</td></tr>
<?
			//printf("<tr><td><b>Par:</b></td><td class=\"HoleNumberBox\">%s</td><td class=\"HoleNumberBox\">%s</td><td class=\"HoleNumberBox\">%s</td><td class=\"HoleNumberBox\">%s</td><td class=\"HoleNumberBox\">%s</td><td class=\"HoleNumberBox\">%s</td><td class=\"HoleNumberBox\">%s</td><td class=\"HoleNumberBox\">%s</td><td class=\"HoleNumberBox\">%s</td><td class=\"HoleNumberBox\">%s</td></tr>", $scoreSqlRow["par10"], $scoreSqlRow["par11"], $scoreSqlRow["par12"], $scoreSqlRow["par13"], $scoreSqlRow["par14"], $scoreSqlRow["par15"], $scoreSqlRow["par16"], $scoreSqlRow["par17"], $scoreSqlRow["par18"], calcBackPar($scoreSqlRow));
			printf("<tr><td><b>Par:</b></td>
			<td name=\"ParHole10\" class=\"HoleNumberBox\"><div id=\"ParHole10\">%s</div></td>
			<td name=\"ParHole11\" class=\"HoleNumberBox\"><div id=\"ParHole11\">%s</div></td>
			<td name=\"ParHole12\" class=\"HoleNumberBox\"><div id=\"ParHole12\">%s</div></td>
			<td name=\"ParHole13\" class=\"HoleNumberBox\"><div id=\"ParHole13\">%s</div></td>
			<td name=\"ParHole14\" class=\"HoleNumberBox\"><div id=\"ParHole14\">%s</div></td>
			<td name=\"ParHole15\" class=\"HoleNumberBox\"><div id=\"ParHole15\">%s</div></td>
			<td name=\"ParHole16\" class=\"HoleNumberBox\"><div id=\"ParHole16\">%s</div></td>
			<td name=\"ParHole17\" class=\"HoleNumberBox\"><div id=\"ParHole17\">%s</div></td>
			<td name=\"ParHole18\" class=\"HoleNumberBox\"><div id=\"ParHole18\">%s</div></td>
			<td name=\"Back9Total\" class=\"HoleNumberBox\"><div id=\"Back9Total\">%s</div></td></tr>",$scoreSqlRow["par10"], $scoreSqlRow["par11"], $scoreSqlRow["par12"], $scoreSqlRow["par13"], $scoreSqlRow["par14"], $scoreSqlRow["par15"], $scoreSqlRow["par16"], $scoreSqlRow["par17"], $scoreSqlRow["par18"], calcBackPar($scoreSqlRow));
			//printf("<tr><td>Score:</td><td><input value=\"%s\" class=\"ScoreInputBox\" type=\"text\" name=\"par10\"></td><td><input value=\"%s\" class=\"ScoreInputBox\" type=\"text\" name=\"par11\"></td><td><input value=\"%s\" class=\"ScoreInputBox\" type=\"text\" name=\"par12\"></td><td><input value=\"%s\" class=\"ScoreInputBox\" type=\"text\" name=\"par13\"></td><td><input value=\"%s\" class=\"ScoreInputBox\" type=\"text\" name=\"par14\"></td><td><input value=\"%s\" class=\"ScoreInputBox\" type=\"text\" name=\"par15\"></td><td><input value=\"%s\" class=\"ScoreInputBox\" type=\"text\" name=\"par16\"></td><td><input value=\"%s\" class=\"ScoreInputBox\" type=\"text\" name=\"par17\"></td><td><input value=\"%s\" class=\"ScoreInputBox\" type=\"text\" name=\"par18\"></td><td><input disabled value=\"%s\" type=\"text\" class=\"ScoreInputBox\"></td></tr>", $scoreSqlRow["hole10"], $scoreSqlRow["hole11"], $scoreSqlRow["hole12"], $scoreSqlRow["hole13"], $scoreSqlRow["hole14"], $scoreSqlRow["hole15"], $scoreSqlRow["hole16"], $scoreSqlRow["hole17"], $scoreSqlRow["hole18"], calcBack($scoreSqlRow));
			?>
			
			<tr><td><b>Score:</b></td>
			    <td><input class="ScoreInputBox" type="text" name="par10" onKeyUp="HandleKeyUp(this, document.scorecard.par11)" onFocus="HandleFocus(this)"></td>
			    <td><input class="ScoreInputBox" type="text" name="par11" onKeyUp="HandleKeyUp(this, document.scorecard.par12)" onFocus="HandleFocus(this)"></td>
			    <td><input class="ScoreInputBox" type="text" name="par12" onKeyUp="HandleKeyUp(this, document.scorecard.par13)" onFocus="HandleFocus(this)"></td>
			    <td><input class="ScoreInputBox" type="text" name="par13" onKeyUp="HandleKeyUp(this, document.scorecard.par14)" onFocus="HandleFocus(this)"></td>
			    <td><input class="ScoreInputBox" type="text" name="par14" onKeyUp="HandleKeyUp(this, document.scorecard.par15)" onFocus="HandleFocus(this)"></td>
			    <td><input class="ScoreInputBox" type="text" name="par15" onKeyUp="HandleKeyUp(this, document.scorecard.par16)" onFocus="HandleFocus(this)"></td>
			    <td><input class="ScoreInputBox" type="text" name="par16" onKeyUp="HandleKeyUp(this, document.scorecard.par17)" onFocus="HandleFocus(this)"></td>
			    <td><input class="ScoreInputBox" type="text" name="par17" onKeyUp="HandleKeyUp(this, document.scorecard.par18)" onFocus="HandleFocus(this)"></td>
			    <td><input class="ScoreInputBox" type="text" name="par18" onKeyUp="HandleKeyUp(this, document.scorecard.putt10)" onFocus="HandleFocus(this)"></td>
			    <td><input disabled class="ScoreInputBox" type="text" name="inn"></td>
			</tr>
			
			<tr><td><b>Putts:</b></td>
			    <td><input class="ScoreInputBox" type="text" name="putt10" onKeyUp="HandlePuttKeyUp(this, document.scorecard.putt11)" onFocus="HandleFocus(this)"></td>
			    <td><input class="ScoreInputBox" type="text" name="putt11" onKeyUp="HandlePuttKeyUp(this, document.scorecard.putt12)" onFocus="HandleFocus(this)"></td>
			    <td><input class="ScoreInputBox" type="text" name="putt12" onKeyUp="HandlePuttKeyUp(this, document.scorecard.putt13)" onFocus="HandleFocus(this)"></td>
			    <td><input class="ScoreInputBox" type="text" name="putt13" onKeyUp="HandlePuttKeyUp(this, document.scorecard.putt14)" onFocus="HandleFocus(this)"></td>
			    <td><input class="ScoreInputBox" type="text" name="putt14" onKeyUp="HandlePuttKeyUp(this, document.scorecard.putt15)" onFocus="HandleFocus(this)"></td>
			    <td><input class="ScoreInputBox" type="text" name="putt15" onKeyUp="HandlePuttKeyUp(this, document.scorecard.putt16)" onFocus="HandleFocus(this)"></td>
			    <td><input class="ScoreInputBox" type="text" name="putt16" onKeyUp="HandlePuttKeyUp(this, document.scorecard.putt17)" onFocus="HandleFocus(this)"></td>
			    <td><input class="ScoreInputBox" type="text" name="putt17" onKeyUp="HandlePuttKeyUp(this, document.scorecard.putt18)" onFocus="HandleFocus(this)"></td>
			    <td><input class="ScoreInputBox" type="text" name="putt18" onKeyUp="HandlePuttKeyUp(this, document.scorecard.UpdateScore)" onFocus="HandleFocus(this)"></td>
			    <td><input disabled class="ScoreInputBox" type="text" name="puttinn"></td>
			    
			</tr>

			<tr><td><br></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td></tr>
			<tr>
				<td></td>
			 	<td></td>
			 	<td></td>
				<td></td>
				<td></td>
				<td></td>
				<td></td>
				<td COLSPAN=3><b>Total Score:</b></td>
				<td><input disabled class="ScoreInputBox" type="text" name="total" value="<?printf("%s", $scoreSqlRow["hole1"] + $scoreSqlRow["hole2"] + $scoreSqlRow["hole3"] + $scoreSqlRow["hole4"] + $scoreSqlRow["hole5"] + $scoreSqlRow["hole6"] + $scoreSqlRow["hole7"] + $scoreSqlRow["hole8"] + $scoreSqlRow["hole9"]);?>"></td>
			</tr>
			<tr>
				<td></td>
			 	<td></td>
			 	<td></td>
				<td></td>
				<td></td>
				<td></td>
				<td></td>
				<td COLSPAN=3><b>Total Putts:</b></td>
				<td><input disabled class="ScoreInputBox" type="text" name="putttotal" value="<?printf("%s", $scoreSqlRow["putt1"] + $scoreSqlRow["putt2"] + $scoreSqlRow["putt3"] + $scoreSqlRow["putt4"] + $scoreSqlRow["putt5"] + $scoreSqlRow["putt6"] + $scoreSqlRow["putt7"] + $scoreSqlRow["putt8"] + $scoreSqlRow["putt9"]);?>"></td>
			</tr>
		</table>
		</div>
		<script language="JavaScript">
		<!--
		//GetTees(document.scorecard.Tees.value, PopulateScorecardParValues);
		//-->
		</script>
		<table class="ScoreCardTable">
			<?
		}
		
		
		
		
		
		
		
		
		
		
		
		printf("<tr><td colspan=\"5\"><b>Num of Greens in Reg:</b>  </td><td colspan=\"6\"><input class=\"ScoreInputBox\" type=\"text\" name=\"greensInReg\" value=\"%s\"></td></tr>", $scoreSqlRow["greens"]);
		printf("<tr><td colspan=\"5\"><b>Num of Fairways Hit:</b>  </td><td colspan=\"6\"><input class=\"ScoreInputBox\" type=\"text\" name=\"fairwaysHit\" value=\"%s\"></td></tr>", $scoreSqlRow["fairways"]);
		printf("<tr><td colspan=\"5\"><b>Num of Penalties:</b>  </td><td colspan=\"6\"><input class=\"ScoreInputBox\" type=\"text\" name=\"penalties\" value=\"%s\"></td></tr>", $scoreSqlRow["penalties"]);
?>
		</table>
<?
		printf("<table class=\"ScoreCardTable\">");
		printf("<tr><td><b>Comments:</b></td></tr>");
		printf("<tr><td><textarea class=\"ScoreCardCommentBox\" type=\"text\" name=\"commentText\">%s</textarea></td></tr>", $scoreSqlRow["comment"]);
		printf("<input type=\"hidden\" name=\"Back9HTML\">");
		printf("</table> ");	
?>

<script language="JavaScript">
<!--
document.scorecard.Back9HTML.value = document.getElementById("secondnine").innerHTML;
GetTees(document.scorecard.Tees.value, PopulateScorecardParValues);
//-->
</script>


		<br>
		<table class="ScoreCardTable">
		<input type="submit" value="Update Score" name="UpdateScore">
		<input type="submit" value="Delete Score" name="DeleteScore" onClick="javascript:return confirm('Are you sure you want to delete?')">
		<br><br>Holes 1-9 or 10-18 required.
		</table>
		<td>
		</form>
		</table>
<?	
}

function DisCourseEntryForm($postPage)
{
?>
		<table class="CourseTable">
		<form action="<?printf("%s", $postPage);?>" method="POST" name="scorecard">
		<td>
		<table>
		<tr>
		<tr><td><b>Course Name:</b> </td><td><input class="InputBoxWidth" type="text" name="CourseName"><span class="RequiredFieldIndicator">&nbsp*</span></td></tr> 
		<tr><td><b>City:</b>  </td><td><input class="InputBoxWidth" type="text" name="City"><span class="RequiredFieldIndicator">&nbsp*</span></td></tr> 
		<tr><td><b>State/Province:</b>  </td><td><input class="InputBoxWidth" type="text" name="State"><span class="RequiredFieldIndicator">&nbsp*</span></td></tr>
		<tr><td><b>Tee Name:</b>  </td><td><input class="InputBoxWidth" type="text" name="Tees"><span class="RequiredFieldIndicator">&nbsp*</span></td></tr> 
		<tr><td><b>Course Rating:</b>  </td><td><input class="InputBoxWidth" type="text" name="CourseRating"><span class="RequiredFieldIndicator">&nbsp*</span></td></tr> 
		<tr><td><b>Slope Rating:</b>  </td><td><input class="InputBoxWidth" type="text" name="SlopeRating"><span class="RequiredFieldIndicator">&nbsp*</span></td></tr> 
		</table> 
		<table class="ScoreCardTable">
		<tr><td><br></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td></tr>
		<tr><td><b>Hole:</b></td><td class="HoleNumberBox">1</td><td class="HoleNumberBox">2</td><td class="HoleNumberBox">3</td><td class="HoleNumberBox">4</td><td class="HoleNumberBox">5</td><td class="HoleNumberBox">6</td><td class="HoleNumberBox">7</td><td class="HoleNumberBox">8</td><td class="HoleNumberBox">9</td></tr>
		
		<tr><td><b>Par:</b></td>
		    <td><input class="ScoreInputBox" type="text" name="par1" onKeyUp="HandleCourseKeyUp(this, document.scorecard.StoreNewCourse)" onFocus="HandleFocus(this)"></td>
		    <td><input class="ScoreInputBox" type="text" name="par2" onKeyUp="HandleCourseKeyUp(this, document.scorecard.StoreNewCourse)" onFocus="HandleFocus(this)"></td>
		    <td><input class="ScoreInputBox" type="text" name="par3" onKeyUp="HandleCourseKeyUp(this, document.scorecard.StoreNewCourse)" onFocus="HandleFocus(this)"></td>
		    <td><input class="ScoreInputBox" type="text" name="par4" onKeyUp="HandleCourseKeyUp(this, document.scorecard.StoreNewCourse)" onFocus="HandleFocus(this)"></td>
		    <td><input class="ScoreInputBox" type="text" name="par5" onKeyUp="HandleCourseKeyUp(this, document.scorecard.StoreNewCourse)" onFocus="HandleFocus(this)"></td>
		    <td><input class="ScoreInputBox" type="text" name="par6" onKeyUp="HandleCourseKeyUp(this, document.scorecard.StoreNewCourse)" onFocus="HandleFocus(this)"></td>
		    <td><input class="ScoreInputBox" type="text" name="par7" onKeyUp="HandleCourseKeyUp(this, document.scorecard.StoreNewCourse)" onFocus="HandleFocus(this)"></td>
		    <td><input class="ScoreInputBox" type="text" name="par8" onKeyUp="HandleCourseKeyUp(this, document.scorecard.StoreNewCourse)" onFocus="HandleFocus(this)"></td>
		    <td><input class="ScoreInputBox" type="text" name="par9" onKeyUp="HandleCourseKeyUp(this, document.scorecard.StoreNewCourse)" onFocus="HandleFocus(this)"></td>
		    		    <td><input disabled class="ScoreInputBox" type="text" name="out" value="0"></td>
		</tr>
		<tr><td><br></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td></tr>
		
		<tr><td><b>Hole:</b></td><td class="HoleNumberBox">10</td><td class="HoleNumberBox">11</td><td class="HoleNumberBox">12</td><td class="HoleNumberBox">13</td><td class="HoleNumberBox">14</td><td class="HoleNumberBox">15</td><td class="HoleNumberBox">16</td><td class="HoleNumberBox">17</td><td class="HoleNumberBox">18</td></tr>
		<tr><td><b>Par:</b></td>
			    <td><input class="ScoreInputBox" type="text" name="par10" onKeyUp="HandleCourseKeyUp(this, document.scorecard.StoreNewCourse)" onFocus="HandleFocus(this)"></td>
			    <td><input class="ScoreInputBox" type="text" name="par11" onKeyUp="HandleCourseKeyUp(this, document.scorecard.StoreNewCourse)" onFocus="HandleFocus(this)"></td>
			    <td><input class="ScoreInputBox" type="text" name="par12" onKeyUp="HandleCourseKeyUp(this, document.scorecard.StoreNewCourse)" onFocus="HandleFocus(this)"></td>
			    <td><input class="ScoreInputBox" type="text" name="par13" onKeyUp="HandleCourseKeyUp(this, document.scorecard.StoreNewCourse)" onFocus="HandleFocus(this)"></td>
			    <td><input class="ScoreInputBox" type="text" name="par14" onKeyUp="HandleCourseKeyUp(this, document.scorecard.StoreNewCourse)" onFocus="HandleFocus(this)"></td>
			    <td><input class="ScoreInputBox" type="text" name="par15" onKeyUp="HandleCourseKeyUp(this, document.scorecard.StoreNewCourse)" onFocus="HandleFocus(this)"></td>
			    <td><input class="ScoreInputBox" type="text" name="par16" onKeyUp="HandleCourseKeyUp(this, document.scorecard.StoreNewCourse)" onFocus="HandleFocus(this)"></td>
			    <td><input class="ScoreInputBox" type="text" name="par17" onKeyUp="HandleCourseKeyUp(this, document.scorecard.StoreNewCourse)" onFocus="HandleFocus(this)"></td>
			    <td><input class="ScoreInputBox" type="text" name="par18" onKeyUp="HandleCourseKeyUp(this, document.scorecard.StoreNewCourse)" onFocus="HandleFocus(this)"></td>
			    <td><input disabled class="ScoreInputBox" type="text" name="inn" value="0"></td>
			</tr>
			<tr><td><br></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td></tr>
			<tr>
				<td></td>
			 	<td></td>
			 	<td></td>
				<td></td>
				<td></td>
				<td></td>
				<td></td>
				<td></td>
				<td COLSPAN=2><b>Total:</b></td>
				<td><input disabled class="ScoreInputBox" type="text" name="total" value="0"></td>
			</tr>
		</table>
		<br>
		<table>
		<input type="submit" value="Save Course" name="StoreNewCourse">
		<br><br><span class="RequiredFieldIndicator">&nbsp*</span> indicates a required field, holes 1-9 required.
		<br>
		</table>
		<td>
		</form>
		</table>
<?
}



function DisTeeEntryForm($courseid, $postPage)
{
		$sql = "select name, city, state from course_tbl where id = $courseid";
		$result = mysql_query($sql) or die("Could not get course details: " . mysql_error());
		$row = mysql_fetch_array($result);
?>
		<table class="CourseTable">
		<form action="<?printf("%s", $postPage);?>" method="POST" name="scorecard">
		<td>
		<table>
		<tr>
		<tr><td><b>Name:</b> </td><td><input value=<?printf("\"%s\"",$row["name"]);?> disabled="true" class="InputBoxWidth" type="text" name="CourseName"><span class="RequiredFieldIndicator">&nbsp*</span></td></tr> 
		<tr><td><b>City:</b>  </td><td><input value=<?printf("\"%s\"",$row["city"]);?> disabled="true" class="InputBoxWidth" type="text" name="City"><span class="RequiredFieldIndicator">&nbsp*</span></td></tr> 
		<tr><td><b>State/Province:</b>  </td><td><input value=<?printf("\"%s\"",$row["state"]);?> disabled="true" class="InputBoxWidth" type="text" name="State"><span class="RequiredFieldIndicator">&nbsp*</span></td></tr>
		<tr><td><b>Tee Name:</b>  </td><td><input class="InputBoxWidth" type="text" name="Tees"><span class="RequiredFieldIndicator">&nbsp*</span></td></tr> 
		<tr><td><b>Slope Rating:</b>  </td><td><input class="InputBoxWidth" type="text" name="SlopeRating"><span class="RequiredFieldIndicator">&nbsp*</span></td></tr> 
		<tr><td><b>Course Rating:</b>  </td><td><input class="InputBoxWidth" type="text" name="CourseRating"><span class="RequiredFieldIndicator">&nbsp*</span></td></tr> 
		</table> 
		<table class="ScoreCardTable">
		<tr><td><br></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td></tr>
		<tr><td><b>Hole:</b></td><td class="HoleNumberBox">1</td><td class="HoleNumberBox">2</td><td class="HoleNumberBox">3</td><td class="HoleNumberBox">4</td><td class="HoleNumberBox">5</td><td class="HoleNumberBox">6</td><td class="HoleNumberBox">7</td><td class="HoleNumberBox">8</td><td class="HoleNumberBox">9</td></tr>
		
		<tr><td><b>Par:</b></td>
		    <td><input class="ScoreInputBox" type="text" name="par1" onKeyUp="HandleCourseKeyUp(this, document.scorecard.StoreNewTees)" onFocus="HandleFocus(this)"></td>
		    <td><input class="ScoreInputBox" type="text" name="par2" onKeyUp="HandleCourseKeyUp(this, document.scorecard.StoreNewTees)" onFocus="HandleFocus(this)"></td>
		    <td><input class="ScoreInputBox" type="text" name="par3" onKeyUp="HandleCourseKeyUp(this, document.scorecard.StoreNewTees)" onFocus="HandleFocus(this)"></td>
		    <td><input class="ScoreInputBox" type="text" name="par4" onKeyUp="HandleCourseKeyUp(this, document.scorecard.StoreNewTees)" onFocus="HandleFocus(this)"></td>
		    <td><input class="ScoreInputBox" type="text" name="par5" onKeyUp="HandleCourseKeyUp(this, document.scorecard.StoreNewTees)" onFocus="HandleFocus(this)"></td>
		    <td><input class="ScoreInputBox" type="text" name="par6" onKeyUp="HandleCourseKeyUp(this, document.scorecard.StoreNewTees)" onFocus="HandleFocus(this)"></td>
		    <td><input class="ScoreInputBox" type="text" name="par7" onKeyUp="HandleCourseKeyUp(this, document.scorecard.StoreNewTees)" onFocus="HandleFocus(this)"></td>
		    <td><input class="ScoreInputBox" type="text" name="par8" onKeyUp="HandleCourseKeyUp(this, document.scorecard.StoreNewTees)" onFocus="HandleFocus(this)"></td>
		    <td><input class="ScoreInputBox" type="text" name="par9" onKeyUp="HandleCourseKeyUp(this, document.scorecard.StoreNewTees)" onFocus="HandleFocus(this)"></td>
		    		    <td><input disabled class="ScoreInputBox" type="text" name="out" value="0"></td>
		</tr>
		<tr><td><br></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td></tr>
		
		<tr><td><b>Hole:</b></td><td class="HoleNumberBox">10</td><td class="HoleNumberBox">11</td><td class="HoleNumberBox">12</td><td class="HoleNumberBox">13</td><td class="HoleNumberBox">14</td><td class="HoleNumberBox">15</td><td class="HoleNumberBox">16</td><td class="HoleNumberBox">17</td><td class="HoleNumberBox">18</td></tr>
		<tr><td><b>Par:</b></td>
			    <td><input class="ScoreInputBox" type="text" name="par10" onKeyUp="HandleCourseKeyUp(this, document.scorecard.StoreNewTees)" onFocus="HandleFocus(this)"></td>
			    <td><input class="ScoreInputBox" type="text" name="par11" onKeyUp="HandleCourseKeyUp(this, document.scorecard.StoreNewTees)" onFocus="HandleFocus(this)"></td>
			    <td><input class="ScoreInputBox" type="text" name="par12" onKeyUp="HandleCourseKeyUp(this, document.scorecard.StoreNewTees)" onFocus="HandleFocus(this)"></td>
			    <td><input class="ScoreInputBox" type="text" name="par13" onKeyUp="HandleCourseKeyUp(this, document.scorecard.StoreNewTees)" onFocus="HandleFocus(this)"></td>
			    <td><input class="ScoreInputBox" type="text" name="par14" onKeyUp="HandleCourseKeyUp(this, document.scorecard.StoreNewTees)" onFocus="HandleFocus(this)"></td>
			    <td><input class="ScoreInputBox" type="text" name="par15" onKeyUp="HandleCourseKeyUp(this, document.scorecard.StoreNewTees)" onFocus="HandleFocus(this)"></td>
			    <td><input class="ScoreInputBox" type="text" name="par16" onKeyUp="HandleCourseKeyUp(this, document.scorecard.StoreNewTees)" onFocus="HandleFocus(this)"></td>
			    <td><input class="ScoreInputBox" type="text" name="par17" onKeyUp="HandleCourseKeyUp(this, document.scorecard.StoreNewTees)" onFocus="HandleFocus(this)"></td>
			    <td><input class="ScoreInputBox" type="text" name="par18" onKeyUp="HandleCourseKeyUp(this, document.scorecard.StoreNewTees)" onFocus="HandleFocus(this)"></td>
			    <td><input disabled class="ScoreInputBox" type="text" name="inn" value="0"></td>
			</tr>
			<tr><td><br></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td></tr>
			<tr>
				<td></td>
			 	<td></td>
			 	<td></td>
				<td></td>
				<td></td>
				<td></td>
				<td></td>
				<td></td>
				<td COLSPAN=2><b>Total:</b></td>
				<td><input disabled class="ScoreInputBox" type="text" name="total" value="0"></td>
			</tr>
		</table>
		<br>
		<table>
		<input type="submit" value="Save" name="StoreNewTees">
		<br><br><span class="RequiredFieldIndicator">&nbsp*</span> indicates a required field, holes 1-9 required.
		</table>
		<td>
		<?printf("<input type=\"hidden\" value=\"%s\" name=\"courseid\">", $courseid);?>
		</form>
		</table>
<?
}





















function DisCourse( $courseID, $postPage )
{
		$sql = "select ct.*, ct.name as coursename from course_tbl ct where ct.id = ";
		$sql .= $courseID;
		//printf("%s",$sql);
		$result = mysql_query($sql) or die("Could not get selected course: " . mysql_error());
		$row = mysql_fetch_array($result);
		
		$sql = "select * from tee_tbl where courseid = ";
		$sql .= $courseID;
		$teeresult = mysql_query($sql) or die("Could not get tees for selected course: " . mysql_error());
		
		printf("<table class=\"CourseTable\">");
		printf("<form action=\"%s\" method=\"POST\" name=\"scorecard\">", $postPage);
		printf("<td>");
		printf("<table class=\"ScoreCardTable\">");
		printf("<tr>");
		/*
		printf("<tr><td>Course Name: </td><td><input class=\"InputBoxWidth\" type=\"text\" name=\"CourseName\" value=\"%s\"></td></tr>", $row["course_tbl.name"]);
		printf("<tr><td>Slope Rating:  </td><td><input class=\"InputBoxWidth\" type=\"text\" name=\"SlopeRating\" value=\"%s\"></td></tr>", $row["slope"]);
		printf("<tr><td>Course Rating:  </td><td><input class=\"InputBoxWidth\" type=\"text\" name=\"CourseRating\" value=\"%s\"></td></tr> ", $row["rating"]);
		printf("<tr><td>City:  </td><td><input class=\"InputBoxWidth\" type=\"text\" name=\"City\" value=\"%s\"></td></tr> ", $row["city"]);
		printf("<tr><td>State/Province:  </td><td><input class=\"InputBoxWidth\" type=\"text\" name=\"State\" value=\"%s\"></td></tr>", $row["state"]);
		printf("</table> ");
		printf("<table class=\"ScoreCardTable\">");
		printf("<tr><td>Hole:</td><td class=\"HoleNumberBox\">1</td><td class=\"HoleNumberBox\">2</td><td class=\"HoleNumberBox\">3</td><td class=\"HoleNumberBox\">4</td><td class=\"HoleNumberBox\">5</td><td class=\"HoleNumberBox\">6</td><td class=\"HoleNumberBox\">7</td><td class=\"HoleNumberBox\">8</td><td class=\"HoleNumberBox\">9</td></tr>");
		printf("<tr><td>Par:</td><td><input class=\"ScoreInputBox\" type=\"text\" name=\"par1\" value=\"%s\"></td><td><input class=\"ScoreInputBox\" type=\"text\" name=\"par2\" value=\"%s\"></td><td><input class=\"ScoreInputBox\" type=\"text\" name=\"par3\" value=\"%s\"></td><td><input class=\"ScoreInputBox\" type=\"text\" name=\"par4\" value=\"%s\"></td><td><input class=\"ScoreInputBox\" type=\"text\" name=\"par5\" value=\"%s\"></td><td><input class=\"ScoreInputBox\" type=\"text\" name=\"par6\" value=\"%s\"></td><td><input class=\"ScoreInputBox\" type=\"text\" name=\"par7\" value=\"%s\"></td><td><input class=\"ScoreInputBox\" type=\"text\" name=\"par8\" value=\"%s\"></td><td><input class=\"ScoreInputBox\" type=\"text\" name=\"par9\" value=\"%s\"></td></tr>", $row["par1"], $row["par2"], $row["par3"], $row["par4"], $row["par5"], $row["par6"], $row["par7"], $row["par8"], $row["par9"]);
		printf("<tr><td>Hole:</td><td class=\"HoleNumberBox\">10</td><td class=\"HoleNumberBox\">11</td><td class=\"HoleNumberBox\">12</td><td class=\"HoleNumberBox\">13</td><td class=\"HoleNumberBox\">14</td><td class=\"HoleNumberBox\">15</td><td class=\"HoleNumberBox\">16</td><td class=\"HoleNumberBox\">17</td><td class=\"HoleNumberBox\">18</td></tr>");
		printf("<tr><td>Par:</td><td><input class=\"ScoreInputBox\" type=\"text\" name=\"par10\" value=\"%s\"></td><td><input class=\"ScoreInputBox\" type=\"text\" name=\"par11\" value=\"%s\"></td><td><input class=\"ScoreInputBox\" type=\"text\" name=\"par12\" value=\"%s\"></td><td><input class=\"ScoreInputBox\" type=\"text\" name=\"par13\" value=\"%s\"></td><td><input class=\"ScoreInputBox\" type=\"text\" name=\"par14\" value=\"%s\"></td><td><input class=\"ScoreInputBox\" type=\"text\" name=\"par15\" value=\"%s\"></td><td><input class=\"ScoreInputBox\" type=\"text\" name=\"par16\" value=\"%s\"></td><td><input class=\"ScoreInputBox\" type=\"text\" name=\"par17\" value=\"%s\"></td><td><input class=\"ScoreInputBox\" type=\"text\" name=\"par18\" value=\"%s\"></td></tr>", $row["par10"], $row["par11"], $row["par12"], $row["par13"], $row["par14"], $row["par15"], $row["par16"], $row["par17"], $row["par18"]);
		*/
				?>
		<tr><td><b>Course Name:</b> </td><td><input class="InputBoxWidth" type="text" name="CourseName" value="<?printf("%s", $row["coursename"]);?>"><span class="RequiredFieldIndicator">&nbsp*</span></td></tr> 
		<tr><td><b>City:</b>  </td><td><input class="InputBoxWidth" type="text" name="City" value="<?printf("%s", $row["city"]);?>"><span class="RequiredFieldIndicator">&nbsp*</span></td></tr> 
		<tr><td><b>State/Province:</b>  </td><td><input class="InputBoxWidth" type="text" name="State" value="<?printf("%s", $row["state"]);?>"><span class="RequiredFieldIndicator">&nbsp*</span></td></tr>
		<tr><td valign="top"><b>Tees:</b></td>
		<td>
			<table border="0" cellspacing="0" cellpadding="5">
			<?
				$rowCnt = 0;
				while ($CourseRows = mysql_fetch_array($teeresult))
				{
					$classname = ($rowCnt % 2) ? 'CourseList2' : 'CourseList1';
					printf("<tr class=\"$classname\"><td>%s</td><td>&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp<A HREF=\"courseadmin.php?EditTee=%s&courseid=%s\">Edit</A>&nbsp</td><td>&nbsp<A HREF=\"courseadmin.php?DeleteTee=%s&ShowDetails=1&CourseID=%s\" onclick=\"javascript:return confirm('Are you sure you want to delete?')\">Delete</A>&nbsp</td></tr>",$CourseRows["name"], $CourseRows["id"], $courseID, $CourseRows["id"], $courseID);
					$rowCnt++;
				}				
				printf("<tr><td colspan=\"3\"><A HREF=\"addtee.php?courseid=%s\">Add New Tee</A></td></tr>",$courseID);
			?>
			</table>
		</td></tr> 
		</table> 
		<?
		printf("<br>");
		printf("<table>");
		printf("<input type=\"submit\" value=\"Save\" name=\"SaveChangedCourse\">");
		?><br><br><span class="RequiredFieldIndicator">&nbsp*</span> indicates a required field.<?
		printf("</table>");
		printf("<td>");
		printf("<input type=\"hidden\" value=\"%s\" name=\"courseid\">", $courseID);
		printf("</form>");
		printf("</table>");
}

function DisTees( $teeid, $postPage )
{
		$sql = "select ct.*, tt.*, ct.name as coursename, tt.name as teename from tee_tbl tt, course_tbl ct where tt.id = ";
		$sql .= $teeid;
		$sql .= " and tt.courseid = ct.id;";
		$teeresult = mysql_query($sql) or die("Could not get tees:  " . mysql_error());
		$row = mysql_fetch_array($teeresult);
		
		printf("<table class=\"CourseTable\">");
		printf("<form action=\"%s\" method=\"POST\" name=\"scorecard\">", $postPage);
		printf("<td>");
		printf("<table>");
		printf("<tr>");
		?>
		<tr><td><b>Course Name:</b> </td><td><input disabled="true" class="InputBoxWidth" type="text" name="CourseName" value="<?printf("%s", $row["coursename"]);?>"><span class="RequiredFieldIndicator">&nbsp*</span></td></tr> 
		<tr><td><b>City:</b>  </td><td><input disabled="true" class="InputBoxWidth" type="text" name="City" value="<?printf("%s", $row["city"]);?>"><span class="RequiredFieldIndicator">&nbsp*</span></td></tr> 
		<tr><td><b>State/Province:</b>  </td><td><input disabled="true" class="InputBoxWidth" type="text" name="State" value="<?printf("%s", $row["state"]);?>"><span class="RequiredFieldIndicator">&nbsp*</span></td></tr>
		<tr><td><b>Tee Name:</b>  </td><td><input class="InputBoxWidth" type="text" name="Tees" value="<?printf("%s", $row["teename"]);?>"><span class="RequiredFieldIndicator">&nbsp*</span></td></tr>
		<tr><td><b>Course Rating:</b>  </td><td><input class="InputBoxWidth" type="text" name="CourseRating" value="<?printf("%s", $row["rating"]);?>"><span class="RequiredFieldIndicator">&nbsp*</span></td></tr> 
		<tr><td><b>Slope Rating:</b>  </td><td><input class="InputBoxWidth" type="text" name="SlopeRating" value="<?printf("%s", $row["slope"]);?>"><span class="RequiredFieldIndicator">&nbsp*</span></td></tr> 
		</table> 
		<table class="ScoreCardTable">
		<tr><td><br></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td></tr>
		<tr><td><b>Hole:</b></td><td class="HoleNumberBox">1</td><td class="HoleNumberBox">2</td><td class="HoleNumberBox">3</td><td class="HoleNumberBox">4</td><td class="HoleNumberBox">5</td><td class="HoleNumberBox">6</td><td class="HoleNumberBox">7</td><td class="HoleNumberBox">8</td><td class="HoleNumberBox">9</td><td class="HoleNumberBox">Out</td></tr>
		
		<tr><td><b>Par:</b></td>
		    <td><input value="<?printf("%s", $row["par1"]);?>" class="ScoreInputBox" type="text" name="par1" onKeyUp="HandleCourseKeyUp(this, document.scorecard.SaveChangedTees)" onFocus="HandleFocus(this)"></td>
		    <td><input value="<?printf("%s", $row["par2"]);?>" class="ScoreInputBox" type="text" name="par2" onKeyUp="HandleCourseKeyUp(this, document.scorecard.SaveChangedTees)" onFocus="HandleFocus(this)"></td>
		    <td><input value="<?printf("%s", $row["par3"]);?>" class="ScoreInputBox" type="text" name="par3" onKeyUp="HandleCourseKeyUp(this, document.scorecard.SaveChangedTees)" onFocus="HandleFocus(this)"></td>
		    <td><input value="<?printf("%s", $row["par4"]);?>" class="ScoreInputBox" type="text" name="par4" onKeyUp="HandleCourseKeyUp(this, document.scorecard.SaveChangedTees)" onFocus="HandleFocus(this)"></td>
		    <td><input value="<?printf("%s", $row["par5"]);?>" class="ScoreInputBox" type="text" name="par5" onKeyUp="HandleCourseKeyUp(this, document.scorecard.SaveChangedTees)" onFocus="HandleFocus(this)"></td>
		    <td><input value="<?printf("%s", $row["par6"]);?>" class="ScoreInputBox" type="text" name="par6" onKeyUp="HandleCourseKeyUp(this, document.scorecard.SaveChangedTees)" onFocus="HandleFocus(this)"></td>
		    <td><input value="<?printf("%s", $row["par7"]);?>" class="ScoreInputBox" type="text" name="par7" onKeyUp="HandleCourseKeyUp(this, document.scorecard.SaveChangedTees)" onFocus="HandleFocus(this)"></td>
		    <td><input value="<?printf("%s", $row["par8"]);?>" class="ScoreInputBox" type="text" name="par8" onKeyUp="HandleCourseKeyUp(this, document.scorecard.SaveChangedTees)" onFocus="HandleFocus(this)"></td>
		    <td><input value="<?printf("%s", $row["par9"]);?>" class="ScoreInputBox" type="text" name="par9" onKeyUp="HandleCourseKeyUp(this, document.scorecard.SaveChangedTees)" onFocus="HandleFocus(this)"></td>
		    		    <td><input disabled class="ScoreInputBox" type="text" name="out" value="<?printf("%s", calcFrontPar($row));?>"></td>
		</tr>
		<tr><td><br></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td></tr>
		
		<tr><td><b>Hole:</b></td><td class="HoleNumberBox">10</td><td class="HoleNumberBox">11</td><td class="HoleNumberBox">12</td><td class="HoleNumberBox">13</td><td class="HoleNumberBox">14</td><td class="HoleNumberBox">15</td><td class="HoleNumberBox">16</td><td class="HoleNumberBox">17</td><td class="HoleNumberBox">18</td><td class="HoleNumberBox">In</td></tr>
		<tr><td><b>Par:</b></td>
			    <td><input value="<?printf("%s", $row["par10"]);?>" class="ScoreInputBox" type="text" name="par10" onKeyUp="HandleCourseKeyUp(this, document.scorecard.SaveChangedTees)" onFocus="HandleFocus(this)"></td>
			    <td><input value="<?printf("%s", $row["par11"]);?>" class="ScoreInputBox" type="text" name="par11" onKeyUp="HandleCourseKeyUp(this, document.scorecard.SaveChangedTees)" onFocus="HandleFocus(this)"></td>
			    <td><input value="<?printf("%s", $row["par12"]);?>" class="ScoreInputBox" type="text" name="par12" onKeyUp="HandleCourseKeyUp(this, document.scorecard.SaveChangedTees)" onFocus="HandleFocus(this)"></td>
			    <td><input value="<?printf("%s", $row["par13"]);?>" class="ScoreInputBox" type="text" name="par13" onKeyUp="HandleCourseKeyUp(this, document.scorecard.SaveChangedTees)" onFocus="HandleFocus(this)"></td>
			    <td><input value="<?printf("%s", $row["par14"]);?>" class="ScoreInputBox" type="text" name="par14" onKeyUp="HandleCourseKeyUp(this, document.scorecard.SaveChangedTees)" onFocus="HandleFocus(this)"></td>
			    <td><input value="<?printf("%s", $row["par15"]);?>" class="ScoreInputBox" type="text" name="par15" onKeyUp="HandleCourseKeyUp(this, document.scorecard.SaveChangedTees)" onFocus="HandleFocus(this)"></td>
			    <td><input value="<?printf("%s", $row["par16"]);?>" class="ScoreInputBox" type="text" name="par16" onKeyUp="HandleCourseKeyUp(this, document.scorecard.SaveChangedTees)" onFocus="HandleFocus(this)"></td>
			    <td><input value="<?printf("%s", $row["par17"]);?>" class="ScoreInputBox" type="text" name="par17" onKeyUp="HandleCourseKeyUp(this, document.scorecard.SaveChangedTees)" onFocus="HandleFocus(this)"></td>
			    <td><input value="<?printf("%s", $row["par18"]);?>" class="ScoreInputBox" type="text" name="par18" onKeyUp="HandleCourseKeyUp(this, document.scorecard.SaveChangedTees)" onFocus="HandleFocus(this)"></td>
			    <td><input disabled class="ScoreInputBox" type="text" name="inn" value="<?printf("%s", calcBackPar($row));?>"></td>
			</tr>
			<tr><td><br></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td></tr>
			<tr>
				<td></td>
			 	<td></td>
			 	<td></td>
				<td></td>
				<td></td>
				<td></td>
				<td></td>
				<td></td>
				<td COLSPAN=2><b>Total:</b></td>
				<td><input disabled class="ScoreInputBox" type="text" name="total" value="<?printf("%s", (calcFrontPar($row) + calcBackPar($row)) );?>"></td>
			</tr>
		<?
		printf("</table>");
		printf("<br>");
		printf("<table>");
		printf("<input type=\"submit\" value=\"Save\" name=\"SaveChangedTees\">");
		?><br><br><span class="RequiredFieldIndicator">&nbsp*</span> indicates a required field, holes 1-9 required.<?
		printf("</table>");
		printf("<td>");
		printf("<input type=\"hidden\" value=\"%s\" name=\"teeid\">", $teeid);
		printf("<input type=\"hidden\" value=\"%s\" name=\"courseid\">", $_GET["courseid"]);
		printf("</form>");
		printf("</table>");
}

function getParForHole( $teeID, $holeNum )
{
	$parNum = "par$holeNum"; 
	$sql = "select $parNum from tee_tbl where id = $teeID and $parNum is not null";
	$result = mysql_query($sql) or die("Could not get par for the hole: " . mysql_error());
	$row = mysql_fetch_array($result);
	if ( mysql_num_rows($result) == 0 )
		die("Could not get par for the hole");
	else
		return $row["$parNum"];
}

function getNumOf( $scoreType, $teeID, $holeNum, $fromdate, $todate )
{
	$par = getParForHole( $teeID, $holeNum );
	
	$holeInOne = 1; 
	$doubleEagle = 0; 
	$eagle = 0; 
	$birdie = 0; 
	$bogie = 0;
	$double = 0;
	
	if ( $par == 3 )
	{
		$birdie = 2;
		$bogie = 4;
		$double = 5;
		
		// Double eagle doesn't exist for a par 3 and we would
		// catch an eagle when we report the hole in ones.
		if ( $scoreType == "DOUBLE_EAGLE" || $scoreType == "EAGLE" )
			return 0;
	}
	else if ( $par == 4 )
	{
		$eagle = 2;
		$birdie = 3;
		$bogie = 5;
		$double = 6;
		
		// Double eagle would be reported as hole in one.
		if ( $scoreType == "DOUBLE_EAGLE" )
			return 0;
	}
	else if ( $par == 5 )
	{
		$doubleEagle = 2;
		$eagle = 3;
		$birdie = 4;
		$bogie = 6;
		$double = 7;
	}
	else
	{
		die("Invalid par number for hole");
	}
	
	$holeName = "hole$holeNum"; 
	$sql = "select count(*) as cnt from score_tbl where teeid = $teeID and $holeName";
	if ( $scoreType == "HOLE_IN_ONE" )
		$sql .= " = $holeInOne";
	else if ( $scoreType == "DOUBLE_EAGLE" )
		$sql .= " = $doubleEagle";
	else if ( $scoreType == "EAGLE" )
		$sql .= " = $eagle";
	else if ( $scoreType == "BIRDIE" )
		$sql .= " = $birdie";
	else if ( $scoreType == "PAR" )
		$sql .= " = $par";
	else if ( $scoreType == "BOGIE" )
		$sql .= " = $bogie";
	else if ( $scoreType == "DOUBLE" )
		$sql .= " = $double";
	else
		$sql .= " > $double";
		
		
	if ( $fromdate != "" && $todate != "" )
		$sql .= " and dateplayed >= '$fromdate' and dateplayed <= '$todate'";
	else if ( $fromdate != "" )
		$sql .= " and dateplayed >= '$fromdate'";
	else if ( $todate != "" )
		$sql .= " and dateplayed <= '$todate'";
		
		
	$result = mysql_query($sql) or die("Could not get number of certain scores for the hole: " . mysql_error());
	$row = mysql_fetch_array($result);
	$cnt = $row["cnt"];
	if ( $cnt == 0 )
		return "0";
	else
		return $cnt;
}

function DisCourseStats( $teeid )
{
	
		// Find out if we should filter stats by a date range.
		$fromdate = "";
		$todate = "";
		if ( $_POST["dostatfilter"] )
		{
			
			//$fromdate = $_POST["statfromdt"];
			//$todate = $_POST["stattodt"];
			
			
			//
			// FROM DATE
			//
			$fromdate .= sprintf("%u",$_POST["TheYearfrom"]); // YEAR
			if ($_POST["TheMonthfrom"] < 10)
				$fromdate .= sprintf("0%u",$_POST["TheMonthfrom"]); // MONTH
			else
				$fromdate .= sprintf("%u",$_POST["TheMonthfrom"]); // MONTH
			if ($_POST["TheDayfrom"] < 10)
				$fromdate .= sprintf("0%u",$_POST["TheDayfrom"]); // DAY
			else
				$fromdate .= sprintf("%u",$_POST["TheDayfrom"]); // DAY
			
			/*
			if ($_POST["TheMonthfrom"] < 10)
				$fromdate .= sprintf("0%u",$_POST["TheMonthfrom"]); // MONTH
			else
				$fromdate .= sprintf("%u",$_POST["TheMonthfrom"]); // MONTH
			$fromdate .= "/";
			if ($_POST["TheDayfrom"] < 10)
				$fromdate .= sprintf("0%u",$_POST["TheDayfrom"]); // DAY
			else
				$fromdate .= sprintf("%u",$_POST["TheDayfrom"]); // DAY
			$fromdate .= "/";
			$fromdate .= sprintf("%u",$_POST["TheYearfrom"]); // YEAR
			*/
			//printf("fromdate:  %s", $fromdate);
	
	
			//
			// TO DATE
			//
			$todate .= sprintf("%u",$_POST["TheYearto"]); // YEAR
			if ($_POST["TheMonthto"] < 10)
				$todate .= sprintf("0%u",$_POST["TheMonthto"]); // MONTH
			else
				$todate .= sprintf("%u",$_POST["TheMonthto"]); // MONTH
			if ($_POST["TheDayto"] < 10)
				$todate .= sprintf("0%u",$_POST["TheDayto"]); // DAY
			else
				$todate .= sprintf("%u",$_POST["TheDayto"]); // DAY
			
			
			/*
			if ($_POST["TheMonthto"] < 10)
				$todate .= sprintf("0%u",$_POST["TheMonthto"]); // MONTH
			else
				$todate .= sprintf("%u",$_POST["TheMonthto"]); // MONTH
			$todate .= "/";
			if ($_POST["TheDayto"] < 10)
				$todate .= sprintf("0%u",$_POST["TheDayto"]); // DAY
			else
				$todate .= sprintf("%u",$_POST["TheDayto"]); // DAY
			$todate .= "/";
			$todate .= sprintf("%u",$_POST["TheYearto"]); // YEAR
			*/
			//printf("todate:  %s", $todate);
		}

		// should contain [greens hit] [possible num of greens]
		
		$greensArray = array();
		$greensArray = getGreensArray($teeid, $fromdate, $todate);
		
		// should contain [fairways hit] [possible num of fairways]
		$fairwaysArray = array();
		$fairwaysArray = getfairwaysArray($teeid, $fromdate, $todate);
		
		// should contain [number of penalties] [holes played]
		$penaltiesArray = array();
		$penaltiesArray = getpenaltiesArray($teeid, $fromdate, $todate);
	
		$courseAverageArray = array();
		$courseAverageArray = getCourseAverageArray($teeid, $fromdate, $todate);
		
		$coursePuttAverageArray = array();
		$coursePuttAverageArray = getCoursePuttAverageArray($teeid, $fromdate, $todate);
		
		
		
		$worstHoleArray = array();
		$bestHoleArray = array();
		$bestToWorstHoleArray = array();
		$worstHoleArray = getWorstAverageHoleArray($courseAverageArray, $teeid, $fromdate, $todate);
		$bestHoleArray = getBestAverageHoleArray($courseAverageArray, $teeid, $fromdate, $todate);
		$bestToWorstHoleArray = getBestToWorstHoleArray($courseAverageArray, $teeid, $fromdate, $todate);
		

		$BestHoleString;
		if ( count($bestHoleArray) > 1 )
		{
			for ($i = 0; $i < count($bestHoleArray); $i++)
			{
				if ( ($i + 1) != count($bestHoleArray) )
				{
					$BestHoleString .= $bestHoleArray[$i];
					$BestHoleString .= ", ";
				}
				else
					$BestHoleString .= $bestHoleArray[$i];
			}
			printf("<br>");
		}
		else
			$BestHoleString = $bestHoleArray[0];

		$WorstHoleString;
		if ( count($worstHoleArray) > 1 )
		{
			for ($i = 0; $i < count($worstHoleArray); $i++)
			{
				if ( ($i + 1) != count($worstHoleArray) )
				{
					$WorstHoleString .= $worstHoleArray[$i];
					$WorstHoleString .= ", ";
				}
				else
					$WorstHoleString .= $worstHoleArray[$i];
			}
		}
		else
			$WorstHoleString = $worstHoleArray[0];
		
		
		/*	
		printf("Holes from best to worst as you have played them on average:");
		for ($i = 0; $i < count($bestToWorstHoleArray); $i++)
		{
			if ( ($i + 1) != count($bestToWorstHoleArray) )
				printf("%s, ", $bestToWorstHoleArray[$i]);
			else
				printf("%s<br><br>", $bestToWorstHoleArray[$i]);
		}
		*/
		
		printf("<table border=\"0\" cellspacing=\"0\" cellpadding=\"5\">");
		printf("<TR class=\"CourseList1\"><TD>AVERAGE SCORE:  %s</TD></TR>", formatHandi( $courseAverageArray[0] + $courseAverageArray[1]+ $courseAverageArray[2]+ $courseAverageArray[3]+ $courseAverageArray[4]+ $courseAverageArray[5]+ $courseAverageArray[6]+ $courseAverageArray[7]+ $courseAverageArray[8] + $courseAverageArray[9] + $courseAverageArray[10]+ $courseAverageArray[11]+ $courseAverageArray[12]+ $courseAverageArray[13]+ $courseAverageArray[14]+ $courseAverageArray[15]+ $courseAverageArray[16]+ $courseAverageArray[17] ));
		printf("<TR class=\"CourseList2\"><TD>AVG PUTTS PER ROUND</B>:  %s</TD></TR>", formatHandi( $coursePuttAverageArray[0] + $coursePuttAverageArray[1]+ $coursePuttAverageArray[2]+ $coursePuttAverageArray[3]+ $coursePuttAverageArray[4]+ $coursePuttAverageArray[5]+ $coursePuttAverageArray[6]+ $coursePuttAverageArray[7]+ $coursePuttAverageArray[8] + $coursePuttAverageArray[9] + $coursePuttAverageArray[10]+ $coursePuttAverageArray[11]+ $coursePuttAverageArray[12]+ $coursePuttAverageArray[13]+ $coursePuttAverageArray[14]+ $coursePuttAverageArray[15]+ $coursePuttAverageArray[16]+ $coursePuttAverageArray[17] ));
		printf("<TR class=\"CourseList1\"><TD>BEST HOLE(S):  %s</TD></TR>", $BestHoleString);
		printf("<TR class=\"CourseList2\"><TD>WORST HOLE(S):  %s</TD></TR>", $WorstHoleString);
		if ( $greensArray[1] == 0 )
			printf("<tr class=\"CourseList1\"><td>GREENS IN REG: 0 out of 0</td></tr>");
		else
			printf("<tr class=\"CourseList1\"><td>GREENS IN REG: %s%%</td></tr>", formatHandi($greensArray[0]/$greensArray[1] * 100) );
			
		if ( $fairwaysArray[1] == 0 )
			printf("<tr class=\"CourseList2\"><td>FAIRWAYS HIT: 0 out of 0</td></tr>");
		else
			printf("<tr class=\"CourseList2\"><td>FAIRWAYS HIT: %s%%</td></tr>", formatHandi($fairwaysArray[0]/$fairwaysArray[1] * 100) );
			
		if ( $penaltiesArray[1] == 0 )
			printf("<tr class=\"CourseList1\"><td>PENALTIES: 0 over 0 holes</td></tr>");
		else
			printf("<tr class=\"CourseList1\"><td>PENALTIES: %s per 9 holes</td></tr>", formatHandi($penaltiesArray[0]/($penaltiesArray[1]/9) ));
		printf("</table><br>");
		
		
		$sql = "select concat(ct.name, '  (', tt.name, ')') as coursename, tt.*, ct.* from course_tbl ct, tee_tbl tt where tt.courseid = ct.id and tt.id = ";
		$sql .= $teeid;
		$result = mysql_query($sql) or die("Could not get stats: " . mysql_error());
		$row = mysql_fetch_array($result);
		printf("<table class=\"CourseTable\">");
		printf("<td>");
		printf("<table class=\"ScoreCardTable\">");
		printf("<tr class=\"CourseList1\"><td><b>Hole:</b></td><td class=\"HoleNumberBox\">1</td><td class=\"HoleNumberBox\">2</td><td class=\"HoleNumberBox\">3</td><td class=\"HoleNumberBox\">4</td><td class=\"HoleNumberBox\">5</td><td class=\"HoleNumberBox\">6</td><td class=\"HoleNumberBox\">7</td><td class=\"HoleNumberBox\">8</td><td class=\"HoleNumberBox\">9</td><td class=\"HoleNumberBoxTotal\"><b>Out</b></td></tr>");
		printf("<tr class=\"CourseList1\"><td><b>Par:</b></td><td class=\"HoleNumberBox\">%s</td><td class=\"HoleNumberBox\">%s</td><td class=\"HoleNumberBox\">%s</td><td class=\"HoleNumberBox\">%s</td><td class=\"HoleNumberBox\">%s</td><td class=\"HoleNumberBox\">%s</td><td class=\"HoleNumberBox\">%s</td><td class=\"HoleNumberBox\">%s</td><td class=\"HoleNumberBox\">%s</td><td class=\"HoleNumberBox\">%s</td></tr>", $row["par1"], $row["par2"], $row["par3"], $row["par4"], $row["par5"], $row["par6"], $row["par7"], $row["par8"], $row["par9"], $row["par1"] +  $row["par2"] +  $row["par3"] +  $row["par4"] +  $row["par5"] +  $row["par6"] +  $row["par7"] +  $row["par8"] +  $row["par9"]);
		printf("<tr class=\"CourseList2\"><td><b>Avg:</b></td><td class=\"HoleNumberBox\">%s</td><td class=\"HoleNumberBox\">%s</td><td class=\"HoleNumberBox\">%s</td><td class=\"HoleNumberBox\">%s</td><td class=\"HoleNumberBox\">%s</td><td class=\"HoleNumberBox\">%s</td><td class=\"HoleNumberBox\">%s</td><td class=\"HoleNumberBox\">%s</td><td class=\"HoleNumberBox\">%s</td><td class=\"HoleNumberBox\">%s</td></tr>", formatHandi($courseAverageArray[0]), formatHandi($courseAverageArray[1]), formatHandi($courseAverageArray[2]), formatHandi($courseAverageArray[3]), formatHandi($courseAverageArray[4]), formatHandi($courseAverageArray[5]), formatHandi($courseAverageArray[6]), formatHandi($courseAverageArray[7]), formatHandi($courseAverageArray[8]), formatHandi($courseAverageArray[18]));
		printf("<tr class=\"CourseList1\"><td><b>Putt Avg:</b></td><td class=\"HoleNumberBox\">%s</td><td class=\"HoleNumberBox\">%s</td><td class=\"HoleNumberBox\">%s</td><td class=\"HoleNumberBox\">%s</td><td class=\"HoleNumberBox\">%s</td><td class=\"HoleNumberBox\">%s</td><td class=\"HoleNumberBox\">%s</td><td class=\"HoleNumberBox\">%s</td><td class=\"HoleNumberBox\">%s</td><td class=\"HoleNumberBox\">%s</td></tr>", formatPuttHandi($coursePuttAverageArray[0]), formatPuttHandi($coursePuttAverageArray[1]), formatPuttHandi($coursePuttAverageArray[2]), formatPuttHandi($coursePuttAverageArray[3]), formatPuttHandi($coursePuttAverageArray[4]), formatPuttHandi($coursePuttAverageArray[5]), formatPuttHandi($coursePuttAverageArray[6]), formatPuttHandi($coursePuttAverageArray[7]), formatPuttHandi($coursePuttAverageArray[8]), formatPuttHandi($coursePuttAverageArray[18]));
		printf("<tr class=\"CourseList2\"><td><b>Hole In Ones:</b></td><td class=\"HoleNumberBox\">%s</td><td class=\"HoleNumberBox\">%s</td><td class=\"HoleNumberBox\">%s</td><td class=\"HoleNumberBox\">%s</td><td class=\"HoleNumberBox\">%s</td><td class=\"HoleNumberBox\">%s</td><td class=\"HoleNumberBox\">%s</td><td class=\"HoleNumberBox\">%s</td><td class=\"HoleNumberBox\">%s</td></tr>", getNumOf("HOLE_IN_ONE", $teeid, 1, $fromdate, $todate), getNumOf("HOLE_IN_ONE", $teeid, 2, $fromdate, $todate), getNumOf("HOLE_IN_ONE", $teeid, 3, $fromdate, $todate), getNumOf("HOLE_IN_ONE", $teeid, 4, $fromdate, $todate), getNumOf("HOLE_IN_ONE", $teeid, 5, $fromdate, $todate), getNumOf("HOLE_IN_ONE", $teeid, 6, $fromdate, $todate), getNumOf("HOLE_IN_ONE", $teeid, 7, $fromdate, $todate), getNumOf("HOLE_IN_ONE", $teeid, 8, $fromdate, $todate), getNumOf("HOLE_IN_ONE", $teeid, 9, $fromdate, $todate));
		printf("<tr class=\"CourseList1\"><td><b>Double Eagles:</b></td><td class=\"HoleNumberBox\">%s</td><td class=\"HoleNumberBox\">%s</td><td class=\"HoleNumberBox\">%s</td><td class=\"HoleNumberBox\">%s</td><td class=\"HoleNumberBox\">%s</td><td class=\"HoleNumberBox\">%s</td><td class=\"HoleNumberBox\">%s</td><td class=\"HoleNumberBox\">%s</td><td class=\"HoleNumberBox\">%s</td></tr>", getNumOf("DOUBLE_EAGLE", $teeid, 1, $fromdate, $todate), getNumOf("DOUBLE_EAGLE", $teeid, 2, $fromdate, $todate), getNumOf("DOUBLE_EAGLE", $teeid, 3, $fromdate, $todate), getNumOf("DOUBLE_EAGLE", $teeid, 4, $fromdate, $todate), getNumOf("DOUBLE_EAGLE", $teeid, 5, $fromdate, $todate), getNumOf("DOUBLE_EAGLE", $teeid, 6, $fromdate, $todate), getNumOf("DOUBLE_EAGLE", $teeid, 7, $fromdate, $todate), getNumOf("DOUBLE_EAGLE", $teeid, 8, $fromdate, $todate), getNumOf("DOUBLE_EAGLE", $teeid, 9, $fromdate, $todate));
		printf("<tr class=\"CourseList2\"><td><b>Eagles:</b></td><td class=\"HoleNumberBox\">%s</td><td class=\"HoleNumberBox\">%s</td><td class=\"HoleNumberBox\">%s</td><td class=\"HoleNumberBox\">%s</td><td class=\"HoleNumberBox\">%s</td><td class=\"HoleNumberBox\">%s</td><td class=\"HoleNumberBox\">%s</td><td class=\"HoleNumberBox\">%s</td><td class=\"HoleNumberBox\">%s</td></tr>", getNumOf("EAGLE", $teeid, 1, $fromdate, $todate), getNumOf("EAGLE", $teeid, 2, $fromdate, $todate), getNumOf("EAGLE", $teeid, 3, $fromdate, $todate), getNumOf("EAGLE", $teeid, 4, $fromdate, $todate), getNumOf("EAGLE", $teeid, 5, $fromdate, $todate), getNumOf("EAGLE", $teeid, 6, $fromdate, $todate), getNumOf("EAGLE", $teeid, 7, $fromdate, $todate), getNumOf("EAGLE", $teeid, 8, $fromdate, $todate), getNumOf("EAGLE", $teeid, 9, $fromdate, $todate));
		printf("<tr class=\"CourseList1\"><td><b>Birdies:</b></td><td class=\"HoleNumberBox\">%s</td><td class=\"HoleNumberBox\">%s</td><td class=\"HoleNumberBox\">%s</td><td class=\"HoleNumberBox\">%s</td><td class=\"HoleNumberBox\">%s</td><td class=\"HoleNumberBox\">%s</td><td class=\"HoleNumberBox\">%s</td><td class=\"HoleNumberBox\">%s</td><td class=\"HoleNumberBox\">%s</td></tr>", getNumOf("BIRDIE", $teeid, 1, $fromdate, $todate), getNumOf("BIRDIE", $teeid, 2, $fromdate, $todate), getNumOf("BIRDIE", $teeid, 3, $fromdate, $todate), getNumOf("BIRDIE", $teeid, 4, $fromdate, $todate), getNumOf("BIRDIE", $teeid, 5, $fromdate, $todate), getNumOf("BIRDIE", $teeid, 6, $fromdate, $todate), getNumOf("BIRDIE", $teeid, 7, $fromdate, $todate), getNumOf("BIRDIE", $teeid, 8, $fromdate, $todate), getNumOf("BIRDIE", $teeid, 9, $fromdate, $todate));
		printf("<tr class=\"CourseList2\"><td><b>Pars:</b></td><td class=\"HoleNumberBox\">%s</td><td class=\"HoleNumberBox\">%s</td><td class=\"HoleNumberBox\">%s</td><td class=\"HoleNumberBox\">%s</td><td class=\"HoleNumberBox\">%s</td><td class=\"HoleNumberBox\">%s</td><td class=\"HoleNumberBox\">%s</td><td class=\"HoleNumberBox\">%s</td><td class=\"HoleNumberBox\">%s</td></tr>", getNumOf("PAR", $teeid, 1, $fromdate, $todate), getNumOf("PAR", $teeid, 2, $fromdate, $todate), getNumOf("PAR", $teeid, 3, $fromdate, $todate), getNumOf("PAR", $teeid, 4, $fromdate, $todate), getNumOf("PAR", $teeid, 5, $fromdate, $todate), getNumOf("PAR", $teeid, 6, $fromdate, $todate), getNumOf("PAR", $teeid, 7, $fromdate, $todate), getNumOf("PAR", $teeid, 8, $fromdate, $todate), getNumOf("PAR", $teeid, 9, $fromdate, $todate));
		printf("<tr class=\"CourseList1\"><td><b>Bogies:</b></td><td class=\"HoleNumberBox\">%s</td><td class=\"HoleNumberBox\">%s</td><td class=\"HoleNumberBox\">%s</td><td class=\"HoleNumberBox\">%s</td><td class=\"HoleNumberBox\">%s</td><td class=\"HoleNumberBox\">%s</td><td class=\"HoleNumberBox\">%s</td><td class=\"HoleNumberBox\">%s</td><td class=\"HoleNumberBox\">%s</td></tr>", getNumOf("BOGIE", $teeid, 1, $fromdate, $todate), getNumOf("BOGIE", $teeid, 2, $fromdate, $todate), getNumOf("BOGIE", $teeid, 3, $fromdate, $todate), getNumOf("BOGIE", $teeid, 4, $fromdate, $todate), getNumOf("BOGIE", $teeid, 5, $fromdate, $todate), getNumOf("BOGIE", $teeid, 6, $fromdate, $todate), getNumOf("BOGIE", $teeid, 7, $fromdate, $todate), getNumOf("BOGIE", $teeid, 8, $fromdate, $todate), getNumOf("BOGIE", $teeid, 9, $fromdate, $todate));
		printf("<tr class=\"CourseList2\"><td><b>Doubles:</b></td><td class=\"HoleNumberBox\">%s</td><td class=\"HoleNumberBox\">%s</td><td class=\"HoleNumberBox\">%s</td><td class=\"HoleNumberBox\">%s</td><td class=\"HoleNumberBox\">%s</td><td class=\"HoleNumberBox\">%s</td><td class=\"HoleNumberBox\">%s</td><td class=\"HoleNumberBox\">%s</td><td class=\"HoleNumberBox\">%s</td></tr>", getNumOf("DOUBLE", $teeid, 1, $fromdate, $todate), getNumOf("DOUBLE", $teeid, 2, $fromdate, $todate), getNumOf("DOUBLE", $teeid, 3, $fromdate, $todate), getNumOf("DOUBLE", $teeid, 4, $fromdate, $todate), getNumOf("DOUBLE", $teeid, 5, $fromdate, $todate), getNumOf("DOUBLE", $teeid, 6, $fromdate, $todate), getNumOf("DOUBLE", $teeid, 7, $fromdate, $todate), getNumOf("DOUBLE", $teeid, 8, $fromdate, $todate), getNumOf("DOUBLE", $teeid, 9, $fromdate, $todate));
		printf("<tr class=\"CourseList1\"><td><b>Others:</b></td><td class=\"HoleNumberBox\">%s</td><td class=\"HoleNumberBox\">%s</td><td class=\"HoleNumberBox\">%s</td><td class=\"HoleNumberBox\">%s</td><td class=\"HoleNumberBox\">%s</td><td class=\"HoleNumberBox\">%s</td><td class=\"HoleNumberBox\">%s</td><td class=\"HoleNumberBox\">%s</td><td class=\"HoleNumberBox\">%s</td></tr>", getNumOf("OTHER", $teeid, 1, $fromdate, $todate), getNumOf("OTHER", $teeid, 2, $fromdate, $todate), getNumOf("OTHER", $teeid, 3, $fromdate, $todate), getNumOf("OTHER", $teeid, 4, $fromdate, $todate), getNumOf("OTHER", $teeid, 5, $fromdate, $todate), getNumOf("OTHER", $teeid, 6, $fromdate, $todate), getNumOf("OTHER", $teeid, 7, $fromdate, $todate), getNumOf("OTHER", $teeid, 8, $fromdate, $todate), getNumOf("OTHER", $teeid, 9, $fromdate, $todate));
		
		?>
		<tr><td><br></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td></tr>
		<?
		if (!isNineHole($teeid))
		{
			
			printf("<tr class=\"CourseList1\"><td><b>Hole:</b></td><td class=\"HoleNumberBox\">10</td><td class=\"HoleNumberBox\">11</td><td class=\"HoleNumberBox\">12</td><td class=\"HoleNumberBox\">13</td><td class=\"HoleNumberBox\">14</td><td class=\"HoleNumberBox\">15</td><td class=\"HoleNumberBox\">16</td><td class=\"HoleNumberBox\">17</td><td class=\"HoleNumberBox\">18</td><td class=\"HoleNumberBoxTotal\"><b>In</b></td></tr>");
			printf("<tr class=\"CourseList1\"><td><b>Par:</b></td><td class=\"HoleNumberBox\">%s</td><td class=\"HoleNumberBox\">%s</td><td class=\"HoleNumberBox\">%s</td><td class=\"HoleNumberBox\">%s</td><td class=\"HoleNumberBox\">%s</td><td class=\"HoleNumberBox\">%s</td><td class=\"HoleNumberBox\">%s</td><td class=\"HoleNumberBox\">%s</td><td class=\"HoleNumberBox\">%s</td><td class=\"HoleNumberBox\">%s</td></tr>", $row["par10"], $row["par11"], $row["par12"], $row["par13"], $row["par14"], $row["par15"], $row["par16"], $row["par17"], $row["par18"], $row["par10"] +  $row["par11"] +  $row["par12"] +  $row["par13"] +  $row["par14"] +  $row["par15"] +  $row["par16"] +  $row["par17"] +  $row["par18"]);
			printf("<tr class=\"CourseList2\"><td><b>Avg:</b></td><td class=\"HoleNumberBox\">%s</td><td class=\"HoleNumberBox\">%s</td><td class=\"HoleNumberBox\">%s</td><td class=\"HoleNumberBox\">%s</td><td class=\"HoleNumberBox\">%s</td><td class=\"HoleNumberBox\">%s</td><td class=\"HoleNumberBox\">%s</td><td class=\"HoleNumberBox\">%s</td><td class=\"HoleNumberBox\">%s</td><td class=\"HoleNumberBox\">%s</td></tr>", formatHandi($courseAverageArray[9]), formatHandi($courseAverageArray[10]), formatHandi($courseAverageArray[11]), formatHandi($courseAverageArray[12]), formatHandi($courseAverageArray[13]), formatHandi($courseAverageArray[14]), formatHandi($courseAverageArray[15]), formatHandi($courseAverageArray[16]), formatHandi($courseAverageArray[17]), formatHandi($courseAverageArray[19]));
			printf("<tr class=\"CourseList1\"><td><b>Putt Avg:</b></td><td class=\"HoleNumberBox\">%s</td><td class=\"HoleNumberBox\">%s</td><td class=\"HoleNumberBox\">%s</td><td class=\"HoleNumberBox\">%s</td><td class=\"HoleNumberBox\">%s</td><td class=\"HoleNumberBox\">%s</td><td class=\"HoleNumberBox\">%s</td><td class=\"HoleNumberBox\">%s</td><td class=\"HoleNumberBox\">%s</td><td class=\"HoleNumberBox\">%s</td></tr>", formatPuttHandi($coursePuttAverageArray[9]), formatPuttHandi($coursePuttAverageArray[10]), formatPuttHandi($coursePuttAverageArray[11]), formatPuttHandi($coursePuttAverageArray[12]), formatPuttHandi($coursePuttAverageArray[13]), formatPuttHandi($coursePuttAverageArray[14]), formatPuttHandi($coursePuttAverageArray[15]), formatPuttHandi($coursePuttAverageArray[16]), formatPuttHandi($coursePuttAverageArray[17]), formatPuttHandi($coursePuttAverageArray[19]));
			printf("<tr class=\"CourseList2\"><td><b>Hole In Ones:</b></td><td class=\"HoleNumberBox\">%s</td><td class=\"HoleNumberBox\">%s</td><td class=\"HoleNumberBox\">%s</td><td class=\"HoleNumberBox\">%s</td><td class=\"HoleNumberBox\">%s</td><td class=\"HoleNumberBox\">%s</td><td class=\"HoleNumberBox\">%s</td><td class=\"HoleNumberBox\">%s</td><td class=\"HoleNumberBox\">%s</td></tr>", getNumOf("HOLE_IN_ONE", $teeid, 10, $fromdate, $todate), getNumOf("HOLE_IN_ONE", $teeid, 11, $fromdate, $todate), getNumOf("HOLE_IN_ONE", $teeid, 12, $fromdate, $todate), getNumOf("HOLE_IN_ONE", $teeid, 13, $fromdate, $todate), getNumOf("HOLE_IN_ONE", $teeid, 14, $fromdate, $todate), getNumOf("HOLE_IN_ONE", $teeid, 15, $fromdate, $todate), getNumOf("HOLE_IN_ONE", $teeid, 16, $fromdate, $todate), getNumOf("HOLE_IN_ONE", $teeid, 17, $fromdate, $todate), getNumOf("HOLE_IN_ONE", $teeid, 18, $fromdate, $todate));
			printf("<tr class=\"CourseList1\"><td><b>Double Eagles:</b></td><td class=\"HoleNumberBox\">%s</td><td class=\"HoleNumberBox\">%s</td><td class=\"HoleNumberBox\">%s</td><td class=\"HoleNumberBox\">%s</td><td class=\"HoleNumberBox\">%s</td><td class=\"HoleNumberBox\">%s</td><td class=\"HoleNumberBox\">%s</td><td class=\"HoleNumberBox\">%s</td><td class=\"HoleNumberBox\">%s</td></tr>", getNumOf("DOUBLE_EAGLE", $teeid, 10, $fromdate, $todate), getNumOf("DOUBLE_EAGLE", $teeid, 11, $fromdate, $todate), getNumOf("DOUBLE_EAGLE", $teeid, 12, $fromdate, $todate), getNumOf("DOUBLE_EAGLE", $teeid, 13, $fromdate, $todate), getNumOf("DOUBLE_EAGLE", $teeid, 14, $fromdate, $todate), getNumOf("DOUBLE_EAGLE", $teeid, 15, $fromdate, $todate), getNumOf("DOUBLE_EAGLE", $teeid, 16, $fromdate, $todate), getNumOf("DOUBLE_EAGLE", $teeid, 17, $fromdate, $todate), getNumOf("DOUBLE_EAGLE", $teeid, 18, $fromdate, $todate));
			printf("<tr class=\"CourseList2\"><td><b>Eagles:</b></td><td class=\"HoleNumberBox\">%s</td><td class=\"HoleNumberBox\">%s</td><td class=\"HoleNumberBox\">%s</td><td class=\"HoleNumberBox\">%s</td><td class=\"HoleNumberBox\">%s</td><td class=\"HoleNumberBox\">%s</td><td class=\"HoleNumberBox\">%s</td><td class=\"HoleNumberBox\">%s</td><td class=\"HoleNumberBox\">%s</td></tr>", getNumOf("EAGLE", $teeid, 10, $fromdate, $todate), getNumOf("EAGLE", $teeid, 11, $fromdate, $todate), getNumOf("EAGLE", $teeid, 12, $fromdate, $todate), getNumOf("EAGLE", $teeid, 13, $fromdate, $todate), getNumOf("EAGLE", $teeid, 14, $fromdate, $todate), getNumOf("EAGLE", $teeid, 15, $fromdate, $todate), getNumOf("EAGLE", $teeid, 16, $fromdate, $todate), getNumOf("EAGLE", $teeid, 17, $fromdate, $todate), getNumOf("EAGLE", $teeid, 18, $fromdate, $todate));
			printf("<tr class=\"CourseList1\"><td><b>Birdies:</b></td><td class=\"HoleNumberBox\">%s</td><td class=\"HoleNumberBox\">%s</td><td class=\"HoleNumberBox\">%s</td><td class=\"HoleNumberBox\">%s</td><td class=\"HoleNumberBox\">%s</td><td class=\"HoleNumberBox\">%s</td><td class=\"HoleNumberBox\">%s</td><td class=\"HoleNumberBox\">%s</td><td class=\"HoleNumberBox\">%s</td></tr>", getNumOf("BIRDIE", $teeid, 10, $fromdate, $todate), getNumOf("BIRDIE", $teeid, 11, $fromdate, $todate), getNumOf("BIRDIE", $teeid, 12, $fromdate, $todate), getNumOf("BIRDIE", $teeid, 13, $fromdate, $todate), getNumOf("BIRDIE", $teeid, 14, $fromdate, $todate), getNumOf("BIRDIE", $teeid, 15, $fromdate, $todate), getNumOf("BIRDIE", $teeid, 16, $fromdate, $todate), getNumOf("BIRDIE", $teeid, 17, $fromdate, $todate), getNumOf("BIRDIE", $teeid, 18, $fromdate, $todate));
			printf("<tr class=\"CourseList2\"><td><b>Pars:</b></td><td class=\"HoleNumberBox\">%s</td><td class=\"HoleNumberBox\">%s</td><td class=\"HoleNumberBox\">%s</td><td class=\"HoleNumberBox\">%s</td><td class=\"HoleNumberBox\">%s</td><td class=\"HoleNumberBox\">%s</td><td class=\"HoleNumberBox\">%s</td><td class=\"HoleNumberBox\">%s</td><td class=\"HoleNumberBox\">%s</td></tr>", getNumOf("PAR", $teeid, 10, $fromdate, $todate), getNumOf("PAR", $teeid, 11, $fromdate, $todate), getNumOf("PAR", $teeid, 12, $fromdate, $todate), getNumOf("PAR", $teeid, 13, $fromdate, $todate), getNumOf("PAR", $teeid, 14, $fromdate, $todate), getNumOf("PAR", $teeid, 15, $fromdate, $todate), getNumOf("PAR", $teeid, 16, $fromdate, $todate), getNumOf("PAR", $teeid, 17, $fromdate, $todate), getNumOf("PAR", $teeid, 18, $fromdate, $todate));
			printf("<tr class=\"CourseList1\"><td><b>Bogies:</b></td><td class=\"HoleNumberBox\">%s</td><td class=\"HoleNumberBox\">%s</td><td class=\"HoleNumberBox\">%s</td><td class=\"HoleNumberBox\">%s</td><td class=\"HoleNumberBox\">%s</td><td class=\"HoleNumberBox\">%s</td><td class=\"HoleNumberBox\">%s</td><td class=\"HoleNumberBox\">%s</td><td class=\"HoleNumberBox\">%s</td></tr>", getNumOf("BOGIE", $teeid, 10, $fromdate, $todate), getNumOf("BOGIE", $teeid, 11, $fromdate, $todate), getNumOf("BOGIE", $teeid, 12, $fromdate, $todate), getNumOf("BOGIE", $teeid, 13, $fromdate, $todate), getNumOf("BOGIE", $teeid, 14, $fromdate, $todate), getNumOf("BOGIE", $teeid, 15, $fromdate, $todate), getNumOf("BOGIE", $teeid, 16, $fromdate, $todate), getNumOf("BOGIE", $teeid, 17, $fromdate, $todate), getNumOf("BOGIE", $teeid, 18, $fromdate, $todate));
			printf("<tr class=\"CourseList2\"><td><b>Doubles:</b></td><td class=\"HoleNumberBox\">%s</td><td class=\"HoleNumberBox\">%s</td><td class=\"HoleNumberBox\">%s</td><td class=\"HoleNumberBox\">%s</td><td class=\"HoleNumberBox\">%s</td><td class=\"HoleNumberBox\">%s</td><td class=\"HoleNumberBox\">%s</td><td class=\"HoleNumberBox\">%s</td><td class=\"HoleNumberBox\">%s</td></tr>", getNumOf("DOUBLE", $teeid, 10, $fromdate, $todate), getNumOf("DOUBLE", $teeid, 11, $fromdate, $todate), getNumOf("DOUBLE", $teeid, 12, $fromdate, $todate), getNumOf("DOUBLE", $teeid, 13, $fromdate, $todate), getNumOf("DOUBLE", $teeid, 14, $fromdate, $todate), getNumOf("DOUBLE", $teeid, 15, $fromdate, $todate), getNumOf("DOUBLE", $teeid, 16, $fromdate, $todate), getNumOf("DOUBLE", $teeid, 17, $fromdate, $todate), getNumOf("DOUBLE", $teeid, 18, $fromdate, $todate));
			printf("<tr class=\"CourseList1\"><td><b>Others:</b></td><td class=\"HoleNumberBox\">%s</td><td class=\"HoleNumberBox\">%s</td><td class=\"HoleNumberBox\">%s</td><td class=\"HoleNumberBox\">%s</td><td class=\"HoleNumberBox\">%s</td><td class=\"HoleNumberBox\">%s</td><td class=\"HoleNumberBox\">%s</td><td class=\"HoleNumberBox\">%s</td><td class=\"HoleNumberBox\">%s</td></tr>", getNumOf("OTHER", $teeid, 10, $fromdate, $todate), getNumOf("OTHER", $teeid, 11, $fromdate, $todate), getNumOf("OTHER", $teeid, 12, $fromdate, $todate), getNumOf("OTHER", $teeid, 13, $fromdate, $todate), getNumOf("OTHER", $teeid, 14, $fromdate, $todate), getNumOf("OTHER", $teeid, 15, $fromdate, $todate), getNumOf("OTHER", $teeid, 16, $fromdate, $todate), getNumOf("OTHER", $teeid, 17, $fromdate, $todate), getNumOf("OTHER", $teeid, 18, $fromdate, $todate));
			
			?>
			<tr><td><br></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td></tr>
			<tr>
				<td></td>
			 	<td></td>
			 	<td></td>
				<td></td>
				<td></td>
				<td></td>
				<td></td>
				<td COLSPAN=3><b>Avg Score:</b></td>
				<td class="HoleNumberBox"><?printf("%s", formatHandi( $courseAverageArray[0] + $courseAverageArray[1]+ $courseAverageArray[2]+ $courseAverageArray[3]+ $courseAverageArray[4]+ $courseAverageArray[5]+ $courseAverageArray[6]+ $courseAverageArray[7]+ $courseAverageArray[8] + $courseAverageArray[9] + $courseAverageArray[10]+ $courseAverageArray[11]+ $courseAverageArray[12]+ $courseAverageArray[13]+ $courseAverageArray[14]+ $courseAverageArray[15]+ $courseAverageArray[16]+ $courseAverageArray[17] )); ?></td>
			</tr>
			<tr>
				<td></td>
			 	<td></td>
			 	<td></td>
				<td></td>
				<td></td>
				<td></td>
				<td></td>
				<td COLSPAN=3><b>Avg Putts:</b></td>
				<td class="HoleNumberBox"><? printf("%s", formatHandi( $coursePuttAverageArray[0] + $coursePuttAverageArray[1]+ $coursePuttAverageArray[2]+ $coursePuttAverageArray[3]+ $coursePuttAverageArray[4]+ $coursePuttAverageArray[5]+ $coursePuttAverageArray[6]+ $coursePuttAverageArray[7]+ $coursePuttAverageArray[8] + $coursePuttAverageArray[9] + $coursePuttAverageArray[10]+ $coursePuttAverageArray[11]+ $coursePuttAverageArray[12]+ $coursePuttAverageArray[13]+ $coursePuttAverageArray[14]+ $coursePuttAverageArray[15]+ $coursePuttAverageArray[16]+ $coursePuttAverageArray[17] )); ?></td>
			</tr>
			<?
		}
		printf("</table>");
		printf("</form>");
		printf("</table>");
		?>

		
		<form action="coursestats.php?ShowCourseStats=1&teeid=<?printf("%s", $teeid);?>" method="POST" name="statfilterform">
		<table class="FilterTable">
		<tr><td colspan="4"><b>View Stats...</b></td></tr>
		<tr><td><b>From:</b> </td><td></td> <td>
			<?
			if ($fromdate != "")
			{
				$theDate = substr($fromdate, 4, 2);
				$theDate .= "/";
				$theDate .= substr($fromdate, 6, 2);
				$theDate .= "/";
				$theDate .= substr($fromdate, 0, 4);
				//printf("got here1: %s  %s", $fromdate, $theDate);
				getDateControl(getdate(strtotime($fromdate)), "from");
			}
			else
			{
				//printf("got here2: %s", getMinDate()	);
				getDateControl(getdate(strtotime(getMinDate($teeid))), "from");
			}
			?>
		</td></tr>
		<tr><td><b>To:</b> </td><td></td>   <td>
			<?
			if ($todate != "")
			{
				$theDate = substr($todate, 4, 2);
					$theDate .= "/";
				$theDate .= substr($todate, 6, 2);
				$theDate .= "/";
				$theDate .= substr($todate, 0, 4);
				//printf("got here3: %s %s", $todate, $theDate);
				getDateControl(getdate(strtotime($todate)), "to");
			}
			else
			{
				//printf("got here4");
				getDateControl(getdate(strtotime(getMaxDate($teeid))), "to");
			}
			?>
		</td></tr>
		<tr><td colspan = "3"><input type="submit" name="dostatfilter" value="Apply Date Range"></td></tr>
		</table>
		</form>
		
		
		<?
}

function DisplayScorecard ( $type, $id = 0, $postpage = "" )
{
	if ( $type == "ENTER_ROUND" )
	{
		DisScoreEntryForm( $id, $postpage );
	}
	else if ( $type == "EDIT_ROUND" )
	{
		DisScore( $id, $postpage );
	}
	else if ( $type == "NEW_COURSE" )
	{
		DisCourseEntryForm( $postpage );
	}
	else if ( $type == "EDIT_COURSE" )
	{
		DisCourse( $id, $postpage );
	}
	else if ( $type == "COURSE_STATS" )
	{
		DisCourseStats( $id );
	}
}

function getMinDate($teeid = "")
{
	$sql = "SELECT min(dateplayed) mindate from score_tbl where userid = ";
	$sql .= $_SESSION['userid'];
	if ( $teeid != "" )
	{
		$sql .= sprintf(" and teeid = %s", $teeid);
	}
	//printf("$sql <br>");
	$result = mysql_query($sql) or die("Could not get mindate from list of scores: " . mysql_error());
	$row = mysql_fetch_array($result);
	$dbDate = $row["mindate"];
	$minDt = substr($dbDate, 5, 2);
	$minDt .= "/";
	$minDt .= substr($dbDate, 8, 2);
	$minDt .= "/";
	$minDt .= substr($dbDate, 0, 4);
	return $minDt;
}

function getMaxDate($teeid = "")
{
	$sql = "SELECT max(dateplayed) maxdate from score_tbl where userid = ";
	$sql .= $_SESSION['userid'];
	if ( $teeid != "" )
	{
		$sql .= sprintf(" and teeid = %s", $teeid);
	}
	//printf("$sql <br>");
	$result = mysql_query($sql) or die("Could not get maxdate from list of scores: " . mysql_error());
	$row = mysql_fetch_array($result);
	$dbDate = $row["maxdate"];
	$maxDt = substr($dbDate, 5, 2);
	$maxDt .= "/";
	$maxDt .= substr($dbDate, 8, 2);
	$maxDt .= "/";
	$maxDt .= substr($dbDate, 0, 4);
	return $maxDt;
}



//
//  STATISTICAL FUNCTIONS
//


function getMaxScore($uid)
{
	$sql = "select max(score) as maxscore from score_tbl where userid = $uid";
	//printf("%s<br>", $sql);
	$result = mysql_query($sql) or die("Could not get max score " . mysql_error());
	if (mysql_num_rows($result) == 0)
		return "No Scores";
	$row = mysql_fetch_array($result);
	return $row["maxscore"];
}

function getCourseAndTeeName($teeid)
{
	$sql = "select concat(ct.name, ' (', tt.name, ')') as courseandtee from course_tbl ct, tee_tbl tt where tt.courseid = ct.id and tt.id = $teeid";
	//printf("%s<br>", $sql);
	$result = mysql_query($sql) or die("Could not get course and tee name " . mysql_error());
	if (mysql_num_rows($result) == 0)
		return "Could not find course and tee name";
	$row = mysql_fetch_array($result);
	return $row["courseandtee"];
}

function getAvgScore($uid, $fromdate="", $todate="")
{

	if (strlen($teeid) > 0)
		$sql = "select avg(score) as avgscore from score_tbl where userid = $uid and teeid = $teeid";
	else
		$sql = "select avg(score) as avgscore from score_tbl where userid = $uid";
		
	
	if ( strlen($fromdate) > 0 )
		$sql .= " and dateplayed >= $fromdate and dateplayed <= $todate";
	//printf("sql:  %s<br>", $sql);
	$result = mysql_query($sql) or die("Could not get avg score " . mysql_error());
	if (mysql_num_rows($result) == 0)
		return "No Scores";
	$row = mysql_fetch_array($result);
	return number_format($row["avgscore"], 1);
}

function getMinScore($uid)
{
	$sql = "select min(score) as minscore from score_tbl where userid = $uid";
	//printf("%s<br>", $sql);
	$result = mysql_query($sql) or die("Could not get min score " . mysql_error());
	if (mysql_num_rows($result) == 0)
		return "No Scores";
	$row = mysql_fetch_array($result);
	return $row["minscore"];
}

function getMinDatePlayed($uid)
{
	$sql = "select min(dateplayed) as mindateplayed from score_tbl where userid = $uid";
	//printf("%s<br>", $sql);
	$result = mysql_query($sql) or die("Could not get min dateplayed " . mysql_error());
	if (mysql_num_rows($result) == 0)
		return "No Scores";
	$row = mysql_fetch_array($result);
	return $row["mindateplayed"];
}

function getScoreHistory($uid, $fromdate="", $todate="")
{
	$sql = "select score from score_tbl where userid = $uid";
	if ( strlen($fromdate) > 0 )
		$sql .= " and dateplayed >= $fromdate and dateplayed <= $todate";
	$sql .= " order by dateplayed desc limit 100";
	$result = mysql_query($sql) or die("Could not get scores " . mysql_error());
	//if (mysql_num_rows($result) == 0)
	//	return "No Scores";
	$scoresArray = array();
	
	while ($row = mysql_fetch_array($result))
		$scoresArray[] = $row["score"];

	return array_reverse($scoresArray);
}

function getLastScores($uid, $num, $teeid="")
{
	$sql = "";
	if ( strlen($teeid) > 0 )
		$sql = "select score, dateplayed as thedate, date_format(dateplayed,'%m/%d/%y') as dateplayed from score_tbl where userid = $uid and teeid = $teeid order by thedate desc limit $num;";
	else
		$sql = "select score, dateplayed as thedate, date_format(dateplayed,'%m/%d/%y') as dateplayed from score_tbl where userid = $uid order by thedate desc limit $num;";
	//echo $sql;
	$result = mysql_query($sql) or die("Could not get scores " . mysql_error());
	//if (mysql_num_rows($result) == 0)
	//	return "No Scores";
	$scoresArray = array();
	
	//
	//  THIS GETS A LITTLE FUNKY TO ACCOUNT FOR PLAYING MULTIPLE ROUNDS IN ONE DAY
	//  WE NEED TO MAKE A DIFFERENT KEY FOR EACH KEY VALUE PAIR IN THE ASSOCIATIVE ARRAY
	//  SINCE OUR KEYS ARE DATES I'M APPENDING "(X)" ON THE END OF ANY DUPLICATES
	//
	$lastDatePlayed = "";
	$i = 1;
	while ($row = mysql_fetch_array($result))
	{
		if ($row["dateplayed"] == $lastDatePlayed)
		{
			$datePlayed = $row["dateplayed"];
			$datePlayed .= '(';
			$datePlayed .= $i;
			$datePlayed .= ')';
			$i++;
			$scoresArray[$datePlayed] = $row["score"];
		}
		else
		{
			$scoresArray[$row["dateplayed"]] = $row["score"];
			$lastDatePlayed = $row["dateplayed"];
			$i = 1;
		}
	}
	return array_reverse($scoresArray);
}


// THE ONLY DIFFERENCE FROM THE ABOVE FUNCTION IS THIS ONE RETURNS DATE AND TEEID AS KEY VALUE
// INSTEAD OF DATE AND SCORE.
function getDatesAndIds($uid, $num, $teeid="")
{
	$sql = "";
	if ( strlen($teeid) > 0 )
		$sql = "select id, dateplayed as thedate, date_format(dateplayed,'%m/%d/%y') as dateplayed from score_tbl where userid = $uid and teeid = $teeid order by thedate desc limit $num;";
	else
		$sql = "select id, dateplayed as thedate, date_format(dateplayed,'%m/%d/%y') as dateplayed from score_tbl where userid = $uid order by thedate desc limit $num;";
	//echo $sql;
	$result = mysql_query($sql) or die("Could not get ids " . mysql_error());
	//if (mysql_num_rows($result) == 0)
	//	return "No Scores";
	$scoresArray = array();
	
	//
	//  THIS GETS A LITTLE FUNKY TO ACCOUNT FOR PLAYING MULTIPLE ROUNDS IN ONE DAY
	//  WE NEED TO MAKE A DIFFERENT KEY FOR EACH KEY VALUE PAIR IN THE ASSOCIATIVE ARRAY
	//  SINCE OUR KEYS ARE DATES I'M APPENDING "(X)" ON THE END OF ANY DUPLICATES
	//
	$lastDatePlayed = "";
	$i = 1;
	while ($row = mysql_fetch_array($result))
	{
		if ($row["dateplayed"] == $lastDatePlayed)
		{
			$datePlayed = $row["dateplayed"];
			$datePlayed .= '(';
			$datePlayed .= $i;
			$datePlayed .= ')';
			$i++;
			$scoresArray[$datePlayed] = $row["id"];
		}
		else
		{
			$scoresArray[$row["dateplayed"]] = $row["id"];
			$lastDatePlayed = $row["dateplayed"];
			$i = 1;
		}
	}
	return array_reverse($scoresArray);
}

function getAvgPuttsPerGreen($uid, $fromdate="", $todate="")
{		
		$sql = "select * from score_tbl where userid = $uid";
		if ( strlen($fromdate) > 0 )
			$sql .= " and dateplayed >= $fromdate and dateplayed <= $todate";
		$result = mysql_query($sql) or die("Could not get scores " . mysql_error());
		$puttTotal = 0;
		$puttCount = 0;
		while ($row = mysql_fetch_array($result))
		{
			for ($i = 1; $i < 19; $i++)
			{
				$colName = "putt";
				$colName .= $i;
				if (strlen($row[$colName]) > 0)
				{
					$puttTotal += $row[$colName];				
					$puttCount++;
				}
			}
		}
		if ($puttCount == 0)
			return -1;	// NONE WERE RECORDED DURING THIS TIME PERIOD
		else
		{
			$avg = ( $puttTotal / $puttCount );
			return number_format($avg, 1);
		}
}

function getPuttsPerGreen($ScoreID)
{		
		$sql = "select * from score_tbl where id = $ScoreID";
		$result = mysql_query($sql) or die("Could not get scores " . mysql_error());
		$puttTotal = 0;
		$puttCount = 0;
		while ($row = mysql_fetch_array($result))
		{
			for ($i = 1; $i < 19; $i++)
			{
				$colName = "putt";
				$colName .= $i;
				if (strlen($row[$colName]) > 0)
				{
					$puttTotal += $row[$colName];				
					$puttCount++;
				}
			}
		}
		if ($puttCount == 0)
			return "N/A";	// NONE WERE RECORDED DURING THIS TIME PERIOD
		else
		{
			$avg = ( $puttTotal / $puttCount );
			return number_format($avg, 1);
		}
}



function getPercentFairwaysHit($uid, $fromdate="", $todate="")
{	
		$sql = "select distinct teeid from score_tbl where userid = $uid";
		if ( strlen($fromdate) > 0 )
			$sql .= " and dateplayed >= $fromdate and dateplayed <= $todate";
		$result = mysql_query($sql) or die("Could not get scores " . mysql_error());
		$fairwaysHit = 0;
		$fairwaysPossible = 0;
		while ($row = mysql_fetch_array($result))
		{
			$fairwaysArray = array();
			$fairwaysArray = getFairwaysArray($row[teeid], $fromdate, $todate);
			$fairwaysPossible += $fairwaysArray[1];
			$fairwaysHit += $fairwaysArray[0];
		}
	
		if ( $fairwaysPossible == 0)
			return -1;	// NONE WERE RECORDED DURING THIS TIME PERIOD
		else
		{ 
			$percent = number_format(100*( $fairwaysHit / $fairwaysPossible ), 1);
			$number = $percent;
			//$number .= "%";
			return $number;
		}
}

function getPercentGreensInRegulation($uid, $fromdate="", $todate="")
{	
		$sql = "select distinct teeid from score_tbl where userid = $uid";
		if ( strlen($fromdate) > 0 )
			$sql .= " and dateplayed >= $fromdate and dateplayed <= $todate";
		$result = mysql_query($sql) or die("Could not get scores " . mysql_error());
		$greensHit = 0;
		$greensPossible = 0;
		while ($row = mysql_fetch_array($result))
		{
			$greensArray = array();
			$greensArray = getGreensArray($row[teeid], $fromdate, $todate);
			$greensPossible += $greensArray[1];
			$greensHit += $greensArray[0];
		}
		
		//printf("Greens Possible:  %s<br>", $greensPossible);
		//printf("Greens Hit:  %s<br>", $greensHit);
		
		if ( $greensPossible == 0)
			return -1;	// NONE WERE RECORDED DURING THIS TIME PERIOD
		else
		{ 
			$percent = number_format(100*( $greensHit / $greensPossible ), 1);
			$number = $percent;
			//$number .= "%";
			return $number;
		}
}

function getPercentGIR( $scoreid )
{
	$sql = "select * from score_tbl where id = $scoreid";

	$result = mysql_query($sql) or die("Could not select score history " . mysql_error());
	if (mysql_num_rows($result) == 0)
		die("No scores for that date range or course");
	
	$greensArray = array();
	$greensHit = 0;
	$greensPossible = 0;
	while ($row = mysql_fetch_array($result))
	{
		if ($row["greens"] != null)
		{
			if ($row["hole1"] != null)
				$greensPossible += 9;
			if ($row["hole10"] != null)
				$greensPossible += 9;
			$greensHit += $row["greens"];
		}
		else
			return "N/A";
	}
		
	$greensHit = number_format(100*($greensHit / $greensPossible), 1);
	return $greensHit;
}


function getPenaltiesPerNineHoles($uid, $fromdate="", $todate="")
{	
		$sql = "select distinct teeid from score_tbl where userid = $uid";
		if ( strlen($fromdate) > 0 )
			$sql .= " and dateplayed >= $fromdate and dateplayed <= $todate";
		$result = mysql_query($sql) or die("Could not get scores " . mysql_error());
		$penalties = 0;
		$holesPlayed = 0;
		while ($row = mysql_fetch_array($result))
		{
			$penaltiesArray = array();
			$penaltiesArray = getpenaltiesArray($row[teeid], $fromdate, $todate);
			$holesPlayed += $penaltiesArray[1];
			$penalties += $penaltiesArray[0];
		}
		
		//printf("HolesPlayed:  %s<br>", $holesPlayed);
		//printf("Penalties:  %s<br>", $penalties);
	
		if ( $holesPlayed == 0)
			return -1;	// NONE WERE RECORDED DURING THIS TIME PERIOD
		else
		{ 
			$numOfNineHoleRounds = $holesPlayed / 9;
			$numOfPenaltiesPerNineHoleRound = $penalties / $numOfNineHoleRounds;
			return number_format($numOfPenaltiesPerNineHoleRound, 1);
		}
}

function getPenaltiesPerNineForRound($ScoreID)
{	
	$sql = "select * from score_tbl where id = $ScoreID";

	//printf("%s<br>", $sql);
	$result = mysql_query($sql) or die("Could not select score history " . mysql_error());
	if (mysql_num_rows($result) == 0)
		die("No scores for that date range or course");
	
	$penaltiesArray = array();
	$holesPlayed = 0;
	$penalties = 0;
	$row = mysql_fetch_array($result);
	if ($row["penalties"] != null)
	{
		if ($row["hole1"] != null)
			$holesPlayed += 9;
		if ($row["hole10"] != null)
			$holesPlayed += 9;
		$penalties += $row["penalties"];
		$numOfNineHoleRounds = $holesPlayed / 9;
		$numOfPenaltiesPerNineHoleRound = $penalties / $numOfNineHoleRounds;
		return number_format($numOfPenaltiesPerNineHoleRound, 1);
	}
	else
	{
		return "N/A";
	}
}

function getAvgScoreByMonth($uid)
{	
	$theArray = array();
	$monthCount = 0;
	$firstDayOfMonth = mktime(0, 0, 0, date("m"), 1, date("y"));
	$FloorDate = strtotime("01/01/2003");
	$i = 0;
	while ($monthCount < 12 && $FloorDate < $firstDayOfMonth)
	{
		$firstDayOfMonth = mktime(0, 0, 0, date("m")-$i, 1, date("y"));
		$lastDayOfMonth = mktime(0, 0, 0, date("m")-($i-1), 0, date("y"));
		//printf("First day of the month %s<br>", date("m/d/y", $firstDayOfMonth));
		//printf("Last day of the month %s<br>", date("m/d/y", $lastDayOfMonth));
		
		$avgScore = getAvgScore($uid, date("Ymd", $firstDayOfMonth), date("Ymd", $lastDayOfMonth));
		if ( $avgScore > 0 )
		{
			$theArray[date("M y", $firstDayOfMonth)]=$avgScore;
			$monthCount++;
		}
		$i++;
	}
	return array_reverse($theArray);
/*
	$theArray = array();
	for ($i=0; $i<12; $i++)
	{
		$firstDayOfMonth = mktime(0, 0, 0, date("m")-$i, 1, date("y"));
		$lastDayOfMonth = mktime(0, 0, 0, date("m")-($i-1), 0, date("y"));
		//printf("First day of the month %s<br>", date("m/d/y", $firstDayOfMonth));
		//printf("Last day of the month %s<br>", date("m/d/y", $lastDayOfMonth));
		
		$avgScore = getAvgScore($uid, date("Ymd", $firstDayOfMonth), date("Ymd", $lastDayOfMonth));
		if ( $avgScore > 0 )
			$theArray[date("M y", $firstDayOfMonth)]=$avgScore;
	}
	return array_reverse($theArray);
	*/
}

function getAvgPuttsPerGreenByMonth($uid)	// GETS THE LAST 12 MONTHS
{	
	$theArray = array();
	$monthCount = 0;
	$firstDayOfMonth = mktime(0, 0, 0, date("m"), 1, date("y"));
	$FloorDate = strtotime("01/01/2003");
	$i = 0;
	while ($monthCount < 12 && $FloorDate < $firstDayOfMonth)
	{
		$firstDayOfMonth = mktime(0, 0, 0, date("m")-$i, 1, date("y"));
		$lastDayOfMonth = mktime(0, 0, 0, date("m")-($i-1), 0, date("y"));
		//printf("First day of the month %s<br>", date("m/d/y", $firstDayOfMonth));
		//printf("Last day of the month %s<br>", date("m/d/y", $lastDayOfMonth));
		
		$avgPutts = getAvgPuttsPerGreen($uid, date("Ymd", $firstDayOfMonth), date("Ymd", $lastDayOfMonth));
		if ( $avgPutts > 0 )
		{
			$theArray[date("M y", $firstDayOfMonth)]=$avgPutts;
			$monthCount++;
		}
		$i++;
	}
	return array_reverse($theArray);



	/*
	$theArray = array();
	for ($i=0; $i<12; $i++)
	{
		$firstDayOfMonth = mktime(0, 0, 0, date("m")-$i, 1, date("y"));
		$lastDayOfMonth = mktime(0, 0, 0, date("m")-($i-1), 0, date("y"));
		//printf("First day of the month %s<br>", date("m/d/y", $firstDayOfMonth));
		//printf("Last day of the month %s<br>", date("m/d/y", $lastDayOfMonth));
		
		$avgPutts = getAvgPuttsPerGreen($uid, date("Ymd", $firstDayOfMonth), date("Ymd", $lastDayOfMonth));
		if ( $avgPutts > 0 )
			$theArray[date("M y", $firstDayOfMonth)]=$avgPutts;
	}
	return array_reverse($theArray);
	*/
	
}

function getPercentFairwaysHitByMonth($uid)	// GETS THE LAST 12 MONTHS
{	

	$theArray = array();
	$monthCount = 0;
	$firstDayOfMonth = mktime(0, 0, 0, date("m"), 1, date("y"));  
	$FloorDate = strtotime("01/01/2003");
	$i = 0;
	while ($monthCount < 12 && $FloorDate < $firstDayOfMonth)
	{
		$firstDayOfMonth = mktime(0, 0, 0, date("m")-$i, 1, date("y"));
		$lastDayOfMonth = mktime(0, 0, 0, date("m")-($i-1), 0, date("y"));
		//printf("First day of the month %s<br>", date("m/d/y", $firstDayOfMonth));
		//printf("Last day of the month %s<br>", date("m/d/y", $lastDayOfMonth));
		
		$perFair = getPercentFairwaysHit($uid, date("Ymd", $firstDayOfMonth), date("Ymd", $lastDayOfMonth));
		if ( $perFair > 0 )
		{
			$theArray[date("M y", $firstDayOfMonth)]=$perFair;
			$monthCount++;
		}
		$i++;
	}
	return array_reverse($theArray);
	
	/*
	$theArray = array();
	for ($i=0; $i<12; $i++)
	{
		$firstDayOfMonth = mktime(0, 0, 0, date("m")-$i, 1, date("y"));
		$lastDayOfMonth = mktime(0, 0, 0, date("m")-($i-1), 0, date("y"));
		//printf("First day of the month %s<br>", date("m/d/y", $firstDayOfMonth));
		//printf("Last day of the month %s<br>", date("m/d/y", $lastDayOfMonth));
		//$theArray[date("M", $firstDayOfMonth)]=getPercentFairwaysHit($uid, date("Ymd", $firstDayOfMonth), date("Ymd", $lastDayOfMonth));
		
		$perFair = getPercentFairwaysHit($uid, date("Ymd", $firstDayOfMonth), date("Ymd", $lastDayOfMonth));
		if ( $perFair > 0 )
			$theArray[date("M y", $firstDayOfMonth)]=$perFair;
	}
	return array_reverse($theArray);
	*/
}

function getPenaltiesPerNineHolesByMonth($uid)	// GETS THE LAST 12 MONTHS
{	

	$theArray = array();
	$monthCount = 0;
	$firstDayOfMonth = mktime(0, 0, 0, date("m"), 1, date("y"));
	$FloorDate = strtotime("01/01/2003");
	$i = 0;
	while ($monthCount < 12 && $FloorDate < $firstDayOfMonth)
	{
		$firstDayOfMonth = mktime(0, 0, 0, date("m")-$i, 1, date("y"));
		$lastDayOfMonth = mktime(0, 0, 0, date("m")-($i-1), 0, date("y"));
		//printf("First day of the month %s<br>", date("m/d/y", $firstDayOfMonth));
		//printf("Last day of the month %s<br>", date("m/d/y", $lastDayOfMonth));
		
		$penalties = getPenaltiesPerNineHoles($uid, date("Ymd", $firstDayOfMonth), date("Ymd", $lastDayOfMonth));
		if ( $penalties > 0 )
		{
			$theArray[date("M y", $firstDayOfMonth)]=$penalties;
			$monthCount++;
		}
		$i++;
	}
	return array_reverse($theArray);

	/*
	$theArray = array();
	for ($i=0; $i<12; $i++)
	{
		$firstDayOfMonth = mktime(0, 0, 0, date("m")-$i, 1, date("y"));
		$lastDayOfMonth = mktime(0, 0, 0, date("m")-($i-1), 0, date("y"));
		//printf("First day of the month %s<br>", date("m/d/y", $firstDayOfMonth));
		//printf("Last day of the month %s<br>", date("m/d/y", $lastDayOfMonth));
		//$theArray[date("M", $firstDayOfMonth)]=getPenaltiesPerNineHoles($uid, date("Ymd", $firstDayOfMonth), date("Ymd", $lastDayOfMonth));
		
		
		$penalties = getPenaltiesPerNineHoles($uid, date("Ymd", $firstDayOfMonth), date("Ymd", $lastDayOfMonth));
		if ( $penalties > 0 )
			$theArray[date("M y", $firstDayOfMonth)]=$penalties;
	}
	return array_reverse($theArray);
	*/
}

function getPercentGreensInRegulationByMonth($uid)	// GETS THE LAST 12 MONTHS
{	
	
	$theArray = array();
	$monthCount = 0;
	$firstDayOfMonth = mktime(0, 0, 0, date("m"), 1, date("y"));
	$FloorDate = strtotime("01/01/2003");
	$i = 0;
	while ($monthCount < 12 && $FloorDate < $firstDayOfMonth)
	{
		$firstDayOfMonth = mktime(0, 0, 0, date("m")-$i, 1, date("y"));
		$lastDayOfMonth = mktime(0, 0, 0, date("m")-($i-1), 0, date("y"));
		//printf("First day of the month %s<br>", date("m/d/y", $firstDayOfMonth));
		//printf("Last day of the month %s<br>", date("m/d/y", $lastDayOfMonth));
		
		$perGIR = getPercentGreensInRegulation($uid, date("Ymd", $firstDayOfMonth), date("Ymd", $lastDayOfMonth));
		if ( $perGIR > 0 )
		{
			$theArray[date("M y", $firstDayOfMonth)]=$perGIR;
			$monthCount++;
		}
		$i++;
	}
	return array_reverse($theArray);
	
	/*
	$theArray = array();
	for ($i=0; $i<12; $i++)
	{
		$firstDayOfMonth = mktime(0, 0, 0, date("m")-$i, 1, date("y"));
		$lastDayOfMonth = mktime(0, 0, 0, date("m")-($i-1), 0, date("y"));
		//printf("First day of the month %s<br>", date("m/d/y", $firstDayOfMonth));
		//printf("Last day of the month %s<br>", date("m/d/y", $lastDayOfMonth));
		//$theArray[date("M", $firstDayOfMonth)]=getPercentGreensInRegulation($uid, date("Ymd", $firstDayOfMonth), date("Ymd", $lastDayOfMonth));
		
		$perGIR = getPercentGreensInRegulation($uid, date("Ymd", $firstDayOfMonth), date("Ymd", $lastDayOfMonth));
		if ( $perGIR > 0 )
			$theArray[date("M y", $firstDayOfMonth)]=$perGIR;
	}
	return array_reverse($theArray);
	*/
}


function DisplayQuickStats($uid)
{
	printf("<table border=\"0\" cellspacing=\"0\" cellpadding=\"5\">");

	printf("<tr class=\"CourseList1\"><td>");	
	$minScore = getMinScore($uid);
	if (strlen($minScore) == 0)
		$minScore = "N/A";
	printf("Best Score:  %s<br>", $minScore);
	printf("</td></tr>");	
	
	printf("<tr class=\"CourseList2\"><td>");
	$avgScore = getAvgScore($uid);
	if ($avgScore <= 0)
		$avgScore = "N/A";
	printf("Average Score:  %s<br>", $avgScore);
	printf("</td></tr>");	
	
	printf("<tr class=\"CourseList1\"><td>");
	$maxScore = getMaxScore($uid);
	if (strlen($maxScore) == 0)
		$maxScore = "N/A";
	printf("Worst Score:  %s<br>", $maxScore);
	printf("</td></tr>");	
	
	printf("<tr class=\"CourseList2\"><td>");
	$avgPutts = getAvgPuttsPerGreen($uid);
	if ($avgPutts <= 0)
		$avgPutts = "N/A";
	printf("Avg Putts Per Green:  %s<br>", $avgPutts);
	printf("</td></tr>");	
	
	printf("<tr class=\"CourseList1\"><td>");
	$fairways = getPercentFairwaysHit($uid);
	if ($fairways <= 0)
		$fairways = "N/A";
	else
		$fairways .= "%";
	printf("Percent fairways hit:  %s<br>", $fairways);
	printf("</td></tr>");	
	
	printf("<tr class=\"CourseList2\"><td>");
	$greens = getPercentGreensInRegulation($uid);
	if ($greens <= 0)
		$greens = "N/A";
	else
		$greens .= "%";
	printf("Percent greens in regulation:  %s<br>", $greens);
	printf("</td></tr>");	
	
	printf("<tr class=\"CourseList1\"><td>");
	$penalties = getPenaltiesPerNineHoles($uid);
	if ($penalties <= 0)
		$penalties = "N/A";
	printf("Penalties per 9 holes:  %s<br>", $penalties);
	printf("</td></tr>");	
	
	printf("</table>");
}




function getMaxArrayValue($arr)
{
	$count = count($arr);
	if ($count == 0)
		return "empty";
	$max = 0;
	for ($i = 0; $i < $count; $i++)
	{
		if ( $i == 0 )
			$max = current($arr);
		else if (current($arr) > $max)
			$max = current($arr);
		next($arr);
	}
	return $max;
}

function getMinArrayValue($arr)
{
	$count = count($arr);
	if ($count == 0)
		return "empty";
	$min = 0;
	for ($i = 0; $i < $count; $i++)
	{
		if ( $i == 0 )
			$min = current($arr);
		else if (current($arr) < $min)
			$min = current($arr);
		next($arr);
	}
	return $min;
}

function setRangeValues(&$minVal, &$maxVal, $percent)
{
	// MAKE IT X PERCENT SMALLER
	$minVal *= (1 - $percent);
	
	// DROP THE DECIMAL POINTS
	$minVal = floor($minVal);
	
	// MAKE IT X PERCENT BIGGER
	$maxVal *= (1 + $percent);
	
	// DROP THE DECIMAL POINTS
	$maxVal = ceil($maxVal);
}

function updateEmail($userId, $email)
{
	$sql = "update user set email = '$email' where id = '$userId'";
	$result = mysql_query($sql) or die("Could not update account with new email: " . mysql_error());;
}

function getConfigValue( $configName )
{
	$sql = "select * from config_tbl where name = '";
	$sql .= $configName;
	$sql .= "'";
	
	$result = mysql_query($sql) or die("Could not get config value:  " . mysql_error());
	$row = mysql_fetch_array($result);
	if ( mysql_num_rows($result) == 0 )
		die("Could not get config value.");
	else
		return $row["value"];
}


//
//  THIS FUNCTION IS SO YOU CAN HAVE A CONFIG NAME LIKE "ADMIN_USERS" WITH 
//  A BUNCH OF DIFFERENT VALID VALUES.  IN EFFECT A GROUP.
//
function inConfigName( $configName, $configValue )
{
	$sql = "select * from config_tbl where name = '$configName' and value = '$configValue'";
	
	$result = mysql_query($sql) or die("Could not get config value:  " . mysql_error());
	if ( mysql_num_rows($result) == 0 )
		return 0;
	return 1;
}

function SendEmail($to, $subject, $message)
{
	$headers = "From: garthmcgraw@StatGolf.com\r\n";
	mail($to, $subject, $message, $headers);
}

function PrintGiftCertificate($gc)
{
?>
			<h1>Gift Certificate</h1> 
			<div id="paragraphtext">
			<b>THANK YOU FOR YOUR GIFT CERTIFICATE PURCHASE</b><br><br>
			
			This certificate is good for a 1 year membership to StatGolf, the premier golf handicap calculator and
			game analysis tool on the web.<br><br>
			
			Please print this page and present it to the recipient<br><br>
			
			<?printf("Use the following gift code when signing up or renewing an existing membership at <a href=\"www.statgolf.com\">www.statgolf.com</a>:  <b>%s</b>  ", $_GET['gc']);?>
			<br><br>We appreciate your business and hope you enjoy StatGolf.  If you have
			any questions or concerns please drop us a line at
			<a href="mailto:customercare@statgolf.com">customercare@statgolf.com</a>.
			</div>
<?
}

function getNumScores()
{
	$sql = "select * from score_tbl";
	
	$result = mysql_query($sql) or die("Could not get scores:  " . mysql_error());
	return mysql_num_rows($result);
}

function getNumScoresSince( $days )
{
	$sql = "select * from score_tbl where createdt >= CURDATE() - INTERVAL $days day;";
	
	$result = mysql_query($sql) or die("Could not get scores:  " . mysql_error());
	return mysql_num_rows($result);
}

function getNumScoresYesterday()
{
	$sql = "select * from score_tbl where createdt > CURDATE() - INTERVAL 1 day and createdt < CURDATE()";
	
	$result = mysql_query($sql) or die("Could not get scores:  " . mysql_error());
	return mysql_num_rows($result);
}

function getNumUsersSince( $days )
{
	$sql = "select * from user where createdt >= CURDATE() - INTERVAL $days day;";
	
	$result = mysql_query($sql) or die("Could not get users:  " . mysql_error());
	return mysql_num_rows($result);
}

function getNumUsersYesterday()
{
	$sql = "select * from user where createdt > CURDATE() - INTERVAL 1 day and createdt < CURDATE()";
	
	$result = mysql_query($sql) or die("Could not get users:  " . mysql_error());
	return mysql_num_rows($result);
}

function getNumUsers()
{
	$sql = "select * from user";
	
	$result = mysql_query($sql) or die("Could not get users:  " . mysql_error());
	return mysql_num_rows($result);
}

function getNumCourses()
{
	$sql = "select * from course_tbl;";
	
	$result = mysql_query($sql) or die("Could not get courses:  " . mysql_error());
	return mysql_num_rows($result);
}

function getNumCoursesSince( $days )
{
	$sql = "select * from course_tbl where createdt >= CURDATE() - INTERVAL $days day;";
	
	$result = mysql_query($sql) or die("Could not get users:  " . mysql_error());
	return mysql_num_rows($result);
}

function getNumCoursesYesterday()
{
 	$sql = "select * from course_tbl where createdt > CURDATE() - INTERVAL 1 day and createdt < CURDATE()";
	
	$result = mysql_query($sql) or die("Could not get users:  " . mysql_error());
	return mysql_num_rows($result);
}


function getUsersAndNumScores()
{
	$sql = "select ut.email as email, count(score) as num_scores, date_format(max(st.createdt), '%m/%d/%Y') as last_score, date_format(ut.createdt, '%m/%d/%Y') as acct_create_dt from user ut, score_tbl st where ut.id = st.userid group by ut.id order by num_scores desc limit 20";
	
	$result = mysql_query($sql) or die("Could not get Users and Num Scores:  " . mysql_error());
	if ( mysql_num_rows($result) == 0 )
		return 0;
	return $result;
}

function getStatesAndNumCourses()
{
	//$sql = "select s.name state, count(*) num_courses from state_tbl s, (select upper(name) name, upper(state) state  from course_tbl  group by name) c where (UPPER(s.abbr) = UPPER(c.state) or UPPER(s.name) = UPPER(c.state)) group by s.name order by s.name asc";
    $sql = "select s.name state, count(*) num_courses from state_tbl s, (select upper(name) name, upper(state) state  from course_tbl  group by name) c where (UPPER(s.abbr) = UPPER(c.state) or UPPER(s.name) = UPPER(c.state)) group by s.name order by num_courses desc";
	
	$result = mysql_query($sql) or die("Could not get States and Num Courses:  " . mysql_error());
	if ( mysql_num_rows($result) == 0 )
		return 0;
	return $result;
}

//
//  If you ever get stored procs, use this function.
//
/*
function CopyCourse($CID, $UID)
{
	$sql = "call CopyCourse($CID, $UID)";
	mysql_query($sql) or die("Could not get copy course:  " . mysql_error());
}
*/
function CopyCourse($CID, $UID)
{
	//
	//  COPY THE COURSE
	//
	$sql = "select * from course_tbl where id = $CID";
	//echo $sql;
	$result = mysql_query($sql) or die("Could not get course " . mysql_error());
	$row = mysql_fetch_array($result);
	$coursename = $row["name"];
	$coursecity = $row["city"];
	$coursestate = $row["state"];
	$insert = "insert into course_tbl (userid, name, city, state) values ($UID, '$coursename', '$coursecity', '$coursestate')";	
	//echo $insert;
	mysql_query($insert) or die("Could not copy course:  " . mysql_error());
	
	//
	//  COPY THE ASSOCIATED TEES
	//
	$sql = "select * from tee_tbl where courseid = $CID";
	//echo $sql;
	$result = mysql_query($sql) or die("Could not get tees " . mysql_error());
	$courseid = mysql_insert_id();
	while($row = mysql_fetch_array($result))
	{
		$name = $row['name']; 
		$slope = $row['slope']; 
		$rating = $row['rating'];
		$par1 = $row['par1'];
		$par2 = $row['par2'];
		$par3 = $row['par3']; 
		$par4 = $row['par4']; 
		$par5 = $row['par5']; 
		$par6 = $row['par6']; 
		$par7 = $row['par7']; 
		$par8 = $row['par8']; 
		$par9 = $row['par9']; 
		$par10 = $row['par10']; 
		$par11 = $row['par11']; 
		$par12 = $row['par12']; 
		$par13 = $row['par13']; 
		$par14 = $row['par14']; 
		$par15 = $row['par15']; 
		$par16 = $row['par16']; 
		$par17 = $row['par17']; 
		$par18 = $row['par18'];
		if (is_null($row['par1']))
			$par1 = "null";
		if (is_null($row['par2']))
			$par2 = "null";
		if (is_null($row['par3']))
			$par3 = "null";
		if (is_null($row['par4']))
			$par4 = "null";
		if (is_null($row['par5']))
			$par5 = "null";
		if (is_null($row['par6']))
			$par6 = "null";
		if (is_null($row['par7']))
			$par7 = "null";
		if (is_null($row['par8']))
			$par8 = "null";
		if (is_null($row['par9']))
			$par9 = "null";
		if (is_null($row['par10']))
			$par10 = "null";
		if (is_null($row['par11']))
			$par11 = "null";
		if (is_null($row['par12']))
			$par12 = "null";
		if (is_null($row['par13']))
			$par13 = "null";
		if (is_null($row['par14']))
			$par14 = "null";
		if (is_null($row['par15']))
			$par15 = "null";
		if (is_null($row['par16']))
			$par16 = "null";
		if (is_null($row['par17']))
			$par17 = "null";
		if (is_null($row['par18']))
			$par18 = "null";
		$insert = "INSERT INTO tee_tbl (courseid, name, slope, rating, par1, par2, par3, par4, par5, par6, par7, par8, par9, par10, par11, par12, par13, par14, par15, par16, par17, par18) VALUES($courseid, '$name', $slope, $rating, $par1, $par2, $par3, $par4, $par5, $par6, $par7, $par8, $par9, $par10, $par11, $par12, $par13, $par14, $par15, $par16, $par17, $par18)";	
		//echo $insert;
		mysql_query($insert) or die("Could not copy tees:  " . mysql_error());	
	}
	
}

function GetMostRecentScores($num)
{
	ConnectToDB();
	$sql = "select date_format(s.dateplayed,'%b %D') dt, s.score score, c.name coursename, u.fname firstname, st.abbr state from score_tbl s, tee_tbl t, course_tbl c, user u, state_tbl st where s.teeid = t.id and t.courseid = c.id and s.userid = u.id and ((upper(c.state) = upper(st.name)) or (upper(c.state) = upper(st.abbr))) order by s.dateplayed desc, s.id desc limit $num";
	$result = mysql_query($sql) or die("Could not get scores " . mysql_error());
	return $result;
}





















?>
