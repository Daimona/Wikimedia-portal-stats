#!/usr/bin/php
<?php
# Wikimedia stats by local projects
# Copyright (C) 2017  Valerio Bozzolan and contributors
#
# This program is free software: you can redistribute it and/or modify
# it under the terms of the GNU Affero General Public License as
# published by the Free Software Foundation, either version 3 of the
# License, or (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
# GNU Affero General Public License for more details.
#
# You should have received a copy of the GNU Affero General Public License
# along with this program. If not, see <http://www.gnu.org/licenses/>.

isset( $argv ) or die("CLI only");

require 'autoload.php';

$len = strlen( PORTALSTATS_PREFIX );

$data = [];

$ls = scandir( DATA_PATH );
foreach($ls as $file) {
	if( $file === '..' || $file === '.' ) {
		continue;
	}

	$prefix = substr($file, 0, $len);

	if( $prefix === PORTALSTATS_PREFIX ) {
		// portalstats-it-pageviews-20170805-010000.gz.json
		preg_match('/([a-z]+)-[a-z]+-([0-9]{4})([0-9]{2})([0-9]{2})-([0-9]{2})[0-9]+/',
			$file,
			$matches
		);

		if( count( $matches ) !== 6 ) {
			logmsg("Wrong format filename '$file'");
			continue;
		}

		list(, $wiki, $year, $month, $day, $hour) = $matches;

		$year  = (int) $year;
		$month = (int) $month;
		$day   = (int) $day;
		$hour  = (int) $hour;

		if( ! isset( $WIKILOG_2_WIKIAPI[ $wiki ] ) ) {
			logmsg("Wiki $wiki unknown.");
		}

		$data[] = [
			'date' => [
				'y' => $year,
				'm' => $month,
				'd' => $day,
				'h' => $hour
			],
			'wiki' => $wiki,
			'url'  => $WIKILOG_2_WIKIAPI[ $wiki ],
			'file' => DATA_ROOT . _ . $file
		];
	}
}

usort( $data, function ( $a, $b ) {
	if( $a['wiki'] !== $b['wiki'] ) {
		return $a['wiki'] < $b['wiki'] ? -1 : 1;
	}
	if( $a['date']['y'] !== $b['date']['y'] ) {
		return $a['date']['y'] < $b['date']['y'] ? -1 : 1;
	}
	if( $a['date']['m'] !== $b['date']['m'] ) {
		return $a['date']['m'] < $b['date']['m'] ? -1 : 1;
	}
	if( $a['date']['d'] !== $b['date']['d'] ) {
		return $a['date']['d'] < $b['date']['d'] ? -1 : 1;
	}
	return $a['date']['h'] < $b['date']['h'] ? -1 : 1;
} );

file_put_contents( DATAREADY_PATH, json_encode( $data ) );
