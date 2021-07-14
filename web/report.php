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
			<p class="lead">You requested a snelSLiM report for <span class="emphasize">Corpus &quot;<?php echo $report['c1']; ?>&quot;</span> against <span class="emphasize">Corpus &quot;<?php echo $report['c2']; ?>&quot;</span> on <?php echo date("d M Y \a\\t H:i", strtotime($report['datetime'])); ?> (finished in <?php echo $diff->format('%hh%im%ss'); ?>) using a statistical probability of <span class="emphasize"><?php echo $cutoff_transform[$report['cutoff']] ?></span><a href="?statistics" target="_blank" data-toggle="tooltip" class="formtooltip" title="Click for more information about statistical probability cut-off values"><span class="glyphicon glyphicon-question-sign"></span></a> and <span class="emphasize"><?php echo $report['freqnum']; ?></span><a href="?statistics" target="_blank" data-toggle="tooltip" class="formtooltip" title="Click for more information about what the number of frequent items means, or refer to the user manual"><span class="glyphicon glyphicon-question-sign"></span></a> of the most frequent items from the primary corpus to end up finding <span class="emphasize"><?php echo substr_count($c1report, "\n"); ?> stable lexical markers</span>. In the table you can see whether they were attracted to the first corpus or repulsed by it, and look into the effect size using the log odds ratio. For more information on these measures, please consult the help pages. In the second tab of this report you can learn more about the distribution of markers across your target corpus using visualisations and frequency tables.</p>
			<?php
			if(file_exists('../data/reports/' . $report['id'] . '/collocinvalid')) {
				echo '<p class="lead collocinvalid">Collocational analysis was requested but Corpus A (target) was not pre-analysed for collocational analysis or the file was supplied by a user without the required premissions for pre-analysis for collocational analysis.</p>';
			}
			if(file_exists('../data/reports/' . $report['id'] . '/corpusA_warning_numfiles')) {
				echo '<div class="row"><div class="col-md-8 col-md-offset-2"><div class="alert alert-warning"><strong>Warning</strong> Corpus A has very few files. Stability across different texts is an important aspects of Stable Lexical Marker Analysis, so several files are required.</div></div></div>';
			}
			if(file_exists('../data/reports/' . $report['id'] . '/corpusA_warning_small')) {
				echo '<div class="row"><div class="col-md-8 col-md-offset-2"><div class="alert alert-warning"><strong>Warning</strong> Corpus A contains some smaller files. If a file contains fewer than 500 words it may not be very suitable for Stable Lexical Marker Analysis. Consider re-uploading the corpus with the option to discard small files.</div></div></div>';
			}
			if(file_exists('../data/reports/' . $report['id'] . '/corpusA_warning_extrasmall')) {
				echo '<div class="row"><div class="col-md-8 col-md-offset-2"><div class="alert alert-warning"><strong>Warning</strong> Corpus A contains some very small files. If a file contains fewer than 250 words it is most probably unsuitable for Stable Lexical Marker Analysis. Consider re-uploading the corpus with the option to discard small files.</div></div></div>';
			}
			if(file_exists('../data/reports/' . $report['id'] . '/corpusA_warning_distribution')) {
				echo '<div class="row"><div class="col-md-8 col-md-offset-2"><div class="alert alert-warning"><strong>Warning</strong> Corpus A contains files of very different sizes. The smallest file contains over 20 times fewer words than the largest, this may yield untrustworthy results.</div></div></div>';
			}
			if(file_exists('../data/reports/' . $report['id'] . '/corpusB_warning_numfiles')) {
				echo '<div class="row"><div class="col-md-8 col-md-offset-2"><div class="alert alert-warning"><strong>Warning</strong> Corpus B has very few files. Stability across different texts is an important aspects of Stable Lexical Marker Analysis, so several files are required.</div></div></div>';
			}
			if(file_exists('../data/reports/' . $report['id'] . '/corpusB_warning_small')) {
				echo '<div class="row"><div class="col-md-8 col-md-offset-2"><div class="alert alert-warning"><strong>Warning</strong> Corpus B contains some smaller files. If a file contains fewer than 500 words it may not be very suitable for Stable Lexical Marker Analysis. Consider re-uploading the corpus with the option to discard small files.</div></div></div>';
			}
			if(file_exists('../data/reports/' . $report['id'] . '/corpusB_warning_extrasmall')) {
				echo '<div class="row"><div class="col-md-8 col-md-offset-2"><div class="alert alert-warning"><strong>Warning</strong> Corpus B contains some very small files. If a file contains fewer than 250 words it is most probably unsuitable for Stable Lexical Marker Analysis. Consider re-uploading the corpus with the option to discard small files.</div></div></div>';
			}
			if(file_exists('../data/reports/' . $report['id'] . '/corpusB_warning_distribution')) {
				echo '<div class="row"><div class="col-md-8 col-md-offset-2"><div class="alert alert-warning"><strong>Warning</strong> Corpus B contains files of very different sizes. The smallest file contains over 20 times fewer words than the largest, this may yield untrustworthy results.</div></div></div>';
			}
			?>
		</div>
	</div>
</div>

<a href="#banner" id="totopbutton" class="btn btn-primary"><span class="glyphicon glyphicon-arrow-up"></span><br>Back to the top</a>

<div>
  <ul class="nav nav-tabs" role="tablist">
    <li role="presentation" class="active"><a href="#markers" aria-controls="markers" role="tab" data-toggle="tab">Stable Lexical Markers</a></li>
    <li role="presentation"><a href="#files" aria-controls="files" role="tab" data-toggle="tab">Corpus Text Analysis</a></li>
  </ul>

  <div class="tab-content">
    <div role="tabpanel" class="tab-pane active" id="markers">
    	<div class="row">
			<div class="col-md-12">
<?php
				if( !isset($_GET['allitems']) AND !isset($_GET['detailed']) ) {
					echo '<h2 id="slmareport">List of your top 100 Stable Lexical Markers</h2>';
					echo '<p>Below you can find the top 100 Stable Lexical Markers based on their effect size. If you spot any interesting markers, you can press the small icon next to the marker to see more details about the specific token. Use the buttons below to see all markers instead of only the top 100, show all statistical results for all markers, export the results to other applications or for publishing, and options to share your report with fellow researchers or students if you are the owner of this report. Difficulties understanding the numbers? Then please refer to the User Manual and Statistics items within the Help menu at the top right.</p>';
					echo '<a href="?report='.  $report['id'] . '&allitems=' . $report['linksuffix'] . '" class="btn btn-default"><span class="glyphicon glyphicon-resize-vertical" aria-hidden="true"></span> &nbsp; Show all markers</a>&nbsp;&nbsp;&nbsp;<a href="?report='.  $report['id'] . '&detailed=' . $report['linksuffix'] . '" class="btn btn-default"><span class="glyphicon glyphicon-th-list" aria-hidden="true"></span> &nbsp; Show detailed table</a>&nbsp;&nbsp;&nbsp;';
				}
				else {
					echo '<h2 id="slmareport">List of your Stable Lexical Markers</h2>';
					echo '<p>Below you can find all Stable Lexical Markers. If you spot any interesting markers, you can press the small icon next to the marker to see more details about the specific token. Use the buttons below to ';
					if(isset($_GET['detailed'])) { // detailed implies all items
						echo 'export the results to other applications or for publishing, and options to share your report with fellow researchers or students if you are the owner of this report. Difficulties understanding the numbers? Then please refer to the User Manual and Statistics items within the Help menu at the top right.</p>';
					}
					elseif(isset($_GET['allitems'])) {
						echo 'show all statistical results, export the results to other applications or for publishing, and options to share your report with fellow researchers or students if you are the owner of this report. Difficulties understanding the numbers? Then please refer to the User Manual and Statistics items within the Help menu at the top right.</p>';
						echo '<a href="?report='.  $report['id'] . '&detailed=' . $report['linksuffix'] . '" class="btn btn-default"><span class="glyphicon glyphicon-th-list" aria-hidden="true"></span> &nbsp; Show detailed table</a>&nbsp;&nbsp;&nbsp;';
					}
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
								echo '<tr class="repulsion"><td>' . $i . '</td><td class="breakwords">' . htmlentities($fields[0]) . ' &nbsp; <a href="?reportid=' . $report['id'] . '&keydetail=' . urlencode($fields[0]) . $report['linksuffix'] . '" target="_blank"><span class="glyphicon glyphicon-zoom-in"></span></a></td><td>' . $fields[1] . '</td><td>' . round($fields[2],4)*100 . '%</td><td>' . $fields[3] . '</td><td>' . $fields[4] . '</td><td>' . round($fields[5],3) . '</td><td>' . round($fields[6],3) . '</td><td>' . round($fields[7],3) . '</td><td>' . round($fields[8],3) . '</td></tr>';
							}
							else {
								echo '<tr><td>' . $i . '</td><td class="breakwords">' . htmlentities($fields[0]) . ' &nbsp; <a href="?reportid=' . $report['id'] . '&keydetail=' . urlencode($fields[0]) . $report['linksuffix'] . '" target="_blank"><span class="glyphicon glyphicon-zoom-in"></span></a></td><td>' . $fields[1] . '</td><td>' . round($fields[2],4)*100 . '%</td><td>' . $fields[3] . '</td><td>' . $fields[4] . '</td><td>' . round($fields[5],3) . '</td><td>' . round($fields[6],3) . '</td><td>' . round($fields[7],3) . '</td><td>' . round($fields[8],3) . '</td></tr>';
							}
						}
						else {
							if($fields[1] < 0) {
								echo '<tr class="repulsion"><td>' . $i . '</td><td class="breakwords">' . htmlentities($fields[0]) . ' &nbsp; <a href="?reportid=' . $report['id'] . '&keydetail=' . urlencode($fields[0]) . $report['linksuffix'] . '" target="_blank"><span class="glyphicon glyphicon-zoom-in"></span></a></td><td>' . $fields[1] . '</td><td>' . round($fields[2],4)*100 . '%</td><td>' . round($fields[8],3) . '</td></tr>';
							}
							else {
								echo '<tr><td>' . $i . '</td><td class="breakwords">' . htmlentities($fields[0]) . ' &nbsp; <a href="?reportid=' . $report['id'] . '&keydetail=' . urlencode($fields[0]) . $report['linksuffix'] . '" target="_blank"><span class="glyphicon glyphicon-zoom-in"></span></a></td><td>' . $fields[1] . '</td><td>' . round($fields[2],4)*100 . '%</td><td>' . round($fields[8],3) . '</td></tr>';
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
?>
    </div>
    <div role="tabpanel" class="tab-pane" id="files">
    	<h2>Analysis of Corpus Texts/Files based on marker presence</h2>
<?php
		if($visualjson) {
?>
			<p>Below you can find three different visualisations of the texts/files of the involved corpora, followed by frequency tables of markers within the different texts/files of both corpora. The first visualisation is a treemap visualisation of the target corpus, which can be used to investigate inconsistencies in size of the parts of your corpus and to identify texts/files that contain unusual markers representative more of the reference than the target corpus. The second and third visualisations are different ways of displaying how (dis)similar text/files of both corpora are, based on the presence and effect size of markers. Clicking on target corpus files in any of the visualisations, as well as the icon in the frequency tables, will show more details about this specific file. Please refer to the User Manual linked in the help menu on the top right of this page to learn more about how to interpret these visualisations.</p>
			<a href="#freqreport" class="btn btn-default btn-sm"><span class="glyphicon glyphicon-arrow-down"></span>Skip Visualisations and jump to frequency tables</a>
			<div class="row">
				<div class="col-md-12">
					<h3 id="vis">Visualisations</h3>
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
		else {
			echo '<p>This report does not contain any visualisations. This limits this analysis to marker frequencies within the different texts/files of both corpora. If you want to gain further insight, you should re-run this analysis with visualisations enabled.</p>';
		}
?>

		<div class="row">
			<div class="col-md-5">
				<h3 id="freqreport">Frequency in Texts/Files</h3>
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
				<h3>Frequency in Texts/Files</h3>
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
    </div>
  </div>
</div>

<script>
var shiftWindow = function() { scrollBy(0, -70) };
if (location.hash) shiftWindow();
window.addEventListener("hashchange", shiftWindow);
</script>
