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
	$delete = $db->prepare('DELETE FROM corpora WHERE id=? AND owner=?');
	$delete->execute(array($_GET['delete'], $_SESSION['email']));
	if($delete->rowCount() > 0) {
		foreach(scandir('../data/preparsed/saved/' . $_GET['delete']) as $file) {
			if($file === '.' OR $file === '..') {
				continue;
			}
			// no folders here, so no need to check
			unlink('../data/preparsed/saved/' . $_GET['delete'] . '/' . $file);
		}
		rmdir('../data/preparsed/saved/' . $_GET['delete']);
	}
	echo '<div class="row"><div class="col-md-6 col-md-offset-3"><div class="alert alert-success"><strong>Success</strong> The corpus was removed.</div></div></div>';
}

if( isset($_GET['deleteglobal']) && isset($_SESSION['admin']) && ($_SESSION['admin']) ) {
	$delete = $db->prepare('DELETE FROM corpora WHERE id=? AND owner IS NULL');
	$delete->execute(array($_GET['deleteglobal']));
	if($delete->rowCount() > 0) {
		foreach(scandir('../data/preparsed/saved/' . $_GET['deleteglobal']) as $file) {
			if($file === '.' OR $file === '..') {
				continue;
			}
			// no folders here, so no need to check
			unlink('../data/preparsed/saved/' . $_GET['deleteglobal'] . '/' . $file);
		}
		rmdir('../data/preparsed/saved/' . $_GET['deleteglobal']);
	}
	echo '<div class="row"><div class="col-md-6 col-md-offset-3"><div class="alert alert-success"><strong>Success</strong> The corpus was removed.</div></div></div>';
}

if( isset($_GET['castglobal']) && isset($_SESSION['admin']) && ($_SESSION['admin']) ) {
	if(!file_exists('../data/preparsed/saved/' . $_GET['castglobal'] . '/done')) {
		echo '<div class="row"><div class="col-md-6 col-md-offset-3"><div class="alert alert-danger"><strong>Error</strong> This corpus is still being processed or has encountered an error during processing. Only corpora that are marked as having successfully processed can be made available to all users.</div></div></div>';
	}
	else {
		$update = $db->prepare('UPDATE corpora SET owner=NULL WHERE id=? AND owner=?');
		$update->execute(array($_GET['castglobal'], $_SESSION['email']));
		echo '<div class="row"><div class="col-md-6 col-md-offset-3"><div class="alert alert-success"><strong>Success</strong> Your corpus is now global.</div></div></div>';
	}
}

if($_SERVER['REQUEST_METHOD'] == 'POST' AND empty($_POST)) {
	echo '<div class="row"><div class="col-md-6 col-md-offset-3"><div class="alert alert-danger"><strong>Error</strong> The corpus exceeded the maximum upload size. Please use high compression or contact the administrator of your snelSLiM installation if you need to upload a very large corpus.</div></div></div>';
}
if(isset($_POST['add']) AND !$demo) {
	if ( ($_POST['c1-format'] == 'conll') AND  (intval($_POST['c1-extra-conll']) < 1) ) {
		echo '<div class="row"><div class="col-md-6 col-md-offset-3"><div class="alert alert-danger"><strong>Error</strong> You have chosen CoNLL as the format for your corpus, but you have not specified which column to select.</div></div></div>';
	}
	elseif ( ($_POST['c1-format'] == 'xpath') AND  (strlen($_POST['c1-extra-xpath']) < 2) ) {
		echo '<div class="row"><div class="col-md-6 col-md-offset-3"><div class="alert alert-danger"><strong>Error</strong> You have chosen XML with custom XPath as the format for your corpus, but you have not specified your XPath query.</div></div></div>';
	}
	elseif (strlen($_POST['c1-name']) < 2) {
		echo '<div class="row"><div class="col-md-6 col-md-offset-3"><div class="alert alert-danger"><strong>Error</strong> Please supply a name for your corpus</div></div></div>';
	}
	elseif ( ($_POST['c1-format'] == 'eindhoven') AND ($_POST['c1-discard-cutoff'] != 'never') ) {
		echo '<div class="row"><div class="col-md-6 col-md-offset-3"><div class="alert alert-danger"><strong>Error</strong> Discarding small corpus files isn\'t available for corpora in Eindhoven format.</div></div></div>';
	}
	else {
		require('uploadparse.php');
		if($_FILES['c1-file']['error'] !== UPLOAD_ERR_OK) {
			echo '<div class="row"><div class="col-md-6 col-md-offset-3"><div class="alert alert-danger"><strong>Error</strong> The corpus generated an upload error.</div></div></div>';
			require('html/bottom.html');
			exit;
		}
		elseif(empty($_FILES)) {
			echo '<div class="row"><div class="col-md-6 col-md-offset-3"><div class="alert alert-danger"><strong>Error</strong> No corpus was uploaded or the corpus exceeded the maximum upload size. Please use high compression or contact the administrator of your snelSLiM installation if you need to upload a very large corpus.</div></div></div>';
			require('html/bottom.html');
			exit;
		}
		$extra = NULL;
		if($_POST['c1-format'] == 'conll') {
			$extra = $_POST['c1-extra-conll'];
		}
		elseif($_POST['c1-format'] == 'xpath') {
			$extra = $_POST['c1-extra-xpath'];
		}
		$plainwords = FALSE;
		if($_SESSION['poweruser'] == 1 && isset($_POST['c1-plainwords']) && $_POST['c1-plainwords'] == 'on') {
			$plainwords = TRUE;
		}
		$discard_cutoff = 0;
		if($_POST['c1-discard-cutoff'] == '250' ) {
			$discard_cutoff = 250;
		}
		elseif($_POST['c1-discard-cutoff'] == '500') {
			$discard_cutoff = 500;
		}
		$insert_corpus = $db->prepare('INSERT INTO corpora (name, format, extra, owner, datetime) VALUES (?,?,?,?,NOW())');
		$insert_corpus->execute(array($_POST['c1-name'], $_POST['c1-format'], $extra, $_SESSION['email']));
		$id = $db->lastInsertId();
		$corpus = uploadparse($_FILES['c1-file'], $_POST['c1-format'], $extra, $plainwords, $discard_cutoff, false, $id);
		
		echo '<div class="row"><div class="col-md-6 col-md-offset-3"><div class="alert alert-success"><strong>Success</strong> Your corpus has been saved correctly and is being processed.</div></div></div>';
	}
}

if(isset($_GET['add'])) {
?>
	<form action="?corpora" method="post" enctype="multipart/form-data">
	<div class="row">
		<div class="col-md-4 col-md-offset-4">
			<fieldset>
				<legend>New corpus</legend>
				<?php if($demo) echo '<div class="alert alert-info">This feature is not available in this demo.</div>'; ?>
				<div class="form-group">
					<label for="c1-name" class="control-label">Name</label>
					<input class="form-control" id="c1-name" type="text" name="c1-name" <?php if($demo) echo "disabled" ?>>
				</div>
				<div class="form-group">
					<label for="c1-file" class="control-label">
						Corpus file
						<a href="https://github.com/bertvandepoel/snelSLiM/tree/master/docs/user-manual.pdf" target="_blank" data-toggle="tooltip" class="formtooltip" title="Supply your corpus in the form of a zip or tar containing your texts or fragments. For more detailed instructions please refer to the manual."><span class="glyphicon glyphicon-question-sign"></span></a>
					</label>
					<input class="form-control" id="c1-file" type="file" name="c1-file" <?php if($demo) echo "disabled" ?>>
				</div>
				<div class="form-group">
					<label for="c1-format" class="control-label">
						Corpus format new corpus
						<a href="?formats" target="_blank" data-toggle="tooltip" class="formtooltip" title="If you are not sure what to select, click here to go to the Corpus Formats help page"><span class="glyphicon glyphicon-question-sign"></span></a>
					</label>
					<select class="form-control" id="c1-format" name="c1-format">
						<option value="autodetect">Autodetect format</option>
						<option value="plain">Plain text (txt)</option>
						<option value="plain-striptags">Plain text (txt) with tags removed</option>
						<option value="conll">CoNLL tab-seperated values, specify column index</option>
						<option value="folia-text-fast">FoLiA XML - fast method: literal string</option>
						<option value="folia-lemma-fast">FoLiA XML - fast method: lemma</option>
						<option value="folia-text-xpath">FoLiA XML - slow method: literal string</option>
						<option value="folia-lemma-xpath">FoLiA XML - slow method: lemma</option>
						<option value="dcoi-text">DCOI XML: literal string</option>
						<option value="dcoi-lemma">DCOI XML: lemma</option>
						<option value="alpino-text">Alpino XML: literal string</option>
						<option value="alpino-lemma">Alpino XML: lemma</option>
						<option value="textgrid">PRAAT TextGrid (literal transcript only)</option>
						<option value="bnc-text">TEI XML - BNC/Brown Corpus variant: literal string</option>
						<option value="bnc-lemma">TEI XML - BNC/Brown Corpus variant: lemma</option>
						<option value="eindhoven">Corpus Eindhoven format (literal string only)</option>
						<option value="gysseling-text">Corpus Gysseling format: literal string</option>
						<option value="gysseling-lemma">Corpus Gysseling format: lemma</option>
						<option value="graf-text">XCES GrAF: literal string (may not be available)</option>
						<option value="graf-lemma">XCES GrAF: base</option>
						<option value="opus-text">NLPL OPUS (XML or parsed): literal string</option>
						<option value="opus-lemma">NLPL OPUS (XML or parsed): lemma</option>
						<option value="xpath">XML, specify XPath</option>
					</select>
				</div>
				<div class="form-group collapse" id="c1-extra-conll-container">
					<label for="c1-extra-conll" class="control-label">Index of column containing words or lemmas</label>
					<input class="form-control" id="c1-extra-conll" type="text" name="c1-extra-conll">
				</div>
				<div class="form-group collapse" id="c1-extra-xpath-container">
					<label for="c1-extra-xpath" class="control-label">XPath Query</label>
					<input class="form-control" id="c1-extra-xpath" type="text" name="c1-extra-xpath">
					<p>Please refer to the <a href="?formats#xpath" target="_blank">section on XPath</a> on the corpus formats help page for important limitations.</p>
				</div>
				<?php if($_SESSION['poweruser'] == 1) { ?>
				<div class="form-group">
					<label for="c1-plainwords" class="control-label"><input id="c1-plainwords" type="checkbox" name="c1-plainwords"> Enable collocational analysis for this corpus <span class="emphasize">(beware: this option can use a lot of disk space and slows processing)</span></label>
				</div>
				<?php } ?>
				<div class="form-group">
					<label class="control-label">Discard small corpus files that may distort results:</label>
					<div class="radio">
						<label for="c1-discard-cutoff-never"><input id="c1-discard-cutoff-never" name="c1-discard-cutoff" type="radio" value="never" checked>Never</label><br>
						<label for="c1-discard-cutoff-250"><input id="c1-discard-cutoff-250" name="c1-discard-cutoff" type="radio" value="250">When the file contains less than 250 tokens</label><br>
						<label for="c1-discard-cutoff-500"><input id="c1-discard-cutoff-500" name="c1-discard-cutoff" type="radio" value="500">When the file contains less than 500 tokens</label>
					</div>
				</div>
				<div class="form-group">
					<button type="submit" class="btn btn-primary" name="add" <?php if($demo) echo "disabled" ?>><span class="glyphicon glyphicon-plus" aria-hidden="true"></span> &nbsp; Add Corpus</button>
				</div>
			</fieldset>
		</div>
	</div>
	</form>



<?php
}
else {
	$get_corpora = $db->prepare('SELECT id, name, format, extra, datetime FROM corpora WHERE owner=?');
	$get_corpora->execute(array($_SESSION['email']));
	
	$formats = array('conll' => 'CoNLL tab-seperated values', 'folia-text-fast' => 'FoLiA XML - fast method: literal string', 'folia-lemma-fast' => 'FoLiA XML - fast method: lemma', 'folia-text-xpath' => 'FoLiA XML - slow method: literal string', 'folia-lemma-xpath' => 'FoLiA XML - slow method: lemma', 'dcoi-text' => 'DCOI XML: literal string', 'dcoi-lemma' => 'DCOI XML: lemma', 'plain' => 'Plain text (txt)', 'plain-striptags' => 'Plain text (txt) with tags removed', 'alpino-text' => 'Alpino XML: literal string', 'alpino-lemma' => 'Alpino XML: lemma', 'textgrid' => 'PRAAT TextGrid (literal transcript only)', 'bnc-text' => 'TEI XML - BNC/Brown Corpus variant: literal string', 'bnc-lemma' => 'TEI XML - BNC/Brown Corpus variant: lemma', 'eindhoven' => 'Corpus Eindhoven format (literal string only)', 'gysseling-text' => 'Corpus Gysseling format: literal string', 'gysseling-lemma' => 'Corpus Gysseling format: lemma', 'graf-text' => 'XCES GrAF: literal string (may not be available)', 'graf-lemma' => 'XCES GrAF: base', 'opus-text' => 'NLPL OPUS: literal string', 'opus-lemma' => 'NLPL OPUS: lemma', 'xpath' => 'XML');
?>

<div class="page-header" id="banner">
	<div class="row">
		<div class="col-md-12">
			<h1>My corpora</h1>
			<p class="lead">Below are all corpora you have saved for multiple and/or future uses.</p>
		</div>
	</div>
</div>
<?php
if( isset($_SESSION['admin']) && ($_SESSION['admin']) ) {
	$get_global_corpora = $db->prepare('SELECT id, name, format, extra, datetime FROM corpora WHERE owner IS NULL');
	$get_global_corpora->execute(array());
?>

<div class="row">
	<div class="col-md-12">
		<div class="alert alert-info alert-dismissible"><button type="button" class="close" data-dismiss="alert">×</button><span class="glyphicon glyphicon-info-sign" aria-hidden="true"></span> &nbsp; As an admin, you are able to upgrade your personal corpora to global corpora. Beware that those corpora are then available for any user to generate reports based on. An admin can also delete global corpora, this deletion affects all users.</div>
	</div>
</div>

<div class="row">
	<div class="col-md-12">
		<h2>Global corpora</h2>
	</div>
</div>

<div class="row">
	<div class="col-md-12">
		<table class="table table-striped table-hover">
			<thead>
				<tr><th>Name</th><th>Size</th><th>Format</th><th>Uploaded on</th><th>Status</th><th>Delete</th></tr>
			</thead>
			<tbody>
<?php
			while($corpus = $get_global_corpora->fetch(PDO::FETCH_ASSOC)) {
				if($corpus['format'] == 'autodetect') {
					$autodetect = file_get_contents('../data/preparsed/saved/' . $corpus['id'] . '/autodetect');
					if($autodetect == 'unknown') {
						$corpus['format'] = 'Autodetect: unknown format';
					}
					elseif($autodetect == 'partknown') {
						$corpus['format'] = 'Autodetect: some files are an unknown format';
					}
					elseif($autodetect == 'mixed') {
						$corpus['format'] = 'Autodetect: several different formats';
					}
					elseif($autodetect == 'xml-opus') {
						$corpus['format'] = 'Autodetect: unknown XML format (might be NLPL OPUS)';
					}
					elseif($autodetect == 'xml') {
						$corpus['format'] = 'Autodetect: unknown XML format';
					}
					elseif($autodetect == 'tabs') {
						$corpus['format'] = 'Autodetect: CoNLL tsv format, column number required';
					}
					elseif($autodetect == 'textgrid') {
						$corpus['format'] = 'Autodetect: ' . $formats[$autodetect];
					}
					elseif($autodetect == 'eindhoven') {
						$corpus['format'] = 'Autodetect: ' . $formats[$autodetect];
					}
					elseif($autodetect == 'folia') {
						$corpus['format'] = 'Autodetect: ' . $formats['folia-lemma-fast'];
					}
					else {
						$corpus['format'] = 'Autodetect: ' . $formats[$autodetect . '-lemma'];
					}
				}
				else {
					if($corpus['format'] == 'conll') {
						$corpus['format'] = $formats[$corpus['format']] . ' (column ' . $corpus['extra'] . ')';
					}
					elseif($corpus['format'] == 'xpath') {
						$corpus['format'] = $formats[$corpus['format']] . ' (XPath query: ' . $corpus['extra'] . ')';
					}
					else {
						$corpus['format'] = $formats[$corpus['format']];
					}
				}
				if(file_exists('../data/preparsed/saved/' . $corpus['id'] . '/error')) {
					$status = '<span class="label label-danger"><span class="glyphicon glyphicon-exclamation-sign" aria-hidden="true"></span> error</span>';
				}
				elseif(file_exists('../data/preparsed/saved/' . $corpus['id'] . '/done')) {
					$status = '<span class="label label-success"><span class="glyphicon glyphicon-ok-sign" aria-hidden="true"></span> done</span>';
				}
				else {
					$status = '<span class="label label-default"><span class="glyphicon glyphicon-hourglass" aria-hidden="true"></span> processing</span>';
				}
				
				$corpuslabel = '';
				if(file_exists('../data/preparsed/saved/' . $corpus['id'] . '/plainwords')) {
					$corpuslabel .= ' &nbsp; <span class="label label-info" title="Prepared for Collocational Analysis">CA ready</span>';
				}
				if(file_exists('../data/preparsed/saved/' . $corpus['id'] . '/warning_numfiles')) {
					$corpuslabel .= ' &nbsp; <span class="label label-warning" title="This corpus has very few files. Stability across different texts is an important aspects of Stable Lexical Marker Analysis, so several files are required.">warning</span>';
				}
				if(file_exists('../data/preparsed/saved/' . $corpus['id'] . '/warning_small')) {
					$corpuslabel .= ' &nbsp; <span class="label label-warning" title="This corpus contains some smaller files. If a file contains fewer than 500 words it may not be very suitable for Stable Lexical Marker Analysis. Consider re-uploading the corpus with the option to discard small files.">warning</span>';
				}
				if(file_exists('../data/preparsed/saved/' . $corpus['id'] . '/warning_extrasmall')) {
					$corpuslabel .= ' &nbsp; <span class="label label-warning" title="This corpus contains some very small files. If a file contains fewer than 250 words it is most probably unsuitable for Stable Lexical Marker Analysis. Consider re-uploading the corpus with the option to discard small files.">warning</span>';
				}
				if(file_exists('../data/preparsed/saved/' . $corpus['id'] . '/warning_distribution')) {
					$corpuslabel .= ' &nbsp; <span class="label label-warning" title="This corpus contains files of very different sizes. The smallest file contains over 20 times fewer words than the largest, this may yield untrustworthy results.">warning</span>';
				}
				$corpussize = '';
				if(file_exists('../data/preparsed/saved/' . $corpus['id'] . '/corpussize')) {
					$corpussize = number_format(file_get_contents('../data/preparsed/saved/' . $corpus['id'] . '/corpussize'), 0, '.', ' ') . ' tokens';
					$corpusfiles = scandir('../data/preparsed/saved/' . $corpus['id'] . '/');
					$numfiles = 0;
					foreach($corpusfiles as $corpusfile) {
						if(substr($corpusfile, -9) == '.snelslim') {
							$numfiles++;
						}
					}
					$corpussize .= ' (in ' . $numfiles . ' files)';
				}
				
				echo '<tr><td>' . $corpus['name'] . $corpuslabel . '</td><td>' . $corpussize . '</td><td>' . $corpus['format'] . '</td><td>' . date("d M Y \a\\t H:i", strtotime($corpus['datetime'])) . '</td><td>' . $status . '</td><td><a class="btn btn-primary btn-xs" href="?corpora&deleteglobal=' . $corpus['id'] . '"><span class="glyphicon glyphicon-trash" aria-hidden="true"></span> Delete</a></td>';
			}
?>
			</tbody>
		</table>
	</div>
</div>

<div class="row">
	<div class="col-md-12">
		<h2>Personal corpora</h2>
	</div>
</div>

<?php
}
?>
<div class="row">
	<div class="col-md-12">
		<a href="?corpora&add" class="btn btn-primary" role="button"><span class="glyphicon glyphicon-plus" aria-hidden="true"></span> &nbsp; Add new corpus</a>
	</div>
</div>
<div class="row">
	<div class="col-md-12">
		<table class="table table-striped table-hover">
			<thead>
<?php
			if( isset($_SESSION['admin']) && ($_SESSION['admin']) ) { 
				echo '<tr><th>Name</th><th>Size</th><th>Format</th><th>Uploaded on</th><th>Status</th><th>Make corpus global</th><th>Delete</th></tr>';
			}
			else {
				echo '<tr><th>Name</th><th>Size</th><th>Format</th><th>Uploaded on</th><th>Status</th><th>Delete</th></tr>';
			}
?>
			</thead>
			<tbody>
<?php
			while($corpus = $get_corpora->fetch(PDO::FETCH_ASSOC)) {
				if($corpus['format'] == 'autodetect') {
					$autodetect = file_get_contents('../data/preparsed/saved/' . $corpus['id'] . '/autodetect');
					if($autodetect == 'unknown') {
						$corpus['format'] = 'Autodetect: unknown format';
					}
					elseif($autodetect == 'partknown') {
						$corpus['format'] = 'Autodetect: some files are an unknown format';
					}
					elseif($autodetect == 'mixed') {
						$corpus['format'] = 'Autodetect: several different formats';
					}
					elseif($autodetect == 'xml-opus') {
						$corpus['format'] = 'Autodetect: unknown XML format (might be NLPL OPUS)';
					}
					elseif($autodetect == 'xml') {
						$corpus['format'] = 'Autodetect: unknown XML format';
					}
					elseif($autodetect == 'tabs') {
						$corpus['format'] = 'Autodetect: CoNLL tsv format, column number required';
					}
					elseif($autodetect == 'textgrid') {
						$corpus['format'] = 'Autodetect: ' . $formats[$autodetect];
					}
					elseif($autodetect == 'eindhoven') {
						$corpus['format'] = 'Autodetect: ' . $formats[$autodetect];
					}
					elseif($autodetect == 'folia') {
						$corpus['format'] = 'Autodetect: ' . $formats['folia-lemma-fast'];
					}
					else {
						$corpus['format'] = 'Autodetect: ' . $formats[$autodetect . '-lemma'];
					}
				}
				else {
					if($corpus['format'] == 'conll') {
						$corpus['format'] = $formats[$corpus['format']] . ' (column ' . $corpus['extra'] . ')';
					}
					elseif($corpus['format'] == 'xpath') {
						$corpus['format'] = $formats[$corpus['format']] . ' (XPath query: ' . $corpus['extra'] . ')';
					}
					else {
						$corpus['format'] = $formats[$corpus['format']];
					}
				}
				if(file_exists('../data/preparsed/saved/' . $corpus['id'] . '/error')) {
					$error = file_get_contents('../data/preparsed/saved/' . $corpus['id'] . '/error');
					$status = '<span class="label label-danger" data-toggle="tooltip" title="' . $error . '"><span class="glyphicon glyphicon-exclamation-sign" aria-hidden="true"></span> error</span>';
				}
				elseif(file_exists('../data/preparsed/saved/' . $corpus['id'] . '/done')) {
					$status = '<span class="label label-success"><span class="glyphicon glyphicon-ok-sign" aria-hidden="true"></span> done</span>';
				}
				else {
					$status = '<span class="label label-default"><span class="glyphicon glyphicon-hourglass" aria-hidden="true"></span> processing</span>';
				}
				
				$corpuslabel = '';
				if(file_exists('../data/preparsed/saved/' . $corpus['id'] . '/plainwords')) {
					$corpuslabel .= ' &nbsp; <span class="label label-info" title="Prepared for Collocational Analysis">CA ready</span>';
				}
				if(file_exists('../data/preparsed/saved/' . $corpus['id'] . '/warning_numfiles')) {
					$corpuslabel .= ' &nbsp; <span class="label label-warning" title="This corpus has very few files. Stability across different texts is an important aspects of Stable Lexical Marker Analysis, so several files are required.">warning</span>';
				}
				if(file_exists('../data/preparsed/saved/' . $corpus['id'] . '/warning_small')) {
					$corpuslabel .= ' &nbsp; <span class="label label-warning" title="This corpus contains some smaller files. If a file contains fewer than 500 words it may not be very suitable for Stable Lexical Marker Analysis. Consider re-uploading the corpus with the option to discard small files.">warning</span>';
				}
				if(file_exists('../data/preparsed/saved/' . $corpus['id'] . '/warning_extrasmall')) {
					$corpuslabel .= ' &nbsp; <span class="label label-warning" title="This corpus contains some very small files. If a file contains fewer than 250 words it is most probably unsuitable for Stable Lexical Marker Analysis. Consider re-uploading the corpus with the option to discard small files.">warning</span>';
				}
				if(file_exists('../data/preparsed/saved/' . $corpus['id'] . '/warning_distribution')) {
					$corpuslabel .= ' &nbsp; <span class="label label-warning" title="This corpus contains files of very different sizes. The smallest file contains over 20 times fewer words than the largest, this may yield untrustworthy results.">warning</span>';
				}
				$corpussize = '';
				if(file_exists('../data/preparsed/saved/' . $corpus['id'] . '/corpussize')) {
					$corpussize = number_format(file_get_contents('../data/preparsed/saved/' . $corpus['id'] . '/corpussize'), 0, '.', ' ') . ' tokens';
					$corpusfiles = scandir('../data/preparsed/saved/' . $corpus['id'] . '/');
					$numfiles = 0;
					foreach($corpusfiles as $corpusfile) {
						if(substr($corpusfile, -9) == '.snelslim') {
							$numfiles++;
						}
					}
					$corpussize .= ' (in ' . $numfiles . ' files)';
				}
				
				if( isset($_SESSION['admin']) && ($_SESSION['admin']) ) {
				echo '<tr><td>' . $corpus['name'] . $corpuslabel . '</td><td>' . $corpussize . '</td><td>' . $corpus['format'] . '</td><td>' . date("d M Y \a\\t H:i", strtotime($corpus['datetime'])) . '</td><td>' . $status . '</td><td><a class="btn btn-primary btn-xs" href="?corpora&castglobal=' . $corpus['id'] . '"><span class="glyphicon glyphicon-globe" aria-hidden="true"></span> Make corpus global</a></td><td><a class="btn btn-primary btn-xs" href="?corpora&delete=' . $corpus['id'] . '"><span class="glyphicon glyphicon-trash" aria-hidden="true"></span> Delete</a></td>';
				}
				else {		
					echo '<tr><td>' . $corpus['name'] . $corpuslabel . '</td><td>' . $corpussize . '</td><td>' . $corpus['format'] . '</td><td>' . date("d M Y \a\\t H:i", strtotime($corpus['datetime'])) . '</td><td>' . $status . '</td><td><a class="btn btn-primary btn-xs" href="?corpora&delete=' . $corpus['id'] . '"><span class="glyphicon glyphicon-trash" aria-hidden="true"></span> Delete</a></td>';
				}
			}
?>
			</tbody>
		</table>
	</div>
</div>

<?php

}
