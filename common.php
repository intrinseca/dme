<?php

/** 
	* Common.php
	* Establishes global database connection
	*/

//File should not be opened directly
if(!defined('DME'))
{
	die('This file is not an entry point');
}

//DB parameters
$dsn = 'mysql:dbname=fitzscos;host=127.0.0.1';
$user = 'fitzscos';
$password = 'Eitaeyae';

//Connect to DB
$dbh = new PDO($dsn, $user, $password);
$dbh -> setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

?>