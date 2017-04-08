<?php
include('includes/classes.php');

// CHECK TO SEE IF USER IS LOGGED IN THIS BLOCK MUST BE IN EVER PAGE ****
session_start();
session_regenerate_id();

if(!isset($_SESSION['user']))      // if there is no valid session
{
    header("Location: account_login.php?dst=".$_SERVER[REQUEST_URI]);
}
// **********************************************************************

$conn = openDB();

// DEBUGGING
$agency = 1;
//

$userQuery = $conn->prepare("SELECT id,userName,userEmail,password,previousPassword,userCellCarrier,userCellPhone FROM users WHERE userName=?");
$userQuery->bind_param("s",$_SESSION['user']);
$userQuery->execute();
$userResult = $userQuery->get_result();
$userRow = $userResult->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
	$refererPath = parse_url($_SERVER["HTTP_REFERER"],PHP_URL_PATH);
	if ($refererPath != "/mainapp/account_settings.php") {
		$_SESSION['previousURL'] = $_SERVER["HTTP_REFERER"];
	}

        $jumbotron = "";
        $jumbotron =  file_get_contents("includes/accountform.html");

        $jumbotron = str_replace("{{uname}}",$_SESSION['user'], $jumbotron);
        $jumbotron = str_replace("{{email}}",$userRow['userEmail'], $jumbotron);
        $jumbotron = str_replace("{{sms_phonenumber}}",$userRow['userCellPhone'], $jumbotron);



        echo generateHTML($_SESSION['perms'],$_SESSION['licensed'],"Settings",$jumbotron);

}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

$userQuery = $conn->prepare("SELECT id,userName,userEmail,password,previousPassword,userCellCarrier,userCellPhone FROM users WHERE userName=?");
$userQuery->bind_param("s",$_SESSION['user']);
$userQuery->execute();
$userResult = $userQuery->get_result();
$user = $userResult->fetch_assoc();

if ($_POST['Submit'] == "Cancel") {
	if ($_SESSION['previousURL'] == "" ){$_SESSION['previousURL'] = "/mainapp/";}
	header( "Location: ".$_SESSION['previousURL']);
	$conn->close();
	exit();

	}

$msg = "";
if ($_POST['email'] != $user['userEmail']){
	$query2 = $conn->prepare("UPDATE users SET userEmail=? WHERE userName=?");
	$query2->bind_param('ss', $_POST['email'], $_SESSION['user']);
	$query2->execute();
	$msg = $msg."?e=success&";
}


if($_POST['password1'] != "password"){

	if ($_POST['password1'] == $_POST['password2']){

		$newpwhash = password_hash(base64_encode(hash('sha256', $_POST['password1'], true)),PASSWORD_DEFAULT);

		if (password_verify(base64_encode(hash('sha256', $_POST['password0'], true)),$user['password'])) {

			if (!(password_verify(base64_encode(hash('sha256', $_POST['password1'], true)),$user['previousPassword']))) {
				$query2 = $conn->prepare("UPDATE users SET password=?, previousPassword=?, lastpasswordSet=? WHERE userName=?");
	                        $query2->bind_param('ssss', $newpwhash, $user['password'],time(),$_SESSION[user]);
	                        $query2->execute();
				$msg = $msg."?p=success";
			} else {
				$conn->close();
				$msg = $msg."?p=oldpw";
			}

		} else {
	                $conn->close();
			$msg = $msg."?p=previouspw";
               	}
	}
	else {
	       $conn->close();
        	$msg = $msg."?p=mismatch";
	}
}

header( "Location: account_settings.php".$msg);

}// END POST IF
$conn->close();
