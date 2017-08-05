/*
 * Wikimedia stats by local projects
 * Copyright (C) 2017  Valerio Bozzolan and contributors
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

var svg    = d3.select('svg');
var select = d3.select('select[name=pick-dataset]');

d3.json( dataReadyAPI, function (dataReady) {
	select.selectAll('option')
		.data( dataReady )
		.enter()
			.append('option')
			.html( function ( d ) {
				return d.wiki + ": " +
				d.date.y.toString() + "/" +
				d.date.m.toString() + "/" +
				d.date.d.toString() + " h" +
				d.date.h.toString();
			} );

	fetchAndDraw();
	select.on('change', fetchAndDraw);
} );

function fetchAndDraw() {
	this.fetchCache = {};

	var that = this;

	var selected = select.selectAll("option")
		.filter( function () { 
			return this.selected; 
		} )
		.each( function (d) {
			if( that.fetchCache[ d.file ] ) {
				draw( { stats: that.fetchCache[ d.file ] } );
			} else {
				d3.json( d.file, function (stats) {
					that.fetchCache[ d.file ] = stats;
					draw( { stats: stats } );
				} );
			}
		} );
}

d3.selectAll('.by-ratio').on('click', function () {
	draw( { by: 'ratio', toggleOrder: true } );
} );

d3.selectAll('.by-hits').on('click', function () {
	draw( { by: 'hits', toggleOrder: true } );
} );

d3.selectAll('.by-pages').on('click', function () {
	draw( { by: 'pages', toggleOrder: true } );
} );

function toggleOrder(order) {
	return order === 'desc' ? 'asc' : 'desc';
}

d3.select(window).on('resize', function () {
	draw();
} );

function draw( args ) {
	var args = args || {};

	if( this.initialStats === undefined ) {
		if( args.stats ) {
			this.initialStats = args.stats;
		}
	}

	var stats = this.initialStats;

	// No portals
	if( ! stats ) {
		console.log("No portals");
		return;
	}

	var elements = svg.selectAll('g');

	// Merge
	if( args.stats && elements.size() ) {
		if( stats.wiki === args.stats.wiki ) {
			for(var i=0; i<stats.portals.length; i++) {
				for(var j=0; j<args.stats.portals.length; j++) {
					var found = true;
					if( stats.portals[i].name === args.stats.portals[j].name ) {
						for(var prop in args.stats.portals[j]) {
							stats.portals[i][prop] = args.stats.portals[j][prop];
						}
						break;
					}
					if( ! found ) {
						console.log("Missing portal");
						console.log(stats.portals[i]);
						args.clear = true;
						break;
					}
				}
			}
		} else {
			args.clear = true;
		}
	}

	var DEFAULT_BY    = 'ratio'; // 'ratio', 'hits', 'pages'
	var DEFAULT_ORDER = 'desc';  // 'desc', 'asc'

	this.previousBy    = this.previousBy                || DEFAULT_BY;
	this.previousOrder = this.previousOrder             || DEFAULT_ORDER;
	args.by            = args.by                        || this.previousBy  || DEFAULT_BY;
	args.toggleOrder   = args.toggleOrder === undefined ? false             :  args.toggleOrder;

	if( args.order === undefined ) {
		args.order = ( args.toggleOrder && args.by === this.previousBy )
			? toggleOrder( this.previousOrder )
			: this.previousOrder;
	}

	this.previousBy    = args.by;
	this.previousOrder = args.order;

	this.previous = args.by;

	var order = args.order === 'desc'
		? function (a, b) { return a < b ? 1 : -1; }
		: function (a, b) { return a > b ? 1 : -1; };

	var iCallback     = function ( d, i ) { return i;       };
	var hitsCallback  = function ( d )    { return d.hits;  };
	var pagesCallback = function ( d )    { return d.pages; };
	var nameCallback  = function ( d )    { return d.name;  };
	var ratioCallback = function ( d )    {
		var pages = pagesCallback( d );
		if( pages === 0 ) {
			return 0; // Penalization
		}
		return hitsCallback(d) / pages;
	};

	var minHits  = d3.min( stats.portals, hitsCallback );
	var maxHits  = d3.max( stats.portals, hitsCallback );
	var minPages = d3.min( stats.portals, pagesCallback );
	var maxPages = d3.max( stats.portals, pagesCallback );
	var minRatio = d3.min( stats.portals, ratioCallback );
	var maxRatio = d3.max( stats.portals, ratioCallback );

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

	stats.portals.sort( sortingCallback );

	var color = d3.scaleOrdinal(d3.schemeCategory20c);

	var minSize = 15;
	var maxSize = 100;

	var realMaxSize = maxSize + minSize;

	var width = d3.select('body').node().getBoundingClientRect().width;
	svg.attr('width', width);

	var radiusCallback = function ( d, i ) {
		return ( interestingValue(d) - minInterestingValue ) *
			( maxSize - minSize ) /
			( maxInterestingValue - minInterestingValue ) +
			minSize;
	};

	var SHORT     = 150;
	var NORMAL    = 500;
	var LONG      = 900;
	var EXTRALONG = 1000;

	if( args.clear ) {
		elements.remove();

		if( args.stats ) {
			stats = this.initialStats = args.stats;
		}
	}

	if( ! svg.selectAll('g').size() ) {

		// Only first time

		elements = elements.data( stats.portals ).enter()
			.append('g');

		elements.append('circle')
			.attr('title', nameCallback )
			.style('fill', function ( d, i ) {
				return color( i );
			} );

		elements.append('text')
			.attr('class', 'data-name');

		elements.append('text')
			.attr('class', 'data-value')
			.attr('transform', 'translate(100, 20)');

		elements.on('mouseover', function() {
			d3.select(this).select('circle')
				.transition().duration( NORMAL )
					.style('opacity', 0.5)
		} );

		elements.on('mouseout', function() {
			d3.select(this).select('circle')
				.transition().duration( SHORT )
					.style('opacity', 1);
		} );

		elements.on('click', function () {
			var el = d3.select(this);
			var data = el.data()[0];
			var i = stats.portals.indexOf(data);

			stats.portals.splice(i, 1);

			var transition = el.transition().duration( NORMAL )
				.style('opacity', 0);

			transition.selectAll('circle')
				.attr('r', 0);

			transition.selectAll('text')
				.style('font-size', '1px');

			setTimeout( function () {
				draw( { toggleOrder: false } );
			}, NORMAL );
		} );

	}

	var PADDING = 200;
	var COLS    = parseInt( width / ( realMaxSize + PADDING ) );
	if( COLS < 1 ) {
		COLS = 1;
	}

	elements.transition().duration( EXTRALONG )
		.attr('transform', function (d) {

			var iOrdered = stats.portals.indexOf( d );
			var col = iOrdered % COLS;
			var x = width / COLS * col + PADDING / 2;
			var y = parseInt( iOrdered / COLS ) * realMaxSize * 2 + realMaxSize;

			// Proud note:
			//
			// The above formulas have been written in 2~3 minutes
			// only writing them in my head while wandering across my room
			// WITHOUT testing them in any way AND THEY WORKED AT THE FIRST ATTEMPT!
			//
			// 	«OHHHH! IN YOUR FACE MATH TESTS, IN YOUR FACE!»
			//
			// -- Valerio Bozzolan 4 agosto 2017 11:41

			return 'translate(' + x.toString() + ',' + y.toString() + ')';
		} )

	elements.selectAll('circle')
		.transition().duration( NORMAL )
		.attr('r', radiusCallback )

	elements.selectAll('text.data-name')
		.text( nameCallback );

	elements.selectAll('text.data-value')
		.text( humanInterestingValue );

	var height = parseInt( stats.portals.length / COLS ) * realMaxSize * 2;

	svg.transition().duration( LONG )
		.attr('height', height);
}
