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

?>
<!DOCTYPE html>
<html>
<head>
	<title><?php echo _("Wikimedia portal stats") ?></title>
	<style>
	text {
		font-size:0.9em;
	}
	text.data-value {
		font-size:0.5em;
	}
	</style>
</head>
<body>
	<p>
	<button class="by-ratio">By ratio</button>
	<button class="by-hits">By hits</button>
	<button class="by-pages">By pages</button>
	</p>

	<svg width="960" height="960" text-anchor="middle"></svg>
	<script src="<?php echo D3_PATH ?>"></script>
	<script>
	var svg = d3.select('svg');

	var latestPortals;

	var dataReadyAPI = '<?php echo DATAREADY_ROOT ?>';
	d3.json( dataReadyAPI, function (dataReady) {
		var done = 0;

		for( file in dataReady.files ) {
			if( done > 0 ) {
				return;
			}

			done++;

			var file = dataReady.files[ file ];
			d3.json( file, function (stats) {

				latestPortals = [];
				for(var portal in stats.portals) {
					var portalData = stats.portals[ portal ];
					if( portalData.hits === 0 || portalData.pages === 0 ) {
						continue;
					}
					latestPortals.push( {
						name: portal,
						data: portalData
					} );
				}
				draw();

			} );
		}
	} );

	d3.selectAll('.by-ratio').on('click', function () {
		draw( { by: 'ratio' } );
	} );

	d3.selectAll('.by-hits').on('click', function () {
		draw( { by: 'hits' } );
	} );

	d3.selectAll('.by-pages').on('click', function () {
		draw( { by: 'pages' } );
	} );

	function toggleOrder(order) {
		return order === 'desc' ? 'asc' : 'desc';
	}

	function draw( args ) {
		var args = args || {};

		var DEFAULT_BY    = 'ratio'; // 'ratio', 'hits', 'pages'
		var DEFAULT_ORDER = 'asc';  // 'desc', 'asc'

		this.previousBy    = this.previousBy    || DEFAULT_BY;
		this.previousOrder = this.previousOrder || DEFAULT_ORDER;
		args.by            = args.by            || this.previousBy || DEFAULT_BY;

		if( args.order === undefined ) {
			args.order = args.by === this.previousBy
				? toggleOrder( this.previousOrder )
				: this.previousOrder;
		}

		this.previousBy    = args.by;
		this.previousOrder = args.order;

		console.log("ASD");
		console.log(this.previousOrder);
		console.log(args.order);

		this.previous = args.by;

		var order = args.order === 'desc'
			? function (a, b) { return a < b ? 1 : -1; }
			: function (a, b) { return a > b ? 1 : -1; };

		var iCallback     = function ( d, i ) { return i; };
		var hitsCallback  = function ( d ) { return d.data.hits;   };
		var pagesCallback = function ( d ) { return d.data.pages;  };
		var nameCallback  = function ( d ) { return d.name;        };
		var ratioCallback = function ( d ) { return hitsCallback(d) / pagesCallback(d); };

		var minHits  = d3.min( latestPortals, hitsCallback );
		var maxHits  = d3.max( latestPortals, hitsCallback );
		var minPages = d3.min( latestPortals, pagesCallback );
		var maxPages = d3.max( latestPortals, pagesCallback );
		var minRatio = d3.min( latestPortals, ratioCallback );
		var maxRatio = d3.max( latestPortals, ratioCallback );

		var interestingValue;
		var secondInterestingValue;
		var minInterestingValue;
		var maxInterestingValue;
		var humanInterestingValue;

		switch( args.by ) {
			case 'hits':
				interestingValue       = hitsCallback;
				secondInterestingValue = pagesCallback;
				minInterestingValue    = minHits;
				maxInterestingValue    = maxHits;
				humanInterestingValue  = function (d) {
					return interestingValue(d).toString() + " hits";
				};
				break;
			case 'pages':
				interestingValue       = pagesCallback;
				secondInterestingValue = hitsCallback;
				minInterestingValue    = minPages;
				maxInterestingValue    = maxPages;
				humanInterestingValue  = function (d) {
					return interestingValue(d).toString() + " pages";
				};
				break;
			default:
				interestingValue       = ratioCallback;
				secondInterestingValue = hitsCallback;
				minInterestingValue    = minRatio;
				maxInterestingValue    = maxRatio;
				humanInterestingValue  = function (d) {
					return hitsCallback(d).toString() + " hits / " + pagesCallback(d).toString() + " pages";
				};
		}

		var sortingCallback  = function ( a, b ) {
			var aV = interestingValue(a);
			var bV = interestingValue(b);
			if( aV === bV ) {
				return secondSortingCallback(a, b);
			}
			return order(aV, bV);
		};
		var secondSortingCallback = function (a, b) {
			var aV = secondInterestingValue(a);
			var bV = secondInterestingValue(b);
			if( aV === bV ) {
				return 0;
			}
			return order(aV, bV);
		}

		latestPortals = latestPortals.sort( sortingCallback );

		var color = d3.scaleOrdinal(d3.schemeCategory20c);

		var minSize = 15;
		var maxSize = 50;

		var realMaxSize = maxSize + minSize;

		var width = d3.select('body').node().getBoundingClientRect().width;
		svg.attr('width', width);

		var radiusCallback = function ( d ) {
			return ( interestingValue(d) - minInterestingValue )
				*
				( maxSize - minSize )
				/
				( maxInterestingValue - minInterestingValue )
				+ minSize;
		}

		svg.selectAll('g').remove();

		var elements = svg.selectAll('g')
			.data( latestPortals ).enter()
				.append('g')
					.attr('transform', function (d, i) {
						var x = width / 2;
						var y = i * realMaxSize * 2 + realMaxSize;
						return 'translate(' + x.toString() + ',' + y.toString() + ')';
					} );

		elements.append('circle')
			.attr('title', nameCallback )
			.attr('r', radiusCallback )
			.style('fill', function ( d, i ) {
				return color( i );
			} );

		elements.append('text')
			.attr('class', 'data-name')
			.text( nameCallback );

		elements.append('text')
			.text( humanInterestingValue )
			.attr('class', 'data-value')
			.attr('transform', 'translate(100, 20)');

		elements.on('mouseover', function() {
			d3.select(this).select('circle')
				.transition()
				.duration(500)
				.style('opacity', 0.5)
		} );

		elements.on('mouseout', function() {
			d3.select(this).select('circle')
				.transition()
				.duration(150)
				.style('opacity', 1);
		} );

		elements.on('click', function () {
			var data = d3.select(this).data()[0];

			var i = latestPortals.indexOf( data );
			latestPortals.splice(i, 1);

			draw( { order: args.order } );
		} );

		var height = elements.size() * realMaxSize * 2;
		svg.attr('height', height);
	}

	</script>
</body>
</html>
