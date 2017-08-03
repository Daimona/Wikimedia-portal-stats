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

$goods = [];

$ls = scandir( DATA_PATH );
foreach($ls as $file) {
	if( $file === '..' || $file === '.' ) {
		continue;
	}

	$prefix = substr($file, 0, $len);

	if( $prefix === PORTALSTATS_PREFIX ) {
		$goods[] = DATA_ROOT . _ . $file;
	}
}

file_put_contents( DATAREADY_PATH, json_encode( [
	'files' => $goods
] ) );
