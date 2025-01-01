/**
 * Colorist.js
 *
 * License: MIT <https://opensource.org/licenses/MIT>
 *
 * @version 1.0.0
 * @author	Yan Alexandrov (c) 2021
 */
( function( global, factory ) {
	if( typeof define === 'function' && define.amd ) {
		define( [], factory );
	} else if( typeof exports === 'object' ) {
		module.exports = factory();
	} else {
		global.colorist = factory();
	}
}( this, function() {
	'use strict';

	var tools = {};

	var hue_drag_started   = false,
		alpha_drag_started = false,
		sat_drag_started   = false;

	var color = [0, 100, 50],
		alpha = 1;

	var sat_width  = 220,
		sat_height = 220,
		hue_height = 220;

	var colorist = {
		options: {},
		init: function( elem, options ) {

			this.elem    = elem;
			this.options = Object.assign(
				this.options,
				{
					default: elem.value ? elem.value : '#000',
					palette: [
						'#eb144c', '#ff6900', '#fcb900', '#ffed2d', '#7bdcb5', '#8ed1fc', '#d8a1e4', '#d8d8d8', '#737373',
					],
					choosen: function( elem, value ) {}
				},
				JSON.parse( elem.getAttribute( 'data-colorist' ) ),
				options
			);

			// create color box
			var isTouchCapable = 'ontouchstart' in window || window.DocumentTouch && document instanceof window.DocumentTouch || navigator.maxTouchPoints > 0 || window.navigator.msMaxTouchPoints > 0,
				siblings       = elem.nextSibling.nextElementSibling;
			if( !siblings || !siblings.classList.contains( 'colorist' ) ) {
				elem.insertAdjacentHTML( 'afterend', `
				<div class="colorist" draggable="false">
					<div class="colorist__box" draggable="false">
						<div class="colorist__box_point point"></div>
					</div>
					<div class="colorist__hue">
						<div class="colorist__hue_point point"></div>
					</div>
					<div class="colorist__alpha">
						<div class="colorist__alpha_point point"></div>
					</div>
					<div class="colorist__dye"></div>
				</div>` );
			}

			tools = {
				input	 : elem,
				colorist : document.querySelector( '.colorist' ),
				hue		 : document.querySelector( '.colorist__hue' ),
				box		 : document.querySelector( '.colorist__box' ),
				alpha	 : document.querySelector( '.colorist__alpha' ),
				dye		 : document.querySelector( '.colorist__dye' ),

				box_point  : document.querySelector( '.colorist__box_point' ),
				hue_point  : document.querySelector( '.colorist__hue_point' ),
				alpha_point: document.querySelector( '.colorist__alpha_point' ),
			};

			sat_width  = tools.box.offsetWidth,
			sat_height = tools.box.offsetHeight,
			hue_height = tools.hue.offsetHeight;

			this.setColors( this.options.default );

			if( this.options.palette && !tools.dye.hasChildNodes() ) {
				this.options.palette.forEach( function( clr, i ) {
					tools.dye.insertAdjacentHTML( 'beforeend', '<i class="colorist__dye_point" style="color: ' + clr + '"></i>' );
				} );
			}

			// HIDE
			document.addEventListener( isTouchCapable ? 'touchstart' : 'mousedown', function(e) {
				if( tools.colorist ) {
					if( !tools.colorist.contains( e.target ) ) {
						tools.colorist.parentNode.removeChild( tools.colorist );
						tools.colorist = null;
					}
				}
			} );

			tools.colorist.style.visibility = 'visible';
			tools.colorist.style.opacity	= 1;

			if( this.options.palette && tools.dye.hasChildNodes() ) {
				var dye_points = document.querySelectorAll( '.colorist__dye_point' );
				for ( var dye_point of dye_points ) {
					dye_point.addEventListener( isTouchCapable ? 'touchstart' : 'click', function(e) {
						colorist.setColors( e.target.style.color );
						tools.input.value = e.target.style.color;
						colorist.options.choosen( tools.input, e.target.style.color );
					} );
				}
			}

			// HUE DRAG START
			tools.hue.addEventListener( isTouchCapable ? 'touchstart' : 'mousedown', function(e) {
				hue_drag_started = true;
				tools.hue_point.classList.add( 'active' );

				colorist.setHuePickerValue(e);
			} );

			// ALPHA DRAG START
			tools.alpha.addEventListener( isTouchCapable ? 'touchstart' : 'mousedown', function(e) {
				alpha_drag_started = true;
				tools.alpha_point.classList.add( 'active' );

				colorist.setAlphaPickerValue(e);
			} );

			// COLOR DRAG START
			tools.box.addEventListener( isTouchCapable ? 'touchstart' : 'mousedown', function(e) {
				sat_drag_started = true;

				tools.box_point.classList.add( 'active' );
				colorist.setSatPickerValue(e);
			} );

			// COLOR, HUE AND ALPHA DRAG
			document.addEventListener( isTouchCapable ? 'touchmove' : 'mousemove', function(e) {
				// COLOR DRAG MOVE
				if( sat_drag_started ) {
					colorist.setSatPickerValue(e);
				}

				// LINE DRAG MOVE
				if( hue_drag_started ) {
					colorist.setHuePickerValue(e);
				}

				// ALPHA DRAG MOVE
				if( alpha_drag_started ) {
					colorist.setAlphaPickerValue(e);
				}
				colorist.setPickerIcon();
			} );

			// COLOR, HUE AND ALPHA DRAG END
			document.addEventListener( isTouchCapable ? 'touchend' : 'mouseup', function() {
				if( sat_drag_started ) {
					tools.box_point.classList.remove( 'active' );
					sat_drag_started = false;
				}

				if( hue_drag_started ) {
					tools.hue_point.classList.remove( 'active' );
					hue_drag_started = false;
				}

				if( alpha_drag_started ) {
					tools.alpha_point.classList.remove( 'active' );
					alpha_drag_started = false;
				}
				colorist.setPickerIcon();
			} );
		},
		/**
		 * Set colors in color picker box
		 * @param {string} clr - Color must be in the formats: HEX( #XXXXXX or #XXX ), RGB(A)
		 */
		setColors: function( clr ) {
			var rgba = colorist.hexToRgba( clr );

			if( !rgba ) {
				rgba = clr.replace(/^rgba?\(|\s+|\)$/g, '').split(',');
				rgba = {
					r: parseInt( rgba[0] ),
					g: parseInt( rgba[1] ),
					b: parseInt( rgba[2] ),
					a: rgba[3] ? parseFloat( rgba[3] ) : 1,
				};
			}

			alpha = rgba.a;

			if( rgba ) {
				var rgb = [ rgba.r, rgba.g, rgba.b ],
					hsl = colorist.rgbToHsl( rgb ),
					hsv = colorist.rgbToHsv( rgba.r, rgba.g, rgba.b );

				color = hsl;

				Object.assign( tools.box.style, {
					background: 'hsl(' + hsl[0] + ', 100%, 50%)',
				} );
				Object.assign( tools.box_point.style, {
					left: hsl[1] + '%',
					top: 100 - ( hsv[2] * 100 ) + '%',
				} );
				Object.assign( tools.hue_point.style, {
					background: 'hsl(' + hsl[0] + ', 100%, 50%)',
					top: hsl[0] / 360 * 100 + '%',
				} );
				Object.assign( tools.alpha.style, {
					background: 'linear-gradient( 180deg, rgba(' + rgb.join(', ') + ', 1), rgba(' + rgb.join(', ') + ', 0) )',
				} );
				Object.assign( tools.alpha_point.style, {
					top: 100 - ( rgba.a * 100 ) + '%',
				} );
			}
		},
		rgbToHsv: function( r, g, b ) {
			if( arguments.length === 1 ) {
				g = r.g, b = r.b, r = r.r;
			}
			var max = Math.max( r, g, b ),
				min = Math.min( r, g, b ),
				d = max - min,
				h,
				s = ( max === 0 ? 0 : d / max ),
				v = max / 255;

			switch ( max ) {
				case min:
					h = 0;
					break;
				case r:
					h = ( g - b ) + d * ( g < b ? 6 : 0 );
					h /= 6 * d;
					break;
				case g:
					h = ( b - r ) + d * 2;
					h /= 6 * d;
					break;
				case b:
					h = ( r - g ) + d * 4;
					h /= 6 * d;
					break;
			}

			return [
				h,
				s,
				v
			];
		},
		rgbToHsl: function( rgbArr ) {
			var r1 = rgbArr[0] / 255;
			var g1 = rgbArr[1] / 255;
			var b1 = rgbArr[2] / 255;

			var maxColor = Math.max( r1, g1, b1 );
			var minColor = Math.min( r1, g1, b1 );
			//Calculate L:
			var L = ( maxColor + minColor ) / 2;
			var S = 0;
			var H = 0;
			if( maxColor != minColor ) {
				//Calculate S:
				if( L < 0.5 ) {
					S = ( maxColor - minColor ) / ( maxColor + minColor );
				} else {
					S = ( maxColor - minColor ) / ( 2.0 - maxColor - minColor );
				}
				//Calculate H:
				if( r1 == maxColor ) {
					H = ( g1 - b1 ) / ( maxColor - minColor );
				} else if( g1 == maxColor ) {
					H = 2.0 + ( b1 - r1 ) / ( maxColor - minColor );
				} else {
					H = 4.0 + ( r1 - g1 ) / ( maxColor - minColor );
				}
			}

			L = L * 100;
			S = S * 100;
			H = H * 60;
			if( H < 0 ) {
				H += 360;
			}
			return [H, S, L];
		},
		rgbaToHex: function( r, g, b, a = 1 ) {
			var rgb   = b | ( g << 8 ) | ( r << 16 ),
				alpha = a < 1 ? ( ( a * 255 ) | 1 << 8 ).toString( 16 ).slice( 1 ) : '';
			return '#' + ( 0x1000000 + rgb ).toString( 16 ).slice( 1 ) + alpha;
		},
		hexToRgba: function( hex ) {
			hex = hex.replace(/#/g, '');
			if( hex.length === 3 ) {
				hex = hex.split('').map( function( hex ) {
					return hex + hex;
				} ).join('');
			}
			var result = /^([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})[\da-z]{0,0}$/i.exec( hex );

			return result ? {
				r: parseInt( result[1], 16 ),
				g: parseInt( result[2], 16 ),
				b: parseInt( result[3], 16 ),
				a: result[4] ? parseInt( result[4], 16 ) : 1,
			} : null;
		},
		bound01: function( n, max ) {
			if( typeof n == 'string' && n.indexOf( '.' ) != -1 && parseFloat( n ) === 1 ) {
				n = '100%';
			}

			var processPercent = typeof n === 'string' && n.indexOf( '%' ) != -1;
			n = Math.min( max, Math.max( 0, parseFloat( n ) ) );

			// Automatically convert percentage into number
			if( processPercent ) {
				n = parseInt( n * max, 10 ) / 100;
			}

			// Handle floating point rounding errors
			if( ( Math.abs( n - max ) < 0.000001 ) ) {
				return 1;
			}

			// Convert into [0, 1] range if it isn't already
			return ( n % max ) / parseFloat( max );
		},
		hslToRgb: function( h, s, l ) {
			var r, g, b;

			h = colorist.bound01( h, 360 );
			s = colorist.bound01( s, 100 );
			l = colorist.bound01( l, 100 );

			function hue2rgb( p, q, t ) {
				if( t < 0 ) t += 1;
				if( t > 1 ) t -= 1;
				if( t < 1 / 6 ) return p + ( q - p ) * 6 * t;
				if( t < 1 / 2 ) return q;
				if( t < 2 / 3 ) return p + ( q - p ) * ( 2 / 3 - t ) * 6;
				return p;
			}

			if( s === 0 ) {
				r = g = b = l; // achromatic
			} else {
				var q = l < 0.5 ? l * ( 1 + s ) : l + s - l * s;
				var p = 2 * l - q;
				r = hue2rgb( p, q, h + 1 / 3 );
				g = hue2rgb( p, q, h );
				b = hue2rgb( p, q, h - 1 / 3 );
			}

			return [ Math.round( r * 255 ), Math.round( g * 255 ), Math.round( b * 255 ) ];
		},
		offset: function( elem ) {
			var docElem, win,
				box = {
					top:  0,
					left: 0
				},
				doc = elem && elem.ownerDocument;

			docElem = doc.documentElement;

			if( typeof elem.getBoundingClientRect !== typeof undefined ) {
				box = elem.getBoundingClientRect();
			}
			win = ( doc !== null && doc === doc.window ) ? doc : doc.nodeType === 9 && doc.defaultView;
			return {
				top:  box.top  + win.pageYOffset - docElem.clientTop,
				left: box.left + win.pageXOffset - docElem.clientLeft
			};
		},
		segmentNumber: function( number, min, max ) {
			return Math.max( min, Math.min( number, max ) );
		},
		returnPickedColor: function() {
			var rgb = colorist.hslToRgb( color[0], color[1], color[2] ),
				hex = colorist.rgbaToHex( rgb[0], rgb[1], rgb[2], alpha );

			tools.hue_point.style.background = 'hsl( ' + color[0] + ',100%, 50% )';
			tools.alpha.style.background = 'linear-gradient( 180deg, rgba(' + rgb.join(', ') + ', 1), rgba(' + rgb.join(', ') + ', 0) )';

			if( alpha < 1 ) {
				var clr = 'rgba(' + rgb.join(', ') + ', ' + parseFloat( alpha.toPrecision( 2 ) ) + ')';
			} else {
				var clr = hex.toUpperCase();
			}

			tools.input.value = clr;
			colorist.options.choosen( tools.input, clr );
		},
		setHuePickerValue: function(e) {
			color[0] = colorist.segmentNumber( Math.floor( ( ( ( ( e.pageY || e.touches[0].pageY ) - colorist.offset( tools.box ).top ) / hue_height ) * 360 ) ), 0, 360 );

			tools.hue_point.style.top = colorist.segmentNumber( ( ( ( e.pageY || e.touches[0].pageY ) - colorist.offset( tools.box ).top ) / hue_height ) * 100, 0, hue_height / 2 ) + '%';

			tools.box.style.background = 'hsl(' + color[0] + ', 100%, 50%)';

			colorist.returnPickedColor();
		},
		setAlphaPickerValue: function(e) {
			alpha = colorist.segmentNumber( ( ( ( e.pageY || e.touches[0].pageY ) - colorist.offset( tools.box ).top ) / hue_height ) * 100, 0, hue_height / 2 );

			tools.alpha_point.style.top = alpha + '%';

			alpha = ( 100 - alpha ) / 100;

			colorist.returnPickedColor();
		},
		setSatPickerValue: function(e) {

			var rect_position = {
				left: colorist.offset( tools.box ).left,
				top: colorist.offset( tools.box ).top
			};

			var x = e.pageX || e.touches[0].pageX,
				y = e.pageY || e.touches[0].pageY;

			var position = [
				colorist.segmentNumber( x - rect_position.left, 0, sat_width ),
				colorist.segmentNumber( y - rect_position.top, 0, sat_height )
			];

			tools.box_point.style.left = position[0] + 'px';
			tools.box_point.style.top  = position[1] + 'px';

			color[1] = Math.floor( ( position[0] / sat_width ) * 100 );

			var x = x - colorist.offset( tools.box ).left,
				y = y - colorist.offset( tools.box ).top;
			//constrain x max
			if( x > sat_width ) {
				x = sat_width;
			}
			if( x < 0 ) {
				x = 0;
			}
			if( y > sat_height ) {
				y = sat_height;
			}
			if( y < 0 ) {
				y = 0;
			}

			//convert between hsv and hsl
			var xRatio    = x / sat_width * 100,
				yRatio    = y / sat_height * 100,
				hsvValue  = 1 - ( yRatio / 100 ),
				hsvSaturation = xRatio / 100,
				lightness = ( hsvValue / 2 ) * ( 2 - hsvSaturation );

			color[2] = Math.floor( lightness * 100 );

			colorist.returnPickedColor();
		},
		setPickerIcon: function() {
			this.elem.style.backgroundRepeat = 'no-repeat';
			this.elem.style.backgroundPosition = 'right 4px center';
			this.elem.style.backgroundImage = "url(\"data:image/svg+xml;charset=UTF-8,%3csvg width='28' height='28' fill='none' xmlns='http://www.w3.org/2000/svg'%3e%3ccircle cx='14' cy='14' r='14' fill='" + this.elem.value.replace( '#', '%23' ) + "'/%3e%3cpath d='M19.84 17.38a.45.45 0 00-.68 0C18.7 17.9 18 18.83 18 19.5c0 .97.5 1.5 1.5 1.5.97 0 1.5-.53 1.5-1.5 0-.67-.71-1.6-1.16-2.12zm.9-4.44l-5.68-5.68a.87.87 0 00-1.23 0l-1.88 1.87a.5.5 0 01-.7 0l-2.01-2a.44.44 0 00-.62 0L8 7.75a.44.44 0 000 .62l2 2c.2.2.2.51 0 .7l-2.23 2.24a2.62 2.62 0 000 3.72l3.2 3.2a2.62 2.62 0 003.71 0l6.06-6.06a.87.87 0 000-1.23zm-3.1 1.9a.5.5 0 01-.35.16H9.83a.5.5 0 01-.36-.85l1.77-1.82c.2-.2.52-.2.72-.01l1.24 1.24a.87.87 0 001.24-1.23l-1.25-1.25a.5.5 0 010-.7l.9-.91c.2-.2.51-.2.71 0l3.74 3.74c.2.19.2.5 0 .7l-.9.94z' fill='%23fff'/%3e%3c/svg%3e\")"
		},
	};
	return colorist;
} ) );
