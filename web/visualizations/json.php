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


if(isset($_GET['sharetoken'])) {
	$get_tokenreport = $db->prepare('SELECT id, owner, c1, c2, freqnum, cutoff, datetime FROM reports, share_link WHERE share_link.sharetoken=? AND reports.id=share_link.reportid ORDER BY id DESC');
	$get_tokenreport->execute(array($_GET['sharetoken']));
	$report = $get_tokenreport->fetch(PDO::FETCH_ASSOC);
}
else {
	$get_report = $db->prepare('SELECT c1, c2, freqnum, cutoff, datetime FROM reports WHERE id=? AND owner=?');
	$get_report->execute(array($_GET['reportid'], $_SESSION['email']));
	$report = $get_report->fetch(PDO::FETCH_ASSOC);
	if(!$report) {
		$get_shares = $db->prepare('SELECT id, owner, c1, c2, freqnum, cutoff, datetime FROM reports, share_user WHERE reports.id=? AND share_user.account=? AND reports.id=share_user.reportid ORDER BY id DESC');
		$get_shares->execute(array($_GET['reportid'], $_SESSION['email']));
		$report = $get_shares->fetch(PDO::FETCH_ASSOC);
	}
}

if(!$report) {
	echo '[{"id":1,"name":"corpus"},{"id":2,"name":"attracted","parent_total":1,"parent_unique":1},{"id":3,"name":"repulsed","parent_total":1,"parent_unique":1},{"id":4,"name":"balanced","parent_total":1,"parent_unique":1},{"id":5,"name":"Error: This report was deleted or is not available to you.","size_total":2,"size_keyword_total":2,"size_keyword_unique":2,"size_keyword_percentage_total":1,"size_keyword_percentage_unique":1,"parent_total":4,"parent_unique":4}]';
	exit;
}
if(file_exists('../data/reports/' . $_GET['reportid'] . '/visuals')) {
	echo file_get_contents('../data/reports/' . $_GET['reportid'] . '/visuals/treemap.json');
}
