<?php
/*
 * snelSLiM - Interface for quick Stable Lexical Marker Analysis
 * Copyright (c) 2017-2019 Bert Van de Poel
 * Under superivison of Prof. Dr. Dirk Speelman
 * 
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>. 
 */


if(isset($_GET['sharetoken'])) {
	$get_tokenreport = $db->prepare('SELECT c1, c2 FROM reports, share_link WHERE share_link.sharetoken=? AND reports.id=share_link.reportid ORDER BY id DESC');
	$get_tokenreport->execute(array($_GET['sharetoken']));
	$report = $get_tokenreport->fetch(PDO::FETCH_ASSOC);
}
else {
	$get_report = $db->prepare('SELECT c1, c2 FROM reports WHERE id=? AND owner=?');
	$get_report->execute(array($_GET['reportid'], $_SESSION['email']));
	$report = $get_report->fetch(PDO::FETCH_ASSOC);
	if(!$report) {
		$get_shares = $db->prepare('SELECT c1, c2 FROM reports, share_user WHERE reports.id=? AND share_user.account=? AND reports.id=share_user.reportid ORDER BY id DESC');
		$get_shares->execute(array($_GET['reportid'], $_SESSION['email']));
		$report = $get_shares->fetch(PDO::FETCH_ASSOC);
	}
}

if(!$report) {
	echo '<div class="row"><div class="col-md-6 col-md-offset-3"><div class="alert alert-danger"><strong>Error</strong> This report was deleted or is not available to you.</div></div></div>';
	require('html/bottom.html');
	exit;
}

if(file_exists('../data/reports/' . $_GET['reportid'] . '/visuals')) {
	$jsonstring = file_get_contents('../data/reports/' . $_GET['reportid'] . '/visuals/treemap.json');
	$json = json_decode($jsonstring, TRUE);
	$filestats = FALSE;
	foreach($json as $item) {
		if($item['name'] == $_GET['fragvis']) {
			$filestats = $item;
			break;
		}
	}
	?>
	
<div class="page-header">
	<div class="row">
		<div class="col-md-12">
			<h1>Detailed report for file &quot;<?php echo $_GET['fragvis']; ?>&quot;</h1>
			<p class="lead">You requested more details about the file <span class="emphasize">&quot;<?php echo $_GET['fragvis']; ?>&quot;</span> of your snelSLiM report for <span class="emphasize">Corpus &quot;<?php echo $report['c1']; ?>&quot;</span> against <span class="emphasize">Corpus &quot;<?php echo $report['c2']; ?>&quot;</span>. The file contains <span class="emphasize"><?php echo $item['size_total']; ?></span> inidividual words of which <span class="emphasize"><?php echo $item['size_keyword_total']; ?></span> are stable lexical markers. The file contains <span class="emphasize"><?php echo $item['size_keyword_unique']; ?></span> unique stable lexical markers. This means that <span class="emphasize"><?php echo round($item['size_keyword_percentage_total']*100, 2); ?>%</span> of this file are stable lexical markers.
			<?php
				if($item['parent_total'] == 2) {
					echo 'The majority of the stable lexical markers in this file are <span class="emphasize">attracted</span>. ';
				}
				elseif($item['parent_total'] == 3) {
					echo 'The majority of the stable lexical markers in this file are <span class="emphasize">repulsed</span>. ';
				}
				else {
					echo 'There is an <span class="emphasize">equal amount</span> of attracted and repulsed stable lexical markers in this file. ';
				}
				
				if($item['parent_unique'] == 2) {
					echo 'While of the unique stable lexical markers, a majority in this file are <span class="emphasize">attracted</span>.';
				}
				elseif($item['parent_unique'] == 3) {
					echo 'While of the unique stable lexical markers, a majority in this file are <span class="emphasize">repulsed</span>.';
				}
				else {
					echo 'While for the unique stable lexical markers, there is an <span class="emphasize">equal amount</span> of attracted and repulsed markers in this file.';
				}
			?>
			</p>
		</div>
	</div>
</div>
	
	<?php
}

