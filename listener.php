<?php
DEFINE ( 'XIBO', true );
define ( 'WEBSITE_VERSION', 91 );
error_reporting ( 0 );
ini_set ( 'display_errors', 0 );
ini_set ( 'gd.jpeg_ignore_warning', 1 );

require ("configuration.php");

// Get input data from POST message
$hardwareKey = null;
$key = null;

// ___________________________________________________________________________________
if (isset ( $_POST ['key'] )) // Get key
	$key = $_POST ['key'];
else
	echo "\nCannot get key from POST message";
$layoutKey = $keys [$key];

if (isset ( $_POST ['HK'] ))
	$hardwareKey = $_POST ['HK'];
else
	echo "\nCannot get HardwareKey from POST message";

if ($layoutKey == null)
	echo "\nWrong input key. Please check the POST message format againt!";
elseif (isDisplayingLayout ( $hardwareKey ) == True) {
	echo "\nThe Xibo Client is displaying the campaign: $currentCampaign. There is nothing to do!";
} else { // Else -> Set a new event
         
	// Required Config Files
	require_once ("lib/app/pdoconnect.class.php");
	require_once ("lib/app/translationengine.class.php");
	require_once ("lib/app/debug.class.php");
	require_once ("lib/app/kit.class.php");
	require_once ("lib/app/pagemanager.class.php");
	require_once ("lib/app/menumanager.class.php");
	require_once ("lib/app/modulemanager.class.php");
	require_once ("lib/app/permissionmanager.class.php");
	require_once ("lib/app/formmanager.class.php");
	require_once ("lib/app/helpmanager.class.php");
	require_once ("lib/app/responsemanager.class.php");
	require_once ("lib/app/datemanager.class.php");
	require_once ("lib/app/app_functions.php");
	require_once ("lib/data/data.class.php");
	require_once ("lib/modules/module.interface.php");
	require_once ("lib/modules/modulefactory.class.php");
	require_once ("lib/modules/module.class.php");
	require_once ("lib/app/session.class.php");
	require_once ("lib/app/cache.class.php");
	require_once ("lib/app/thememanager.class.php");
	require_once ("lib/pages/base.class.php");
	require_once ("lib/Helper/Log.php");
	require_once ("lib/Helper/ObjectVars.php");
	require_once ("3rdparty/parsedown/parsedown.php");
	require_once ("3rdparty/jdatetime/jdatetime.class.php");
	
	require_once ("config/config.class.php");
	require_once ("config/db_config.php");
	// Sort out Magic Quotes
	if (get_magic_quotes_gpc ()) {
		function stripslashes_deep($value) {
			$value = is_array ( $value ) ? array_map ( 'stripslashes_deep', $value ) : stripslashes ( $value );
			
			return $value;
		}
		
		$_POST = array_map ( 'stripslashes_deep', $_POST );
		$_GET = array_map ( 'stripslashes_deep', $_GET );
		$_COOKIE = array_map ( 'stripslashes_deep', $_COOKIE );
		$_REQUEST = array_map ( 'stripslashes_deep', $_REQUEST );
	}
	if (! file_exists ( "settings.php" )) {
		Kit::Redirect ( "install.php" );
		die ();
	}
	// __________________________Main code__________________________________
	Config::Load (); // Run Settings.php
	
	/*
	 * Create User and DB object
	 */
	// Test our DB connection through PDO
	try {
		PDOConnect::init ();
	} catch ( PDOException $e ) {
		die ( 'Database connection problem.' );
	}
	// create a database class instance (legacy)
	$db = new database ();
	
	if (! $db->connect_db ( $dbhost, $dbuser, $dbpass )) {
		die ( 'Database connection problem.' );
	}
	
	if (! $db->select_db ( $dbname )) {
		die ( 'Database connection problem.' );
	}
	// ______________________________________________________________
	date_default_timezone_set ( Config::GetSetting ( "defaultTimezone" ) );
	
	// Error Handling (our error handler requires a DB connection
	set_error_handler ( array (
			new Debug (),
			"ErrorHandler" 
	) );
	
	// Define an auto-load function
	spl_autoload_register ( function ($class) {
		Kit::ClassLoader ( $class );
	} );
	
	// Define the VERSION
	Config::Version ();
	// Deal with HTTPS/STS config
	if (Kit::isSSL ()) {
		Kit::IssueStsHeaderIfNecessary ();
	} else {
		if (Config::GetSetting ( 'FORCE_HTTPS', 0 ) == 1) {
			$redirect = "https://" . $_SERVER ['HTTP_HOST'] . $_SERVER ['REQUEST_URI'];
			header ( "Location: $redirect" );
			exit ();
		}
	}
	
	// What is the production mode of the server?
	if (Config::GetSetting ( 'SERVER_MODE' ) == 'Test')
		ini_set ( 'display_errors', 1 );
		// Debugging?
	if (Debug::getLevel ( Config::GetSetting ( 'audit' ) ) == 10)
		error_reporting ( E_ALL );
		
		// Setup the translations for gettext
	TranslationEngine::InitLocale ();
	
	// Create login control system
	require_once ('modules/' . Config::GetSetting ( "userModule" ));
	
	// Page variable set? Otherwise default to index
	$page = Kit::GetParam ( 'p', _REQUEST, _WORD, 'index' );
	$function = Kit::GetParam ( 'q', _REQUEST, _WORD );
	
	// Does the version in the DB match the version of the code?
	// If not then we need to run an upgrade. Change the page variable to upgrade
	if (DBVERSION != WEBSITE_VERSION && ! (($page == 'index' && $function == 'login') || $page == 'error')) {
		require_once ('install/upgradestep.class.php');
		$page = 'upgrade';
		
		if (Kit::GetParam ( 'includes', _POST, _BOOL )) {
			$upgradeFrom = Kit::GetParam ( 'upgradeFrom', _POST, _INT );
			$upgradeTo = Kit::GetParam ( 'upgradeTo', _POST, _INT );
			
			for($i = $upgradeFrom + 1; $i <= $upgradeTo; $i ++) {
				if (file_exists ( 'install/database/' . $i . '.php' )) {
					include_once ('install/database/' . $i . '.php');
				}
			}
		}
	}
	
	// Create a Session
	$session = new Session ();
	
	// Work out the location of this service
	$serviceLocation = Kit::GetXiboRoot ();
	
	// OAuth
	require_once ('lib/oauth.inc.php');
	// Assign the page name to the session
	$session->set_page ( session_id (), $page );
	// Create a user
	$user = new User ( $db );
	require_once ("lib/pages/schedule.class.php");
	
	// ____________________________Main code__________________________________
	$schedule = new scheduleDAO ( $db, $user );
	
	$result = $schedule->querryDB ();
	
	if ($licensed == 0) {
		echo "non-licensed client";
	} elseif ($result ['keyCampaignIDs'] != null) {
		$expiredEventID = $result ['expiredEventID'];
		$keyCampaignIDs = $result ['keyCampaignIDs'];
		
		$schedule->querryMINDisplayOrder ();
		// Schedule a new event

		$underEventIDs = getUnderLayout ();
		$schedule->displayNow ( $keyCampaignIDs, $displayGroupID );
		// Remove expired event from schedule
		if (count ( $expiredEventID ) > 0) {
			$schedule->removeExpiredEvent ( $expiredEventID );
		}
		if (count ( $underEventIDs ) > 0)
			$schedule->removeExpiredEvent ( $underEventIDs );
	}
}
function isDisplayingLayout($hardwareKey) {
	require ("globalVar.php");
	$conn = new mysqli ( $dbhost, $dbuser, $dbpass, $dbname );
	// SQL connection checking
	if ($conn->connect_error) {
		die ( "Connection failed: " . $conn->connect_error );
	}
	isDefaultLayoutDisplaying ( $hardwareKey );
	// __________________________________________________________________________
	// Get MIN display order from Database
	$sql = "SELECT MIN(DisplayOrder) FROM schedule WHERE DisplayGroupIDs='$displayGroupID'";
	$result = $conn->query ( $sql );
	if ($result->num_rows > 0)
		$minOrder = $result->fetch_assoc () ["MIN(DisplayOrder)"];
		
		// Get current displaying layout
	$now = time ();
	$sql = "SELECT CampaignID,FromDT,ToDT,eventID FROM schedule WHERE DisplayGroupIDs='$displayGroupID' AND ToDT > '$now' AND DisplayOrder='$minOrder'";
	$result = $conn->query ( $sql );
	if ($result->num_rows > 0) {
		$row = $result->fetch_assoc ();
		$campaignID = $row ["CampaignID"];
		$sql = "SELECT campaign FROM campaign WHERE campaignID='$campaignID'";
		$result = $conn->query ( $sql );
		if ($result->num_rows > 0) {
			$currentCampaign = $result->fetch_assoc () ["campaign"];
		}
		$conn->close ();
		// Check whether the current layout and the proposed layout is the same?
		if (strpos ( $currentCampaign, $layoutKey ) !== False) {
			// Check whether the current layout is going to end soon. If yes, extent the ToDT in Database
			$now = $now + 100;
			if ($now >= $toDT) {
				$eventID = $row ["eventID"];
				$toDT = $row ["ToDT"];
				$fromDT = $row ["FromDT"];
				$duration = $fromDt + $hours * 3600 + $minutes * 60 + $seconds;
				$sql = "UPDATE schedule SET ToDT='$now+$duration' WHERE eventID='$eventID'";
				$result = $conn->query ( $sql );
				if ($result->num_rows > 0) {
					echo "\nEvent extending completed";
				}
				$sql = "UPDATE schedule_detail SET ToDT='$now+$duration' WHERE eventID='$eventID'";
				$result = $conn->query ( $sql );
			}
			return True;
		} else
			return False;
	} else {
		$conn->close ();
		return False;
	}
}
function isDefaultLayoutDisplaying($hardwareKey) {
	require ("globalVar.php");
	$conn = new mysqli ( $dbhost, $dbuser, $dbpass, $dbname );
	// SQL connection checking
	if ($conn->connect_error) {
		die ( "Connection failed: " . $conn->connect_error );
	}
	// __________________________________________________________________________
	$defaultLayoutID = null;
	$sql = "SELECT display,displayID,defaultlayoutid,licensed FROM display WHERE license='$hardwareKey'";
	$result = $conn->query ( $sql );
	if ($result->num_rows > 0) {
		$row = $result->fetch_assoc ();
		$displayName = $row ["display"];
		$displayID = $row ["displayID"];
		$defaultLayoutID = $row ['defaultlayoutid'];
		$licensed = $row ['licensed'];
		
		//__________________________________________________________________________
		// Check whether this Display is belong to a Group
		$isBelongToGroup = isBelongToGroup ( $displayID );
		if ($isBelongToGroup == 0) {
			$sql = "SELECT DisplayGroupID FROM displaygroup WHERE DisplayGroup='$displayName'";
			$result = $conn->query ( $sql );
			if ($result->num_rows > 0) {
				$displayGroupID = $result->fetch_assoc () ["DisplayGroupID"];
			} else
				echo "\nnonRecord DisplayGroupID for $displayName";
		} else {
			$displayGroupID = $groupID;
			echo "\nDisplayGroupID:" . $displayGroupID;
		}
	} else{
		echo "\nnonRecord display for Hardwarekey $hardwareKey";
	}
}
/*
 * Check whether the current Display belong to a DisplayGroup
 */
function isBelongToGroup($displayID) {
	require ("globalVar.php");
	$conn = new mysqli ( $dbhost, $dbuser, $dbpass, $dbname );
	$groupID = null;
	$isBelongToGroup = 0;
	$sql = "SELECT displaygroup.DisplayGroupID FROM lkdisplaydg,displaygroup 
	WHERE (lkdisplaydg.displayID='$displayID') 
		AND (displaygroup.IsDisplaySpecific='0') 
		AND (lkdisplaydg.DisplayGroupID=displaygroup.DisplayGroupID)";
	$result = $conn->query ( $sql );
	if ($result->num_rows > 0) {
		$groupID = $result->fetch_assoc () ["DisplayGroupID"];
		$isBelongToGroup = 1;
		
		$sql = "SELECT DisplayID FROM lkdisplaydg WHERE DisplayGroupID='$groupID'";
		$result = $conn->query ( $sql );
		$groupMembersID = array ();
		if ($result->num_rows > 0) {
			while ( $row = $result->fetch_assoc () ) {
				$groupMembersID [] = $row ["DisplayID"];
			}
		}
	}
	$conn->close ();
	return $isBelongToGroup;
}
/**
 * Get the ID of under displaying layout to remove
 */
function getUnderLayout() {
	require ("globalVar.php");
	$conn = new mysqli ( $dbhost, $dbuser, $dbpass, $dbname );
	// SQL connection checking
	if ($conn->connect_error) {
		die ( "Connection failed: " . $conn->connect_error );
	}
	$underLayoutIDs = array ();
	
	$sql = "SELECT eventID FROM schedule WHERE DisplayGroupIDs='$displayGroupID' AND DisplayOrder >= '$displayOrder'";
	// $sql = "SELECT eventID FROM schedule";
	$result = $conn->query ( $sql );
	if ($result->num_rows > 0) {
		while ( $row = $result->fetch_assoc () ) {
			$underEventIDs [] = $row ["eventID"];
		}
	}
	$conn->close ();
	return $underEventIDs;
}
?>