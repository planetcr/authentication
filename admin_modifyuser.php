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
	if ($refererPath != "/mainapp/admin_modifyuser.php") {
		$_SESSION['previousURL'] = $_SERVER["HTTP_REFERER"];
	}

	$userToEdit = $_GET['user'];

	$userQuery = $conn->prepare("SELECT id,userName,userEmail,password,previousPassword,agencyID,userCellCarrier,userCellPhone FROM users WHERE userName=?");
	$userQuery->bind_param("s",$userToEdit);
	$userQuery->execute();
	$userResult = $userQuery->get_result();
	$user = $userResult->fetch_assoc();

        $agencyHTML = "";
        $agencyQuery = $conn->prepare("SELECT agencyName,id FROM agencies");
        $agencyQuery->execute();
        $agencyQuery->bind_result($agencyName, $id);

        while ($agencyQuery->fetch())
        {
		$defaultHTML = "";

		if ($id == $user['agencyID']) { $defaultHTML = "selected=\"selected\"";}

                $agencyHTML = $agencyHTML."  <option value=\"".$id."\" ".$defaultHTML.">".$agencyName."</option>\r\n";
        }

        $jumbotron = "";
        $jumbotron =  file_get_contents("includes/modifyuser.html");


        $jumbotron = str_replace("{{agencies}}",$agencyHTML, $jumbotron);
        $jumbotron = str_replace("{{uname}}",$userToEdit, $jumbotron);
        $jumbotron = str_replace("{{email}}",$user['userEmail'], $jumbotron);
        $jumbotron = str_replace("{{sms_phonenumber}}",$userRow['userCellPhone'], $jumbotron);



        echo generateHTML($_SESSION['perms'],$_SESSION['licensed'],"Settings",$jumbotron);

}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

$userQuery = $conn->prepare("SELECT id,userName,userEmail,password,previousPassword,agencyID,userCellCarrier,userCellPhone FROM users WHERE userName=?");
$userQuery->bind_param("s",$_POST['username']);
$userQuery->execute();
$userResult = $userQuery->get_result();
$user = $userResult->fetch_assoc();

$msg="?user=".$_POST['username'];

if ($_POST['Submit'] == "Cancel") {
	if ($_SESSION['previousURL'] == "" ){$_SESSION['previousURL'] = "/mainapp/";}
	header( "Location: ".$_SESSION['previousURL']);
	$conn->close();
	exit();

	}

if ($_POST['Submit'] == "Delete") {
	$deleteUserQuery = $conn->prepare("DELETE FROM users WHERE userName=?");
	$deleteUserQuery->bind_param("s",$_POST['username']);
	$deleteUserQuery->execute();

	if ($_SESSION['previousURL'] == "" ){$_SESSION['previousURL'] = "/mainapp/";}
	header( "Location: ".$_SESSION['previousURL']);
	$conn->close();
	exit();

	}

if ($_POST['email'] != $user['userEmail']){
	$query2 = $conn->prepare("UPDATE users SET userEmail=? WHERE userName=?");
	$query2->bind_param('ss', $_POST['email'], $_POST['username']);
	$query2->execute();
	$msg = $msg."&e=success";
}

if ($_POST['agency'] != $user['agencyID']){
	$query2 = $conn->prepare("UPDATE users SET agencyID=? WHERE userName=?");
	$query2->bind_param('ss', $_POST['agency'], $_POST['username']);
	$query2->execute();
	$msg = $msg."&a=success";
}


if($_POST['password1'] != "password"){

	$newpwhash = password_hash(base64_encode(hash('sha256', $_POST['password1'], true)),PASSWORD_DEFAULT);

	$query2 = $conn->prepare("UPDATE users SET password=?, previousPassword=?, lastpasswordSet=? WHERE userName=?");
        $query2->bind_param('ssss', $newpwhash, $user['password'],time(),$_POST['username']);
        $query2->execute();
	$msg = $msg."&p=success";
}

header( "Location: admin_modifyuser.php".$msg);

}// END POST IF
$conn->close();
