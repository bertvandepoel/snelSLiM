<?php
/*
 * snelSLiM - Interface for quick Stable Lexical Marker Analysis
 * Copyright (c) 2017 Bert Van de Poel
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
		foreach(scandir('../slm/preparsed/saved/' . $_GET['delete']) as $file) {
			// no folders here, so no need to check
			unlink('../slm/preparsed/saved/' . $_GET['delete'] . '/' . $file);
		}
		rmdir('../slm/preparsed/saved/' . $_GET['delete']);
	}
	echo '<div class="row"><div class="col-md-6 col-md-offset-3"><div class="alert alert-success"><strong>Success</strong> The corpus was removed.</div></div></div>';
}

if(isset($_GET['deleteglobal'])) {
	$delete = $db->prepare('DELETE FROM corpora WHERE id=? AND owner IS NULL');
	$delete->execute(array($_GET['deleteglobal']));
	if($delete->rowCount() > 0) {
		foreach(scandir('../slm/preparsed/saved/' . $_GET['deleteglobal']) as $file) {
			// no folders here, so no need to check
			unlink('../slm/preparsed/saved/' . $_GET['deleteglobal'] . '/' . $file);
		}
		rmdir('../slm/preparsed/saved/' . $_GET['deleteglobal']);
	}
	echo '<div class="row"><div class="col-md-6 col-md-offset-3"><div class="alert alert-success"><strong>Success</strong> The corpus was removed.</div></div></div>';
}

if( isset($_GET['castglobal']) && isset($_SESSION['admin']) && ($_SESSION['admin']) ) {
	$update = $db->prepare('UPDATE corpora SET owner=NULL WHERE id=? AND owner=?');
	$update->execute(array($_GET['castglobal'], $_SESSION['email']));
	echo '<div class="row"><div class="col-md-6 col-md-offset-3"><div class="alert alert-success"><strong>Success</strong> Your corpus is now global.</div></div></div>';
}

if(isset($_POST['add'])) {
	if ( ($_POST['c1-format'] == 'conll') AND  (intval($_POST['c1-extra-conll']) < 1) ) {
		echo '<div class="row"><div class="col-md-6 col-md-offset-3"><div class="alert alert-danger"><strong>Error</strong> You have chosen CoNLL as the format for your corpus, but you have not specified which column to select.</div></div></div>';
	}
	elseif ( ($_POST['c1-format'] == 'xpath') AND  (strlen($_POST['c1-extra-xpath']) < 2) ) {
		echo '<div class="row"><div class="col-md-6 col-md-offset-3"><div class="alert alert-danger"><strong>Error</strong> You have chosen XML with custom XPath as the format for your corpus, but you have not specified your XPath query.</div></div></div>';
	}
	elseif (strlen($_POST['c1-name']) < 2) {
		echo '<div class="row"><div class="col-md-6 col-md-offset-3"><div class="alert alert-danger"><strong>Error</strong> Please supply a name for your corpus</div></div></div>';
	}
	else {
		require('uploadparse.php');
		if($_FILES['c1-file']['error'] !== UPLOAD_ERR_OK) {
			echo '<div class="row"><div class="col-md-6 col-md-offset-3"><div class="alert alert-danger"><strong>Error</strong> Corpus one generated an upload error.</div></div></div>';
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
		$insert_corpus = $db->prepare('INSERT INTO corpora (name, format, extra, owner, datetime) VALUES (?,?,?,?,NOW())');
		$insert_corpus->execute(array($_POST['c1-name'], $_POST['c1-format'], $extra, $_SESSION['email']));
		$id = $db->lastInsertId();
		$corpus = uploadparse($_FILES['c1-file'], $_POST['c1-format'], $extra, $plainwords, false, $id);
		
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
				<div class="form-group">
					<label for="c1-name" class="control-label">Name</label>
					<input class="form-control" id="c1-name" type="text" name="c1-name">
				</div>
				<div class="form-group">
					<label for="c1-file" class="control-label">
						Corpus file
						<a href="#" data-toggle="tooltip" class="formtooltip" title="Supply your corpus in the form of a zip or tar containing your texts or fragments directly or in a single level of subfolders"><span class="glyphicon glyphicon-question-sign"></span></a>
					</label>
					<input class="form-control" id="c1-file" type="file" name="c1-file">
				</div>
				<div class="form-group">
					<label for="c1-format" class="control-label">
						Corpus format new corpus
						<a href="?formats" data-toggle="tooltip" class="formtooltip" title="If you are not sure what to select, click here to go to the Corpus Formats help page"><span class="glyphicon glyphicon-question-sign"></span></a>
					</label>
					<select class="form-control" id="c1-format" name="c1-format">
						<option value="autodetect">Autodetect format</option>
						<option value="plain">Plain text (txt)</option>
						<option value="conll">CoNLL tab-seperated values, specify column index</option>
						<option value="folia-text-fast">FoLiA XML - fast method: literal string</option>
						<option value="folia-lemma-fast">FoLiA XML - fast method: lemma</option>
						<option value="folia-text-xpath">FoLiA XML - slow method: literal string</option>
						<option value="folia-lemma-xpath">FoLiA XML - slow method: lemma</option>
						<option value="dcoi-text">DCOI XML: literal string</option>
						<option value="dcoi-lemma">DCOI XML: lemma</option>
						<option value="alpino-text">Alpino XML: literal string</option>
						<option value="alpino-lemma">Alpino XML: lemma</option>
						<option value="bnc-text">TEI XML - BNC/Brown Corpus variant: literal string</option>
						<option value="bnc-lemma">TEI XML - BNC/Brown Corpus variant: lemma</option>
						<option value="eindhoven">Corpus Eindhoven format (literal string only)</option>
						<option value="gysseling-text">Corpus Gysseling format: literal string</option>
						<option value="gysseling-lemma">Corpus Gysseling format: lemma</option>
						<option value="masc-text">OANC MASC XML: literal string</option>
						<option value="masc-lemma">OANC MASC XML: base</option>
						<option value="oanc">OANC XML (base only)</option>
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
				</div>
				<?php if($_SESSION['poweruser'] == 1) { ?>
				<div class="form-group">
					<label for="c1-plainwords" class="control-label"><input id="c1-plainwords" type="checkbox" name="c1-plainwords"> Enable collocational analysis for this corpus <span class="emphasize">(beware: this option can use a lot of disk space and slows processing)</span></label> 
				</div>
				<?php } ?>
				<div class="form-group">
					<button type="submit" class="btn btn-primary" name="add">Add Corpus</button>
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
	
	$formats = array('conll' => 'CoNLL tab-seperated values (specified column index as extra)', 'folia-text-fast' => 'FoLiA XML - fast method: literal string', 'folia-lemma-fast' => 'FoLiA XML - fast method: lemma', 'folia-text-xpath' => 'FoLiA XML - slow method: literal string', 'folia-lemma-xpath' => 'FoLiA XML - slow method: lemma', 'dcoi-text' => 'DCOI XML: literal string', 'dcoi-lemma' => 'DCOI XML: lemma', 'plain' => 'Plain text (txt)', 'alpino-text' => 'Alpino XML: literal string', 'alpino-lemma' => 'Alpino XML: lemma', 'bnc-text' => 'TEI XML - BNC/Brown Corpus variant: literal string', 'bnc-lemma' => 'TEI XML - BNC/Brown Corpus variant: lemma', 'eindhoven' => 'Corpus Eindhoven format (literal string only)', 'gysseling-text' => 'Corpus Gysseling format: literal string', 'gysseling-lemma' => 'Corpus Gysseling format: lemma', 'masc-text' => 'OANC MASC XML: literal string', 'masc-lemma' => 'OANC MASC XML: base', 'oanc' => 'OANC XML (base only)', 'xpath' => 'XML (specified XPath as extra)');
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
		<div class="alert alert-info alert-dismissible"><button type="button" class="close" data-dismiss="alert">×</button>As an admin, you are able to upgrade your personal corpora to global corpora. Beware that those corpora are then available for any user to generate reports based on. An admin can also delete global corpora, this deletion affects all users.</div>
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
				<tr><th>Name</th><th>Format</th><th>Extra format option</th><th>Uploaded on</th><th>Status</th><th>Delete</th></tr>
			</thead>
			<tbody>
<?php
			while($corpus = $get_global_corpora->fetch(PDO::FETCH_ASSOC)) {
				if($corpus['format'] == 'autodetect') {
					$autodetect = file_get_contents('../slm/preparsed/saved/' . $corpus['id'] . '/autodetect');
					if($autodetect == 'unknown') {
						$corpus['format'] = 'Autodetect: unknown format';
					}
					elseif($autodetect == 'partknown') {
						$corpus['format'] = 'Autodetect: some files are an unknown format';
					}
					elseif($autodetect == 'mixed') {
						$corpus['format'] = 'Autodetect: several different formats';
					}
					elseif($autodetect == 'xml') {
						$corpus['format'] = 'Autodetect: unknown XML format';
					}
					elseif($autodetect == 'tabs') {
						$corpus['format'] = 'Autodetect: CoNLL tsv format, column number required';
					}
					elseif($autodetect == 'oanc' || $autodetect == 'eindhoven') {
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
					$corpus['format'] = $formats[$corpus['format']];
				}
				if(file_exists('../slm/preparsed/saved/' . $corpus['id'] . '/error')) {
					$status = '<span class="label label-danger">error</span>';
				}
				elseif(file_exists('../slm/preparsed/saved/' . $corpus['id'] . '/done')) {
					$status = '<span class="label label-success">done</span>';
				}
				else {
					$status = '<span class="label label-default">processing</span>';
				}
				
				$corpuslabel = '';
				if(file_exists('../slm/preparsed/saved/' . $corpus['id'] . '/plainwords')) {
					$corpuslabel = ' &nbsp; <span class="label label-info" title="Prepared for Collocational Analysis">CA ready</span>';
				}
				
				echo '<tr><td>' . $corpus['name'] . $corpuslabel . '</td><td>' . $corpus['format'] . '</td><td>' . $corpus['extra'] . '</td><td>' . date("d M Y \a\\t H:i", strtotime($corpus['datetime'])) . '</td><td>' . $status . '</td><td><a class="btn btn-primary btn-xs" href="?corpora&deleteglobal=' . $corpus['id'] . '">Delete</a></td>';
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
		<a href="?corpora&add" class="btn btn-primary" role="button">Add new corpus</a>
	</div>
</div>
<div class="row">
	<div class="col-md-12">
		<table class="table table-striped table-hover">
			<thead>
<?php
			if( isset($_SESSION['admin']) && ($_SESSION['admin']) ) { 
				echo '<tr><th>Name</th><th>Format</th><th>Extra format option</th><th>Uploaded on</th><th>Status</th><th>Make corpus global</th><th>Delete</th></tr>';
			}
			else {
				echo '<tr><th>Name</th><th>Format</th><th>Extra format option</th><th>Uploaded on</th><th>Status</th><th>Delete</th></tr>';
			}
?>
			</thead>
			<tbody>
<?php
			while($corpus = $get_corpora->fetch(PDO::FETCH_ASSOC)) {
				if($corpus['format'] == 'autodetect') {
					$autodetect = file_get_contents('../slm/preparsed/saved/' . $corpus['id'] . '/autodetect');
					if($autodetect == 'unknown') {
						$corpus['format'] = 'Autodetect: unknown format';
					}
					elseif($autodetect == 'partknown') {
						$corpus['format'] = 'Autodetect: some files are an unknown format';
					}
					elseif($autodetect == 'mixed') {
						$corpus['format'] = 'Autodetect: several different formats';
					}
					elseif($autodetect == 'xml') {
						$corpus['format'] = 'Autodetect: unknown XML format';
					}
					elseif($autodetect == 'tabs') {
						$corpus['format'] = 'Autodetect: CoNLL tsv format, column number required';
					}
					elseif($autodetect == 'oanc' || $autodetect == 'eindhoven') {
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
					$corpus['format'] = $formats[$corpus['format']];
				}
				if(file_exists('../slm/preparsed/saved/' . $corpus['id'] . '/error')) {
					$error = file_get_contents('../slm/preparsed/saved/' . $corpus['id'] . '/error');
					$status = '<span class="label label-danger" data-toggle="tooltip" title="' . $error . '">error</span>';
				}
				elseif(file_exists('../slm/preparsed/saved/' . $corpus['id'] . '/done')) {
					$status = '<span class="label label-success">done</span>';
				}
				else {
					$status = '<span class="label label-default">processing</span>';
				}
				
				$corpuslabel = '';
				if(file_exists('../slm/preparsed/saved/' . $corpus['id'] . '/plainwords')) {
					$corpuslabel = ' &nbsp; <span class="label label-info" title="Prepared for Collocational Analysis">CA ready</span>';
				}
				
				if( isset($_SESSION['admin']) && ($_SESSION['admin']) ) {
				echo '<tr><td>' . $corpus['name'] . $corpuslabel . '</td><td>' . $corpus['format'] . '</td><td>' . $corpus['extra'] . '</td><td>' . date("d M Y \a\\t H:i", strtotime($corpus['datetime'])) . '</td><td>' . $status . '</td><td><a class="btn btn-primary btn-xs" href="?corpora&castglobal=' . $corpus['id'] . '">Make corpus global</a></td><td><a class="btn btn-primary btn-xs" href="?corpora&delete=' . $corpus['id'] . '">Delete</a></td>';
				}
				else {		
					echo '<tr><td>' . $corpus['name'] . $corpuslabel . '</td><td>' . $corpus['format'] . '</td><td>' . $corpus['extra'] . '</td><td>' . date("d M Y \a\\t H:i", strtotime($corpus['datetime'])) . '</td><td>' . $status . '</td><td><a class="btn btn-primary btn-xs" href="?corpora&delete=' . $corpus['id'] . '">Delete</a></td>';
				}
			}
?>
			</tbody>
		</table>
	</div>
</div>

<?php

}
