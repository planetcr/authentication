<?php
session_start();
session_regenerate_id();
if(!isset($_SESSION['user']))      // if there is no valid session
{
    header("Location: login.php?dst=".$_SERVER[REQUEST_URI]);
}

// Database config settings
$DB_HOST = "localhost";
$DB_NAME = "securecloudbackup";
$DB_USER = "cloudbackup";
$DB_PASS = "cloudbackup";

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

//if (isset($_GET['dst']) && strpos($_GET['dst'], "http://") !== false) { dst = $_POST['dst'] }

$conn = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
$conn->set_charset("utf8");
if($conn->connect_errno > 0) {
  die('Connection failed [' . $conn->connect_error . ']');
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
	if ($_GET['error'] == "incorrectoldpw") {$errortext="Old Password is Incorrect!";}
	if ($_GET['error'] == "newpwmismatch") {$errortext="New Password Mismatch!";}
	$htmlerror = "";
	$html =  file_get_contents("includes/changepw.html");

if  ( isset($_GET['error'])) {
	$htmlerror = str_replace("{{error}}",$errortext, file_get_contents("includes/modalhtml.html"));
	echo "$htmlerror";
	}
	$html = str_replace("{{js}}",file_get_contents("includes/modaljava.js"), $html);

//	$html = str_replace("{{instmodal}}", $htmlerror, $html);
	$html = str_replace("{{dst}}", $dst, $html);
        echo $html;

}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	if( isset($_POST['oldpw']) && isset($_POST['newpsw']) && isset($_POST['newpswvfy']) )
	{
		$newpwhash = password_hash(base64_encode(hash('sha256', $_POST['newpsw'], true)),PASSWORD_DEFAULT);

		$query = $conn->prepare("SELECT id,user,password,level FROM BACKUP_USERS WHERE user = ?");
		$query->bind_param('s', $_SESSION['user']);
		$query->execute();
		$result = $query->get_result();
	        $row = $result->fetch_assoc();
		$id = intval($row[id]);
		if ($_POST['newpsw'] == $_POST['newpswvfy']){
			if (password_verify(base64_encode(hash('sha256', $_POST['oldpw'], true)),$row['password'])) {

				$query2 = $conn->prepare("UPDATE BACKUP_USERS SET password=? WHERE id=?");
				$query2->bind_param('si', $newpwhash, $id);
				$query2->execute();
//				echo $newpwhash." ,".$id;
				header( "Location: ".$dst);
			} else {
				$conn->close();
				header( "Location: changepw.php?error=incorrectoldpw&dst=".$dst);
			}
		
		} else {
			$conn->close();
//			echo "1".$_POST['newpsw']." 2".$_POST['newpswvfy'];
			header( "Location: changepw.php?error=newpwmismatch&dst=".$dst);
		}
	} 
$conn->close();

}


//$stored = password_hash(
//    base64_encode(hash('sha256', "test", true)),PASSWORD_DEFAULT);
//echo $stored."\r\n";


?>
