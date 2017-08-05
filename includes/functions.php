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

function logmsg($msg) {
	echo "$msg\n";
}

function fetch_url( $url ) {
	logmsg("Downloading $url...");
	$result = file_get_contents( $url );
	if( false === $result ) {
		die("Can't reach $url");
	}
	return $result;
}

function find_links( $html ) {
	preg_match_all('/href="(.*)"/' , $html, $out);
	if( ! isset( $out[1] ) ) {
		die("Can't found years");
	}

	// Strip '../'
	array_shift( $out[1] );
	sort( $out[1] );
	return array_reverse( $out[1] );
}

function sanitize_filename($filename) {
	return str_replace( [ '/', '\\', '..' ], '', $filename);
}

function data_wiki_portals_path($wiki) {
	return DATA_PATH . __ . sanitize_filename( "portals-$wiki.txt" );
}

function data_wiki_portal_pages_path($wiki, $portal) {
	return DATA_PATH . __ . sanitize_filename( "portalpages-$wiki-$portal.txt" );
}

function data_wiki_portal_stats_path($wiki, $date) {
	return DATA_PATH . __ . sanitize_filename( PORTALSTATS_PREFIX . "-$wiki-$date.json");
}

function file_2_array($file) {
	if( ! file_exists( $file ) ) {
		return [];
	}
	return explode("\n", file_get_contents( $file ) );
}

function array_2_file($array, $file) {
	file_put_contents( $file, implode("\n", $array) );
}

/**
 * @param string Wiki alias e.g. 'it.m', 'en.zero' ecc.
 * @return string Wiki e.g. 'it', 'en' ecc.
 */
function normalize_wiki($wiki) {
	return str_replace( ['.m', '.zero'], '', $wiki );
}

/**
 * @param string Wiki e.g. 'en'
 * @param array Wiki aliases e.g. ['en', 'en.m', 'en.zero']
 */
function wiki_aliases($wiki) {
	return [$wiki, "$wiki.m", "$wiki.zero"];
}
