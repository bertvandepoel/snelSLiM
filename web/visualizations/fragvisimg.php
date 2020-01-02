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


$get_report = $db->prepare('SELECT c1, c2, freqnum, cutoff, datetime FROM reports WHERE id=? AND owner=?');
$get_report->execute(array($_GET['reportid'], $_SESSION['email']));
$report = $get_report->fetch(PDO::FETCH_ASSOC);
if(!$report) {
	require('html/top.html');
	echo '<div class="row"><div class="col-md-6 col-md-offset-3"><div class="alert alert-error"><strong>Error</strong> This report was deleted or is not available to you.</div></div></div>';
	require('html/bottom.html');
	exit;
}

if(file_exists('../slm/reports/' . $_GET['reportid'] . '/visuals')) {
	$visstring = file_get_contents('../slm/reports/' . $_GET['reportid'] . '/visuals/' . $_GET['fragvisimg'] . ".snelvis");
	$height = 500;
	$width = ceil(strlen($visstring) / $height);
	if($width < 50) {
		$width = 50;
		$height = ceil(strlen($visstring) / $width);
	}
	$gd = imagecreatetruecolor($width, $height);
	$a = imagecolorallocate($gd, 75, 75, 255);
	$r = imagecolorallocate($gd, 255, 75, 75);
	$b = imagecolorallocate($gd, 204, 204, 204);
	$x = imagecolorallocate($gd, 255, 255, 255);
	$cordx = 0;
	$cordy = 0;
	for($i=0; $i<strlen($visstring); $i++) {
		if($visstring[$i] == 'a') {
			imagesetpixel($gd, $cordx, $cordy, $a);
		}
		elseif($visstring[$i] == 'r') {
			imagesetpixel($gd, $cordx, $cordy, $r);
		}
		elseif($visstring[$i] == 'b') {
			imagesetpixel($gd, $cordx, $cordy, $b);
		}
		else {
			// then it has to be x
			imagesetpixel($gd, $cordx, $cordy, $x);
		}
		$cordx++;
		if($cordx == $width) {
			$cordx = 0;
			$cordy++;
		}
	}
	$gd2 = imagecreatetruecolor($width*10, $height*10);
	imagecopyresampled($gd2, $gd, 0, 0, 0, 0, $width*10, $height*10, $width, $height);
	if(isset($_GET['rotate'])) {
		imageflip($gd2, IMG_FLIP_HORIZONTAL);
		$gd2 = imagerotate($gd2, 90, 0);
	}
	header('Content-Type: image/png');
	imagepng($gd2);
}
