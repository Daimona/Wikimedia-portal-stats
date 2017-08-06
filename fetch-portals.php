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

#
# This script fetches all the portals from a certain wiki using the standard APIs.
# This is incredible long if you have not bot permissions.
#

require 'autoload.php';

$LIMIT = 500;

foreach( $WIKILOG_2_WIKIAPI as $wiki => $wiki_url ) {
	$wiki_api = $wiki_url . 'w/api.php';

	$portals_path = data_wiki_portals_path($wiki);

	if( ! file_exists( $portals_path ) ) {
		logmsg("Filling $portals_path");
		$api = APIRequest::factory($wiki_api, [
			'action'      => 'query',
			'list'        => 'allpages',
			'apnamespace' => 100, // Portal namespace
			'aplimit'     => $LIMIT
		] );

		$portals = [];

		while( $api->hasNext() ) {
			$result = $api->getNext();
			foreach($result->query->allpages as $page) {
				if( false === strpos($page->title, '/' ) ) {
					$portals[] = $page->title;
				}
			}
		}

		logmsg("Fetched " . count( $portals ) . " portals");

		array_2_file($portals, $portals_path);
	}

	$portals = file_2_array( $portals_path );

	foreach($portals as $portal) {
		$portal_pages_path = data_wiki_portal_pages_path($wiki, $portal);

		if( ! file_exists( $portal_pages_path ) ) {
			$api = APIRequest::factory($wiki_api, [
				'action'      => 'query',
				'titles'      => $portal,
				'prop'        => 'linkshere',
				'lhprop'      => 'title',
				'lhnamespace' => 0,
				'lhlimit'     => $LIMIT // 500 for bots
			] );

			$portal_pages = [];

			while( $api->hasNext() ) {
				$result = $api->getNext();

				if( isset( $result->query ) ) {
					foreach($result->query->pages as $from) {
						if( isset( $from->linkshere ) ) {
							foreach($from->linkshere as $page) {
								$portal_pages[] = $page->title;
							}
						}
					}
				}
			}

			logmsg("Fetched " . count( $portal_pages ) . " pages in " . $portal);

			array_2_file($portal_pages, $portal_pages_path);
		}
	}
}
