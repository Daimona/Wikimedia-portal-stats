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

	// Match only interesting logs
	$match = 'pageviews';
	if( strpos( $year_month_log, $match ) !== 0 ) {
		continue;
	}

	// Skip yet calculated log
	$all_stats_for_this_date_yet_exists = true;
	foreach($WIKILOG_2_WIKIAPI as $wiki => $wiki_api) {
		if( ! file_exists( data_wiki_portal_stats_path($wiki, $year_month_log ) ) ) {
			$all_stats_for_this_date_yet_exists = false;
			break;
		}
	}
	if( $all_stats_for_this_date_yet_exists ) {
		continue;
	}

	//                     |  Year  || Month  ||  Day   | | Hour |
	preg_match('/pageviews-([0-9]{4})([0-9]{2})([0-9]{2})-([0-9]+).gz/',
		$year_month_log,
		$matches
	);

	// Skip unattended log title
	if( count( $matches ) !== 5 ) {
		logmsg("Wrong '$year_month_log'");
		continue;
	}

	list(, $year, $month, $day, $hour) = $matches;
	$year  = (int) $year;
	$month = (int) $month;
	$day   = (int) $day;
	$hour  = (int) substr($hour, 0, 2);

	// Skip unattended log title
	if( $year === 0 || $month === 0 || $day === 0 ) {
		die("Wrong match $year_month_log");
	}

	$log_path = DATA_PATH . __ . $year_month_log;
	$log_path_extracted = str_replace('.gz', '', $log_path);
	if( ! file_exists( $log_path_extracted ) ) {
		if( ! file_exists( $log_path ) ) {
			file_put_contents($log_path,
				fetch_url( PAGEVIEWS . $year_dir . $year_month . $year_month_log )
			);
		}

		/*
		 * How can I extract or uncompress gzip file using php?
		 *
		 * @author Vasu
		 * @license CC BY-SA 3.0
		 * @see https://stackoverflow.com/a/17685755
		 */
		$buffer_size = 4096;
		$file = gzopen($log_path, 'rb');
		$out_file = fopen($log_path_extracted, 'wb');
		while( ! gzeof( $file ) ) {
			fwrite( $out_file, gzread($file, $buffer_size) );
		}
		fclose($out_file);
		gzclose($file);

		// The gzip is now unuseful
		logmsg("Unlinking $log_path");
		unlink( $log_path );
	}

	// Counters
	$portal_hits_counter   = [];
	$portal_pages_counter  = [];
	$pages_without_portals = [];

	// Caches
	$portals      = [];
	$portal_pages = [];

	// Init counters & caches
	foreach($WIKILOG_2_WIKIAPI as $wiki => $wiki_api) {
		$portal_hits_counter  [ $wiki ] = [];
		$portal_pages_counter [ $wiki ] = [];
		$pages_without_portals[ $wiki ] = 0;
		$portals              [ $wiki ] = [];
		$portal_pages         [ $wiki ] = [];
	}

	// Read every line
	$file = fopen($log_path_extracted, 'r');
	$i = 0;
	while( ! feof($file) ) {
		// Get a file line
		$line = fgets($file);

		if( $i % 5000 === 0 ) {
			logmsg("Parsing line $i...");
		}
		$i++;

		/*
		 * Parse the log line
		 *
		 * @see https://meta.wikimedia.org/wiki/Traffic_reporting#Data_format
		 */
		$line_exploded = explode(' ', $line);
		if( count( $line_exploded ) !== 4 ) {
			continue;
		}
		list($wiki, $page, $hits, $bytes) = $line_exploded;

		// 'en.m' ecc. → 'en'
		$wiki = normalize_wiki( $wiki );

		// Skip unwanted wikis
		if( ! isset( $WIKILOG_2_WIKIAPI[ $wiki ] ) ) {
			continue;
		}

		// Fill portals cache once
		if( ! $portals[ $wiki ] ) {
			$portals[ $wiki ] = file_2_array(  data_wiki_portals_path( $wiki ) );
		}

		// Skip wikis without portals
		if( ! $portals[ $wiki ] ) {
			logmsg("Portals not fetched yet from '$wiki'");
			continue;
		}

		foreach( $portals[ $wiki ] as $portal ) {
			// Fill portal pages cache
			if( ! isset( $portal_pages[ $wiki ][ $portal ] ) ) {
				$portal_pages[ $wiki ][ $portal ] = array_flip(
					file_2_array( data_wiki_portal_pages_path( $wiki, $portal ) )
				);
			}

			// Warn portals without pages
			if( ! $portal_pages[ $wiki ][ $portal ] ) {
				logmsg("Portal '$portal' from '$wiki' without pages");
				continue;
			}

			// Init portal counters
			if( ! isset( $portal_pages_counter[ $wiki ][ $portal ], $portal_hits_counter[ $wiki ][ $portal ] ) ) {
				$portal_pages_counter[ $wiki ][ $portal ] = 0;
				$portal_hits_counter [ $wiki ][ $portal ] = 0;
			}

			// The log has underscores but the fetched page titles hasn't
			$page = str_replace('_', ' ', $page);

			if( isset( $portal_pages[ $wiki ][ $portal ][ $page ] ) ) {
				$portal_pages_counter[ $wiki ][ $portal ] ++;
				$portal_hits_counter [ $wiki ][ $portal ] += (int) $hits;
			} else {
				$pages_without_portals[ $wiki ]++;
			}
		}
	}
	fclose($file);

	logmsg("Unlinking $log_path_extracted");
	unlink( $log_path_extracted );

	// Output data
	foreach( $WIKILOG_2_WIKIAPI as $wiki => $wiki_api ) {
		$data = [
			'date'        => [
				'y' => $year,
				'm' => $month,
				'd' => $day,
				'h' => $hour
			],
			'wiki'        => $wiki,
			'api'         => $wiki_api,
			'orphanPages' => $pages_without_portals[ $wiki ],
			'portals'     => []
		];

		foreach($portals[ $wiki ] as $portal) {
			$data['portals'][] = [
				'name'        => $portal,
				'pages'       => count( $portal_pages[ $wiki ][ $portal ] ),
				'pagesHitted' => $portal_pages_counter[ $wiki ][ $portal ],
				'hits'        => $portal_hits_counter [ $wiki ][ $portal ]
			];
		}

		file_put_contents(
			data_wiki_portal_stats_path( $wiki, $year_month_log ),
			json_encode( $data )
		);
	}

	logmsg("End $year_month_log.");
	sleep(3);
}
