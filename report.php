<?php

/**
	* Report.php
	*	Inserts a new error report into the database
	*/

//Register as entry point
define('DME', true);
	
//Connect to DB.
require_once('common.php');

//Check message is present
if(!isset($_POST['message']))
{
	die('No message to insert');
}

//Get message text
$message = $_POST['message'];

//Get error date/time from email header
preg_match('/^Date: (.*)/m', $message, $matches);
$date = strtotime($matches[1]);	

//Get printer ID from body
preg_match('/^Printer: (.*)/m', $message, $matches);
$printer = $matches[1];

//Get fault description from body
preg_match('/^Fault: (.*)/m', $message, $matches);
$fault = $matches[1];

//Insert into table
$query = $dbh -> prepare('INSERT INTO dme_messages(time,printer,fault) VALUES(?,?,?)'); 

if(!$query -> execute(array($date, $printer, $fault)))
{
	die('Insert failed');
}

?>