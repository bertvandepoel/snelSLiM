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
	$delete = $db->prepare('DELETE FROM accounts WHERE email=?');
	$delete->execute(array($_GET['delete']));
	if($delete->rowCount() > 0) {
		$get_reports = $db->prepare('SELECT id FROM reports WHERE owner=?');
		$get_reports->execute(array($_GET['delete']));
		while($report = $get_reports->fetch(PDO::FETCH_ASSOC)) {
			unlink('../slm/reports/' . $report['id'] . '/c1.report');
			unlink('../slm/reports/' . $report['id'] . '/c2.report');
			unlink('../slm/reports/' . $report['id'] . '/c1frag.report');
			unlink('../slm/reports/' . $report['id'] . '/c2frag.report');
			unlink('../slm/reports/' . $report['id'] . '/done');
			unlink('../slm/reports/' . $report['id'] . '/error');
			rmdir('../slm/reports/' . $report['id']);
		}
		$delete_reports = $db->prepare('DELETE FROM reports WHERE owner=?');
		$delete_reports->execute(array($_GET['delete']));
		
		$get_corpora = $db->prepare('SELECT id FROM corpora WHERE owner=?');
		$get_corpora->execute(array($_GET['delete']));
		while($corpus = $get_corpora->fetch(PDO::FETCH_ASSOC)) {
			foreach(scandir('../slm/preparsed/saved/' . $corpus['id']) as $file) {
				unlink('../slm/preparsed/saved/' . $corpus['id'] . '/' . $file);
			}
			rmdir('../slm/preparsed/saved/' . $corpus['id']);
		}
		$delete_corpora = $db->prepare('DELETE FROM corpora WHERE owner=?');
		$delete_corpora->execute(array($_GET['delete']));
	}
}
elseif(isset($_POST['create'])) {
	require('mail.php');
	if($_POST['isAdmin'] == 'yes') {
		$isadmin = 1;
	}
	else {
		$isadmin = 0;
	}
	$insert = $db->prepare('INSERT INTO accounts VALUES (?,?,?)');
	$insert->execute(array($_POST['email'], password_hash($_POST['password'], PASSWORD_DEFAULT), $isadmin));
	email_new_account($_POST['email'], $_POST['password']);
}

if(isset($_GET['add'])) {
?>
	<div class="row">
		<div class="well col-md-4 col-md-offset-4">
			<form action="?accounts" method="post">
				<fieldset>
					<legend>Create new account</legend>
					<div class="form-group">
						<label for="inputEmail" class="control-label">Email</label>
						<input class="form-control" id="inputEmail" placeholder="Email" type="text" name="email">
					</div>
					<div class="form-group">
						<label for="inputPassword" class="control-label">Password</label>
						<input class="form-control" id="inputPassword" placeholder="Password" type="password" name="password">
					</div>
					<div class="form-group">
						<label class="control-label">Admin account?</label><br>
						<label for="adminyes" class="radio-inline"><input type="radio" name="isAdmin" value="yes" id="adminyes">Yes</label>
						<label for="adminno" class="radio-inline"><input type="radio" name="isAdmin" value="no" id="adminno" checked>No</label>
					</div>
					<div class="form-group">
						<button type="submit" class="btn btn-primary" name="create">Create new account</button>
					</div>
				</fieldset>
			</form>
		</div>
	</div>
	
	
<?php
}
else {
	$get_accounts = $db->prepare('SELECT email FROM accounts');
	$get_accounts->execute(array());

?>

<div class="page-header" id="banner">
	<div class="row">
		<div class="col-md-12">
			<h1>Accounts</h1>
			<p class="lead">Below are all the accounts in snelSLiM, keep in mind that if you delete an account, all reports and corpora that have been uploaded through that account will be deleted.</p>
		</div>
	</div>
</div>
<div class="row">
	<div class="col-md-4 col-md-offset-4" style="margin-bottom: 10px;">
		<a href="?accounts&add" class="btn btn-primary" role="button">Add new account</a>
	</div>
</div>
<div class="row">
	<div class="col-md-4 col-md-offset-4">
		<table class="table table-striped table-hover">
			<thead>
				<tr><th>account</th><th>Delete</th></tr>
			</thead>
			<tbody>
<?php
			while($account = $get_accounts->fetch(PDO::FETCH_ASSOC)) {
				echo '<tr><td>' . $account['email'] . '</td><td><a class="btn btn-primary btn-xs" href="?accounts&delete=' . $account['email'] . '">Delete</a></td>';
			}
?>
			</tbody>
		</table>
	</div>
</div>
<?php

}
