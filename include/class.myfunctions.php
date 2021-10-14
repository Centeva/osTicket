<?php
/*********************************************************************
    class.ticket-report.php

	This is a group of custom functions created by Patrick Hewes
	for use on the ticket-report.php page
	
	LAST UPDATED - 12/30/2014
	
**********************************************************************/

require_once(INCLUDE_DIR.'ajax.tickets.php');
include_once(INCLUDE_DIR.'class.ticket.php');
require_once(INCLUDE_DIR.'class.ajax.php');

$SiteNum	= 12;
$ReqType	= 13;
$URL_PREFIX	= 'http://oaosupport.centeva.com';

//******************** GET SITE NAME FROM ID - ADDED 05/06/14 PJH ********************
function getSiteName($sid) {
	
	switch($sid)
	{
		case 0:
			$site='SAC-Fredericksburg';
			break;
		case 1:
			$site='SAC-Frederick';
			break;
		case 2:
			$site = 'TAC-Austin';
			break;
		case 3:
			$site = 'TAC-NJ';
			break;
		case 4:
			$site = 'ABS';
			break;
		case 5:
			$site = 'OAO';
			break;
		case 6:
			$site = 'Other';
			break;
	}

	return $site;
}

//******************** CONVERT SECS TO TIME - ADDED 03/24/14 PJH ********************
function secs2Time($seconds) {
$d = (int) ($seconds / 86400);
$h = (int) (($seconds - $d*86400) / 3600);
$m = (int) (($seconds - $d*86400 - $h*3600) / 60);
$s = (int) ($seconds - $d*86400 - $h*3600 - $m*60);

	switch($seconds)
	{			
		case ($seconds>86400):
			return $d . "d " . $h . "h " . $m . "m";
			break;
			
		case ($seconds>3600):
			return $h . "h " . $m . "m";
			break;
			
		case ($seconds>60):
			return $m . "m";
			break;
		
		case ($seconds<60):
			return $s . "s";
			break;
	}

//return (($h)?(($h<10)?("".$h."h"):$h):"")." ".(($m)?(($m<60)?("".$m."m"):$m):"00"); //.".".(($s)?(($s<10)?("0".$s):$s):"00");
}

//******************** GET FY - ADDED 05/16/14 PJH ********************
function getFY($id) {

	$date = strtotime($id);
	$iy = strftime('%Y', $date);
	$fy_S = '10/1/';
	$fy_E = '9/30/';
	
	$sD = strtotime($fy_S . $iy);
	$eD = strtotime($fy_E . $iy);
	
	if($date <= $eD) {
		$fy = intval($iy);
	} else {
		$fy = intval(intval($iy) + 1);
	}
	
	return $fy;
}

//******************** GET ARRAY OF SITES ********************
function getNamesArray() {
	
	$i=0;
	
	$select	='SELECT user.name Name, email.address, COUNT(ticket.ticket_ID) Tix, '
				.'ROUND((COUNT(*)/'
				.'(SELECT COUNT(*) FROM '.TICKET_TABLE.'))*100,1) Perc ';
	
	$from	= 'FROM '.USER_TABLE.' user '
			.'LEFT JOIN '.TICKET_TABLE.' ticket ON user.id=ticket.user_id '
			.'LEFT JOIN '.USER_EMAIL_TABLE.' email ON user.default_email_id=email.user_id ';
	
	$groupby='GROUP BY user.name ';
	
	$orderby='ORDER BY COUNT(*) DESC;';
	
	$query	="$select $from $groupby $orderby";
	
	if(!($res=db_query($query)) || !db_num_rows($res))
		return false;
	
	$array = array();
	
	while($row = db_fetch_array($res))
	{
		$i++;
		$array[$i] = $row['Name'];
	}

	return $array;
}

//******************** GET ARRAY OF SITES ********************
function getSitesArray($sDate, $eDate) {
	
	$select	='SELECT org.name ';
	
	$from	='FROM '.ORGANIZATION_TABLE.' org '
				.'LEFT JOIN '.USER_TABLE.' user ON user.org_id=org.id '
				.'LEFT JOIN '.TICKET_TABLE.' ticket ON ticket.user_id=user.id '
				.'LEFT JOIN '.TICKET_STATUS_TABLE.' status ON ticket.status_id=status.id ';
	
	$where	='WHERE org.name<>"Centeva" AND '
			.'status.state="Closed" AND '
			.'(ticket.closed BETWEEN "'.$sDate.'" AND "'.$eDate.'") ';
	
	$groupby='GROUP BY org.name ';
	
	$orderby='ORDER BY COUNT(ticket.closed) DESC';
	
	$query	="$select $from $where $groupby $orderby";

	if(!($res=db_query($query)) || !db_num_rows($res)) 
		return false;
	
	$array = array();
	$i = 0;
	while($row = db_fetch_array($res))
	{
		$array[$i] = $row['name'];
		$i++;
	}

	return $array;
}

function getSelectMenuOptions($sDate, $eDate) {
	
	$array = getSitesArray($sDate, $eDate);
	
	$sites = "";
	foreach($array as $site) {
		$value = str_replace('\/', '/', substr($site, 2, strpos($site, ':')-3));
		$sites = $sites . "<option value='".$site."' >".$value."</option>";
	}
	
	return $sites;
}

//******************** GET OPTION VALUES FOR SELECT LIST - ADDED 05/16/14 PJH ********************
function getOptionsNew($fy) {
	
	$select	="SELECT DISTINCT Year(closed) FY ";
	
	$from	="FROM ".TICKET_TABLE." ";
	
	$orderby="ORDER BY Year(closed) DESC";
	
	$query	="$select $from $orderby";

	$res = runQuery($query);

	$selected = ( $fy == date('Y')+1 ) ? True : False;

	$ivfy = (date('Y-m-d') >= date('Y') . '-10-01' && date('Y-m-d') < date('Y')+1 . '-01-01') 
		? isValidFYNew(date('Y')+1, $selected) : "";

	while( $row=db_fetch_array($res) )
	{
		if ( $row['FY'] ) {
			$selected = ( $row['FY'] == $fy ) ? True : False;
			$ivfy = $ivfy . isValidFYNew( $row['FY'], $selected );
		}
	}
	
	return $ivfy;

}

function isValidFYNew($fy, $selected) {

	$ops = "<option value='$fy'" . ( ( $selected ) ? " selected" : "" ) . " >$fy</option>";

	return $ops;

}

function getOptions($fy) {
	
	$select	="SELECT DISTINCT Year(closed) FY ";
	
	$from	="FROM ".TICKET_TABLE." ";
	
	$orderby="ORDER BY Year(closed) DESC";
	
	$query	="$select $from $orderby";

	$res = db_query($query);
	
	$ivfy = "";
	if (!($res=db_query($query)) || !db_num_rows($res)) {
		return false;
	} else {
		while( $row=db_fetch_array($res) )
		{
			$ivfy = $ivfy . isValidFY($row['FY']);
		}
	}

	if (date('Y-m-d') >= date('Y') . '-10-01' && date('Y-m-d') < date('Y')+1 . '-01-01') {
		$ivfy = $ivfy . isValidFY(date('Y')+1);
	}
	
	return $ivfy;


	/*
	if(!($res=db_query($query)) || !db_num_rows($res)) {
		$res = null;
	}

	# Get the current FY
	$ivfy = "";
	if (date('Y-m-d') >= date('Y') . '-10-01' && date('Y-m-d') < date('Y')+1 . '-01-01') {
		#$ivfy = isValidFY(date('Y')+1, ($fy) ? True : False);
	}

	#if ( $res )	 {
		while($row=db_fetch_array($res))
		{
			#$ivfy = $ivfy . isValidFY($row['FY'], False);
			$ivfy = $ivfy . "<option value='".$row['FY']."' " . ( $fy ) ? 'selected' : '' .">" . $fy . "</option>";
		}
	#}
	
	return $ivfy;
	*/

}

function isValidFY($FY) {
	$sql="SELECT COUNT(closed) count FROM ost_ticket WHERE closed BETWEEN '" . ($FY - 1) . "-10-01 0:00:00' AND '" . $FY . "-09-30 23:59:59'";
	
	if(!($res=db_query($sql)) || !db_num_rows($res)) {
		return false;
	}
	
	$ops = "";
	while($row = db_fetch_array($res)) {
		if($row['count']<>0) {
			$ops = $ops . "<option value='" . $FY . "' " . isSelected($FY) .">" . $FY . "</option>";
		}
	}
		return $ops;
}

//******************** CHECK IF SELECTED OPTION ON FY DROP DOWN - ADDED 05/20/14 PJH ********************
function isSelected ($fy) {
	
	$fyStartDt = date('Y') . '-10-1';
	if($fy=date('Y') && date('Y-m-d') < $fyStartDt) {
		#echo date('Y-m-d') . " | " . $fyStartDt;
		return 'selected';
	} else {
		return '';
	}
}

//******************** GET START DATE FOR FY FILTERING - ADDED 05/20/14 PJH ********************
function getSD($fy) {

	$date = $fy - 1 . '-10-01 00:00:00';
	return $date;

}

//******************** GET END DATE FOR FY FILTERING - ADDED 05/20/14 PJH ********************
function getED($fy) {

	$date = $fy . '-09-30 23:59:59';
	return $date;

}

//******************** CONVERT SQL DATE TO MM/DD/YY FORMAT - ADDED 03/27/14 PJH ********************
function convertDate($date) {
	$NewFormat = DateTime::createFromFormat('Y-m-d', $date);
	$NewDate = $NewFormat->format('m/d/y');
	return $NewDate;
}

function reqTypeSel($v) {
	
	$t = search($v);
	
	return $t;
}

/******************** Run Query ********************/
function runQuery($query) {
	
	$res = db_query($query);
	
	if(!($res=db_query($query)) || !db_num_rows($res)) {
		$res = null;
	}
	
	return $res;
}

?>