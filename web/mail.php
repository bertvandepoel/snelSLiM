<?php
/*
 * snelSLiM - Interface for quick Stable Lexical Marker Analysis
 * Copyright (c) 2019 Bert Van de Poel
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

require('Mail.php');

function email_new_account($address, $password) {
	global $email_from;
	global $email_smtp;
	global $email_smtp_server;
	global $email_smtp_port;
	global $email_smtp_auth;
	global $email_smtp_username;
	global $email_smtp_password;
	
	$headers['From'] = $email_from;
	$headers['To'] = $address;
	$headers['Subject'] = 'Your new snelSLiM account';
	
	if(isset($_SERVER['HTTPS']) AND $_SERVER['HTTPS'] === 'on') {
		$url = 'https://';
	}
	else {
		$url = 'http://';
	}
	$url .= $_SERVER['SERVER_NAME'];
	$clean_request = explode('?', $_SERVER['REQUEST_URI']);
	$url .= $clean_request[0];

	$body = <<<EOT
Hi there,

An administrator has created a snelSLiM account for you. You can now login on $url using the following login details.
Login: $address
Password: $password

Once you've logged in you can change your password using the "Change password" option under the Account menu item.

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
	$mail_handle->send($address, $headers, $body);
}

function email_reset($address) {
	global $db;
	global $email_from;
	global $email_smtp;
	global $email_smtp_server;
	global $email_smtp_port;
	global $email_smtp_auth;
	global $email_smtp_username;
	global $email_smtp_password;
	
	$update = $db->prepare('UPDATE accounts SET hash=? WHERE email=?');
	$password = str_rand(20);
	$update->execute(array(password_hash($password, PASSWORD_DEFAULT), $address));
	
	$headers['From'] = $email_from;
	$headers['To'] = $address;
	$headers['Subject'] = 'Your snelSLiM account password has been reset';
	
	if(isset($_SERVER['HTTPS']) AND $_SERVER['HTTPS'] === 'on') {
		$url = 'https://';
	}
	else {
		$url = 'http://';
	}
	$url .= $_SERVER['SERVER_NAME'];
	$clean_request = explode('?', $_SERVER['REQUEST_URI']);
	$url .= $clean_request[0];

	$body = <<<EOT
Hi there,

You requested a password reset of your snelSLiM account. You can now login on $url with the following new password. Once you've logged in you can change your password again using the "Change password" option under the Account menu item.
Password: $password

If you did not request a new password, please notify one of your snelSLiM administrators.

Kind reards,
snelSLiM
EOT;
	
	if($update->rowCount() > 0) {
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
		$mail_handle->send($address, $headers, $body);
	}
}

function str_rand($length) {
	$chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
	$return = '';
	for ($i = 0; $i < $length; $i++) {
        $return .= $chars[random_int(0, strlen($chars)-1)];
    }
	return $return;
}
if(!function_exists('random_int')) {
	function random_int($min, $max) {
		return rand($min, $max);
	}
}

