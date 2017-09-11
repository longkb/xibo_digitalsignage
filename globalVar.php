<?php

/*
 * Xibo - Digital Signage - http://www.xibo.org.uk
 *
 * This file is part of Xibo - and is automatically generated by the installer
 *
 * You should not need to edit this file, unless your SQL connection details have changed.
 */

defined('XIBO') or die(__("Sorry, you are not allowed to directly access this page.") . "<br />" . __("Please press the back button in your browser."));
//Database connection
global $dbhost;
global $dbuser;
global $dbpass;
global $dbname;

//Input variables
global $key;
global $hardwareKey;

/*
 * Layout configuration
 */
global $keys; //Usecase keys and LayoutName in the adaptive DS
global $layoutKey; 
global $randomLayout; //Use to randomly select a layout from a set of layouts by using layout name

#Single display
global $displayOrder;
global $isPriority;

global $defaultCampaignID;
global $currentCampaign;
global $displayName;
global $licensed;
global $displayGroupID;

#Group and members
global $groupID;
global $groupMembersID;
global $isBelongToGroup;
#Time
global $globalOrder;
global $hours;
global $minutes;
global $seconds;