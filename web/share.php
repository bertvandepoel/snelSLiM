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

$get_report = $db->prepare('SELECT datetime FROM reports WHERE id=? AND owner=?');
$get_report->execute(array($_GET['share'], $_SESSION['email']));
$report = $get_report->fetch(PDO::FETCH_ASSOC);
if(!$report) {
	echo '<div class="row"><div class="col-md-6 col-md-offset-3"><div class="alert alert-danger"><strong>Error</strong> This report was deleted or is not available to you.</div></div></div>';
	require('html/bottom.html');
	exit;
}

if(isset($_GET['genlink'])) {
	$chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
	$token = '';
	for ($i = 0; $i < 25; $i++) {
        $token .= $chars[random_int(0, strlen($chars)-1)];
    }
	
	$insert = $db->prepare('INSERT INTO share_link VALUES (?,?)');
	$insert->execute(array($token, $_GET['share']));
	echo '<div class="row"><div class="col-md-6 col-md-offset-3"><div class="alert alert-success"><strong>Success</strong> A public link to share your report has been generated.</div></div></div>';
}
elseif(isset($_POST['dellink'])) {
	$delete = $db->prepare('DELETE FROM share_link WHERE reportid=?');
	$delete->execute(array($_GET['share']));
	echo '<div class="row"><div class="col-md-6 col-md-offset-3"><div class="alert alert-success"><strong>Success</strong> The public link of your report has been removed. The link will no longer work.</div></div></div>';
}
elseif(isset($_POST['add_share_user'])) {
	$check = $db->prepare('SELECT email FROM accounts WHERE email=?');
	$check->execute(array($_POST['email']));
	$check_result = $check->fetch(PDO::FETCH_ASSOC);
	if(!$check_result) {
		echo '<div class="row"><div class="col-md-6 col-md-offset-3"><div class="alert alert-danger"><strong>Error</strong> The email adress you\'re trying to share your report with isn\'t associated with any known account.</div></div></div>';
	}
	else {
		$insert = $db->prepare('INSERT INTO share_user VALUES (?,?)');
		$insert->execute(array($_GET['share'], $_POST['email']));
		
		require('Mail.php');
		$to = $_POST['email'];

		$headers['From'] = $email_from;
		$headers['To'] = $to;
		$headers['Subject'] = 'A new shared snelSLiM report is available';
		
		if(isset($_SERVER['HTTPS']) AND $_SERVER['HTTPS'] === 'on') {
			$url = 'https://';
		}
		else {
			$url = 'http://';
		}
		$url .= $_SERVER['SERVER_NAME'];
		$clean_request = explode('?', $_SERVER['REQUEST_URI']);
		$url .= $clean_request[0] . '?reports=';

		$body = <<<EOT
Hi there,

This is an email to confirm that another user has shared their snelSLiM report with you. You can see all shared reports on $url

Kind regards,
snelSLiM
EOT;

		$mail_handle = '';
		if($email_smtp) {
			$params = array();
			$params['host'] = $email_smtp_server;
			$params['port'] = $email_smtp_port;
			if($email_smtp_auth) {
				$params['auth'] = TRUE;
				$params['username'] = $email_smtp_username;
				$params['password'] = $email_smtp_password;
			}
			$mail_handle = Mail::factory('smtp', $params);
		}
		else {
			$mail_handle = Mail::factory('mail');
		}
		$mail_handle->send($to, $headers, $body);
		
		echo '<div class="row"><div class="col-md-6 col-md-offset-3"><div class="alert alert-success"><strong>Success</strong> Your report has been shared with the given account.</div></div></div>';
	}
}
elseif(isset($_GET['delete'])) {
	$delete = $db->prepare('DELETE FROM share_user WHERE reportid=? AND account=?');
	$delete->execute(array($_GET['share'], $_GET['delete']));
	echo '<div class="row"><div class="col-md-6 col-md-offset-3"><div class="alert alert-success"><strong>Success</strong> Your report is no longer shared with this user.</div></div></div>';
}

$get_share_link = $db->prepare('SELECT sharetoken FROM share_link WHERE reportid=?');
$get_share_link->execute(array($_GET['share']));
$share_link = $get_share_link->fetch(PDO::FETCH_ASSOC);

$get_share_user = $db->prepare('SELECT account FROM share_user WHERE reportid=?');
$get_share_user->execute(array($_GET['share']));
$share_user = $get_share_user->fetchAll(PDO::FETCH_ASSOC);

?>
<div class="page-header" id="banner">
	<div class="row">
		<div class="col-md-8 col-md-offset-2">
			<h1>Sharing your results</h1>
			<p>If you wish to show a fellow user an interesting result or share your report publicly (for example as part of a publication), you can use use the following form elements to generate a unique shareable link or give specific users access to your report.</p>
			<h3>Shareable link</h3>
			<p>Anyone with this link can access your report, even if they don't have an account on this snelSLiM instance. When the link is revoked (or replaced), those using the link will not be able to access your report anymore.</p>
			<?php
				if(!$share_link) {
					echo '<p><span class="well well-sm">Your report is currently not available through a shareable link.</span> &nbsp; <a href="' . $_SERVER['REQUEST_URI'] . '&amp;genlink=" class="btn btn-primary">Generate link</a></p>';
				}
				else {
					$base = explode('?', $_SERVER['REQUEST_URI']);
					$link = "http://";
					if(isset($_SERVER['HTTPS']) AND $_SERVER['HTTPS'] === 'on') {
						$link = "https://";
					}
					$link .= $_SERVER['SERVER_NAME'] . $base[0];
					echo '<form action="?share=' . $_GET['share'] . '" method="post"><p><span class="well well-sm">' . $link . '?sharetoken=' . $share_link['sharetoken'] . ' <button id="clipboardcopy" data-clipboard-text="' . $link . '?sharetoken=' . $share_link['sharetoken'] . '" data-toggle="tooltip" data-placement="top" title="" data-original-title="Copy link" onclick="$(this).attr(\'data-original-title\', \'Copied\').tooltip(\'fixTitle\').tooltip(\'show\')"><span class="glyphicon glyphicon-copy"></span></button></span> &nbsp; <input type="submit" name="dellink" class="btn btn-primary" value="Revoke link"></p></form>';
				}
			?>
			<h3>Share with fellow users</h3>
			<p>To share the report with a specific (set of) users on this instance of snelSLiM, you can add their account email addresses below. They will be contacted via email and your report will be visible to them on the My Reports page under shared reports.</p>
			<form action="?share=<?php echo $_GET['share']; ?>" method="post" class="form-inline"><p>Account email address: <input name="email" type="text" class="form-control"> &nbsp; <button name="add_share_user" class="btn btn-primary" type="submit"><span class="glyphicon glyphicon-log-in"></span> &nbsp; Share</button></p></form>
			<?php
			if(!empty($share_user)) {
				?>
				<div class="row">
				<div class="col-md-6">
				<table class="table table-striped table-hover">
					<thead>
						<tr><th>Account</th><th class="col-sm-2">Delete</th></tr>
					</thead>
					<tbody>
					<?php
						foreach($share_user as $account) {
							echo '<tr><td>' . $account['account'] . '</td><td><a class="btn btn-primary btn-xs" href="?share=' . $_GET['share'] . '&delete=' . $account['account'] . '"><span class="glyphicon glyphicon-trash" aria-hidden="true"></span> Delete</a></td></tr>';
						}
					?>
					</tbody>
				</table>
				</div>
				</div>
				<?php
			}
			?>
		</div>
	</div>
</div>
<?php
