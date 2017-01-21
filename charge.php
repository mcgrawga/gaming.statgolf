<?php
  //require_once(dirname(__FILE__) . '/config.php');

	include 'functions.php';
	ConnectToDB();

        //
        //  FORM VALIDATION
        //

        if ( !IsEmailValid($_POST['email']) )
        {
                printf("The email you have choosen invalid.  Please choose another email.");
 //               printf("<br>Your card was not charged.  Hit the back button a couple of times and correct.");
                DisplayCommonFooter();
                return;
        }
        if ( userPreExists($_POST['email']) )
        {
                printf("The email you have choosen is already in use, please choose another email.");
  //              printf("<br>Your card was not charged.  Hit the back button a couple of times and correct.");
                DisplayCommonFooter();
                return;
        }
        if ( !IsPasswordValid($_POST['password']) )
        {
                printf("The password you have choosen invalid.  Please choose another password.");
   //             printf("<br>Your card was not charged.  Hit the back button a couple of times and correct.");
                DisplayCommonFooter();
                return;
        }
        if ( !IsCPasswordValid($_POST['confirmpassword']) )
        {
                printf("The confirmation password you have choosen invalid.  Please choose another password.");
    //            printf("<br>Your card was not charged.  Hit the back button a couple of times and correct.");
                DisplayCommonFooter();
                return;
        }
        if ( $_POST['password'] != $_POST['confirmpassword'] )
        {
                printf("Passwords do not match.");
     //           printf("<br>Your card was not charged.  Hit the back button a couple of times and correct.");
                DisplayCommonFooter();
                return;
        }

        $sql = "insert into user (password, email) values ('";
        $sql .= $_POST['password'];
        $sql .= "', '";
        $sql .= $_POST['email'];
        $sql .= "')";
        //printf("%s", $sql);
        mysql_query($sql) or die("Could not create the new user account: " . mysql_error());

/*
  $token  = $_POST['stripeToken'];

  $customer = Stripe_Customer::create(array(
      'email' => $_POST['email'],
      'card'  => $token
  ));

  $charge = Stripe_Charge::create(array(
      'customer' => $customer->id,
      'amount'   => 500,
      'currency' => 'usd'
  ));

*/




  printf("Account Creation Successful.  <br>Click <a href=\"index.php\">here</a> to log in.");
?>
