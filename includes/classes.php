<?php
function openDB()
{
	// Database config settings
	// CREATE DATABASE planetcr_mainapp;
	// GRANT ALL PRIVILEGES ON planetcr_mainapp.* to planetcr_mainappusr@localhost identified by 'planetcr';
	// FLUSH PRIVILEGES;
	// USE planetcr_mainapp;
	// CREATE TABLE agencies (ID int(11) AUTO_INCREMENT,agencyName varchar(64) NOT NULL,agencyPhone varchar(64) NOT NULL,agencyEmail varchar(64) NOT NULL, \
	//          agencyCellCarrier int(2),agencyCellPhone varchar(64), purchasedClaimApp int(2), purchasedSurveyApp int(2), purchasedMapApp int(2), PRIMARY KEY  (ID));

	// INSERT INTO agencies (agencyName, agencyPhone, agencyEmail, agencyCellCarrier, agencyCellPhone, purchasedClaimApp, purchasedSurveyApp, purchasedMapApp) VALUES \
	//	    ('Just Testing','319-555-1212','root@planetcr.com','3','3195551212','1','1','1');

	// CREATE TABLE users (ID int(11) AUTO_INCREMENT,userName varchar(64) NOT NULL,password varchar(64) NOT NULL, agencyID int(11) NOT NULL, userEmail varchar(64) NOT NULL, \
	// 	    userPermissions int(16) NOT NULL, previousPassword varchar(64), lastpasswordSet int(16), lastLogin int(16), rememberMeCookie varchar(64), \
	//	    userCellCarrier int(2), userCellPhone varchar(64), PRIMARY KEY  (ID));

	// INSERT INTO users (userName, password, agencyID, userEmail, userPermissions) VALUES \
	//	    ('admin', '$2y$10$VNSTF.bX2nI1wo0/OpITzOL7mXLM7R/wz3Q3cMeTMc6pKlfkVl9wK', '1', 'sgarringer@gmail.com',999);

	$DB_HOST = "localhost";
	$DB_NAME = "app";
	$DB_USER = "usr";
	$DB_PASS = "pass";

	$conn = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
	$conn->set_charset("utf8");

	if($conn->connect_errno > 0) { die('Connection failed [' . $conn->connect_error . ']');}

	if (mysqli_connect_errno()) { die('Connection failed [' .mysqli_connect_error(). ']');}

	return $conn;
}
function generateAppList($current_app, $license)
{
        $mainNavBar = "";
	// Home
        $mainNavBar = $mainNavBar."<li role=\"presentation\" ";
	if($current_app == "Home"){$mainNavBar = $mainNavBar."class=\"active\"";}
	$mainNavBar = $mainNavBar."><a href=\"/mainapp/\">Home</a></li>";

	if ($license['purchasedClaimApp'] == '1') {
	        $mainNavBar = $mainNavBar."<li role=\"presentation\"><a href=\"\">ClaimApp</a></li>";
	}
	if ($license['purchasedSurveyApp'] == '1') {
	        $mainNavBar = $mainNavBar."<li role=\"presentation\"><a href=\"\">SurveyApp</a></li>";
	}
	if ($license['purchasedMapApp'] == '1') {
			$mainNavBar = $mainNavBar."<li role=\"presentation\" ";
	        if($current_app == "MapApp"){$mainNavBar = $mainNavBar."class=\"active\"";}
			$mainNavBar = $mainNavBar."><a href=\"mapapp.php\">MapApp</a></li>";
	}

	return $mainNavBar;
}
function generateAcctList($current_acct, $account_perms)
{
        $rightNavBar = "";
        if($account_perms == 999) { $rightNavBar = $rightNavBar."<li><a href=\"admin_portal.php\">Admin Portal</a>"; }
	$rightNavBar = $rightNavBar."<li ";
        if($current_acct == "Settings"){$rightNavBar = $rightNavBar." class=\"active\" ";}
	$rightNavBar = $rightNavBar."><li><a href=\"account_settings.php\">Account Settings</a></li>";
        $rightNavBar = $rightNavBar."<li><a href=\"account_logout.php\">Log Off</a></li>";

	return $rightNavBar;
}

function generateHTML($account_perms, $license, $current_menu, $pageHTML){
        $html =  file_get_contents("includes/mainpage.html");
        $html = str_replace("{{navbar-nav}}",generateAppList($current_menu,$license), $html);
        $html = str_replace("{{navbar-right}}",generateAcctList($current_menu,$account_perms), $html);
        $html = str_replace("{{jumbotron}}",$pageHTML, $html);

	return $html;
}
