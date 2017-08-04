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

file_exists('config.php')
	or die("RTFM");

define('_', '/');

define('__', DIRECTORY_SEPARATOR);

require 'config.php';

// Data directory name
defined('DATA_DIR')
	or define('DATA_DIR', 'data');

// Relative query string data directory location
defined('DATA_ROOT')
	or define('DATA_ROOT', ROOT . _ . DATA_DIR);

// Absolute filesystem data directory location
defined('DATA_PATH')
	or define('DATA_PATH', ABSPATH . __ . DATA_DIR);

defined('PORTALSTATS_PREFIX')
	or define('PORTALSTATS_PREFIX', 'portalstats');

defined('DATAREADY')
	or define('DATAREADY', 'data-ready.json');

defined('DATAREADY_ROOT')
	or define('DATAREADY_ROOT', DATA_ROOT . _ . DATAREADY);

defined('DATAREADY_PATH')
	or define('DATAREADY_PATH', DATA_PATH . __ . DATAREADY);

defined('D3_PATH')
	or define('D3_PATH', ROOT . '/static/d3/d3.min.js');

defined('JQUERY_PATH')
	or define('JQUERY_PATH', ROOT . '/static/jquery/jquery.min.js');

define('REPO_URL')
	or define('REPO_URL', 'https://github.com/valerio-bozzolan/Wikimedia-portal-stats');

// Some functions
require ABSPATH . __ . 'includes' . __ . 'functions.php';

// https://github.com/valerio-bozzolan/boz-mw
require ABSPATH . __ . 'boz-mw'   . __ . 'autoload.php';
