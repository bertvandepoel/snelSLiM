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
	$delete = $db->prepare('DELETE FROM accounts WHERE email=?');
	$delete->execute(array($_GET['delete']));
	if($delete->rowCount() > 0) {
		$get_reports = $db->prepare('SELECT id FROM reports WHERE owner=?');
		$get_reports->execute(array($_GET['delete']));
		while($report = $get_reports->fetch(PDO::FETCH_ASSOC)) {
			unlink('../data/reports/' . $report['id'] . '/c1.report');
			unlink('../data/reports/' . $report['id'] . '/c2.report');
			unlink('../data/reports/' . $report['id'] . '/c1frag.report');
			unlink('../data/reports/' . $report['id'] . '/c2frag.report');
			unlink('../data/reports/' . $report['id'] . '/done');
			unlink('../data/reports/' . $report['id'] . '/error');
			rmdir('../data/reports/' . $report['id']);
		}
		$delete_reports = $db->prepare('DELETE FROM reports WHERE owner=?');
		$delete_reports->execute(array($_GET['delete']));
		
		$get_corpora = $db->prepare('SELECT id FROM corpora WHERE owner=?');
		$get_corpora->execute(array($_GET['delete']));
		while($corpus = $get_corpora->fetch(PDO::FETCH_ASSOC)) {
			foreach(scandir('../data/preparsed/saved/' . $corpus['id']) as $file) {
				if($file === '.' OR $file === '..') {
					continue;
				}
				unlink('../data/preparsed/saved/' . $corpus['id'] . '/' . $file);
			}
			rmdir('../data/preparsed/saved/' . $corpus['id']);
		}
		$delete_corpora = $db->prepare('DELETE FROM corpora WHERE owner=?');
		$delete_corpora->execute(array($_GET['delete']));
		echo '<div class="row"><div class="col-md-6 col-md-offset-3"><div class="alert alert-success"><strong>Success</strong> The account and all its corpora and reports were deleted</div></div></div>';
	}
}
elseif(isset($_POST['create'])) {
	require('mail.php');
	$isadmin = 0;
	if($_POST['isAdmin'] == 'yes') {
		$isadmin = 1;
	}
	$poweruser = 0;
	if($_POST['poweruser'] == 'yes') {
		$poweruser = 1;
	}
	$insert = $db->prepare('INSERT INTO accounts VALUES (?,?,?,?)');
	$insert->execute(array($_POST['email'], password_hash($_POST['password'], PASSWORD_DEFAULT), $poweruser, $isadmin));
	email_new_account($_POST['email'], $_POST['password']);
	echo '<div class="row"><div class="col-md-6 col-md-offset-3"><div class="alert alert-success"><strong>Success</strong> The new account was created and the user notified</div></div></div>';
}
elseif(isset($_POST['editpermissions'])) {
	$isadmin = 0;
	if($_POST['isAdmin'] == 'yes') {
		$isadmin = 1;
	}
	$poweruser = 0;
	if($_POST['poweruser'] == 'yes') {
		$poweruser = 1;
	}
	$update = $db->prepare('UPDATE accounts SET poweruser=?, admin=? WHERE email=?');
	$update->execute(array($poweruser, $isadmin, $_POST['email']));
	echo '<div class="row"><div class="col-md-6 col-md-offset-3"><div class="alert alert-success"><strong>Success</strong> The changed permissions have been applied. They will only take effect after the user has logged in again.</div></div></div>';
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
						<label class="control-label">Allow user to use a larger amount of disk space (save corpora for collocational analysis)?</label><br>
						<label for="poweruseryes" class="radio-inline"><input type="radio" name="poweruser" value="yes" id="poweruseryes">Yes</label>
						<label for="poweruserno" class="radio-inline"><input type="radio" name="poweruser" value="no" id="poweruserno" checked>No</label>
					</div>
					<div class="form-group">
						<label class="control-label">Admin account?</label><br>
						<label for="adminyes" class="radio-inline"><input type="radio" name="isAdmin" value="yes" id="adminyes">Yes</label>
						<label for="adminno" class="radio-inline"><input type="radio" name="isAdmin" value="no" id="adminno" checked>No</label>
					</div>
					<div class="form-group">
						<button type="submit" class="btn btn-primary" name="create"><span class="glyphicon glyphicon-plus" aria-hidden="true"></span> &nbsp; Create new account</button>
					</div>
				</fieldset>
			</form>
		</div>
	</div>
	
	
<?php
}
elseif(isset($_GET['editpermissions'])) {
	$get = $db->prepare('SELECT email, poweruser, admin FROM accounts WHERE email=?');
	$get->execute(array($_GET['editpermissions']));
	$row = $get->fetch(PDO::FETCH_ASSOC);
	$poweruseryes = '';
	$poweruserno = 'checked';
	$adminyes = '';
	$adminno = 'checked';
	if($row['poweruser'] == 1) {
		$poweruseryes = 'checked';
		$poweruserno = '';
	}
	if($row['admin'] == 1) {
		$adminyes = 'checked';
		$adminno = '';
	}
?>
	<div class="row">
		<div class="well col-md-4 col-md-offset-4">
			<form action="?accounts" method="post">
				<fieldset>
					<legend>Change permissions of existing account</legend>
					<div class="form-group">
						<label for="inputEmail" class="control-label">Email</label>
						<input class="form-control" id="inputEmail" placeholder="Email" type="text" name="email" value="<?php echo $row['email']; ?>" readonly>
					</div>
					<div class="form-group">
						<label class="control-label">Allow user to use a larger amount of disk space (save corpora for collocational analysis)?</label><br>
						<label for="poweruseryes" class="radio-inline"><input type="radio" name="poweruser" value="yes" id="poweruseryes" <?php echo $poweruseryes; ?>>Yes</label>
						<label for="poweruserno" class="radio-inline"><input type="radio" name="poweruser" value="no" id="poweruserno" <?php echo $poweruserno; ?>>No</label>
					</div>
					<div class="form-group">
						<label class="control-label">Admin account?</label><br>
						<label for="adminyes" class="radio-inline"><input type="radio" name="isAdmin" value="yes" id="adminyes" <?php echo $adminyes; ?>>Yes</label>
						<label for="adminno" class="radio-inline"><input type="radio" name="isAdmin" value="no" id="adminno" <?php echo $adminno; ?>>No</label>
					</div>
					<div class="form-group">
						<button type="submit" class="btn btn-primary" name="editpermissions"><span class="glyphicon glyphicon-pencil"></span> &nbsp; Change permissions</button>
					</div>
				</fieldset>
			</form>
		</div>
	</div>
	
	
<?php
}
else {
	$get_accounts = $db->prepare('SELECT email, poweruser, admin FROM accounts');
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
	<div class="col-md-6 col-md-offset-3" style="margin-bottom: 10px;">
		<a href="?accounts&add" class="btn btn-primary" role="button"><span class="glyphicon glyphicon-plus" aria-hidden="true"></span> &nbsp; Add new account</a>
	</div>
</div>
<div class="row">
	<div class="col-md-6 col-md-offset-3">
		<table class="table table-striped table-hover">
			<thead>
				<tr><th>account</th><th>Permissions</th><th>Delete</th></tr>
			</thead>
			<tbody>
<?php
			while($account = $get_accounts->fetch(PDO::FETCH_ASSOC)) {
				$permission = 'Regular user';
				if($account['poweruser'] == 1 AND $account['admin'] == 1) {
					$permission = 'Poweruser & Admin';
				}
				elseif($account['poweruser'] == 1) {
					$permission = 'Poweruser';
				}
				elseif($account['admin'] == 1) {
					$permission = 'Admin';
				}
				echo '<tr><td>' . $account['email'] . '</td><td>' . $permission . ' &nbsp; <a href="?accounts&editpermissions=' . $account['email'] . '" title="Change permissions"><span class="glyphicon glyphicon-pencil"></span></a></td><td><a class="btn btn-primary btn-xs" href="?accounts&delete=' . $account['email'] . '"><span class="glyphicon glyphicon-trash" aria-hidden="true"></span> Delete</a></td>';
			}
?>
			</tbody>
		</table>
	</div>
</div>
<?php

}
