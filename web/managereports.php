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

if(isset($_GET['delete'])) {
	$delete = $db->prepare('DELETE FROM reports WHERE id=? AND owner=?');
	$delete->execute(array($_GET['delete'], $_SESSION['email']));
	if($delete->rowCount() > 0) {
		unlink('../slm/reports/' . $_GET['delete'] . '/c1.report');
		unlink('../slm/reports/' . $_GET['delete'] . '/c1frag.report');
		unlink('../slm/reports/' . $_GET['delete'] . '/c2frag.report');
		unlink('../slm/reports/' . $_GET['delete'] . '/done');
		unlink('../slm/reports/' . $_GET['delete'] . '/error');
		rmdir('../slm/reports/' . $_GET['delete']);
	}
}

$get_reports = $db->prepare('SELECT id, c1, c2, freqnum, cutoff, datetime FROM reports WHERE owner=? ORDER BY id DESC');
$get_reports->execute(array($_SESSION['email']));

?>

<div class="page-header" id="banner">
	<div class="row">
		<div class="col-md-12">
			<h1>My reports</h1>
			<p class="lead">Below are all the reports of previous analyses you've done. Keep in mind the owner of the server may after some time archive your reports.</p>
		</div>
	</div>
</div>
<div class="row">
	<div class="col-md-12">
		<table class="table table-striped table-hover">
			<thead>
				<tr><th>Corpus 1</th><th>Corpus 2</th><th>Probability</th><th>Frequency</th><th>#results</th><th>Requested on</th><th>Ran for</th><th>Options</th><th>Status</th><th>Delete</th></tr>
			</thead>
			<tbody>
<?php
			while($report = $get_reports->fetch(PDO::FETCH_ASSOC)) {
				$diff = NULL;
				if(file_exists('../slm/reports/' . $report['id'] . '/error')) {
					$error = file_get_contents('../slm/reports/' . $report['id'] . '/error');
					$status = '<span class="label label-danger" data-toggle="tooltip" title="' . $error . '"><span class="glyphicon glyphicon-exclamation-sign" aria-hidden="true"></span> error</span>';
					$errortime = filectime('../slm/reports/' . $report['id'] . '/error');
					$d1 = date_create($report['datetime']);
					$d2 = date_create(date('Y-m-d H:i:s', $errortime));
					$diff = date_diff($d1, $d2);
				}
				elseif(file_exists('../slm/reports/' . $report['id'] . '/done')) {
					$status = '<span class="label label-success"><span class="glyphicon glyphicon-ok-sign" aria-hidden="true"></span> done</span>';
					$donetime = filectime('../slm/reports/' . $report['id'] . '/done');
					$d1 = date_create($report['datetime']);
					$d2 = date_create(date('Y-m-d H:i:s', $donetime));
					$diff = date_diff($d1, $d2);
				}
				else {
					$status = '<span class="label label-default"><span class="glyphicon glyphicon-hourglass" aria-hidden="true"></span> processing</span>';
					$d1 = date_create($report['datetime']);
					$d2 = date_create(date('Y-m-d H:i:s'));
					$diff = date_diff($d1, $d2);
				}
				
				if(file_exists('../slm/reports/' . $report['id'] . '/c1.report')) {
					$resultnum = substr_count(file_get_contents('../slm/reports/' . $report['id'] . '/c1.report'), "\n");
				}
				else {
					$resultnum = "";
				}
				
				$options = '';
				if(file_exists('../slm/reports/' . $report['id'] . '/visuals')) {
					$options .= ' <span class="label label-info" title="Visualizations">Vis</span>';
				}
				if(file_exists('../slm/reports/' . $report['id'] . '/collocates.report')) {
					$options .= ' <span class="label label-info" title="Collocational Analysis">CA</span>';
				}
				
				$cutoff_transform = array('3.841459' => '95%', '6.634897' => '99%', '7.879439' => '99.5%', '10.827570' => '99.9%', '12.115670' => '99.95%', '15.136710' => '99.99%');
				$cutoff = $cutoff_transform[$report['cutoff']];
				
				echo '<tr data-href="?report=' . $report['id'] . '"><td class="breakwords">' . $report['c1'] . '</td><td class="breakwords">' . $report['c2'] . '</td><td>' . $cutoff . '</td><td>' . $report['freqnum'] . '</td><td>' . $resultnum . '</td><td>' . date("d M Y \a\\t H:i", strtotime($report['datetime'])) . '</td><td>' . $diff->format('%hh%im%ss') . '</td><td>' . $options . '</td><td>' . $status . '</td><td><a class="btn btn-primary btn-xs" href="?reports&delete=' . $report['id'] . '"><span class="glyphicon glyphicon-trash" aria-hidden="true"></span> Delete</a></td>';
			}
?>
			</tbody>
		</table>
	</div>
</div>
