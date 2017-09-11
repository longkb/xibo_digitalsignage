<?php
DEFINE ( 'XIBO', true );
define ( 'WEBSITE_VERSION', 91 );
error_reporting ( 0 );
ini_set ( 'display_errors', 0 );
ini_set ( 'gd.jpeg_ignore_warning', 1 );

require ("nfcConfig.php");
// Get input data from POST message
$hardwareKey = null;

// ___________________________________________________________________________________

if (isset ( $_POST ['HK'] ) == False) {
	echo "\nCannot get HardwareKey from POST message";
} else {
	$hardwareKey = $_POST ['HK'];
	$isDisplaying = isDisplayingLayout ( $hardwareKey );
	if ($isDisplaying == True) {
		// Respond a corresponding URL
		for($i = 0; $i < count ( $keys ); $i ++) {
			if (strpos ( $currentCampaign, $nfcKeys[$i] ) !== false)
				echo $keys [$nfcKeys[$i]];
		}
	} else {
		echo "\nThere is not any displaying layout.";
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
		return True;
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
		
		// __________________________________________________________________________
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
	} else {
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
	}
	$conn->close ();
	return $isBelongToGroup;
}
?>