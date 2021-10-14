<?php

require('staff.inc.php');
require_once(INCLUDE_DIR.'class.ticket.php');
require_once(INCLUDE_DIR.'class.dept.php');
require_once(INCLUDE_DIR.'class.filter.php');
require_once(INCLUDE_DIR.'class.canned.php');
require_once(INCLUDE_DIR.'class.json.php');
require_once(INCLUDE_DIR.'class.dynamic_forms.php');


$page='';
$ticket = $user = null; //clean start.
//LOCKDOWN...See if the id provided is actually valid and if the user has access.
if($_REQUEST['id']) {
    if(!($ticket=Ticket::lookup($_REQUEST['id'])))
         $errors['err']='Unknown or invalid ticket ID';
    elseif(!$ticket->checkStaffAccess($thisstaff)) {
        $errors['err']='Access denied. Contact admin if you believe this is in error';
        $ticket=null; //Clear ticket obj.
    }
}

//Lookup user if id is available.
if ($_REQUEST['uid'])
    $user = User::lookup($_REQUEST['uid']);

//At this stage we know the access status. we can process the post.
if($_POST && !$errors):

    if(!$errors)
        $thisstaff ->resetStats(); //We'll need to reflect any changes just made!
endif;

/*... Quick stats ...*/
$stats= $thisstaff->getTicketsStats();

//Navigation
$nav->setTabActive('tickets');
if($cfg->showAnsweredTickets()) {
    $nav->addSubMenu(array('desc'=>'Open ('.number_format($stats['open']+$stats['answered']).')',
                            'title'=>'Open Tickets',
                            'href'=>'tickets.php',
                            'iconclass'=>'Ticket'),
                        (!$_REQUEST['status'] || $_REQUEST['status']=='open'));
} else {

    if($stats) {
        $nav->addSubMenu(array('desc'=>'Open ('.number_format($stats['open']).')',
                               'title'=>'Open Tickets',
                               'href'=>'tickets.php',
                               'iconclass'=>'Ticket'),
                            (!$_REQUEST['status'] || $_REQUEST['status']=='open'));
    }

    if($stats['answered']) {
        $nav->addSubMenu(array('desc'=>'Answered ('.number_format($stats['answered']).')',
                               'title'=>'Answered Tickets',
                               'href'=>'tickets.php?status=answered',
                               'iconclass'=>'answeredTickets'),
                            ($_REQUEST['status']=='answered'));
    }
}

if($stats['assigned']) {
    if(!$ost->getWarning() && $stats['assigned']>10)
        $ost->setWarning($stats['assigned'].' tickets assigned to you! Do something about it!');

    $nav->addSubMenu(array('desc'=>'My Tickets ('.number_format($stats['assigned']).')',
                           'title'=>'Assigned Tickets',
                           'href'=>'tickets.php?status=assigned',
                           'iconclass'=>'assignedTickets'),
                        ($_REQUEST['status']=='assigned'));
}

if($stats['overdue']) {
    $nav->addSubMenu(array('desc'=>'Overdue ('.number_format($stats['overdue']).')',
                           'title'=>'Stale Tickets',
                           'href'=>'tickets.php?status=overdue',
                           'iconclass'=>'overdueTickets'),
                        ($_REQUEST['status']=='overdue'));

    if(!$sysnotice && $stats['overdue']>10)
        $sysnotice=$stats['overdue'] .' overdue tickets!';
}

if($thisstaff->showAssignedOnly() && $stats['closed']) {
    $nav->addSubMenu(array('desc'=>'My Closed Tickets ('.number_format($stats['closed']).')',
                           'title'=>'My Closed Tickets',
                           'href'=>'tickets.php?status=closed',
                           'iconclass'=>'closedTickets'),
                        ($_REQUEST['status']=='closed'));
} else {

    $nav->addSubMenu(array('desc'=>'Closed Tickets ('.number_format($stats['closed']).')',
                           'title'=>'Closed Tickets',
                           'href'=>'tickets.php?status=closed',
                           'iconclass'=>'closedTickets'),
                        ($_REQUEST['status']=='closed'));
}

if($thisstaff->canCreateTickets()) {
    $nav->addSubMenu(array('desc'=>'New Ticket',
                           'title' => 'Open New Ticket',
                           'href'=>'tickets.php?a=open',
                           'iconclass'=>'newTicket',
                           'id' => 'new-ticket'),
                        ($_REQUEST['a']=='open'));
}


$inc = 'tickets.inc.php';
if($ticket) {
    $ost->setPageTitle('Ticket #'.$ticket->getNumber());
    $nav->setActiveSubMenu(-1);
    $inc = 'ticket-view.inc.php';
    if($_REQUEST['a']=='edit' && $thisstaff->canEditTickets()) {
        $inc = 'ticket-edit.inc.php';
        if (!$forms) $forms=DynamicFormEntry::forTicket($ticket->getId());
        // Auto add new fields to the entries
        foreach ($forms as $f) $f->addMissingFields();
    } elseif($_REQUEST['a'] == 'print' && !$ticket->pdfExport($_REQUEST['psize'], $_REQUEST['notes']))
        $errors['err'] = 'Internal error: Unable to export the ticket to PDF for print.';
} else {
    $inc = 'tickets.inc.php';
    if($_REQUEST['a']=='open' && $thisstaff->canCreateTickets())
        $inc = 'ticket-open.inc.php';
    elseif($_REQUEST['a'] == 'export') {
        require_once(INCLUDE_DIR.'class.export.php');
        $ts = strftime('%Y%m%d');
        if (!($token=$_REQUEST['h']))
            $errors['err'] = 'Query token required';
        elseif (!($query=$_SESSION['search_'.$token]))
            $errors['err'] = 'Query token not found';
        elseif (!Export::saveTickets($query, "tickets-$ts.csv", 'csv'))
            $errors['err'] = 'Internal error: Unable to dump query results';
    }

    //Clear active submenu on search with no status
    if($_REQUEST['a']=='search' && !$_REQUEST['status'])
        $nav->setActiveSubMenu(-1);

    //set refresh rate if the user has it configured
    if(!$_POST && !$_REQUEST['a'] && ($min=$thisstaff->getRefreshRate()))
        $ost->addExtraHeader('<meta http-equiv="refresh" content="'.($min*60).'" />');
}

require_once(STAFFINC_DIR.'header.inc.php');
//require_once(STAFFINC_DIR.$inc);

/**************************************** CONVERT SECS TO TIME - ADDED 03/24/14 PJH ****************************************/
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

//http://php.about.com/od/finishedphp1/ss/php_calendar_3.htm

$date = time();
$day = date('d', $date);
$month = date('m', $date);
$year = date('Y', $date);
$first_day = mktime(0,0,0,$month, 1, $year);
$title = date('F', $first_day);

$day_of_week = date('D', $first_day);

switch($day_of_week){
	case "Sun": $blank = 0; break;
	case "Mon": $blank = 1; break;
	case "Tue": $blank = 2; break;
	case "Wed": $blank = 3; break;
	case "Thu": $blank = 4; break;
	case "Fri": $blank = 5; break;
	case "Sat": $blank = 6; break;
	
$days_in_month = cal_days_in_month(0, $month, $year);
	
echo "<table border='1' width='294'>";
}


/******************** START FOOTER ********************/
require_once(STAFFINC_DIR.'footer.inc.php');

?>