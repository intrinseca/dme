<?php

$dsn = 'mysql:dbname=mda39;host=127.0.0.1';
$user = 'mda39';
$password = 'pLkQe1oR';

$dbh = new PDO($dsn, $user, $password);
$dbh -> setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

if(isset($_GET['clear']))
{
	$query = $dbh -> prepare('UPDATE dme_messages SET status=2 WHERE id=?');
	$query -> execute (array($_GET['clear']));
	header("Location: dme.php");
}

if(isset($_GET['assign']))
{
	$query = $dbh -> prepare('UPDATE dme_messages SET status=1 WHERE id=? AND status = 0');
	$query -> execute (array($_GET['assign']));

	if($query -> rowCount > 0)
	{
		header("Location: dme.php?count={$query -> rowCount}");
	}
	else
	{
		header("Location: dme.php?error=Already%20Assigned&count={$query -> rowCount}");
	}
}

if(isset($_POST['message']))
{
	$message = $_POST['message'];

	preg_match('/^Date: (.*)/m', $message, $matches);
	$date = strtotime($matches[1]);	

	preg_match('/^Printer: (.*)/m', $message, $matches);
	$printer = $matches[1];

	preg_match('/^Fault: (.*)/m', $message, $matches);
	$fault = $matches[1];

	$query = $dbh -> prepare('INSERT INTO dme_messages(time,printer,fault) VALUES(?,?,?)'); 
	$query -> execute(array($date, $printer, $fault));
}
else
{
	$query = $dbh -> prepare('SELECT * FROM dme_messages ORDER BY status ASC, time DESC');
	$query -> execute();
	$alerts = $query -> fetchAll();

	$printers = array('Copier_LIB_BMT', 'Copier_LIB_1ST', 'Copier_LIB_2ND');

	$query = $dbh -> prepare('SELECT printer, MIN(status) AS status FROM dme_messages GROUP BY printer');
	$query -> execute(array($printers[0]));
	$status = $query -> fetchAll();
?>

<html>
<head>
	<title>Fitz SCO Printer Monitoring</title>
	<link rel="stylesheet" href="style.css" />
  <meta name="viewport" content="width=320" />
</head>
<body>

<div id="container">
	<div id="header">
		<h1>
			<img src="phonebook/images/fitz-crest.png" id="logo" />
			Fitz SCO Printer Monitoring
		</h1>
	</div>

	<div id="content">

		<?php 
			if(isset($_GET['error']))
			{
				echo <<<html
<div class="error"><div><b>Error:</b> {$_GET['error']}</div></div>
html;
			}
		?>

		<table>
			<tr>
				<?php
					foreach($status as $printer)
					{
						$online = ($printer['status'] == 2)?"Online":"Fault";
						echo <<<html
<td>
{$printer['printer']}: {$online}
</td>
html;
					}
				?>
			</tr>
		</table>

		<table>
			<tr>
				<th>Time</th>
				<th>Printer</th>
				<th>Error</th>
				<th></th>
			</tr>

			<?php $i = 1; foreach($alerts as $alert) { ?>
				<tr class="<?php echo ($i % 2 == 0)?"even ":""; ?><?php echo ($alert['status'] == 2)?"clear":""; ?>">
					<td><?php echo date('d/m/y H:i', $alert['time']); ?></td>
					<td><?php echo str_replace('Copier_LIB_', '', $alert['printer']); ?></td>
					<td><?php echo $alert['fault']; ?></td>
					<td>
					<?php if($alert['status'] == 2) { ?>
						Cleared
					<?php } else { ?>
						<a href="dme.php?clear=<?php echo $alert['id'];?>">Clear</a>
					<?php } ?>

					<?php if($alert['status'] == 1) { ?>
						&nbsp;&nbsp;Assigned
					<?php } else if($alert['status'] == 0){ ?>
						&nbsp;&nbsp;<a href="dme.php?assign=<?php echo $alert['id'];?>">Assign</a>
					<?php } ?>
				</tr>
			<?php $i++; } ?>
		</table>
	</div>
</div>
</body>
</html>
<?php } ?>
