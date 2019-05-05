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

require('mysql.php');
require('config.php');
require('Mail.php');

$id = $_GET['id'];

$get = $db->prepare('SELECT owner, c1, c2, datetime FROM reports WHERE id=?');
$get->execute(array($id));
if($row = $get->fetch()) {
	$to = $row['owner'];

	$headers['From'] = $email_from;
	$headers['To'] = $to;
	$headers['Subject'] = 'Your snelSLiM report is now available';
	
	$report_start = date('d M Y, H:i', strtotime($row['datetime']));
	if(isset($_SERVER['HTTPS']) AND $_SERVER['HTTPS'] === 'on') {
		$url = 'https://';
	}
	else {
		$url = 'http://';
	}
	$url .= $_SERVER['SERVER_NAME'];
	$clean_request = explode('callback.php', $_SERVER['REQUEST_URI']);
	$url .= $clean_request[0] . '?report=' . $id;

	$body = <<<EOT
Hi there,

This is an email to confirm that your snelSLiM report from $report_start is now available on $url

Kind reards,
snelSLiM
EOT;
	
	$mail_handle = '';
	if($email_smtp) {
		$params = array();
		$params['host'] = $email_smtp_server;
		$params['port'] = $email_smtp_port;
		//$params['socket_options'] = array('ssl' => array('verify_peer_name' => false));
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
}
