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
 
ini_set('upload_max_filesize', '2G');
ini_set('post_max_size', '2G');



session_start();
require('mysql.php');
require('config.php');
if(isset($_POST['login'])) {
	$get_user = $db->prepare('SELECT hash, poweruser, admin FROM accounts WHERE email=?');
	$get_user->execute(array($_POST['email']));
	$user = $get_user->fetch(PDO::FETCH_ASSOC);
	if( (!$user) OR (!password_verify($_POST['password'], $user['hash']))) {
		require('html/top.html');
		require('html/loginerror.html');
		require('html/login.html');
		require('html/bottom.html');
		exit;
	}
	if(password_needs_rehash($user['hash'], PASSWORD_DEFAULT)) {
		$update_user = $db->prepare('UPDATE accounts SET hash=? WHERE email=?');
		$update_user->execute(array(password_hash($_POST['password'], PASSWORD_DEFAULT), $_POST['email']));
	}
	$_SESSION['loggedin'] = true;
	$_SESSION['email'] = $_POST['email'];
	$_SESSION['poweruser'] = $user['poweruser'];
	$_SESSION['admin'] = $user['admin'];
	require('html/redirectlogin.html');
}

if(isset($_GET['json'])) {
	require('visualizations/json.php');
	exit;
}
elseif(isset($_GET['fragvisimg'])) {
	require('visualizations/fragvisimg.php');
	exit;
}
elseif(isset($_GET['export']) && isset($_GET['format'])) {
	require('export.php');
	exit;
}

if( isset($_SESSION['admin']) && ($_SESSION['admin']) ) {
	require('html/top_admin.html');
}
else {
	require('html/top.html');
}

if(!isset($_SESSION['loggedin']) && isset($_GET['reset'])) {
	if(isset($_POST['reset']) && !filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
		echo '<div class="row"><div class="col-md-6 col-md-offset-3"><div class="alert alert-danger"><strong>Error</strong> please enter a valid email address.</div></div></div>';
	}
	elseif(isset($_POST['reset'])) {
		require('mail.php');
		email_reset($_POST['email']);
		echo '<div class="row"><div class="col-md-6 col-md-offset-3"><div class="alert alert-success"><strong>Success</strong> if an account for that address exists, you will receive an email with a new password.</div></div></div>';
	}
	require('html/resetpassword.html');
}
elseif(!isset($_SESSION['loggedin'])) {
	require('html/login.html');
}
elseif(isset($_GET['corpora'])) {
	require('corpora.php');
}
elseif(isset($_GET['reports'])) {
	require('managereports.php');
}
elseif(isset($_GET['report'])) {
	require('report.php');
}
elseif(isset($_GET['export'])) {
	require('export.php');
}
elseif(isset($_GET['fragvis'])) {
	require('visualizations/fragvis.php');
}
elseif(isset($_GET['keydetail'])) {
	require('keydetail.php');
}
elseif(isset($_GET['about'])) {
	require('html/about.html');
}
elseif(isset($_GET['what'])) {
	require('html/what.html');
}
elseif(isset($_GET['formats'])) {
	require('html/formats.html');
}
elseif(isset($_GET['am'])) {
	require('html/am.html');
}
elseif(isset($_GET['faq'])) {
	require('html/faq.html');
}
elseif(isset($_GET['accounts'])) {
	if($_SESSION['admin']) {
		require('accounts.php');
	}
	else {
		require('html/permissionerror.html');
	}
}
elseif(isset($_GET['pw'])) {
	if(isset($_POST['change'])) {
		if($_POST['password'] !== $_POST['password_confirm']) {
			echo '<div class="row"><div class="col-md-6 col-md-offset-3"><div class="alert alert-danger"><strong>Error</strong> Your password and confirmation did not match.</div></div></div>';
		}
		elseif(strlen($_POST['password']) < 6) {
			echo '<div class="row"><div class="col-md-6 col-md-offset-3"><div class="alert alert-danger"><strong>Error</strong> Your password is too short, to keep your account secure please use a longer password. If you are out of ideas have a look at <a href="https://xkcd.com/936/" target="_blank">this xkcd</a>.</div></div></div>';
		}
		else {
			$hash = password_hash($_POST['password'], PASSWORD_DEFAULT);
			$update_hash = $db->prepare('UPDATE accounts SET hash=? WHERE email=?');
			$update_hash->execute(array($hash, $_SESSION['email']));
			
			echo '<div class="row"><div class="col-md-6 col-md-offset-3"><div class="alert alert-success"><strong>Success</strong> Your password has been changed.</div></div></div>';
		}
	}
	require('html/pw.html');
}
elseif(isset($_GET['logout'])) {
	unset($_SESSION['loggedin']);
	unset($_SESSION['email']);
	require('html/login.html');
	require('html/redirectlogout.html');
}
else {
	if(isset($_POST['analyse'])) {
		if( ($_POST['c1-select'] !== 'none') AND ($_POST['c1-select'] == $_POST['c2-select']) ) {
			// comparing same saved corpus, revert to form
			echo '<div class="row"><div class="col-md-6 col-md-offset-3"><div class="alert alert-danger"><strong>Error</strong> You selected the same corpus twice. This would of course not work out.</div></div></div>';
		}
		elseif ( ($_POST['c1-select'] == 'none') AND ($_POST['c1-format'] == 'conll') AND (intval($_POST['c1-extra-conll']) < 1) ) {
			echo '<div class="row"><div class="col-md-6 col-md-offset-3"><div class="alert alert-danger"><strong>Error</strong> You have chosen CoNLL as the format for your first corpus, but you have not specified which column to select.</div></div></div>';
		}
		elseif ( ($_POST['c2-select'] == 'none') AND ($_POST['c2-format'] == 'conll') AND (intval($_POST['c2-extra-conll']) < 1) ) {
			echo '<div class="row"><div class="col-md-6 col-md-offset-3"><div class="alert alert-danger"><strong>Error</strong> You have chosen CoNLL as the format for your second corpus, but you have not specified which column to select.</div></div></div>';
		}
		elseif ( ($_POST['c1-select'] == 'none') AND ($_POST['c1-format'] == 'xpath') AND (strlen($_POST['c1-extra-xpath']) < 2) ) {
			echo '<div class="row"><div class="col-md-6 col-md-offset-3"><div class="alert alert-danger"><strong>Error</strong> You have chosen XML with custom XPath as the format for your first corpus, but you have not specified your XPath query.</div></div></div>';
		}
		elseif ( ($_POST['c2-select'] == 'none') AND ($_POST['c2-format'] == 'xpath') AND (strlen($_POST['c2-extra-xpath']) < 2) ) {
			echo '<div class="row"><div class="col-md-6 col-md-offset-3"><div class="alert alert-danger"><strong>Error</strong> You have chosen XML with custom XPath as the format for your second corpus, but you have not specified your XPath query.</div></div></div>';
		}
		elseif ($_POST['freqnum'] < 10) {
			echo '<div class="row"><div class="col-md-6 col-md-offset-3"><div class="alert alert-danger"><strong>Error</strong> You want to select at least 10 frequent items and probably much more.</div></div></div>';
		}
		elseif ($_POST['freqnum'] > $max_freqnum) {
			echo '<div class="row"><div class="col-md-6 col-md-offset-3"><div class="alert alert-danger"><strong>Error</strong> The admin has limited the amount of frequent items to a maximum of ' . $max_freqnum . ', please limit your chosen amount.</div></div></div>';
		}
		else {
			$collocleft = 0;
			$collocright = 0;
			$colloc = FALSE;
			if(isset($_POST['colloc']) AND $_POST['colloc'] == 'on') {
				$collocleft = intval($_POST['collocleft']);
				$collocright = intval($_POST['collocright']);
				$colloc = TRUE;
			}
			$nonpowercolloc = FALSE;
			if($_SESSION['poweruser'] != 1 && $colloc) {
				$nonpowercolloc = TRUE;
			}
			// all paths must be relative to application/slm
			require('uploadparse.php');
			if($_POST['c1-select'] == 'none') {
				if($_FILES['c1-file']['error'] !== UPLOAD_ERR_OK) {
					echo '<div class="row"><div class="col-md-6 col-md-offset-3"><div class="alert alert-danger"><strong>Error</strong> Corpus one generated an upload error.</div></div></div>';
					require('html/bottom.html');
					exit;
				}
				elseif($nonpowercolloc) {
					echo '<div class="row"><div class="col-md-6 col-md-offset-3"><div class="alert alert-danger"><strong>Error</strong> You do not have the right permissions to request collocational analysis on a temporary corpus.</div></div></div>';
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
				$corpus1name = $_FILES['c1-file']['name'];
				if($colloc) {
					$corpus1 = uploadparse($_FILES['c1-file'], $_POST['c1-format'], $extra, TRUE);
				}
				else {
					$corpus1 = uploadparse($_FILES['c1-file'], $_POST['c1-format'], $extra);
				}
			}
			else {
				$get_corpusname = $db->prepare('SELECT name FROM corpora WHERE id=?');
				$get_corpusname->execute(array($_POST['c1-select']));
				$row = $get_corpusname->fetch(PDO::FETCH_ASSOC);
				$corpus1name = $row['name'];
				$corpus1 = 'preparsed/saved/' . $_POST['c1-select'];
				if($colloc && !file_exists('../slm/' . $corpus1 . '/plainwords')) {
					echo '<div class="row"><div class="col-md-6 col-md-offset-3"><div class="alert alert-danger"><strong>Error</strong> The corpus A you selected was not prepared for collocational analyse. Please either disable this additional analysis or select or supply a prepared corpus.</div></div></div>';
					require('html/bottom.html');
					exit;
				}
			}
			if($_POST['c2-select'] == 'none') {
				if($_FILES['c2-file']['error'] !== UPLOAD_ERR_OK) {
					echo '<div class="row"><div class="col-md-6 col-md-offset-3"><div class="alert alert-danger"><strong>Error</strong> Corpus two generated an upload error.</div></div></div>';
					require('html/bottom.html');
					exit;
				}
				$extra = NULL;
				if($_POST['c2-format'] == 'conll') {
					$extra = $_POST['c2-extra-conll'];
				}
				elseif($_POST['c2-format'] == 'xpath') {
					$extra = $_POST['c2-extra-xpath'];
				}
				$corpus2name = $_FILES['c2-file']['name'];
				$corpus2 = uploadparse($_FILES['c2-file'], $_POST['c2-format'], $extra);
			}
			else {
				$get_corpusname = $db->prepare('SELECT name FROM corpora WHERE id=?');
				$get_corpusname->execute(array($_POST['c2-select']));
				$row = $get_corpusname->fetch(PDO::FETCH_ASSOC);
				$corpus2name = $row['name'];
				$corpus2 = 'preparsed/saved/' . $_POST['c2-select'];
			}
			
			$insert_report = $db->prepare('INSERT INTO reports (owner, c1, c2, freqnum, cutoff, datetime) VALUES (?,?,?,?,?,NOW())');
			$insert_report->execute(array($_SESSION['email'], $corpus1name, $corpus2name, intval($_POST['freqnum']), $_POST['cutoff']));
			$reportid = $db->lastInsertId();
			$reportdir = 'reports/' . $reportid;
			
			chdir('../slm');
			mkdir($reportdir);
			
			$genviz = 0;
			if(isset($_POST['genviz']) AND $_POST['genviz'] == 'on') {
				$genviz = 1;
			}
			if(isset($_POST['mailresult']) AND $_POST['mailresult'] == 'on') {
				$callback = '';
				if(isset($_SERVER['HTTPS']) AND $_SERVER['HTTPS'] === 'on') {
					$callback = 'https://';
				}
				else {
					$callback = 'http://';
				}
				$callback .= $_SERVER['SERVER_NAME'] . substr($_SERVER['REQUEST_URI'], 0, -1) . 'callback.php?id=' . $reportid;
				shell_exec('nohup ./analyser ' . $corpus1 . ' ' . $corpus2 . ' ' . intval($_POST['freqnum']) . ' ' . $_POST['cutoff'] . ' ' . $reportdir . ' ' . $timeout . ' ' . $genviz . ' ' . $collocleft . ' ' . $collocright . ' ' .  $callback . ' > /dev/null &');
			}
			else {
				shell_exec('nohup ./analyser ' . $corpus1 . ' ' . $corpus2 . ' ' . intval($_POST['freqnum']) . ' ' . $_POST['cutoff'] . ' ' . $reportdir . ' ' . $timeout . ' ' . $genviz . ' ' . $collocleft . ' ' . $collocright . ' > /dev/null &');
			}
			
			echo '<div class="row"><div class="col-md-6 col-md-offset-3"><div class="alert alert-success"><strong>Success</strong> We have successfully received your report request. You will be redirected to the report that is generating for you right now. </div></div></div>';
			
			echo '<script type="text/javascript">window.setTimeout(function(){ window.location.href = "?report=' . $reportid . '"}, 5000)</script>';
			require('../web/html/bottom.html');
			exit;
		}
	}
	
	$get_global_corpora = $db->prepare('SELECT id, name FROM corpora WHERE owner IS NULL');
	$get_global_corpora->execute(array());
	$corpora_dropdown = '';
	$corpora_c1search = '';
	$corpora_c2search = '';
	$first = true;
	while($corpus = $get_global_corpora->fetch(PDO::FETCH_ASSOC)) {
		if($first) {
			$corpora_c1search = '<a class="list-group-item disabled" href="#">Global corpora</a>';
			$corpora_c2search = '<a class="list-group-item disabled" href="#">Global corpora</a>';
			$first = false;
		}
		$corpuslabel = '';
		if(file_exists('../slm/preparsed/saved/' . $corpus['id'] . '/plainwords')) {
			$corpuslabel = ' &nbsp; <span class="label label-info" title="Prepared for Collocational Analysis">CA ready</span>';
		}
		$corpora_dropdown .= '<option value="' . $corpus['id'] . '">' . $corpus['name'] . '</option>';
		$corpora_c1search .= '<a class="list-group-item searchitem c1click" data-href="' . $corpus['id'] . '" href="#">' . $corpus['name'] . $corpuslabel . '</a>';
		$corpora_c2search .= '<a class="list-group-item searchitem c2click" data-href="' . $corpus['id'] . '" href="#">' . $corpus['name'] . $corpuslabel . '</a>';
	}
	
	$get_corpora = $db->prepare('SELECT id, name FROM corpora WHERE owner=?');
	$get_corpora->execute(array($_SESSION['email']));
	$corpora_c1search .= '<a class="list-group-item disabled" href="#">Your personal corpora</a>';
	$corpora_c2search .= '<a class="list-group-item disabled" href="#">Your personal corpora</a>';
	while($corpus = $get_corpora->fetch(PDO::FETCH_ASSOC)) {
		$corpuslabel = '';
		if(file_exists('../slm/preparsed/saved/' . $corpus['id'] . '/plainwords')) {
			$corpuslabel = ' &nbsp; <span class="label label-info" title="Prepared for Collocational Analysis">CA ready</span>';
		}
		$corpora_dropdown .= '<option value="' . $corpus['id'] . '">' . $corpus['name'] . '</option>';
		$corpora_c1search .= '<a class="list-group-item searchitem c1click" data-href="' . $corpus['id'] . '" href="#">' . $corpus['name'] . $corpuslabel . '</a>';
		$corpora_c2search .= '<a class="list-group-item searchitem c2click" data-href="' . $corpus['id'] . '" href="#">' . $corpus['name'] . $corpuslabel . '</a>';
	}
	
	$form = file_get_contents('html/form.html');
	$form = str_replace('%corpus_dropdown%', $corpora_dropdown, $form);
	$form = str_replace('%corpus_c1search%', $corpora_c1search, $form);
	$form = str_replace('%corpus_c2search%', $corpora_c2search, $form);
	echo $form;
}

require('html/bottom.html');
