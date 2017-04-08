<?php
include('includes/classes.php');

session_start();

// Protect against redirect URL that is wide open
if (isset($_POST['dst'])) {
	if (stripos($_POST['dst'], "http") !== false) {
	} else { $dst = $_POST['dst']; }

}

if (isset($_GET['dst'])) {
	if (stripos($_GET['dst'], "http") !== false) {
	} else { $dst = $_GET['dst']; }
}

$dst = strip_tags($dst);

$conn = openDB();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        if ($_GET['error'] == "failed") {$errortext="Authentication Failure!";}
        $htmlerror = "";
        $html =  file_get_contents("includes/loginform.html");
	if  ( isset($_GET['error'])) {
		echo ("		<div class=\"alert alert-danger\" role=\"alert\">");
		echo ("			<center><strong>Oh snap!</strong> ".$errortext."</center>");
		echo ("		</div>");
        }
        $html = str_replace("{{js}}",file_get_contents("includes/modaljava.js"), $html);

        $html = str_replace("{{dst}}", $dst, $html);
        echo $html;

}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	if( isset($_POST['uname']) && isset($_POST['psw']) )
	{
		$query = $conn->prepare("SELECT id,userName,password,agencyID,userPermissions FROM users WHERE userName = ?");
		$query->bind_param('s', $_POST['uname']);
		$query->execute();
		$result = $query->get_result();
	        $row = $result->fetch_assoc();

		if (password_verify(base64_encode(hash('sha256', $_POST['psw'], true)),$row['password'])) {

	                $licenseQuery = $conn->prepare("SELECT id,purchasedClaimApp,purchasedSurveyApp,purchasedMapApp FROM agencies WHERE id = ?");
	                $licenseQuery->bind_param('i', $row['agencyID']);
	                $licenseQuery->execute();
	                $licenseResult = $licenseQuery->get_result();
	                $license = $licenseResult->fetch_assoc();

			$_SESSION['user'] = $_POST['uname'];
			$_SESSION['agency'] = $row['agencyID'];
			$_SESSION['perms'] = $row['userPermissions'];
			$_SESSION['licensed'] = $license;

			mysqli_query($conn,"UPDATE users SET lastLogin='".time()."' WHERE id=".$row['id']);
			$conn->close();
			header( "Location: ".$dst);
		} else {
			$conn->close();
			header( "Location: account_login.php?error=failed&dst=".$dst);
		}
	} else {
		$conn->close();
		header( "Location: account_login.php?error=failed&dst=".$dst);
	}

$conn->close();

}


//$stored = password_hash(
//    base64_encode(hash('sha256', "test", true)),PASSWORD_DEFAULT);
//echo $stored."\r\n";


?>
