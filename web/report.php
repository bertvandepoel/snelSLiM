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
	$get_tokenreport = $db->prepare('SELECT id, owner, c1, c2, freqnum, cutoff, datetime FROM reports, share_link WHERE share_link.sharetoken=? AND reports.id=share_link.reportid ORDER BY id DESC');
	$get_tokenreport->execute(array($_GET['sharetoken']));
	$report = $get_tokenreport->fetch(PDO::FETCH_ASSOC);
	if(!$report OR !file_exists('../data/reports/' . $report['id'])) {
		echo '<div class="row"><div class="col-md-6 col-md-offset-3"><div class="alert alert-danger"><strong>Error</strong> This report was deleted or is not available to you.</div></div></div>';
		require('html/bottom.html');
		exit;
	}
	$report['linksuffix'] = '&sharetoken=' . $_GET['sharetoken'];
}
else {
	if(!file_exists('../data/reports/' . $_GET['report'])) {
		echo '<div class="row"><div class="col-md-6 col-md-offset-3"><div class="alert alert-warning"><strong>Warning</strong> Your report folder does not seem to exist. Usually it does not take long for the folder to process, so something might be wrong</div></div></div>';
		echo '<script type="text/javascript">window.setTimeout(function(){ window.location.href = "?report=' . $_GET['report'] . '"}, 5000)</script>';
		require('html/bottom.html');
		exit;
	}
	elseif(file_exists('../data/reports/' . $_GET['report'] . '/error')) {
		$error = file_get_contents('../data/reports/' . $_GET['report'] . '/error');
		echo '<div class="row"><div class="col-md-6 col-md-offset-3"><div class="alert alert-danger"><strong>Error</strong> The following error occured while generating your report:' . $error . '</div></div></div>';
		require('html/bottom.html');
		exit;
	}
	elseif(!file_exists('../data/reports/' . $_GET['report'] . '/done')) {
		echo '<div class="row"><div class="col-md-6 col-md-offset-3"><div class="alert alert-info"><strong>Info</strong> Your report is still being generated, depending on the size and complexity this may take a while, please standby. This page will check every 5 seconds whether your report is ready.</div></div></div>';
		echo '<script type="text/javascript">window.setTimeout(function(){ window.location.href = "?report=' . $_GET['report'] . '"}, 5000)</script>';
		require('html/bottom.html');
		exit;
	}
	$get_report = $db->prepare('SELECT id, c1, c2, freqnum, cutoff, datetime FROM reports WHERE id=? AND owner=?');
	$get_report->execute(array($_GET['report'], $_SESSION['email']));
	$report = $get_report->fetch(PDO::FETCH_ASSOC);
	if(!$report) {
		$get_shares = $db->prepare('SELECT id, owner, c1, c2, freqnum, cutoff, datetime FROM reports, share_user WHERE reports.id=? AND share_user.account=? AND reports.id=share_user.reportid ORDER BY id DESC');
		$get_shares->execute(array($_GET['report'], $_SESSION['email']));
		$report = $get_shares->fetch(PDO::FETCH_ASSOC);
		if(!$report) {
			echo '<div class="row"><div class="col-md-6 col-md-offset-3"><div class="alert alert-danger"><strong>Error</strong> This report was deleted or is not available to you.</div></div></div>';
			require('html/bottom.html');
			exit;
		}
	}
	$report['linksuffix'] = '';
}


// report is ready!
$c1report = file_get_contents('../data/reports/' . $report['id'] . '/c1.report');
$c1frag = file_get_contents('../data/reports/' . $report['id'] . '/c1frag.report');
$c2frag = file_get_contents('../data/reports/' . $report['id'] . '/c2frag.report');
$visualjson = FALSE;
if(file_exists('../data/reports/' . $report['id'] . '/visuals')) {
	$visualjson = TRUE;
}
$donetime = filectime('../data/reports/' . $report['id'] . '/done');
$d1 = date_create($report['datetime']);
$d2 = date_create(date('Y-m-d H:i:s', $donetime));
$diff = date_diff($d1, $d2);
$cutoff_transform = array('3.841459' => '95% (0.05)', '6.634897' => '99% (00.01)', '7.879439' => '99.5% (00.005)', '10.827570' => '99.9% (00.001)', '12.115670' => '99.95% (00.0005)', '15.136710' => '99.99% (00.0001)');

if(mb_detect_encoding($c1report, 'UTF-8, ISO-8859-1') === 'ISO-8859-1') {
	$c1report = utf8_encode($c1report);
}
if(mb_detect_encoding($c1frag, 'UTF-8, ISO-8859-1') === 'ISO-8859-1') {
	$c1frag = utf8_encode($c1frag);
}
if(mb_detect_encoding($c2frag, 'UTF-8, ISO-8859-1') === 'ISO-8859-1') {
	$c2frag = utf8_encode($c2frag);
}

if(isset($report['owner'])) {
	echo '<div class="row"><div class="col-md-6 col-md-offset-3"><div class="alert alert-info"><strong>This is a shared report.</strong> This report was shared with you by ' . $report['owner'] . '</div></div></div>';
}
?>

<div class="page-header" id="banner">
	<div class="row">
		<div class="col-md-12">
			<h1>Your report is ready</h1>
			<p class="lead">You requested a snelSLiM report for <span class="emphasize">Corpus &quot;<?php echo $report['c1']; ?>&quot;</span> against <span class="emphasize">Corpus &quot;<?php echo $report['c2']; ?>&quot;</span> on <?php echo date("d M Y \a\\t H:i", strtotime($report['datetime'])); ?> (finished in <?php echo $diff->format('%hh%im%ss'); ?>) using a statistical probability of <span class="emphasize"><?php echo $cutoff_transform[$report['cutoff']] ?></span><a href="?statistics" target="_blank" data-toggle="tooltip" class="formtooltip" title="Click for more information about statistical probability cut-off values"><span class="glyphicon glyphicon-question-sign"></span></a> and <span class="emphasize"><?php echo $report['freqnum']; ?></span><a href="?statistics" target="_blank" data-toggle="tooltip" class="formtooltip" title="Click for more information about what the number of frequent items means, or refer to the user manual"><span class="glyphicon glyphicon-question-sign"></span></a> of the most frequent items from the primary corpus to end up finding <span class="emphasize"><?php echo substr_count($c1report, "\n"); ?> stable lexical markers</span>. In the table you can see whether they were attracted to the first corpus or repulsed by it, and look into the effect size using the log odds ratio. For more information on these measures, please consult the help pages. In the bottommost table the results were then used to mark potentially interesting fragments/texts based on marker frequencies.</p>
			<?php
			if(file_exists('../data/reports/' . $report['id'] . '/collocinvalid')) {
				echo '<p class="lead collocinvalid">Collocational analysis was requested but Corpus A (target) was not pre-analysed for collocational analysis or the file was supplied by a user without the required premissions for pre-analysis for collocational analysis.</p>';
			}
			?>
		</div>
	</div>
</div>
<div class="row">
	<div class="col-md-12">
		<h1>SLMA results</h1>
	</div>
</div>

<div class="row">
	<div class="col-md-12">
		<h3>Table of Contents</h3>
		<h4><a href="#slmareport">Go to result table</a></h4>
		<?php if($visualjson){ echo '<h4><a href="#vis">Go to visualizations</a></h4>'; } ?>
		<h4><a href="#freqreport">Go to frequency tables</a></h4>
	</div>
</div>

<a href="#banner" id="totopbutton" class="btn btn-primary"><span class="glyphicon glyphicon-arrow-up"></span><br>Back to the top</a>

<div class="row">
	<div class="col-md-12">
<?php
		if( !isset($_GET['allitems']) AND !isset($_GET['detailed']) ) {
			echo '<h3 id="slmareport">Stable Lexical Marker Analysis - top 100 markers</h3>';
			echo '<a href="?report='.  $report['id'] . '&allitems=' . $report['linksuffix'] . '" class="btn btn-default"><span class="glyphicon glyphicon-resize-vertical" aria-hidden="true"></span> &nbsp; Show all markers</a>&nbsp;&nbsp;&nbsp;';
		}
		else {
			echo '<h3 id="slmareport">Stable Lexical Marker Analysis</h3>';
		}
		if(!isset($_GET['detailed'])) {
			echo '<a href="?report='.  $report['id'] . '&detailed=' . $report['linksuffix'] . '" class="btn btn-default"><span class="glyphicon glyphicon-th-list" aria-hidden="true"></span> &nbsp; Show detailed table</a>&nbsp;&nbsp;&nbsp;';
		}
		echo '<a href="?export=' .  $report['id'] . $report['linksuffix'] . '" class="btn btn-primary"><span class="glyphicon glyphicon-export" aria-hidden="true"></span> &nbsp; Export results</a>';
		if(!isset($report['owner'])) {
			echo '&nbsp;&nbsp;&nbsp;<a href="?share=' .  $report['id'] . '" class="btn btn-primary"><span class="glyphicon glyphicon-share" aria-hidden="true"></span> &nbsp; Share report</a>';
		}
?>
		<table id="resultTable" class="table table-striped table-hover table-condensed">
			<thead>
<?php
				if(isset($_GET['detailed'])) {
					echo '<tr><th>#</th><th>Marker</th><th>Absolute score</th><th>Normalised score</th><th>Attraction</th><th>Repulsion</th><th>Lowest Log Odds Ratio</th><th>Highest Log Odds Ratio</th><th>StdDev</th><th>Log Odds Ratio Score</th></tr>';
				}
				else {
					echo '<tr><th>#</th><th>Marker</th><th>Absolute score</th><th>Normalised score</th><th>Log Odds Ratio Score</th></tr>';
				}
?>
			</thead>
			<tbody>
<?php
		$slm = explode("\n", $c1report);
		$i = 0;
		foreach($slm as $row) {
			if($row !== '') {
				$i++;
				$fields = explode("\t", $row);
				if(isset($_GET['detailed'])) {
					if($fields[1] < 0) {
						echo '<tr class="repulsion"><td>' . $i . '</td><td class="breakwords">' . htmlentities($fields[0]) . ' &nbsp; <a href="?reportid=' . $report['id'] . '&keydetail=' . urlencode($fields[0]) . $report['linksuffix'] . '" target="_blank"><span class="glyphicon glyphicon-zoom-in"></span></a></td><td>' . $fields[1] . '</td><td>' . round($fields[2],4) . '</td><td>' . $fields[3] . '</td><td>' . $fields[4] . '</td><td>' . round($fields[5],3) . '</td><td>' . round($fields[6],3) . '</td><td>' . round($fields[7],3) . '</td><td>' . round($fields[8],3) . '</td></tr>';
					}
					else {
						echo '<tr><td>' . $i . '</td><td class="breakwords">' . htmlentities($fields[0]) . ' &nbsp; <a href="?reportid=' . $report['id'] . '&keydetail=' . urlencode($fields[0]) . $report['linksuffix'] . '" target="_blank"><span class="glyphicon glyphicon-zoom-in"></span></a></td><td>' . $fields[1] . '</td><td>' . round($fields[2],4) . '</td><td>' . $fields[3] . '</td><td>' . $fields[4] . '</td><td>' . round($fields[5],3) . '</td><td>' . round($fields[6],3) . '</td><td>' . round($fields[7],3) . '</td><td>' . round($fields[8],3) . '</td></tr>';
					}
				}
				else {
					if($fields[1] < 0) {
						echo '<tr class="repulsion"><td>' . $i . '</td><td class="breakwords">' . htmlentities($fields[0]) . ' &nbsp; <a href="?reportid=' . $report['id'] . '&keydetail=' . urlencode($fields[0]) . $report['linksuffix'] . '" target="_blank"><span class="glyphicon glyphicon-zoom-in"></span></a></td><td>' . $fields[1] . '</td><td>' . round($fields[2],4) . '</td><td>' . round($fields[8],3) . '</td></tr>';
					}
					else {
						echo '<tr><td>' . $i . '</td><td class="breakwords">' . htmlentities($fields[0]) . ' &nbsp; <a href="?reportid=' . $report['id'] . '&keydetail=' . urlencode($fields[0]) . $report['linksuffix'] . '" target="_blank"><span class="glyphicon glyphicon-zoom-in"></span></a></td><td>' . $fields[1] . '</td><td>' . round($fields[2],4) . '</td><td>' . round($fields[8],3) . '</td></tr>';
					}
				}
				if( !isset($_GET['allitems']) AND !isset($_GET['detailed']) AND $i == 100 ) {
					break;
				}
			}
		}
?>
			</tbody>
		</table>
	</div>
</div>

<?php

		if( !isset($_GET['allitems']) AND !isset($_GET['detailed']) ) {
			echo '<h4>These are the top 100 markers <a href="?report='.  $report['id'] . '&allitems=' . $report['linksuffix'] . '" class="btn btn-primary"><span class="glyphicon glyphicon-resize-vertical" aria-hidden="true"></span> &nbsp; Show all markers</a></h4>';
		}

		if($visualjson) {
?>
			<div class="row">
				<div class="col-md-12">
					<h3 id="vis">Visualizations</h3>
					<h4>Treemap representation of corpus A</h4>
					<?php require('visualizations/treemap.php'); ?>
					<h4>Scatterplot of file clustering based on euclidean distance</h4>
					<?php require('visualizations/scatter_euclid.php'); ?>
					<h4>Scatterplot of file clustering based on an average prototype for each corpus</h4>
					<?php require('visualizations/scatter_prototype.php'); ?>
				</div>
			</div>
<?php
		}
?>

<div class="row">
	<div class="col-md-5">
		<h3 id="freqreport">Frequency in Fragments/Texts</h3>
		<table id="AfragTable" class="table table-striped table-hover table-condensed">
			<thead>
<?php	
		if($visualjson) {
			echo '<tr><th>Filename</th><th>File extension</th><th>Frequency of SLMs</th><th><span class="glyphicon glyphicon-stats"></span></th></tr>';
		}
		else {
			echo '<tr><th>Filename</th><th>File extension</th><th>Frequency of SLMs</th></tr>';
		}
		echo '</thead><tbody>';
		
		$slm = explode("\n", $c1frag);
		foreach($slm as $row) {
			if($row !== '') {
				$fields = explode("\t", $row);
				$pathinfo = pathinfo($fields[0]);
				if($visualjson) {
					echo '<tr><td>' . $fields[0] . '</td><td>' . $pathinfo['extension'] . '</td><td>' . $fields[1] . '</td><td><a href="?reportid=' . $report['id'] . '&fragvis=' . urlencode($fields[0]) . $report['linksuffix'] . '" target="_blank"><span class="glyphicon glyphicon-stats"></span></a></td></tr>';
				}
				else {
					echo '<tr><td>' . $fields[0] . '</td><td>' . $pathinfo['extension'] . '</td><td>' . $fields[1] . '</td></tr>';
				}
			}
		}
?>
			</tbody>
		</table>
	</div>
	<div class="col-md-5 col-md-offset-2">
		<h3>Frequency in Fragments/Texts</h3>
		<table id="BfragTable" class="table table-striped table-hover table-condensed">
			<thead>
				<tr><th>Filename</th><th>File extension</th><th>Frequency of SLMs</th></tr>
			</thead>
			<tbody>
<?php
		$slm = explode("\n", $c2frag);
		foreach($slm as $row) {
			if($row !== '') {
				$fields = explode("\t", $row);
				$pathinfo = pathinfo($fields[0]);
				echo '<tr><td>' . $fields[0] . '</td><td>' . $pathinfo['extension'] . '</td><td>' . $fields[1] . '</td></tr>';
			}
		}
?>
			</tbody>
		</table>
	</div>
	</div>
</div>

<script>
var shiftWindow = function() { scrollBy(0, -70) };
if (location.hash) shiftWindow();
window.addEventListener("hashchange", shiftWindow);
</script>
