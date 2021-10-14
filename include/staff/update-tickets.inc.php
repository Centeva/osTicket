<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
<head>
	<link rel='stylesheet' href='css/tablestyles.css' type='text/css' />
	<script scr="//code.jquery.com/jquery-1.10.2.js"></script>
</head>
<body>
<?php 
$ticketNumErr = $closeDtErr = "";

if ($_SERVER["REQUEST_METHOD"] == "POST")
{
	if (empty($_POST["ticket_num"]))
		$ticketNumErr = "Ticket # is required";
	else {
		$ticket_num = test_input($_POST["ticket_num"]);
		if (!preg_match("/^[0-9 ]*$/",$ticket_num)) {
			$ticketNumErr = "Only numbers allowed";
		}
	}
	
	if (empty($_POST["close_dt"]))
		$ticketNumErr = "Close Date is required";
	else {
		$ticket_num = test_input($_POST["ticket_num"]);
		if (!preg_match("/^[a-zA-Z ]*$/",$ticket_num)) {
			$ticketNumErr = "Only numbers allowed";
		}
	}
}

function test_input($data) {
	$data = trim($data);
	$data = stripslashes($data);
	$data = htmlspecialchars($data);
	return $data;
}

//$sql="UPDATE ost_ticket SET closed=@tCDT WHERE ticketID=@tID;";

//if(!($res=db_query($sql)) || !db_num_rows($res))
//	return false;
?>

<h2>Update Tickets</h2><p>Used to update various ticket values</p>
<ul class='nav nav-tabs' id='tabular-navigation'></ul>
<form method='post' action="<?php echo htmlspecialchars($_SERVER ['PHP_SELF']); ?>">
<?php csrf_token(); ?>
<div class='update-tickets_form'>
	Ticket #: 
		<input type='text' id='ticket_num' name='ticket_num' value='<?php echo $ticket_num; ?>'></input>
		<span class='error'>* <?php echo $ticketNumErr; ?></span><br>
	Close Date/Time: 
		<input type='text' id='close_dt'  name='close_dt' value='<?php echo $close_dt; ?>'></input>
		<span class='error'>* <?php echo $closeDtErr; ?></span>
	<br><br>
	<input type="submit" name="submit" value="Update">
</div>
	<!-- span class='error'>* required field</span -->
</form>

<script>
	
	$( "#ticket_num" )
		.keyup(function() {
			var value = $( this ).val();
			$( "#close_dt" ).val( value );
		})
		.keyup();
		
	$.ajax({
		type: 'POST',
		data: ({p:inpval}),
		url: 'listercust.php',
		success:	function(data) {
			$('.results').html(data);
		});
	
</script>

<!-- UPDATE ost_ticket SET closed=@tCDT #WHERE ticketID=@tID; -->

<?php 
echo $ticket_num;
echo $close_dt;
?>