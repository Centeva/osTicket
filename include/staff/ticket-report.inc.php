<?php

require_once(INCLUDE_DIR.'class.myfunctions.php');

echo "<link rel='stylesheet' href='../css/tablestyles.css' type='text/css' />";
echo "<h2>Ticket Activity</h2>";
echo "<form action='ticket-report.php' method='post' id='save' enctype='multipart/form-data'>";
echo "<p>Reports displaying ticket activities utilizing custom fields</p>";

$time = date('H:i:s', time());
$date = date('Y-m-d') . " " . $time;
$fyBegin = '-10-01 00:00:00';
$fyEnd = '-09-30 23:59:59';
if($_POST['site']==NULL && $_POST['fy']==NULL) {
	$site = 'OAO';
	$fy = ( $date >= date('Y') . '-10-01' && date('Y-m-d') < date('Y')+1 . '-01-01') ? 
		date('Y') + 1 : date('Y');
	$sDate = getFY($date)-1 . $fyBegin;
	$eDate = getFY($date) . $fyEnd;
} else {
	$site=$_POST['site'];
	$fy = $_POST['fy'];
	$sDate = ( $date >= date('Y') . '-10-01' && date('Y-m-d') < date('Y')+1 . '-01-01') ? 
		(int)$fy - 1 . $fyBegin : (int)$fy . $fyBegin;
	$eDate = (int)$fy . $fyEnd;
}
$psDate = ($sDate - 1) . $fyBegin;
$peDate = ($eDate - 1) . $fyEnd;

csrf_token();
#echo "FY: " . substr($fy,2) . " | Date: $date | psDate: $psDate | sDate: $sDate | peDate: $peDate | eDate: $eDate";

echo "<p>"
	#."Filter by Site:&nbsp;&nbsp;"
		#."<select name='site'>"
		#."<option value='OAO' 'selected'>OAO</option>".getSelectMenuOptions($sDate, $eDate)."</select>"  //NEED TO FIX
		."Fiscal Year:&nbsp;&nbsp;"
		."<select name='fy'>".getOptionsNew($fy)."</select>"
		."&nbsp;&nbsp;"
		."<input type='submit' name='submit' value='Submit'>"
	."</p>";

echo "</form>";
echo "<ul class='nav nav-tabs' id='tabular-navigation'></ul>";
echo "<div style='position:relative'>";
echo "<div style='width:100%'>";

date_default_timezone_set('America/Chicago');

TicketForm::ensureDynamicDataView();

$res = getUsersWithNoOrg();
if ( $res ) {

	$t1 = $t2 = 0;	//RESET VARIABLES
	echo "<div class='closeTimes'>"
			."<table>"
			."<tr class='tr4_1'>"
			."<th colspan='3'><h2>Users Not Assigned To An Organization<h2></th>"
			."<tr class='tr4_1_1'>"
			."<th width='200'>User Name</th>"
			."<th width='50'>#<br>Tickets</th>"
			."<th width='200'>Created By Staffer</th>"
			."</tr>";
	while($row = db_fetch_array($res))
	{
		echo "<tr class='tr4_2'>";
		echo "<td>&nbsp;<a href='".$URL_PREFIX."/scp/users.php?id=".$row['id']."'>".$row['name']."</a></td>";
		echo "<td align='center'>&nbsp;".$row['countOf']."</td>";
		echo "<td align='center'>&nbsp;".str_replace("."," ",$row['username'])."</td>";
		echo "</tr>";
	}
	
	echo "<tr class='tr4_1'>";
	echo "<td colspan='3'>&nbsp;Total(s)</td>";
	echo "</tr>";
	echo "</table>";
	echo "</div>";

	$res = null;

}

$res = getTicketsClosedByRequestType($site, $ReqType, $sDate, $eDate);
if ( $res ) {
	$t1 = 0;	$t2 = 0;	//RESET VARIABLES
	echo "<div class='closedByReqType'><table>
			<tr class='tr0_1'><th colspan='3'><h2>Tickets Closed by Request Type</h2></th></tr>
			<tr class='tr0_1_1'><th width='250'>Request Type</th><th width='100'># Closed</th><th width='100'>% of Total</th></tr>";

	while($row = db_fetch_array($res))
	{
		
		$value = str_replace('\/', '/', substr($row['value'], 2, strpos($row['value'], ':')-3));
		
		echo "<tr class='tr0_2'>";
		echo "<td>&nbsp;<a href='".$URL_PREFIX."/scp/tickets.php?advsid='".$value."'>".$value."</a></td>";
		echo "<td align='center'>" . $row['Closed'] . "</td>";
		echo "<td align='center'>" . $row['Perc'] . "%</td>";
		echo "</tr>";
		$t1 = $t1 + $row['Closed'];
		$t2 = $t2 + $row['Perc'];
	}

	echo "<tr class='tr0_1'>";
	echo "<td>&nbsp;Total(s):</td>".
			"<td align='center'>" . $t1 . "</td>".
			"<td align='center'>" . $t2 . "%</td>";
	echo "</tr>";
	echo "</table>";
	echo "</div>";

	$res = null;
}

$res = getTicketsClosedBySiteYtdOffset($sDate, $eDate, $psDate, $peDate);
if ( $res ) {

	$t1 = $t2 = 0;	//RESET VARIABLES
	echo "<div class='closedBySite'>"
			."<table>"
			."<tr class='tr1_1'>"
			."<th colspan='5'><h2>Tickets Closed by Site (prior YTD offset)</h2></th>"
			."</tr>"
			."<tr class='tr1_1_1'>"
			."<th width='150'>Site</th>"
			."<th width='75'>FY" . substr($fy-1,2) . " Closed</th>"
			."<th width='75'>FY" . substr($fy,2) . " Closed</th>"
			."<th width='75'>% Change</th>"
			."<th width='75'>% of Total</th>"
			."</tr>";

	while($row = db_fetch_array($res))
	{
		echo "<tr class='tr1_2'>"
				."<td>&nbsp;".$row['Site']."</td>"
				."<td align='center'>".$row['PriorClosed']."</td>"
				."<td align='center'>".$row['Closed']."</td>"
				."<td align='center'>";
				
		if ($row['PriorClosed']<$row[$Closed]) {
			echo "<span style='color:red'>-";
		} else if($row['PriorClosed']>$row[$Closed]) {
			echo "<span style='color:green'>";
		} else {
			echo "<span style='color:black'>";
		}

		$perChange = ($row['PriorClosed']==0) ? 
		
		/* $perChange =  */
			($row['Closed']*100) : 
			(round(($row['Closed']-$row['PriorClosed'])
					/$row['PriorClosed'],3)*100);
				
		echo "".$perChange."%</span></td>"
				."<td align='center'>".$row['Perc']."%</td>"
				."</tr>";
		$t1 = $t1 + $row['PriorClosed'];
		$t2 = $t2 + $row['Closed'];
		#$t3 = $t3 + $perChange;
		$t4 = $t4 + $row['Perc'];
	}

	$t3 = round((($t2 - $t1) / $t1),3)*100;

	echo "<tr class='tr1_1'>"
			."<td>&nbsp;Total(s):</td>"
			."<td align='center'>".$t1."</td>"
			."<td align='center'>".$t2."</td>"
			."<td align='center'>";

	if ($t3<0) {
		echo "&#8595;";
	} else {
		echo "&#8593;";
	}

	echo $t3."%</td>"
			."<td align='center'>".$t4."%</td>"
			."</tr>"
			."</table>"
			."</div>";

	$res = null;

}

$res = getTicketsClosedBySource($sDate, $eDate);
if ( $res ) {

	$t1 = $t2 = 0;	//RESET VARIABLES
	echo "<div class='closedBySource'>"
	."<table>"
	."<tr class='tr2_1'>"
	."<th colspan='3'><h2>Tickets Closed by Source</h2></th>"
	."</tr>"
	."<tr class='tr2_1_1'><th width='250'>Source</th>"
	."<th width='100'># Closed</th>"
	."<th width='100'>% of Total</th>"
	."</tr>";

	while($row = db_fetch_array($res))
	{
		echo "<tr class='tr2_2'>"
		."<td>&nbsp;".$row['source']."</td>"
		."<td align='center'>".$row['Closed']."</td>"
		."<td align='center'>".$row['Perc']."%</td>"
		."</tr>";
		$t1 = $t1 + $row['Closed'];
		$t2 = $t2 + $row['Perc'];
	}

	echo "<tr class='tr2_1'>"
	."<td>&nbsp;Total(s):</td>"
	."<td align='center'>".$t1."</td>"
	."<td align='center'>".$t2."%</td>"
	."</tr>"
	."</table>"
	."</div>";

	$res = null;

}

$res = getTicketsClosedByStaff($sDate, $eDate);
if ( $res ) {

	$t1 = $t2 = $i = 0;	//RESET VARIABLES
	echo "<div class='closedByStaff'>"
			."<table>"
			."<tr class='tr3_1'><th colspan='3'><h2>Tickets Closed by Staff</h2></th>"
			."<tr class='tr3_1_1'><th width='250'>Name</th>"
				."<th width='100'># Closed</th>"
				."<th width='100'>Avg Close Times</th>"
			."</tr>";

	while($row = db_fetch_array($res))
	{
		echo "<tr class='tr3_2'>"
				."<td>&nbsp;".$row['Name']."</td>"
				."<td align='center'>".$row['Closed']."</td>"
				."<td align='center'>".secs2Time($row['Secs'])."</td>"
			."</tr>";
		$t1 = $t1 + $row['Closed'];
		$t2 = $t2 + $row['Secs'];
		$i = $i + 1;
	}

	echo "<tr class='tr3_1'>"
			."<td>&nbsp;Total(s):</td>"
			."<td align='center'><b>".$t1."</td>"
			."<td align='center'>".secs2Time($t2/$i)."</td>"
		."</tr>"
		."</table>"
		."</div>";

		$res = null;

}

$res = getTopTenLongestTicketTimes($sDate, $eDate);
if ( $res ) {

	$t1 = $t2 = 0;	//RESET VARIABLES
	echo "<div class='closeTimes'>"
		."<table>"
		."<tr class='tr4_1'>"
			."<th colspan='5'><h2>Top 10 Longest Ticket Times To Close<h2></th>"
		."<tr class='tr4_1_1'>"
			."<th width='125'>Name</th>"
			."<th width='75'>Ticket #</th>"
			."<th width='75'>Created</th>"
			."<th width='75'>Closed</th>"
			."<th width='100'>Close Time</th>"
		."</tr>";

	while($row = db_fetch_array($res))
	{
		echo "<tr class='tr4_2'>"
			."<td>&nbsp;".$row['Name']."</td>"
			."<td align='center'>
				<a href='".$URL_PREFIX."/scp/tickets.php?id=".$row['ticket_id']."'>"
				.$row['number']."</a></td>"
			."<td align='center'>".convertDate($row['Created'])."</td>"
			."<td align='center'>".convertDate($row['Closed'])."</td>"
			."<td align='center'>".secs2Time($row['Secs'])."</td>"
		."</tr>";
		$t1 = $t1 + $row['Secs'];
	}

	echo "<tr class='tr4_1'>"
			."<td colspan='4'>&nbsp;Total Time:</td>"
			."<td align='center'>".secs2Time($t1)."</td>"
		."</tr>"
		."</table>"
		."</div>";
	$res = null;
}

$res = getEasAssists($sDate, $eDate);
if ( $res ) {

	$t1 = $t2 = 0;	//RESET VARIABLES
	echo "<div class='escalated'>"
		."<table>"
		."<tr class='tr5_1'>"
			."<th colspan='4'><h2>EAS Assists</h2></th>"
		."<tr class='tr5_1_1'>"
			."<th width='125'># EAS<br>Assisted</th>"
			."<th width='100'>%<br>of<br>Total</th>"
			#."<th width='100'>Avg<br>Close<br>Time</th>"
			#."<th width='100'>Still<br>Open</th>"
			."<th width='125'>Centeva Closed</th>"
			."<th width='100'>%<br>of<br>Total</th>"
		."</tr>";

	while($row = db_fetch_array($res))
	{
		echo "<tr class='tr5_2'>"
			."<td align='center'>".$row['EAS_A']."</td>"
			."<td align='center'>".ROUND(($row['EAS_A']/$row['Total'])*100,2)."%</td>"
			#."<td align='center'>N/A</td>"
			#."<td align='center'>N/A</td>"
			."<td align='center'>".$row['Centeva']."</td>"
			."<td align='center'>".ROUND(($row['Centeva']/$row['Total'])*100,2)."%</td>"
		."</tr>";
		$t1 += $row['EAS_A'];
		$t2 += $row['Centeva'];
	}

	echo "<tr class='tr5_1'>"
			."<td colspan='3'>&nbsp;Total(s):</td>"
			."<td align='center'>".($t1 + $t2)."</td>"
		."</tr>"
		."</table>"
		."</div>";

	#echo "<div class='rightTables'>";
	$res = null;
}

$names = getNamesArray();
$sites = getSitesArray($sDate, $eDate);
foreach($sites as $site) {
	
	$res = getTopTenRequestorsBySite($site, $sDate, $eDate);

	if ( $res ) {

		$t1 = $t2 = $i = 0;	//RESET VARIABLES
		echo "<div class='custSubmitted'>"
			."<table>"
			."<tr class='tr6_1'>"
				."<th colspan='6'><h2>Top 10 Customer Support Requestors & Counts <br>(".$site.")</h2></th>"
			."<tr class='tr6_1_1'>"
				."<th width='50'>Site\nRank</th>"
				."<th width='50'>Overall\nRank</th>"
				."<th width='5'></th>"
				."<th width='180'>Name</th>"
				."<th width='75'># Submitted</th>"
				."<th width='75'>% of<br>Total</th>"
			."</tr>";

		for($i=1; $row=db_fetch_array($res); $i++)
		{
			echo "<tr class='tr6_2'>"
					."<td align='center'>".$i."</td>"
					."<td align='center'>".array_search($row['name'], $names)."</td>" #OVERALL RANK
					."<td>".topFive($row['name'])."</td>"
					."<td>&nbsp;<a href='".$URL_PREFIX."/scp/tickets.php?__"
						."CSRFToken__=934ff7df6cbe35ebff086b47486e6599aa0ceeec&a=search&query="
						.$row['address']."&basic_search=Search'>".$row['name']."</a></td>"
					."<td align='center'>".$row['countOf']."</td>"
					."<td align='center'>".$row['Perc']."%</td>
					</tr>";
			$t1 += $row['countOf'];
			$t2 += $row['Perc'];
		}

		echo "<tr class='tr6_1'>"
				."<td colspan='4'>&nbsp;Total(s):</td>"
				."<td align='center'>".$t1."</td>"
				."<td align='center'>".$t2."%</td>"
			."</tr>"
			."</table>"
			."</div>";
		
		$res = null;

	}
}

/********** FINAL DIV **********/
echo "</div>";

/******************** USERS WITH NO ORGANIZATION ********************/
function getUsersWithNoOrg() {

	$select	='SELECT user.id, user.name, COUNT(ticket.closed) countOf, staff.username ';

	$from	='FROM ost_user user '
				.'LEFT JOIN ost_ticket ticket ON user.id=ticket.user_id '
				.'LEFT JOIN ost_staff staff ON ticket.staff_id=staff.staff_id ';

	$where	='WHERE user.org_id=0 ';

	$groupby='GROUP BY name ';

	$orderby='ORDER BY name, countOf';

	$query	="$select $from $where $groupby $orderby";

	return runQuery($query);

}

/******************** TICKETS CLOSED BY REQUEST TYPE ********************/
function getTicketsClosedByRequestType($site, $ReqType, $sDate, $eDate) {

	if($site <> 'OAO') {
		
		$create = "CREATE TABLE IF NOT EXISTS oaosupport.temp_table (entry_id int(11)); ";
		db_query($create, false);
		
		$insert = 'INSERT INTO temp_table '
			.'(SELECT entry_id FROM '.FORM_ANSWER_TABLE.' WHERE value=\''.$site.'\'); ';
		db_query($insert, false);
				
		$select ='SELECT value, COUNT(*) Closed, ROUND(COUNT(*)/'
					.'(SELECT COUNT(*) FROM '.FORM_ANSWER_TABLE.' oFEV '
						.'LEFT JOIN '.FORM_ENTRY_TABLE.' oFE ON oFEV.entry_id=oFE.id '
						.'LEFT JOIN '.TICKET_TABLE.' ticket ON oFE.object_id=ticket.ticket_id '
						.'LEFT JOIN '.TICKET_STATUS_TABLE.' status ON ticket.status_id=status.id '
					.'WHERE status.state="Closed" AND oFEV.field_id='.$ReqType.')*100,1) Perc ';
					
		$from ='FROM '.FORM_ANSWER_TABLE.' oFEV '
				.'LEFT JOIN '.FORM_ENTRY_TABLE.' oFE ON oFEV.entry_id=oFE.id '
				.'LEFT JOIN '.TICKET_TABLE.' ticket ON oFE.object_id=ticket.ticket_id '
				.'LEFT JOIN '.TICKET_STATUS_TABLE.' status ON ticket.status_id=status.id '
				.'LEFT JOIN temp_table TT ON oFEV.entry_id=TT.entry_id ';
		
		$where ='WHERE status.state="Closed" '
				.'AND (ticket.closed BETWEEN "'.$sDate.'" AND "'.$eDate.'") '
				.'AND oFEV.field_id='.$ReqType.' '
				.'AND value=\''.$site.'\' ';
		
		$groupby ='GROUP BY value ';
		
		$orderby ='ORDER BY Closed DESC;';
		
		$query ="$select $from $where $groupby $orderby";

		#if(!($res=db_query($query)) || !db_num_rows($res))
	#		return false;
		$res = runQuery($query);

		$temp_table = "DROP TABLE IF EXISTS oaosupport.temp_table;";
		db_query($temp_table, false);
		
	} else {

		$select ='SELECT value, COUNT(*) Closed, ROUND(COUNT(*)/'
		.'(SELECT COUNT(*) FROM '.FORM_ANSWER_TABLE.' oFEV '
		.'LEFT JOIN '.FORM_ENTRY_TABLE.' oFE ON oFEV.entry_id=oFE.id '
		.'LEFT JOIN '.TICKET_TABLE.' ticket ON oFE.object_id=ticket.ticket_id '
		.'LEFT JOIN '.TICKET_STATUS_TABLE.' status ON ticket.status_id=status.id '
					.'WHERE status.state="Closed" AND oFEV.field_id='.$ReqType.')*100,1) Perc ';
					
		$from ='FROM .'.FORM_ANSWER_TABLE.' oFEV '
		.'LEFT JOIN '.FORM_ENTRY_TABLE.' oFE ON oFEV.entry_id=oFE.id '
		.'LEFT JOIN '.TICKET_TABLE.' ticket ON oFE.object_id=ticket.ticket_id '
		.'LEFT JOIN '.TICKET_STATUS_TABLE.' status ON ticket.status_id=status.id ';
		
		$where ='WHERE status.state="Closed" '
		.'AND (ticket.closed BETWEEN "'.$sDate.'" AND "'.$eDate.'") '
		.'AND oFEV.field_id='.$ReqType.' ';
		
		$groupby ='GROUP BY value ';
		$orderby ='ORDER BY Closed DESC;';
		
		TicketForm::ensureDynamicDataView();
		
		$query ="$select $from $where $groupby $orderby";

		return runQuery($query);
	}
}

/******************** TICKETS CLOSED BY SITE YTD OFFSET ********************/
function getTicketsClosedBySiteYtdOffset($sDate, $eDate, $psDate, $peDate) {

#	$time = date('H:i:s', time());
#	$psDate = ( $date >= date('Y') . '-10-01' && date('Y-m-d') < date('Y')+1 . '-01-01') ? (int)$fy - 2 . '-10-01 00:00:00' : (int)$fy . '-10-01 00:00:00';
#	$peDate = ( $date >= date('Y') . '-10-01' && date('Y-m-d') < date('Y')+1 . '-01-01') ? (int)$fy - 2 . date('-m-d') . " " . $time : (int)$fy . date('-m-d') . " " . $time;
#	$psDate = ($sDate - 1) . '-10-01 00:00:00';
#	$peDate = ($eDate - 1) . '-09-30 23:59:59';

	$select	='SELECT DISTINCT org.name Site,
			(SELECT COUNT(*) 
			FROM '.TICKET_TABLE.' ticket
				LEFT JOIN '.USER_TABLE.' user ON ticket.user_id=user.id
				LEFT JOIN '.ORGANIZATION_TABLE.' org ON user.org_id=org.id 
			WHERE ticket.closed IS NOT null AND org.name=Site AND 
				(ticket.closed BETWEEN "'.$psDate.'" AND "'.$peDate.'")) 
			PriorClosed,
			
			(SELECT COUNT(*)
			FROM '.TICKET_TABLE.' ticket
				LEFT JOIN '.USER_TABLE.' user ON ticket.user_id=user.id
				LEFT JOIN '.ORGANIZATION_TABLE.' org ON user.org_id=org.id
			WHERE ticket.closed IS NOT null AND org.name=Site AND
				(ticket.closed BETWEEN "'.$sDate.'" AND "'.$eDate.'"))
			Closed, 
			
			ROUND((SELECT COUNT(*)
			FROM '.TICKET_TABLE.' ticket
				LEFT JOIN '.USER_TABLE.' user ON ticket.user_id=user.id
				LEFT JOIN '.ORGANIZATION_TABLE.' org ON user.org_id=org.id
			WHERE ticket.closed IS NOT null AND org.name=Site AND
				(ticket.closed BETWEEN "'.$sDate.'" AND "'.$eDate.'")
			)/(SELECT COUNT(*)
		    FROM '.TICKET_TABLE.' ticket
		    WHERE ticket.closed IS NOT null AND
				(ticket.closed BETWEEN "'.$sDate.'" AND "'.$eDate.'")
		    )*100,1)
		Perc';

	$from	='FROM '.ORGANIZATION_TABLE.' org ';

	$orderby ='ORDER BY Closed DESC;';

	$query ="$select $from $orderby";

	return runQuery($query);

}

/******************** TICKETS CLOSED BY SOURCE ********************/
function getTicketsClosedBySource($sDate, $eDate) {

	$select	='SELECT ticket.source, COUNT(*) Closed, ROUND(COUNT(*)/'
	.'(SELECT COUNT(*) FROM '.TICKET_TABLE.')*100,1) Perc ';

	$from	='FROM '.TICKET_TABLE.' ticket '
	.'LEFT JOIN '.TICKET_STATUS_TABLE.' status ON ticket.status_id=status.id ';

	$where	= 'WHERE status.state="Closed" '
	.'AND (ticket.closed BETWEEN "'.$sDate.'" AND "'.$eDate.'") ';

	$groupby='GROUP BY source ';

	$orderby='ORDER BY Closed DESC;';

	$query = "$select $from $where $groupby $orderby";

	return runQuery($query);

}

/******************** TICKETS CLOSED BY STAFF ********************/
function getTicketsClosedByStaff($sDate, $eDate) {
	
	$select	='SELECT concat(staff.firstname, " ", staff.lastname) Name, count(*) Closed, '
	.'ROUND(SUM(TIMESTAMPDIFF(SECOND, ticket.created, ticket.closed)) / COUNT(*),2) Secs ';

	$from	='FROM '.TICKET_TABLE.' ticket '
	.'LEFT JOIN '.STAFF_TABLE.' staff on staff.staff_id = ticket.staff_id '
	.'LEFT JOIN '.TICKET_STATUS_TABLE.' status ON ticket.status_id=status.id ';

	$where	='WHERE status.state="Closed" '
	.'AND (ticket.closed BETWEEN "'.$sDate.'" AND "'.$eDate.'") ';
	
	$groupby='GROUP BY staff.lastname ';
	
	$orderby='ORDER BY Closed DESC;';
	
	$query ="$select $from $where $groupby $orderby";

	return runQuery($query);
	
}

/******************** TOP 10 LONGEST TICKET TIMES TO CLOSE ********************/
function getTopTenLongestTicketTimes($sDate, $eDate) {

	$select	='SELECT CONCAT(staff.firstname, " ", staff.lastname) Name, ticket.ticket_id, ticket.number, '
	.'CONVERT(ticket.created, DATE) Created, '
				.'CONVERT(ticket.closed, DATE) Closed, '
				.'TIMESTAMPDIFF(SECOND, ticket.created, ticket.closed) Secs ';
				
				$from	='FROM '.TICKET_TABLE.' ticket '
				.'LEFT JOIN '.STAFF_TABLE.' staff ON staff.staff_id = ticket.staff_id '
				.'LEFT JOIN '.TICKET_STATUS_TABLE.' status ON ticket.status_id=status.id ';
				
				$where	='WHERE status.state="Closed" '
				.'AND (ticket.closed BETWEEN "'.$sDate.'" AND "'.$eDate.'") ';
				
	$orderby='ORDER BY TIMESTAMPDIFF(SECOND, ticket.created, ticket.closed) DESC ';

	$limit	='LIMIT 10;';

	$query	="$select $from $where $orderby $limit";

	return runQuery($query);

}

/******************** EAS ASSISTS ********************/
function getEasAssists($sDate, $eDate) {

	$select	='SELECT ';
		
	$part1	='(SELECT TIMESTAMPDIFF(SECOND, ticket.created, ticket.closed)/
				COUNT(ticket.ticket_id)
				FROM '.FORM_ANSWER_TABLE.' oFEV
					INNER JOIN '.FORM_ENTRY_TABLE.' OFE ON oFEV.entry_id=oFE.id
					INNER JOIN '.TICKET_TABLE.' ticket ON ticket.ticket_id=oFE.object_id
				WHERE oFEV.field_id=16 AND 
					(ticket.closed BETWEEN "'.$sDate.'" AND "'.$eDate.'") 
					AND oFEV.value Is Not Null) Secs, ';
			
	$part2	='(SELECT COUNT(*)
				FROM '.FORM_ANSWER_TABLE.' oFEV
					INNER JOIN '.FORM_ENTRY_TABLE.' OFE ON oFEV.entry_id=oFE.id
					INNER JOIN '.TICKET_TABLE.' ticket ON ticket.ticket_id=oFE.object_id
				WHERE oFEV.field_id=16 AND 
					(ticket.closed BETWEEN "'.$sDate.'" AND "'.$eDate.'") AND 
					oFEV.value Is Not Null) EAS_A, ';

	$part3	='(SELECT COUNT(*)
				FROM '.FORM_ANSWER_TABLE.' oFEV
					INNER JOIN '.FORM_ENTRY_TABLE.' OFE ON oFEV.entry_id=oFE.id
					INNER JOIN '.TICKET_TABLE.' ticket ON ticket.ticket_id=oFE.object_id
				WHERE oFEV.field_id=16 AND 
					(ticket.closed BETWEEN "'.$sDate.'" AND "'.$eDate.'") AND 
					oFEV.value Is Null) Centeva, ';

	$part4	='(SELECT COUNT(*)
				FROM '.FORM_ANSWER_TABLE.' oFEV
					INNER JOIN '.FORM_ENTRY_TABLE.' OFE ON oFEV.entry_id=oFE.id
					INNER JOIN '.TICKET_TABLE.' ticket ON ticket.ticket_id=oFE.object_id
				WHERE oFEV.field_id=16 AND 
					(ticket.closed BETWEEN "'.$sDate.'" AND "'.$eDate.'")) Total';

	$query	="$select $part1 $part2 $part3 $part4";

	return runQuery($query);

}

/******************** TOP 10 CUSTOMER SUPPORT REQUESTORS & COUNTS BY SITE ********************/
function getTopTenRequestorsBySite($site, $sDate, $eDate) {
	$select	='SELECT org.name, user.name, email.address, COUNT(ticket.ticket_id) countOf, '
	.'ROUND((COUNT(*) / '
	.'(SELECT COUNT(*) FROM '.TICKET_TABLE.' ticket '
	.'LEFT JOIN '.TICKET_STATUS_TABLE.' status ON ticket.status_id=status.id '
	.'WHERE status.state="Closed" AND '
	.'(ticket.closed BETWEEN "'.$sDate.'" AND "'.$eDate.'")))*100,1) Perc ';
	
	$from	='FROM '.TICKET_TABLE.' ticket '
	.'LEFT JOIN '.USER_TABLE.' user ON ticket.user_id=user.id '
	.'LEFT JOIN '.ORGANIZATION_TABLE.' org ON org.id=user.org_id '
	.'LEFT JOIN '.USER_EMAIL_TABLE.' email ON user.default_email_id=email.user_id '
	.'LEFT JOIN '.TICKET_STATUS_TABLE.' status ON ticket.status_id=status.id ';
	
	$where	='WHERE status.state="Closed" AND '
				.'(ticket.closed BETWEEN "'.$sDate.'" AND "'.$eDate.'") AND '
				.'org.name="'.$site.'" ';
				
				$groupby='GROUP BY user.name ';
				
				$orderby='ORDER BY count(user.name) DESC ';

				$limit	='LIMIT 10;';
				
				$query	="$select $from $where $groupby $orderby $limit";

	return runQuery($query);

}

function topFive($n) {	
	global $names;
	
	if (array_search($n, $names) == 1) {
		return "<font color='#ffd700'>&#x2605;</font>";
	} else if (array_search($n, $names) == 2) {
		return "<font color='#CCCCCC'>&#x2605;</font>";
	} else if (array_search($n, $names) == 3) {
		return "<font color='#CD7F32'>&#x2605;</font>";
	} else if (array_search($n, $names) == 4) {
		return "<font color='#00FF00'>&#x2605;</font>";
	} else if (array_search($n, $names) == 5) {
		return "<font color='#FF0000'>&#x2605;</font>";
	}
	return false;	
}
?>