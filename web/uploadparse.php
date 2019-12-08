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
 
function uploadparse($filepost, $format, $extra, $plainwords = FALSE, $tmp = TRUE, $id = NULL) {
	$randomid = rand(100000, 999999);
	while(file_exists('../slm/unpacked/' . $randomid)) {
		$randomid = rand(100000, 999999);
	}
	mkdir('../slm/unpacked/' . $randomid);
	mkdir('../slm/unpacked/' . $randomid . '/out');
	
	$archive = 'unpacked/' . $randomid . '/' . $filepost['name'];
	$outdir = 'unpacked/' . $randomid . '/out/';
	move_uploaded_file($filepost['tmp_name'], '../slm/unpacked/' . $randomid . '/' . $filepost['name']);
	
	if($tmp) {
		$tmprandomid = $randomid;
		while(file_exists('../slm/preparsed/tmp/' . $tmprandomid)) {
			$tmprandomid = rand(100000, 999999);
		}
		mkdir('../slm/preparsed/tmp/' . $tmprandomid);
		$savedir = 'preparsed/tmp/' . $tmprandomid;
	}
	else {
		mkdir('../slm/preparsed/saved/' . $id);
		$savedir = 'preparsed/saved/' . $id;
	}
	
	if($format == 'autodetect') {
		$arguments = 'autodetect - -';
	}
	elseif($format == 'conll') {
		$arguments = 'conll - ' . intval($extra);
	}
	elseif($format == 'folia-text-fast') {
		$arguments = 'folia text fast';
	}
	elseif($format == 'folia-lemma-fast') {
		$arguments = 'folia lemma fast';
	}
	elseif($format == 'folia-text-xpath') {
		$arguments = 'folia text xpath';
	}
	elseif($format == 'folia-lemma-xpath') {
		$arguments = 'folia lemma xpath';
	}
	elseif($format == 'dcoi-text') {
		$arguments = 'dcoi text -';
	}
	elseif($format == 'dcoi-lemma') {
		$arguments = 'dcoi lemma -';
	}
	elseif($format == 'plain') {
		$arguments = 'plain - -';
	}
	elseif($format == 'alpino-text') {
		$arguments = 'alpino text -';
	}
	elseif($format == 'alpino-lemma') {
		$arguments = 'alpino lemma -';
	}
	elseif($format == 'bnc-text') {
		$arguments = 'bnc text -';
	}
	elseif($format == 'bnc-lemma') {
		$arguments = 'bnc lemma -';
	}
	elseif($format == 'eindhoven') {
		$arguments = 'eindhoven - -';
	}
	elseif($format == 'gysseling-text') {
		$arguments = 'gysseling text -';
	}
	elseif($format == 'gysseling-lemma') {
		$arguments = 'gysseling lemma -';
	}
	elseif($format == 'graf-text') {
		$arguments = 'graf text -';
	}
	elseif($format == 'graf-lemma') {
		$arguments = 'graf lemma -';
	}
	elseif($format == 'xpath') {
		$arguments = 'xpath - ' . escapeshellarg($extra);
	}
	
	if($plainwords) {
		$plainwords = 1;
	}
	else {
		$plainwords = 0;
	}
	
	chdir('../slm');
	shell_exec('nohup ./preparser ' . escapeshellarg($archive) . ' ' . $outdir . ' ' . $arguments . ' ' . $savedir . ' ' . $plainwords . ' > /dev/null &');
	return $savedir;
}
