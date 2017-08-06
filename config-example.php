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

// Modify this file and rename as 'config.php'

// Allowed wiki log => its wiki api
$WIKILOG_2_WIKIAPI = [
	'it'   => 'https://it.wikipedia.org/'
];

// Absolute pathname to this folder (without trailing slash)
define('ABSPATH', __DIR__ );

// Absolute pathname in the query string to this folder (without trailing slash)
define('ROOT', '' );
