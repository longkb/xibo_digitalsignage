<?php

/*
 * Xibo - Digital Signage - http://www.xibo.org.uk
 *
 * This file is part of Xibo - and is automatically generated by the installer
 *
 * You should not need to edit this file, unless your SQL connection details have changed.
 */

defined('XIBO') or die(__("Sorry, you are not allowed to directly access this page.") . "<br />" . __("Please press the back button in your browser."));
require_once ("globalVar.php");

/*
 * Layout configuration
 */
$dbhost = 'localhost';
$dbuser = 'xiboadm';
$dbpass = '1';
$dbname = 'xibodb';

//Receive from POST message
// 	$keys=Array(
// 		[basic] => [Basic]
// 		[man] => [Man]
// 		[woman] => [Woman]
// 		[crowded] => [Crowded]
// 	)
$inputKeys=array("basic","man","woman","crowded");
//The gender field in POST message must contain this string
$layoutKeys=array("[Basic]","[Man]", "[Woman]", "[Crowded]"); //Ten layout trong GUI phai chua cac Key nay (Phan biet ca chu hoa va chu thuong)
for($i=0;$i<count($inputKeys);$i++){
	$keys[$inputKeys[$i]]=$layoutKeys[$i];
}

$isPriority=0;

//The minimum display order that Event can reach
$globalOrder=-5000;

//These variable are used to set a default duration of the event
$hours = 24; //one week
$minutes = 0;
$seconds = 0;