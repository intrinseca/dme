<?php

/**
	* Index.php
	*	Main page
	*/

//Register as entry point
define('DME', true);
	
//Connect to DB.
require_once('common.php');

if(isset($_POST['clear']))
{
	$query = $dbh -> prepare('UPDATE dme_messages SET status=2, clearedby=?, clearedtime=? WHERE status!=2');
	$query -> execute (array('SCO', time()));
	header("Location: index.php");
}

if(isset($_POST['assign']))
{
	$query = $dbh -> prepare('UPDATE dme_messages SET status=1, clearedby=?, clearedtime=? WHERE status=0');
	$query -> execute(array('SCO', time()));
	header("Location: index.php");
}

$query = $dbh -> prepare('SELECT * FROM dme_messages ORDER BY status ASC, time DESC');
$query -> execute();
$alerts = $query -> fetchAll();

$printers = array('Copier_LIB_BMT', 'Copier_LIB_1ST', 'Copier_LIB_2ND');

$query = $dbh -> prepare(<<<sql
SELECT name, description, minstatus, faults
FROM (
	SELECT 
		printer, 
		MIN(status) AS minstatus, 
		GROUP_CONCAT(
			DISTINCT fault 
			ORDER BY time DESC
			SEPARATOR ", "
			) AS faults
	FROM dme_messages
	WHERE status!=2
	GROUP BY printer) AS statustable
RIGHT JOIN dme_printers ON statustable.printer=name
sql
);
$query -> execute();
$status = $query -> fetchAll();
?>

<html>
<head>
	<title>Fitz SCO Printer Monitoring</title>
	<link rel="stylesheet" href="style.css" />
  <meta name="viewport" content="width=320" />
	<link rel="shortcut icon" href="images/favicon.png" />
</head>
<body>

<div id="container">
	<div id="header">
		<h1>
			<img src="images/fitz-crest.png" id="logo" />
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

		<h2>Status</h2>
		
		<table id="status">
			<?php
				foreach($status as $printer)
				{
					if(is_null($printer['minstatus']))
					{
						$faultDesc = "Online";
						$image = "printer.png";
					}
					else
					{
						$faultDesc = "Offline: " . $printer['faults'];
						
						$faultLength = strpos($printer['faults'], ',');
						if($faultLength === false)
							$faultLength = strlen($printer['faults']);
						
						switch(substr($printer['faults'], 0, $faultLength))
						{
							case "Paper out":
								$image = "printer_empty.png";
								break;
							case "Toner out":
								$image = "printer_color.png";
								break;
							case "Down":
								$image = "printer_delete.png";
								break;
							default:
								$image = "printer_error.png";
								break;
						}
					}			
					
					echo <<<html
<tr>
	<th>
		{$printer['description']}
	</th>
	<td>
		<img src="images/{$image}" /> {$faultDesc}
	</td>
</tr>
html;
				}
			?>
		</table>

		<form method="post">
			<input type="submit" name="assign" value="I'm On My Way" />
			<input type="submit" name="clear" value="Dealt With" />
		</form>
		
		<h2>Log</h2>
		
		<table>
			<thead>
				<tr>
					<th>Time</th>
					<th>Printer</th>
					<th>Error</th>
					<th>Status</th>
				</tr>
			</thead>

			<?php $i = 1; foreach($alerts as $alert) { ?>
				<tr class="<?php echo ($i % 2 == 0)?"even ":""; ?><?php echo ($alert['status'] == 2)?"clear":""; ?>">
					<td><?php echo date('d/m/y H:i', $alert['time']); ?></td>
					<td><?php echo str_replace('Copier_LIB_', '', $alert['printer']); ?></td>
					<td><?php echo $alert['fault']; ?></td>
					<td>
						<?php
						switch($alert['status'])
						{
							case 0:
								echo "Outstanding";
								break;
							case 1:
								echo "Assigned to {$alert['clearedby']} at " . date('H:i', $alert['clearedtime']);
								break;
							case 2:
								echo "Cleared by {$alert['clearedby']} at " . date('H:i', $alert['clearedtime']);
								break;
						}
						?>
					</td>
				</tr>
			<?php $i++; } ?>
		</table>
	</div>
</div>
</body>
</html>
