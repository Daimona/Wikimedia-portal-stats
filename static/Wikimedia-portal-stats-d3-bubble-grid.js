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

var svg = d3.select('svg');

var latestPortals;

d3.json( dataReadyAPI, function (dataReady) {
	var done = 0;

	for( file in dataReady.files ) {
		if( done > 0 ) {
			return;
		}

		done++;

		var file = dataReady.files[ file ];
		d3.json( file, function (stats) {
			d3.selectAll('.dataset')
				.text( dataReady.files[ file ] );

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

d3.select(window).on('resize', function () {
	latestPortals && draw( { toggleOrder: false } );
} );

function draw( args ) {
	var args = args || {};

	var DEFAULT_BY    = 'ratio'; // 'ratio', 'hits', 'pages'
	var DEFAULT_ORDER = 'asc';  // 'desc', 'asc'

	this.previousBy    = this.previousBy                || DEFAULT_BY;
	this.previousOrder = this.previousOrder             || DEFAULT_ORDER;
	args.by            = args.by                        || this.previousBy  || DEFAULT_BY;
	args.toggleOrder   = args.toggleOrder === undefined && true             || args.toggleOrder;

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

	latestPortals.sort( sortingCallback );

	var color = d3.scaleOrdinal(d3.schemeCategory20c);

	var minSize = 15;
	var maxSize = 100;

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

	var SHORT     = 150;
	var NORMAL    = 500;
	var LONG      = 900;
	var EXTRALONG = 1000;

	var elements = svg.selectAll('g');

	args.clean && elements.remove();

	if( ! svg.selectAll('g').size() ) {

		// Only first time
		elements = elements.data( latestPortals )
			.enter()
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
			var i = latestPortals.indexOf( data );
			latestPortals.splice(i, 1);

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

	console.log( COLS );

	elements.transition().duration( EXTRALONG )
		.attr('transform', function (d, i) {

			var iOrdered = latestPortals.indexOf( d );
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

	var height = parseInt( latestPortals.length / COLS ) * realMaxSize * 2;

	svg.transition().duration( LONG )
		.attr('height', height);
}
