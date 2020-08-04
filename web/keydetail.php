<?php
/*
 * snelSLiM - Interface for quick Stable Lexical Marker Analysis
 * Copyright (c) 2019 Bert Van de Poel
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

$c1report = file_get_contents('../data/reports/' . $_GET['reportid'] . '/c1.report');
$lines = explode("\n", $c1report);
foreach($lines as $line) {
	if(strpos($line, $_GET['keydetail'] . "\t") === 0) {
		break;
	}
}
$reportfields = explode("\t", $line);
$c1details = file_get_contents('../data/reports/' . $_GET['reportid'] . '/keyword_details.report');
$blocks = explode("\n\n", $c1details);
foreach($blocks as $block) {
	if(strpos($block, $_GET['keydetail'] . "\t") === 0) {
		$lines = explode("\n", $block);
		$files = array();
		foreach($lines as $num => $line) {
			$fields = explode("\t", $line);
			if($num === 0) {
				$pos = $fields[1];
				if($pos == 1) {
					$pos .= 'st';
				}
				elseif($pos == 2) {
					$pos .= 'nd';
				}
				elseif($pos == 3) {
					$pos .= 'rd';
				}
				else {
					$pos .= 'th';
				}
				$globalcount = $fields[2];
				$percentage = $fields[3];
			}
			else {
				$files[$fields[0]] = $fields[1];
			}
		}
		break;
	}
}
if(file_exists('../data/reports/' . $_GET['reportid'] . '/collocates.report')) {
	$collocreport = file_get_contents('../data/reports/' . $_GET['reportid'] . '/collocates.report');
	$blocks = explode("\n\n", $collocreport);
	foreach($blocks as $block) {
		if(strpos($block, $_GET['keydetail'] . "\n") === 0) {
			$lines = explode("\n", $block);
			$collocates = array();
			foreach($lines as $num => $line) {
				if($num === 0) {
					continue;
				}
				$fields = explode("\t", $line);
				$collocates[$fields[0]] = $fields[1];
			}
		}
	}
}

?>
	
<div class="page-header">
	<div class="row">
		<div class="col-md-12">
			<h1>Detailed report for marker &quot;<?php echo $_GET['keydetail']; ?>&quot;</h1>
			<p class="lead">You requested more details about the stable lexical marker <span class="emphasize">&quot;<?php echo $_GET['keydetail']; ?>&quot;</span> of your snelSLiM report for <span class="emphasize">Corpus &quot;<?php echo $report['c1']; ?>&quot;</span> against <span class="emphasize">Corpus &quot;<?php echo $report['c2']; ?>&quot;</span>. The marker occurs <span class="emphasize"><?php echo $globalcount; ?></span> times within the target corpus, making it the <span class="emphasize"><?php echo $pos; ?></span> most frequent word and <span class="emphasize"><?php echo round($percentage*100, 3); ?>%</span> of the words within the corpus.</p>
		</div>
	</div>
</div>
<div class="row">
	<div class="col-md-12">
		<table class="table table-striped table-condensed">
			<thead>
				<tr><th>Absolute score</th><th>Normalised score</th><th>Attraction</th><th>Repulsion</th><th>Lowest Log Odds Ratio</th><th>Highest Log Odds Ratio</th><th>StdDev</th><th>Log Odds Ratio Score</th></tr>
			</thead>
			<tbody>
				<?php echo '<td>' . $reportfields[1] . '</td><td>' . round($reportfields[2],4) . '</td><td>' . $reportfields[3] . '</td><td>' . $reportfields[4] . '</td><td>' . round($reportfields[5],3) . '</td><td>' . round($reportfields[6],3) . '</td><td>' . round($reportfields[7],3) . '</td><td>' . round($reportfields[8],3) . '</td></tr>'; ?>
			</tbody>
		</table>
	</div>
</div>
<div class="row">
	<div class="col-md-5">
		<h3>Frequency in Fragments/Texts</h3>
		<p>Files without an occurance are not listed</p>
		<table class="table table-striped table-hover table-condensed">
			<thead>
				<tr><th>Filename</th><th>Frequency of marker</th></tr>
			</thead>
			<tbody>
				<?php
				foreach($files as $file => $key) {
					echo '<tr><td>' . $file . '</td><td>' . $key . '</td></tr>';
				}
				?>
			</tbody>
		</table>
	</div>

	
	<?php
	
	if(isset($collocates)) {
		?>
		<div class="col-md-5 col-md-offset-2">
			<h3>Collocates</h3>
			<p>Limited to collocates with a logDice score larger than 0</p>
			<table class="table table-striped table-hover table-condensed">
				<thead>
					<tr><th>Collocate</th><th>logDice</th></tr>
				</thead>
				<tbody>
					<?php
					foreach($collocates as $collocate => $score) {
						echo '<tr><td>' . $collocate . '</td><td>' . round($score, 5) . '</td></tr>';
					}
					?>
				</tbody>
			</table>
		</div>
		<?php
	}
	
	?>
</div>
