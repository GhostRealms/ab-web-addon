<?php
require('database.php');

if(isset($_GET['p']) && is_numeric($_GET['p'])) {
	$page = array(
		'max'=>$_GET['p']*25, //The multiple is the maximum amount of results on a single page.
		'min'=>($_GET['p'] - 1)*25,
		'number'=>$_GET['p'],
		'posts'=>0,
		'count'=>0);
}else{
	$page = array(
		'max'=>'25', //The maximum amount of results on a single page.
		'min'=>'0',
		'number'=>'1',
		'posts'=>0,
		'count'=>0);
}

$types = array('all','ban','temp_ban','mute','temp_mute','warning','temp_warning','kick'); //List the types of punishments.
?>
<html lang="en">
	<head>
		<title><?php echo $info['title']; ?></title>
		<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">
		<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
		<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.1.1/jquery.min.js"></script>
		<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>
		<link href="https://maxcdn.bootstrapcdn.com/bootswatch/3.3.7/<?php echo $info['theme']; ?>/bootstrap.min.css" rel="stylesheet">
		<meta content='width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0' name='viewport' />
	</head>
	<body>
		<nav class="navbar navbar-default navbar-fixed-top">
		  <div class="container">
			<div class="navbar-header">
				<button class="navbar-toggle collapsed" type="button" data-toggle="collapse" data-target="#bs-example-navbar-collapse-1">
					<span class="sr-only">Toggle navigation</span>
					<span class="icon-bar"></span>
					<span class="icon-bar"></span>
					<span class="icon-bar"></span>
				</button>
				<a class="navbar-brand" href=""><?php echo $info['title']; ?></a>
			</div>

			<div class="collapse navbar-collapse" id="bs-example-navbar-collapse-1">
				<ul class="nav navbar-nav">
					<li class="active"><a href="">Punishments</a></li>
					<li><a href="https://github.com/mathhulk/ab-web-addon">GitHub</a></li>
					<li><a href="https://www.spigotmc.org/resources/advancedban.8695/">AdvancedBan</a></li>
				</ul>
				<ul class="nav navbar-nav navbar-right">
					<li><a href="https://theartex.net">made by mathhulk</a></li>
				</ul>
			</div>
		  </div>
		</nav>
		
		<div class="container">
			<div class="jumbotron">
				<h1><br><?php echo $info['title']; ?></h1> 
				<p><?php echo $info['description']; ?></p>
				<p>
					<?php
					foreach($types as $type) { //Loop through the types of punishments.
						$result = mysqli_query($con,"SELECT * FROM `".$info['table']."` WHERE punishmentType='".strtoupper($type)."'"); $rows = mysqli_num_rows($result); //Grab the number of rows per punishment type.
						$url = "index.php?type=".$type; //Grab the URL for each type.
						if($type == 'all') { //If the type is all...
							$url = "index.php"; //...set the URL to the same page.
							$result = mysqli_query($con,"SELECT * FROM `".$info['table']."` WHERE punishmentType!='IP_BAN'"); $rows = mysqli_num_rows($result); //Grab the number of rows per punishment type.
						}
						echo '<a href="'.$url.'" class="btn btn-primary btn-md">'.ucwords(str_replace('_','-',$type)).($type != "all" ? "s" : "").' <span class="badge">'.$rows.'</span></a></a>'; //Print the resulting punishment type on the page.
					}
					?>
				</p>
			</div>
			
			<div class="jumbotron">
				<form method="post" action="user.php">
					<div class="input-group">
						<input type="text" maxlength="50" name="user" class="form-control" placeholder="Search for...">
						<span class="input-group-btn">
							<button class="btn btn-default" type="submit">Submit</button>
						</span>
					</div>
				</form>
			</div>
			
			<div class="jumbotron">
				<table class="table table-striped table-hover">
					<thead>
						<tr>
							<th>Username</th>
							<th>Reason</th>
							<th>Operator</th>
							<th>Date</th>
							<th>End</th>
							<th>Type</th>
						</tr>
					</thead>
					<tbody>
						<?php
						if(isset($_GET['type']) && $_GET['type'] != 'all' && in_array(strtolower($_GET['type']),$types)) { //Check to see if the type is in the list of types.
							$punishment = stripslashes($_GET['type']); $punishment = mysqli_real_escape_string($con,$punishment); //Prevent SQL injection by sanitising and escaping the string.
							$result = mysqli_query($con,"SELECT * FROM `".$info['table']."` WHERE punishmentType='".$punishment."' ORDER BY id DESC"); //Grab data from the MYSQL database if a specific type is specified.
						} else {
							$result = mysqli_query($con,"SELECT * FROM `".$info['table']."` WHERE punishmentType!='IP_BAN' ORDER BY id DESC"); //Grab data from the MYSQL database.
						}
						
						$rows = mysqli_num_rows($result); //Grab the amount of results to be used in pagination.
						while($row = mysqli_fetch_array($result)) { //Fetch colums from each row of the MYSQL database.
							if($page['count'] < $page['max'] && $page['count'] >= $page['min']) {
								$page['count'] = $page['count'] + 1; //For some reason, $page['count']++ won't work. *shrugs*
								
								//Start timezone API.
								$time_zone = file_get_contents('http://freegeoip.net/json/'.$_SERVER['REMOTE_ADDR']); //Get the timezone of the visitor.
								$time_zone = json_decode($time_zone, true);
								$time_zone = $time_zone['time_zone'];
														
								$end_date = new DateTime(gmdate('F jS, Y g:i A', $row['end'] / 1000));
								$end_date->setTimezone(new DateTimeZone($time_zone)); //Set the timezone of the date to that of the visitor.
								
								$start_date = new DateTime(gmdate('F jS, Y g:i A', $row['start'] / 1000));
								$start_date->setTimezone(new DateTimeZone($time_zone)); //Set the timezone of the date to that of the visitor.
								//End timezone API.
								
								$end = $end_date->format("F jS, Y")."<br><span class='badge'>".$end_date->format("g:i A")."</span>"; //Grab the end time as a data.
								if($row['end'] == '-1') { //If the end time isn't set...
									$end = 'Not Evaluated'; //...set the end time to N/A.
								}
								echo "<tr><td><img src='https://crafatar.com/renders/head/".$row['uuid']."?scale=2&default=MHF_Steve&overlay' alt='".$row['name']."'>".$row['name']."</td><td>".$row['reason']."</td><td>".$row['operator']."</td><td>".$start_date->format("F jS, Y")."<br><span class='badge'>".$start_date->format("g:i A")."</span></td><td>".$end."</td><td>".ucwords(strtolower(str_replace('_','-',$row['punishmentType'])))."</td></tr>";
								$page['posts'] = $page['posts'] + 1;
							} else {
								$page['count'] = $page['count'] + 1;
								if($page['count'] >= $page['max']) {
									break;
								}
							}
						}
						
						if($page['posts'] == 0) { //Display an error if no punishments could be found.
							echo "<tr><td>---</td><td>No punishments could be located.</td><td>---</td><td>---</td><td>---</td><td>---</td></tr>";
						}
						?>
					</tbody>
				</table>
				<div class="text-center">
					<ul class='pagination'>
						<?php
						if($page['number'] != 1) { //Display a previous page button if the current page is not 1.
							echo "<li><a href='index.php?p=".($page['number'] - 1).(isset($punishment) ? "&type=".$punishment : '')."'>&laquo; Previous Page</a></li>";
						}
						$pages = substr(($rows / 25),0,1); $pagination = 1; //Fetch the number of regular pages.
						$pages_ = substr(($rows / 25),0,1); $pagination = 1; //Quick fix to get the number of pages for the next page button.
						if($rows % 25 != 0) {
							$pages = $pages + 1; //Add one more page if the content will not fit on the number of regular pages.
							$pages_ = $pages_ + 1; //Quick fix to get the number of pages for the next page button.
						}
						while($pages != 0) {
							echo "<li ".($pagination == $page['number'] ? 'class="active"' : '')."><a href='index.php?p=".$pagination.(isset($punishment) ? "&type=".$punishment : '')."'>".$pagination."</a></li>"; //Display the pagination.
							$pagination = $pagination + 1; $pages = $pages - 1;
						}
						if($page['number'] < $pages_) { //Display a next page button if the total punishments is more than that of the current page.
							echo "<li><a href='index.php?p=".($page['number'] + 1).(isset($punishment) ? "&type=".$punishment : '')."'>Next Page &raquo;</a></li>";
						}
						?>
					</ul>
				</div>
			</div>
		</div>
	</body>
</html>