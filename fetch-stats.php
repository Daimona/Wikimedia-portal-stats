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

defined('PAGEVIEWS')
	or define('PAGEVIEWS', 'https://dumps.wikimedia.org/other/pageviews/');

// Only latest year
$year_dirs = find_links( fetch_url( PAGEVIEWS ) );
$year_dirs or die("Missing years?");

$year_dir = $year_dirs[ 0 ];

// Only latest month
$year_months = find_links( fetch_url( PAGEVIEWS . $year_dir ) );
$year_months or die("Missing months?");

$year_month = $year_months[ 0 ];

$year_month_logs = find_links( fetch_url( PAGEVIEWS . $year_dir . $year_month ) );
foreach($year_month_logs as $year_month_log) {
	$match = 'pageviews';
	if( strpos( $year_month_log, $match ) !== 0 ) {
		continue;
	}

	$stats_path = data_wiki_portal_stats( $year_month_log );

	if( file_exists( $stats_path ) ) {
		continue;
	}

	preg_match('/pageviews-([0-9]{4})([0-9]{2})([0-9]{2})-([0-9]+).gz/', $year_month_log, $matches);

	if( count( $matches ) !== 5 ) {
		logmsg("Wrong '$year_month_log'");
		continue;
	}

	list(, $year, $month, $day, $hour) = $matches;

	$year  = (int) $year;
	$month = (int) $month;
	$day   = (int) $day;
	$hour  = (int) $hour;

	if( $year === 0 || $month === 0 || $day === 0 ) {
		die("Wrong match $year_month_log");
	}

	$data_path           = DATA_PATH . __ . $year_month_log;
	$data_path_extracted = str_replace('.gz', '', $data_path);
	if( ! file_exists( $data_path_extracted ) ) {
		if( ! file_exists( $data_path ) ) {
			file_put_contents($data_path,
				fetch_url( PAGEVIEWS . $year_dir . $year_month . $year_month_log )
			);
		}

		/**
		 * How can I extract or uncompress gzip file using php?
		 *
		 * @author Vasu
		 * @license CC BY-SA 3.0
		 * @see https://stackoverflow.com/a/17685755
		 */
		$buffer_size = 4096;
		$file = gzopen($data_path, 'rb');
		$out_file = fopen($data_path_extracted, 'wb');
		while( !gzeof( $file ) ) {
			fwrite( $out_file, gzread($file, $buffer_size) );
		}
		fclose($out_file);
		gzclose($file);
	}

	// The gzip is now unuseful
	logmsg("Unlinking $data_path");
	unlink( $data_path );

	// Counters
	$portal_hits_counter = [];
	$portal_pages_counter = [];

	// Caches
	$portals = [];
	$portal_pages = [];

	// Read every line
	$file = fopen($data_path_extracted, 'r');
	$i = 0;
	while( ! feof($file) ) {
		$line = fgets($file);

		if( $i % 5000 === 0 ) {
			logmsg("Parsing line $i...");
		}

		$i++;

		$line_exploded = explode(' ', $line);

		if( count( $line_exploded ) !== 4 ) {
			continue;
		}

		// https://meta.wikimedia.org/wiki/Traffic_reporting#Data_format
		list($wiki, $page, $hits, $bytes) = $line_exploded;

		if( ! isset( $WIKILOG_2_WIKIAPI[ $wiki ] ) ) {
			continue;
		}

		if( ! isset( $portals[ $wiki ] ) ) {
			$portals[ $wiki ] = file_2_array( data_wiki_portals( $wiki ) );
		}

		if( ! $portals[ $wiki ] ) {
			continue;
		}

		foreach( $portals[ $wiki ] as $portal ) {
			if( ! isset( $portal_pages[ $portal ] ) ) {
				$portal_pages[ $portal ] = array_flip( file_2_array( data_wiki_portal_pages( $portal) ) );
			}

			if( ! $portal_pages ) {
				continue;
			}

			if( ! isset( $portal_hits_counter[ $portal ] ) ) {
				$portal_hits_counter[ $portal ] = 0;
			}

			if( ! isset( $portal_pages_counter[ $portal ] ) ) {
				$portal_pages_counter[ $portal ] = 0;
			}

			// The log has underscores
			$page = str_replace('_', ' ', $page);

			if( isset( $portal_pages[ $portal ][ $page ] ) ) {
				$portal_pages_counter[ $portal ]++;
				$portal_hits_counter[ $portal ] += (int) $hits;
			}
		}
	}
	fclose($file);

	logmsg("Unlinking $data_path_extracted");
	unlink( $data_path_extracted );

	$data = [];
	$data['portals'] = [];

	foreach( $portal_hits_counter as $portal => $hits ) {
		$data[ 'date' ] = $year_month_log;
		$data[ 'portals' ][ $portal ] = [
			'pages' => $portal_pages_counter[ $portal ],
			'hits'  => $hits
		];
	}

	file_put_contents( $stats_path, json_encode( $data ) );

	logmsg("End $year_month_log.");
}
