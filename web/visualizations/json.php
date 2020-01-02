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
	echo '[{"id":1,"name":"corpus"},{"id":2,"name":"attracted","parent_total":1,"parent_unique":1},{"id":3,"name":"repulsed","parent_total":1,"parent_unique":1},{"id":4,"name":"balanced","parent_total":1,"parent_unique":1},{"id":5,"name":"Error: This report was deleted or is not available to you.","size_total":2,"size_keyword_total":2,"size_keyword_unique":2,"size_keyword_percentage_total":1,"size_keyword_percentage_unique":1,"parent_total":4,"parent_unique":4}]';
	exit;
}
if(file_exists('../slm/reports/' . $_GET['reportid'] . '/visuals')) {
	echo file_get_contents('../slm/reports/' . $_GET['reportid'] . '/visuals/treemap.json');
}
