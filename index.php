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

require 'autoload.php';

CONTENT_HEADER and header(CONTENT_HEADER);

?>
<!DOCTYPE html>
<html>
<head>
	<title><?php echo _("Wikimedia portal stats") ?></title>
	<style>
	text.data-value {
		font-size:0.7em;
	}
	</style>
</head>
<body>
	<h1><?php echo _("Wikimedia portal stats") ?></h1>
	<p><?php printf(
		_("Currently viewing %s dataset."),
		'<span class="dataset">//</span>'
	) ?></p>
	<p>
	<button class="by-ratio">By ratio</button>
	<button class="by-hits">By hits</button>
	<button class="by-pages">By pages</button>
	</p>

	<svg width="960" height="960" text-anchor="middle"></svg>

	<p><?php printf(
		_('&copy; 2017 <a href="%s">Valerio Bozzolan</a> & contributors. Made using <a href="%s" title="Data Driven Documents">D3</a>. Data from Wikimedia Foundation <a href="%s" title="Wikimedia Foundation traffic reporting">traffic reporting</a>.'),
		'https://boz.reyboz.it',
		'https://d3js.org',
		_('https://meta.wikimedia.org/wiki/Traffic_reporting')
	) ?></p>

	<p class="license-disclaimer"><?php printf(
		_('<a href="%s" title="Wikimedia portal stats source code">This website</a> is free as in freedom software. You can study, execute, improve and share it respecting the <a href="%s" title="GNU Affero General Public License">GNU AGPL</a>.'),
		REPO_URL,
		'https://www.gnu.org/licenses/agpl-3.0.html'
	) ?></p>

	<script src="<?php echo D3_PATH ?>"></script>
	<script>var dataReadyAPI = '<?php echo DATAREADY_ROOT ?>';</script>
	<script src="<?php echo ROOT ?>/static/Wikimedia-portal-stats-d3-bubble-grid.js"></script>
</body>
</html>
