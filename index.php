<?php

/**
	* Index.php
	*	Main page
	*/

//Register as entry point
define('DME', true);
	
//Connect to DB.
require_once('common.php');

//Clear all outstanding faults
//TODO: Move to separate file
//TODO: Add usernames
if(isset($_POST['clear']))
{
	$query = $dbh -> prepare('UPDATE dme_messages SET status=2, clearedby=?, clearedtime=? WHERE status!=2');
	$query -> execute (array('SCO', time()));
	header("Location: index.php");
}

//Assign all outstanding faults
//TODO: Move to separate page
if(isset($_POST['assign']))
{
	$query = $dbh -> prepare('UPDATE dme_messages SET status=1, clearedby=?, clearedtime=? WHERE status=0');
	$query -> execute(array('SCO', time()));
	header("Location: index.php");
}

//Get status for printers
//Complex query
$query = $dbh -> prepare(<<<sql
SELECT name, description, minstatus, faults
FROM ( 													/* Sub-Query for fault data */
	SELECT 
		printer, 
		MIN(status) AS minstatus, 	/* Lower fault status = not processed */
		GROUP_CONCAT(								/* Gets a comma separated list of outstanding faults */
			DISTINCT fault 						/* No repeats
			ORDER BY time DESC				/* Sort faults by report time */
			SEPARATOR ", "
			) AS faults
	FROM dme_messages
	WHERE status!=2 							/* Show only outstanding faults*/
	GROUP BY printer							/* One row per printer with outstanding faults */
	) AS statustable
RIGHT JOIN dme_printers ON statustable.printer=name	/* Join creates a row even for printers with no outstanding faults, with minstatus=NULL */
sql
);
$query -> execute();
$status = $query -> fetchAll();

//Get the full fault log
$query = $dbh -> prepare('SELECT * FROM dme_messages ORDER BY status ASC, time DESC');
$query -> execute();
$log = $query -> fetchAll();
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

		<h2>Status</h2>
		
		<table id="status">
			<?php
				//Generate status table, one row per printer
				foreach($status as $printer)
				{
					//Null status means no outstanding faults, see query above.
					if(is_null($printer['minstatus']))
					{
						$faultDesc = "Online";
						$image = "printer.png";
					}
					else
					{
						$faultDesc = "Offline: " . $printer['faults'];
						
						//Select icon based on the first (newest) fault in the list
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

			<?php 
				//Index used for odd/even row striping
				$i = 1;
				//Generate log table
				foreach($log as $entry) 
				{ 
			?>
				<tr class="<?php echo ($i % 2 == 0)?"even ":""; ?><?php echo ($entry['status'] == 2)?"clear":""; ?>">
					<td><?php echo date('d/m/y H:i', $entry['time']); ?></td>
					<td><?php echo str_replace('Copier_LIB_', '', $entry['printer']); ?></td>
					<td><?php echo $entry['fault']; ?></td>
					<td>
						<?php
						switch($entry['status'])
						{
							case 0:
								echo "Outstanding";
								break;
							case 1:
								echo "Assigned to {$entry['clearedby']} at " . date('H:i', $entry['clearedtime']);
								break;
							case 2:
								echo "Cleared by {$entry['clearedby']} at " . date('H:i', $entry['clearedtime']);
								break;
						}
						?>
					</td>
				</tr>
			<?php 
					$i++; 
				} 
			?>
		</table>
	</div>
</div>

</body>
</html>
