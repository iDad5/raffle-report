
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Gewinnspiel-Auswertung</title>
<style type="text/css">
	body{
		font-family: sans-serif;
	}
	table{
		background-color: rgba(0,0,0,0.1);
	}
	table, th, td{
		border-collapse: collapse;
		border-color: rgb(255,255,255);
		border-width: 3px;
		border-style: solid;
	}
	th, td{
		padding: 5px;
		font-size: 80%;
	}

</style>
</head>

<body>


<?php
// find all post-ids using the 'page-uploadraffle.php' template
$mysqli = new mysqli('localhost', 'mathieu', 'Mathieu12', 'kreativ_wp');
if ($mysqli->connect_errno) {
    printf("Connect failed: %s\n", $mysqli->connect_error);
    exit();
}
if ($result = $mysqli->query("SELECT post_id FROM mwp_postmeta WHERE meta_key='_wp_page_template' AND meta_value='page-uploadraffle.php'")) {
	$ids  = array();
	while ($row = $result->fetch_assoc()) {
		array_push($ids, $row['post_id']);
	}
	/*
	echo('<pre>');
	var_dump($ids);
	echo('</pre>');
	//*/
}
// find titles to the posts
$postInfos = array();
for ($i = 0; $i < count($ids); $i++){
	$result = $mysqli->query("SELECT post_title, post_status, post_date, post_name FROM mwp_posts WHERE ID=".$ids[$i].";");
	$row = $result->fetch_assoc();
	$postInfos[$i]['id'] = $ids[$i];
	$postInfos[$i]['title'] = $row['post_title'];
	$postInfos[$i]['date'] = $row['post_date'];
	$postInfos[$i]['status'] = $row['post_status'];
	$postInfos[$i]['name'] = $row['post_name'];
}
//put newer poste (= higher ids) up front
$postInfos = array_reverse($postInfos);

/*
echo('<pre>');
var_dump($postInfos);
echo('</pre>');
//*/

echo ('<form method="POST">');
echo ('<select name="uploadID">');
for ($i = 0; $i < count($postInfos); $i++){
	echo('<option value="'.$postInfos[$i]['id'].'">');
	echo($postInfos[$i]['name']);
	echo('</option>');

}
echo ('</select>');
echo('<input type="submit" value="Send!">');
echo ('</form>');


// Generate report


if (isset($_POST['uploadID'])){

	// find page(post)type for the contibutions
	$query = "SELECT meta_value FROM mwp_postmeta WHERE meta_key='ugc_pagetype' AND post_id=".$_POST['uploadID'].";";
	if($result = $mysqli->query($query)){
		$row = $result->fetch_assoc();
		$contriPostType = $row['meta_value'];
		echo('Contribution Post Type:'.$contriPostType);
	} else {
		printf("Error: %s\n", $mysqli->error);
	}

	// collect all contributions that are publicized
	$query = "SELECT ID, post_title FROM mwp_posts WHERE post_type='".$contriPostType."' AND post_status='publish';";
	//echo('<br>'.$query.'<br>');
	if($result = $mysqli->query($query)){
		$contriList = array();
		while($row = $result->fetch_assoc()){
			array_push($contriList, array('ID' => $row['ID'], 'title' => $row['post_title']));
		}
		/*
		echo('<pre>');
		var_dump($contriList);
		echo('</pre>');
		//*/
	} else {
		printf("Error: %s\n", $mysqli->error);
	}

	// get databaseIDs fron the contribution post (post_meta)

	for ($i = 0; $i < count($contriList); $i++){
		$result = $mysqli->query("SELECT meta_value FROM mwp_postmeta WHERE meta_key='dbID' AND post_id=".$contriList[$i]['ID'].";");
		$row = $result->fetch_assoc();
		$contriList[$i]['dbID'] = $row['meta_value'];
		$result = $mysqli->query("SELECT meta_value FROM mwp_postmeta WHERE meta_key='user_email' AND post_id=".$contriList[$i]['ID'].";");
		$row = $result->fetch_assoc();
		$contriList[$i]['userEmail'] = $row['meta_value'];
		$result = $mysqli->query("SELECT meta_value FROM mwp_postmeta WHERE meta_key='user_name' AND post_id=".$contriList[$i]['ID'].";");
		$row = $result->fetch_assoc();
		$contriList[$i]['userName'] = $row['meta_value'];
		$result = $mysqli->query("SELECT meta_value FROM mwp_postmeta WHERE meta_key='user_nick' AND post_id=".$contriList[$i]['ID'].";");
		$row = $result->fetch_assoc();
		$contriList[$i]['userNick'] = $row['meta_value'];
		$result = $mysqli->query("SELECT meta_value FROM mwp_postmeta WHERE meta_key='_thumbnail_id' AND post_id=".$contriList[$i]['ID'].";");
		$row = $result->fetch_assoc();
		$contriList[$i]['thumbID'] = $row['meta_value'];
		$result = $mysqli->query("SELECT meta_value FROM mwp_postmeta WHERE meta_key='_wp_attachment_metadata' AND post_id=".$contriList[$i]['thumbID'].";");
		$row = $result->fetch_assoc();
		$contriList[$i]['thumbInfo'] = unserialize($row['meta_value']);
	}
	/*
	echo('<pre>');
	var_dump($contriList);
	echo('</pre>');
	//*/

	// fetch name of address table and voting-ID form main post and voting-table from voting-post

	$result = $mysqli->query("SELECT meta_value FROM mwp_postmeta WHERE meta_key='dbTable' AND post_id=".$_POST['uploadID'].";");
	$row = $result->fetch_assoc();
	$addressTable = $row['meta_value'];
	$result = $mysqli->query("SELECT meta_value FROM mwp_postmeta WHERE meta_key='my_voting' AND post_id=".$_POST['uploadID'].";");
	$row = $result->fetch_assoc();
	$votingID = $row['meta_value'];
	$result = $mysqli->query("SELECT meta_value FROM mwp_postmeta WHERE meta_key='db_table' AND post_id=".$votingID.";");
	$row = $result->fetch_assoc();
	$votingTable = $row['meta_value'];
	echo('<br>Address-table: '.$addressTable.' voting-link: '.$votingID.' voting-table: '.$votingTable);

	$mysqli->select_db('endkunden_kreativ');

	//  take adresses of contibuters from database connected to uploadpage
	for ($i = 0; $i < count($contriList); $i++){
		if($result = $mysqli->query("SELECT * FROM ".$addressTable." WHERE id='".$contriList[$i]['dbID']."';")){
			$row = $result->fetch_assoc();
			$contriList[$i]['adressData'] = $row;
		}
		else {
				printf("Error: %s\n", $mysqli->error);
		}
	}
	

	// count votes per contribution
	for ($i = 0; $i < count($contriList); $i++){
		$result = $mysqli->query("SELECT id FROM ".$votingTable." WHERE votedforID='".$contriList[$i]['ID']."';");
		$contriList[$i]['numVotes'] = $result->num_rows;
	}
	// 
	usort($contriList, 'sortByVotes');
	/*
	echo('<pre>');
	var_dump($contriList);
	echo('</pre>');
	//*/

	// render table

	echo('<table>');
		echo('<tr>');
			echo('<th>Rang:</th>');
			echo('<th>Stimmen:</th>');
			echo('<th>Beitrag</th>');
			echo('<th>Einsender</th>');
		echo('</tr>');
		for ($i = 0; $i < count($contriList); $i++){
			echo('<tr>');
				echo('<td>'.($i + 1).'</td>');
				echo('<td>'.$contriList[$i]['numVotes'].'</td>');
				echo('<td>'.renderContribution($contriList[$i]).'</td>');
				echo('<td>'.renderContributor($contriList[$i]).'</td>');
			echo('</tr>');
		}
	echo('</table>');
}
function renderContribution($info){
	$path = "http://www.marabu.de/kreativ/wp-content/uploads/";
	$subPath = stripslashes($info['thumbInfo']['file']);
	$subParts = explode('/', $subPath);
	array_pop($subParts);
	$subPath = implode('/', $subParts);
	$path .= $subPath.'/';
	$ret = '<h3>'.$info['title'].'</h3>';
	$ret .= '<img src="'.$path.$info['thumbInfo']['sizes']['thumbnail']['file'].'" alt=""/>';
	return $ret;
}
function renderContributor($info){
	$mail = $info['userEmail'];
	$adr = $info['adressData'];
	$sal = 'Frau';
	if($adr['salutation'] == 'm'){
		$sal = 'Herr';
	}
	$countryCode = strtoupper($adr['country']).'-';
	if($adr['country'] == 'DE'){
		$countryCode = 'D-';
	}
	if($adr['country'] == 'at'){
		$countryCode = 'A-';
	}
	$ret = '<div class="nick">';
	$ret .= 'Nickname: <strong>'.$info['userNick'].'</strong>';
	$ret .= '</div>';
	$ret .= '<div class="mail">';
	$ret .= 'E-Mail: <strong><a href="milto:'.$mail.'">'.$mail.'</a></strong>';
	$ret .= '</div>';
	$ret .= '<div class="address">';
	$ret .= $sal.': <strong>'.$adr['givenname'].' '.$adr['fullname'].'</strong><br/>';
	$ret .= $adr['street'].'<br/>'.$countryCode.$adr['plz'].' '.$adr['place'].'<br/>';
	$ret .= 'Geburtstdatum: '.$adr['dobday'].'.'.$adr['dobmonth'].'.'.$adr['dobyear'];
	$ret .= '</div>';
	return $ret;
}

function sortByVotes($a, $b){
	return $b['numVotes'] - $a['numVotes'];
}

$mysqli->close();
?>
</body>
</html>