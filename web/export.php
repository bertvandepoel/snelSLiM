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

if(isset($_GET['format'])) {
	$get_report = $db->prepare('SELECT c1, c2, freqnum, cutoff, datetime FROM reports WHERE id=? AND owner=?');
	$get_report->execute(array($_GET['export'], $_SESSION['email']));
	$report = $get_report->fetch(PDO::FETCH_ASSOC);
	if(!$report) {
		var_dump($_SESSION);
		echo 'AAAAAAAAAAAA';
		echo '<div class="row"><div class="col-md-6 col-md-offset-3"><div class="alert alert-error"><strong>Error</strong> This report was deleted or is not available to you.</div></div></div>';
		require('html/bottom.html');
		exit;
	}

	$c1report = file_get_contents('../data/reports/' . $_GET['export'] . '/c1.report');
	if(mb_detect_encoding($c1report, 'UTF-8, ISO-8859-1') === 'ISO-8859-1') {
		$c1report = utf8_encode($c1report);
	}
	
	$fields = array();
	$headerrow = '';
	if(isset($_GET['field_marker']) && $_GET['field_marker'] == 'on') {
		$fields[] = 0;
	}
	if(isset($_GET['field_abs']) && $_GET['field_abs'] == 'on') {
		$fields[] = 1;
	}
	if(isset($_GET['field_norm']) && $_GET['field_norm'] == 'on') {
		$fields[] = 2;
	}
	if(isset($_GET['field_attr']) && $_GET['field_attr'] == 'on') {
		$fields[] = 3;
	}
	if(isset($_GET['field_rep']) && $_GET['field_rep'] == 'on') {
		$fields[] = 4;
	}
	if(isset($_GET['field_min']) && $_GET['field_min'] == 'on') {
		$fields[] = 5;
	}
	if(isset($_GET['field_max']) && $_GET['field_max'] == 'on') {
		$fields[] = 6;
	}
	if(isset($_GET['field_stddev']) && $_GET['field_stddev'] == 'on') {
		$fields[] = 7;
	}
	if(isset($_GET['field_lor']) && $_GET['field_lor'] == 'on') {
		$fields[] = 8;
	}
	$c1report = 'Marker	Absolute score	Normalised score	Attraction	Repulsion	Lowest Log Odds Ratio	Highest Log Odds Ratio	StdDev	Log Odds Ratio Score' . "\n" . $c1report;
	
	//formats: CSV, CSV excell, TSV, plain table, LaTeX
	$out = NULL;
	if($_GET['format'] == 'tsv') {
		header('Content-Type: text/tsv');
		header('Content-Disposition: attachment;filename=SnelSLiM_export_' . $_GET['export'] . '.tsv');
		$out = fopen('php://output', 'w');
	}
	elseif($_GET['format'] == 'csv') {
		header('Content-Type: text/csv');
		header('Content-Disposition: attachment;filename=SnelSLiM_export_' . $_GET['export'] . '.csv');
		$out = fopen('php://output', 'w');
	}
	elseif($_GET['format'] == 'csvsep') {
		header('Content-Type: text/csv');
		header('Content-Disposition: attachment;filename=SnelSLiM_export_' . $_GET['export'] . '.csv');
		$out = fopen('php://output', 'w');
		fputcsv($out, array('sep=,'));
	}
	elseif($_GET['format'] == 'table') {
		echo '<table>';
	}
	else {
		header('Content-Type: text/plain');
		echo "% add this package to your preamble";
		echo "\n";
		echo '\usepackage{longtable}';
		echo "\n";
		echo "\n";
		echo '\begin{center}';
		echo "\n";
		echo '\begin{longtable}{';
		for($i=0;$i<count($fields);$i++){
			echo '|l';
		}
		echo '|}';
		echo "\n";
		
		function latex_escape($string) {
			$map = array( 
				"#" => "\\#",
				"$" => "\\$",
				"%" => "\\%",
				"&" => "\\&",
				"~" => "\\~{}",
				"_" => "\\_",
				"^" => "\\^{}",
				"\\" => "\\textbackslash{}",
				"{" => "\\{",
				"}" => "\\}",
			);
			return preg_replace_callback( "/([\^\%~\\\\#\$%&_\{\}])/", "\$map['$1']", $string );
		}
	}
	
	
	
	$slm = explode("\n", $c1report);
	foreach($slm as $row) {
		$rowarray = array();
		$rowfields = explode("\t", $row);
		foreach($fields as $fieldid) {
			$rowarray[] = $rowfields[$fieldid];
		}
		if($_GET['format'] == 'tsv') {
			fputcsv($out, $rowarray, "\t");
		}
		elseif($_GET['format'] == 'csv') {
			fputcsv($out, $rowarray);
		}
		elseif($_GET['format'] == 'csvsep') {
			fputcsv($out, $rowarray);
		}
		elseif($_GET['format'] == 'table') {
			echo '<tr>';
			foreach($rowarray as $field) {
				echo '<td>' . $field . '</td>';
			}
			echo '</tr>';
		}
		else { //latex
			echo '\hline';
			echo "\n";
			$first = TRUE;
			foreach($rowarray as $field) {
				if(!$first) {
					echo ' & ';
				}
				echo latex_escape($field);
				$first = FALSE;
			}
			echo ' \\\\';
			echo "\n";
		}
	}
	if($_GET['format'] == 'tsv' || $_GET['format'] == 'csv' || $_GET['format'] == 'csvsep') {
		fclose($out);
	}
	elseif($_GET['format'] == 'table') {
		echo '</table>';
	}
	else { //latex
		echo '\hline';
		echo "\n";
		echo '\end{longtable}';
		echo "\n";
		echo '\end{center}';
		echo "\n";
	}
}
else {
	?>
	<div class="page-header" id="banner">
		<div class="row">
			<div class="col-md-8 col-md-offset-2">
				<h1>Exporting your results</h1>
				<p>Using the following form you can specify which data you would like to export and to which file format. Keep in mind that depending on the amount of stable lexical markers, the resulting file might be rather large.</p>
				<form action="?">
					<fieldset>
						<div class="form-group">
							<h4>Export file format</h4>
							<select class="form-control" name="format">
								<option value="tsv">TSV (tab seperated values) - ready for use with tools like R</option>
								<option value="csv">CSV (comma seperated values) - ready for use with tools and spreadsheet applications</option>
								<option value="csvsep">CSV for Microsoft Excel</option>
								<option value="table">Simple table - easy to copy to word processors</option>
								<option value="latex">LaTeX table - ready for publishing</option>
							</select>
						</div>
						<div class="form-group">
							<h4>Please select the fields you would like to include in your exported results.</h4>
							<label class="checkbox-inline">
								<input type="checkbox" name="field_marker" checked> Marker
							</label>
							<label class="checkbox-inline">
								<input type="checkbox" name="field_abs" checked> Absolute score
							</label>
							<label class="checkbox-inline">
								<input type="checkbox" name="field_norm" checked> Normalised score
							</label>
							<label class="checkbox-inline">
								<input type="checkbox" name="field_attr"> Attraction
							</label>
							<label class="checkbox-inline">
								<input type="checkbox" name="field_rep"> Repulsion
							</label>
						</div>
						<div class="form-group">
							<label class="checkbox-inline">
								<input type="checkbox" name="field_min"> Lowest Log Odds Ratio
							</label>
							<label class="checkbox-inline">
								<input type="checkbox" name="field_max"> Highest Log Odds Ratio
							</label>
							<label class="checkbox-inline">
								<input type="checkbox" name="field_stddev"> StdDev
							</label>
							<label class="checkbox-inline">
								<input type="checkbox" name="field_lor" checked> Log Odds Ratio Score
							</label>
						</div>
						<div class="form-group">
							<button type="submit" class="btn btn-primary" name="export" value="<?php echo $_GET['export']; ?>"><span class="glyphicon glyphicon-export" aria-hidden="true"></span> &nbsp; Export</button>
						</div>
					</fieldset>
				</form>
			</div>
		</div>
	</div>
	<?php
}
