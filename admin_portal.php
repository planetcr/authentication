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

	if (!isset($_GET['target'])){$target="user";} else {$target=$_GET['target'];}
	$refererPath = parse_url($_SERVER["HTTP_REFERER"],PHP_URL_PATH);
	if ($refererPath != "/mainapp/admin_portal.php") {
		$_SESSION['previousURL'] = $_SERVER["HTTP_REFERER"];
	}

	$agencyHTML = "";
	$agencyListHTML = "";
	$agencyQuery = $conn->prepare("SELECT agencyName,id FROM agencies");
	$agencyQuery->execute();
	$agencyQuery->bind_result($agencyName, $id);

	while ($agencyQuery->fetch())
	{
		$agencyHTML = $agencyHTML."  <option value=\"".$id."\">".$agencyName."</option>\r\n";
		$agencyListHTML = $agencyListHTML."<tr><td><a href=\"admin_modifyagency.php?agency=".$id."\">".$id."</a></td><td>".$agencyName."</td></tr>\r\n";
	}

        $jumbotron = "";
	if ($target == "user"){
		$userHTML = "";

	$ListUserQuery = $conn->prepare("SELECT users.userName, users.userEmail, users.id, users.agencyID, agencies.agencyName FROM users INNER JOIN agencies ON agencies.ID = users.agencyID ORDER BY users.id");


	if ($_GET['sort']=="agency") {
		$ListUserQuery = $conn->prepare("SELECT users.userName, users.userEmail, users.id, users.agencyID, agencies.agencyName FROM users INNER JOIN agencies ON agencies.ID = users.agencyID ORDER BY users.agencyID");
	}

	if ($_GET['sort']=="username") {
		$ListUserQuery = $conn->prepare("SELECT users.userName, users.userEmail, users.id, users.agencyID, agencies.agencyName FROM users INNER JOIN agencies ON agencies.ID = users.agencyID ORDER BY users.userName");
	}

	if ($_GET['sort']=="email") {
		$ListUserQuery = $conn->prepare("SELECT users.userName, users.userEmail, users.id, users.agencyID, agencies.agencyName FROM users INNER JOIN agencies ON agencies.ID = users.agencyID ORDER BY users.userEmail");
	}

	$ListUserQuery->execute();
	$ListUserQuery->bind_result($listUserName, $listuserEmail, $listID, $listagencyID, $listagencyName);
	while ($ListUserQuery->fetch())
	{
		
		$userHTML = $userHTML."<tr><td><a href=\"admin_modifyuser.php?user=".$listUserName."\">".$listID."</a></td><td>".$listagencyName."</td><td>".$listUserName."</td><td>".$listuserEmail."</td></tr>\r\n";
	}


	        $jumbotron =  file_get_contents("includes/adminform_user.html");
	 	$jumbotron = str_replace("{{agencies}}",$agencyHTML, $jumbotron);
//	        $jumbotron = str_replace("{{email}}",$userRow['userEmail'], $jumbotron);
//	        $jumbotron = str_replace("{{sms_phonenumber}}",$userRow['userCellPhone'], $jumbotron);
	        $jumbotron = str_replace("{{modify_user_table}}",$userHTML, $jumbotron);
	}

	if ($target == "agency"){
	        $jumbotron =  file_get_contents("includes/adminform_agency.html");
	 	$jumbotron = str_replace("{{agencies}}",$agencyHTML, $jumbotron);
	        $jumbotron = str_replace("{{email}}",$userRow['userEmail'], $jumbotron);
	        $jumbotron = str_replace("{{sms_phonenumber}}",$userRow['userCellPhone'], $jumbotron);
	        $jumbotron = str_replace("{{modify_agency_table}}",$agencyListHTML, $jumbotron);
	}


        echo generateHTML($_SESSION['perms'],$_SESSION['licensed'],"Settings",$jumbotron);

}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

$msg = "";

if ($_POST['Submit'] == "Cancel") {
	if ($_SESSION['previousURL'] == "" ){$_SESSION['previousURL'] = "/mainapp/";}
	header( "Location: ".$_SESSION['previousURL']);
	$conn->close();
	exit();
	}

if ($_POST['Submit'] == "Search-ModifyUser") {
	header( "Location: admin_modifyuser.php?user=".$_POST['username']);
	$conn->close();
	exit();
	}

if ($_POST['Submit'] == "Save-NewAgency") {

	$emailQuery = $conn->prepare("SELECT id,agencyEmail FROM agencies WHERE agencyEmail=?");
	$emailQuery->bind_param("s",$_POST['email']);
	$emailQuery->execute();
	$emailQuery->store_result();

	if ($_POST['agencyname'] == "") {$msg = $msg."?a=agencyname&";}
	if ($_POST['email'] == "") {$msg = $msg."?a=email&";}
	if (($emailQuery->num_rows) > 0) {$msg = $msg."?a=emailexists&";}
	if ($_POST['contactus_phone'] == "") {$msg = $msg."?a=contactus_phone&";}
	if ($_POST['sms_phone'] == "") {$msg = $msg."?a=sms_phone&";}
	if ($_POST['sms_network'] < 0 || $_POST['sms_network'] > 999) {$msg = $msg."?a=sms_network&";}

	if ($msg == "")  // No errors
		{
		$emailQuery = $conn->prepare("INSERT INTO agencies (agencyName, agencyPhone, agencyEmail, agencyCellCarrier, agencyCellPhone, purchasedClaimApp, purchasedSurveyApp, purchasedMapApp) VALUES (?, ?, ?, ?, ?, ?, ?, ?);");
 		$emailQuery->bind_param("sssisiii",$_POST['agencyname'], $_POST['contactus_phone'], $_POST['email'], $_POST['sms_network'], $_POST['sms_phone'],$_POST['claimapplicense'],$_POST['surveyapplicense'],$_POST['mapapplicense']);
		echo $emailQuery->error_list;
		$emailQuery->execute();
		$msg=$msg."?d=success";
		header( "Location: admin_portal.php".$msg);
		}
	header( "Location: admin_portal.php".$msg);

}

if ($_POST['Submit'] == "Save-NewUser") {
	$userQuery = $conn->prepare("SELECT id,userName FROM users WHERE userName=?");
	$userQuery->bind_param("s",$_POST['username']);
	$userQuery->execute();
	$userQuery->store_result();

	$emailQuery = $conn->prepare("SELECT id,userName,userEmail FROM users WHERE userEmail=?");
	$emailQuery->bind_param("s",$_POST['email']);
	$emailQuery->execute();
	$emailQuery->store_result();

	if (($userQuery->num_rows) > 0) {$msg = $msg."?u=userexists&";}
	if (($emailQuery->num_rows) > 0) {$msg = $msg."?u=emailexists&";}
	if ($_POST['password1'] == "" || $_POST['password1'] == "password") {$msg = $msg."?p=badpassword&";}

	if ($msg == "") { // No errors
		{
		$newpwhash = password_hash(base64_encode(hash('sha256', $_POST['password1'], true)),PASSWORD_DEFAULT);
		$emailQuery = $conn->prepare("INSERT INTO users (userName,password,agencyID,userEmail,userPermissions) VALUES (?, ?, ?, ?, ?);");
 		$emailQuery->bind_param("ssisi",$_POST['username'], $newpwhash, $_POST['agency'], $_POST['email'], $_POST['permissions']);
		echo $emailQuery->error_list;
		$emailQuery->execute();

		$msg=$msg."?d=success";
		}

}

header( "Location: admin_portal.php".$msg);


}// END POST IF
$conn->close();

}
