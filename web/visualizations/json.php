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


$get_report = $db->prepare('SELECT c1, c2, freqnum, cutoff, datetime FROM reports WHERE id=?');
$get_report->execute(array($_GET['reportid']));
$report = $get_report->fetch(PDO::FETCH_ASSOC);

if(file_exists('../slm/reports/' . $_GET['reportid'] . '/visuals')) {
	echo file_get_contents('../slm/reports/' . $_GET['reportid'] . '/visuals/treemap.json');
}
