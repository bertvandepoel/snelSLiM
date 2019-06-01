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


$get_report = $db->prepare('SELECT c1, c2, freqnum, cutoff, datetime FROM reports WHERE id=?');
$get_report->execute(array($_GET['reportid']));
$report = $get_report->fetch(PDO::FETCH_ASSOC);

if(file_exists('../slm/reports/' . $_GET['reportid'] . '/visuals')) {
	$visstring = file_get_contents('../slm/reports/' . $_GET['reportid'] . '/visuals/' . $_GET['fragvis'] . ".snelvis");
	$jsonstring = file_get_contents('../slm/reports/' . $_GET['reportid'] . '/visuals/treemap.json');
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
<div class="row">
	<div class="col-md-12">
		<h1>Visualization</h1>
		<p>The image below is a representation of this specific file. Each square represents a stable lexical marker, ordered by effect size. If the marker is not present in the file, the pixel is white, if the marker is attracted and present, it is blue, when repulsed it's red, and when it is exactly as repulsed as it is attracted, it is grey. Black squares can be ignored. You can click on the image to view it in more detail.</p>
		<p><a href="?reportid=<?php echo $_GET['reportid']; ?>&fragvisimg=<?php echo $_GET['fragvis']; ?>" target="_blank"><img src="?reportid=<?php echo $_GET['reportid']; ?>&fragvisimg=<?php echo $_GET['fragvis']; ?>&rotate" style="max-width: 100%;"></a></p>
	</div>
</div>
	
	<?php
}

