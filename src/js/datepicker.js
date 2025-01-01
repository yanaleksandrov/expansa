// склонение с числительными: https://gist.github.com/realmyst/1262561
( function( global, factory ) {
	typeof exports === 'object' && typeof module !== 'undefined' ? module.exports = factory() :
		typeof define === 'function' && define.amd ? define( factory ) :
		( global.Datepicker = factory() );
}( this, ( function() {
	'use strict';

	var _typeof = typeof Symbol === 'function' && typeof Symbol.iterator === 'symbol' ? function( obj ) {
		return typeof obj;
	} : function( obj ) {
		return obj && typeof Symbol === 'function' && obj.constructor === Symbol && obj !== Symbol.prototype ? 'symbol' : typeof obj;
	};

	var classCallCheck = function( instance, Constructor ) {
		if ( !( instance instanceof Constructor ) ) {
			throw new TypeError( 'Cannot call a class as a function' );
		}
	};

	var createClass = function() {
		function defineProperties( target, props ) {
			for ( var i = 0; i < props.length; i++ ) {
				var descriptor = props[i];
				descriptor.enumerable   = descriptor.enumerable || false;
				descriptor.configurable = true;
				if ( 'value' in descriptor ) {
					descriptor.writable = true
				}
				Object.defineProperty( target, descriptor.key, descriptor );
			}
		}

		return function( Constructor, protoProps, staticProps ) {
			if ( protoProps )  defineProperties( Constructor.prototype, protoProps );
			if ( staticProps ) defineProperties( Constructor, staticProps );
			return Constructor;
		};
	}();

	var toConsumableArray = function( arr ) {
		if ( Array.isArray( arr ) ) {
			for ( var i = 0, arr2 = Array( arr.length ); i < arr.length; i++ ) {
				arr2[i] = arr[i];
			}
			return arr2;
		} else {
			return Array.from( arr );
		}
	};

	function $$( selector, ctx ) {
		var els = ( ctx || document ).querySelectorAll( selector );
		return [].concat( toConsumableArray( els ) )
	}

	function matches( el, selector ) {
		var matchesSelector = el.matches || el.matchesSelector || el.webkitMatchesSelector || el.msMatchesSelector;
		return matchesSelector && matchesSelector.call( el, selector )
	}

	function closest( el, selector, top ) {
		var toofar = top && !top.contains( el );
		while ( el && !toofar ) {
			if ( matches( el, selector ) ) return el;
			toofar = top && !top.contains( el.parentNode );
			el = el.parentNode;
		}
		return false
	}

	function addClass( el, c ) {
		el.classList.add.apply( el.classList, c.split( ' ' ).filter( Boolean ) );
	}

	function removeClass( el, c ) {
		el.classList.remove.apply( el.classList, c.split( ' ' ).filter( Boolean ) );
	}

	function hasClass( el, c ) {
		return c && el.classList.contains( c )
	}

	function toggleClass( el, c, force ) {
		if ( typeof force == 'undefined' ) force = !hasClass( el, c );
		c && ( !!force ? addClass( el, c ) : removeClass( el, c ) );
	}

	function getDataAttributes( elem ) {
		var trim = function trim( s ) {
			return s.trim()
		};
		var obj = {};
		if ( !elem || !elem.dataset ) return obj;
		for ( var key in elem.dataset ) {
			var val = elem.dataset[key];
			if ( /true|false/.test( val.toLowerCase() ) ) {
				val = val.toLowerCase() == 'true';
			} else if ( val[0] == '[' && val.substr( -1 ) == ']' ) {
				val = transform( val.substr( 1, val.length - 2 ).split( ',' ), trim );
			} else if ( /^\d*$/.test( val ) ) {
				val = parseInt( val, 10 );
			}
			obj[key] = val;
		}
		return obj
	}

	function isLeapYear( year ) {
		return year % 4 === 0 && year % 100 !== 0 || year % 400 === 0
	}

	function getDaysInMonth( year, month ) {
		if ( year instanceof Date ) {
			month = year.getMonth();
			year = year.getFullYear();
		}
		return [31, isLeapYear( year ) ? 29 : 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31][month]
	}

	function dateInArray( date, array, dim ) {
		for ( var i = 0; i < array.length; i++ ) {
			var a = date;
			var b = array[i];
			if ( dim == 'year' ) {
				a = a.getFullYear();
				b = b.getFullYear();
			} else if ( dim == 'month' ) {
				a = a.getMonth();
				b = b.getMonth();
			} else {
				a = a.getTime();
				b = b.getTime();
			}
			if ( a == b ) {
				return true
			}
		}
		return false
	}

	function compareDates( a, b ) {
		return a.getTime() - b.getTime()
	}

	function isValidDate( date ) {
		return !!date && date instanceof Date && !isNaN( date.getTime() )
	}
	
	function validateTime( el, max ) {
		var _max = String( max ),
			_pos = el.selectionStart;
		
		var data = [ '^([0-9]' ];
		for( var i = 0; i <= parseInt( _max[0] ); i++ ) {
			if ( i === parseInt( _max[0] ) ) {
				data.push( '|' + i + '[0-' + _max[1] + ']' );
			} else {
				data.push( '|' + i + '[0-9]' );
			}
		}
		data.push( ')$' );
		
		var reg = data.join('');
		
		var regexp = new RegExp( reg );
		if( el.value[0] > _max[0] ) {
			el.value = '0' + el.value;
		}
		var isValid = regexp.test( el.value );
		if( !isValid || el.value.length > 2 ) {
			el.value = el.value.slice( 0, -1 );
		}
		
		var next = el.nextElementSibling;
		if( el.value.length === 2 && isValid && next && next.nodeName === 'INPUT' ) {
			next.focus();
			next.setSelectionRange(0, 0);
		}
	}

	function setToStart( date ) {
		return transform( date, function( d ) {
			if ( d ) d.setHours( 0, 0, 0, 0 );
			return d
		} )
	}

	function dateRange( start, end ) {
		start = new Date( start );
		end   = new Date( end );
		var date = start;
		if ( start > end ) {
			start = end;
			end   = date;
			date  = start;
		}
		var dates = [new Date( date )];
		while ( date < end ) {
			date.setDate( date.getDate() + 1 );
			dates.push( new Date( date ) );
		}
		return dates
	}

	function isPlainObject( obj ) {
		if ( ( typeof obj === 'undefined' ? 'undefined' : _typeof( obj ) ) == 'object' && obj !== null ) {
			var proto = Object.getPrototypeOf( obj );
			return proto === Object.prototype || proto === null
		}
		return false
	}

	function deepExtend() {
		for ( var _len = arguments.length, arg = Array( _len ), _key = 0; _key < _len; _key++ ) {
			arg[_key] = arguments[_key];
		}
		var obj = arg[0],
			other = arg.slice( 1 );
		for ( var i = 0; i < other.length; i++ ) {
			for ( var p in other[i] ) {
				if ( obj[p] !== undefined && _typeof( other[i][p] ) === 'object' && other[i][p] !== null && other[i][p].nodeName === undefined ) {
					if ( other[i][p] instanceof Date ) {
						obj[p] = new Date( other[i][p].getTime() );
					}
					if ( Array.isArray( other[i][p] ) ) {
						obj[p] = other[i][p].slice( 0 );
					} else {
						obj[p] = deepExtend( obj[p], other[i][p] );
					}
				} else {
					obj[p] = other[i][p];
				}
			}
		}
		return obj
	}

	function transform( obj, fn, ctx ) {
		var ret = [].concat( obj ).map( fn, ctx );
		return ret.length === 1 ? ret[0] : ret
	}

	function tmpl( str, data ) {
		var fn = new Function( 'obj', 'var p=[],print=function(){p.push.apply( p,arguments );};' + 'with( obj ){p.push( \'' + str.replace( /[\r\t\n]/g, ' ' ).split( '<%' ).join( '\t' ).replace( /( ( ^|%> )[^\t]* )'/g, '$1\r' ).replace( /\t=( .*? )%>/g, '\',$1,\'' ).split( '\t' ).join( '\' );' ).split( '%>' ).join( 'p.push( \'' ).split( '\r' ).join( '\\\'' ) + '\' );}return p.join( \'\' );' );
		return data ? fn( data ) : fn
	}

	var defaultOptions = {
		inline: false,
		multiple: false,
		ranged: false,
		time: false,
		timeInput: true,
		timeAmPm: true,
		openOn: 'today',
		min: false,
		max: false,
		within: false,
		without: false,
		yearRange: 5,
		months: 3,
		minutesStep: 15,
		weekStart: 0,
		defaultTime: {
			start: [12, 0],
			end: [23, 0]
		},
		separator: ',',
		serialize: function serialize( date ) {
			return date;
			var dateStr = new Date( date ).toLocaleDateString(),
				timeStr = new Date( date ).toLocaleTimeString();
			if ( this.get( 'time' ) ) {
				return dateStr + '@' + timeStr.replace( /( \d{1,2}:\d{2} ):00/, '$1' );
			}
			return dateStr
		},
		deserialize: function deserialize( str ) {
			return new Date( str )
		},
		toValue: false,
		fromValue: false,
		onInit: false,
		onChange: false,
		onRender: false,
		lang: ( navigator.language || navigator.userLanguage || 'en-US' ).substr( 0, 2 ).toLowerCase(),
		i18n: {
			ampm: [],
			months: [],
			weekdays: [],
		},
		classNames: {
			node: 'datepicker',
			wrapper: 'datepicker__wrapper',
			calendar: 'datepicker__calendar',
			inline: 'is-inline',
			selected: 'is-selected',
			disabled: 'is-disabled',
			otherMonth: 'is-otherMonth',
			weekend: 'is-weekend',
			today: 'is-today',
			start: 'is-start',
			end: 'is-end',
		},
		templates: {
			month: [
				'<div class="datepicker__month">',
					'<%= renderHeader() %>',
					'<%= renderMonth() %>',
				'</div>',
			].join( '' ),
			header: [
				'<div class="datepicker__header">',
					'<a class="datepicker__prev<%= ( hasPrev ) ? "" : " is-disabled" %>" data-prev>&lsaquo;</a>',
					'<span class="datepicker__title"><%= renderMonthSelect() %></span>',
					'<span class="datepicker__title"><%= renderYearSelect() %></span>',
					'<a class="datepicker__next<%= ( hasNext ) ? "" : " is-disabled" %>" data-next>&rsaquo;</a>',
				'</div>'
			].join( '' ),
			time: [
				'<div class="datepicker__timepicker">',
					'<%= renderTimepicker() %>',
				'</div>',
			].join( '' ),
			timepicker: [
				'<div class="datepicker__timebox">',
					'<div class="datepicker__time">',
						'<%= renderHourSelect() %> : ',
						'<%= renderMinuteSelect() %>',
					'</div>',
					'<%= renderPeriodSelect() %>',
				'</div>'
			].join( '' ),
			calendar: [
				'<div class="datepicker__box">',
					'<table>',
						'<thead>',
							'<tr>',
								'<% weekdays.forEach( function( name ) { %>',
								'<th><%= name %></th>',
								'<% } ); %>',
							'</tr>',
						'</thead>',
						'<tbody>',
							'<% days.forEach( function( day, i ) { %>',
							'<%= ( i % 7 == 0 ) ? "<tr>" : "" %>',
							'<%= renderDay( day ) %>',
							'<%= ( i % 7 == 6 ) ? "</tr>" : "" %>',
							'<% } ); %>',
						'</tbody>',
					'</table>',
				'</div>'
			].join( '' ),
			day: [
				'<% classNames.unshift( "datepicker__day" ); %>',
				'<td class="<%= classNames.join( " " ) %>" data-day="<%= timestamp %>">',
					'<span><%= daynum %></span>',
				'</td>'
			].join( '' )
		}
	};

	function updateInline( isInline, opts ) {
		var inlineClass = opts.classNames.inline;
		if ( this.node ) {
			toggleClass( this.node, inlineClass, isInline );
			this.wrapper.style.position = isInline ? '' : 'absolute';
			this.wrapper.style.display  = isInline ? '' : 'none';
		}
		this._isOpen = isInline;
		return isInline
	}

	function updateClassNames( classNames, opts ) {
		var nodeClass	= classNames.node,
			inlineClass  = classNames.inline,
			wrapperClass = classNames.wrapper;
		var isInline = opts.inline;
		if ( this.node ) {
			for ( var key in classNames ) {
				switch ( key ) {
					case 'node':
					case 'inline':
						this.node.className = nodeClass + ( isInline ? ' ' + inlineClass : '' );
						break;
					case 'wrapper':
						this.wrapper.className = wrapperClass;
						break;
				}
			}
		}
		return classNames
	}

	function deserializeMinMax( value, opts ) {
		var deserialize = opts.deserialize;
		value = !value ? false : transform( value, deserialize, this );
		return isValidDate( value ) ? value : false
	}

	function deserializeWithinWithout( arr, opts ) {
		var deserialize = opts.deserialize;
		if ( arr.length ) {
			arr = setToStart( transform( arr, deserialize, this ) );
			arr = [].concat( arr ).filter( isValidDate );
		}
		return arr.length ? arr : false
	}

	function deserializeOpenOn( openOn, opts ) {
		var deserialize = opts.deserialize;
		if ( typeof openOn == 'string' && !/^( first|last|today )$/.test( openOn ) ) {
			openOn = deserialize.call( this, openOn );
			if ( !isValidDate( openOn ) ) openOn = new Date;
		}
		if ( !this._month ) {
			var date = openOn;
			if ( typeof date === 'string' || !isValidDate( date ) ) date = new Date;
			date = setToStart( new Date( date.getTime() ) );
			date.setDate( 1 );
			this._month = date;
		}
		return openOn
	}

	function constrainWeekstart( weekstart ) {
		return Math.min( Math.max( weekstart, 0 ), 6 )
	}

	function defaultTimeObject( time, opts ) {
		if ( isPlainObject( time ) ) {
			return deepExtend( {}, time, opts.defaultTime )
		}
		return {
			start: time.slice( 0 ),
			end: time.slice( 0 )
		}
	}

	function bindOptionFunctions( fn ) {
		return typeof fn === 'function' ? fn.bind( this ) : false
	}

	function createTemplateRenderers( templates ) {
		for ( var name in templates ) {
			if ( name === 'select' ) continue;
			this._renderers[name] = tmpl( templates[name] );
		}
		return templates
	}
	
	/**
	 * Translate calendar labels.
	 */
	function translate( type, opts, index ) {
		var data = [];
		if( type === 'months' ) {
			for( var month = 0; month < 12; month++ ) {
				var date = new Date( Date.UTC( 2000, month, 1, 0, 0, 0 ) ),
					name = new Intl.DateTimeFormat( opts.lang, {
						month: 'long',
					} ).format( date );
					
				data.push( name[0].toUpperCase() + name.substring( 1 ) );
			}
			opts.i18n.months = data;
		}
		
		if( type === 'ampm' ) {
			var startDate = new Date( Date.UTC( 2000, 1, 1, 0, 0, 0 ) ),
				endDate   = new Date( Date.UTC( 2000, 1, 1, 12, 0, 0 ) );

			var dateTimeFormat = new Intl.DateTimeFormat( opts.lang, {
				hour: 'numeric',
				minute: 'numeric'
			});
			var parts = dateTimeFormat.formatRangeToParts( startDate, endDate );
			for ( var part of parts ) {
				if( part.type === 'dayPeriod' ) {
					data.push( part.value.toUpperCase() );
				}
			}
			
			if( !data.length ) {
				data = [ 'AM', 'PM' ];
			}
			opts.i18n.ampm = data;
		}
		
		if( type === 'weekdays' ) {
			for( var weekday = 1; weekday <= 7; weekday++ ) {
				var date = new Date( Date.UTC( 2020, 10, weekday, 0, 0, 0 ) ),
					name = new Intl.DateTimeFormat( opts.lang, {
						weekday: 'short',
					} ).format( date );
				
				data.push( name[0].toUpperCase() + name.substring( 1 ) );
			}
			
			if( opts.weekStart === 1 ) {
				data.push( data.shift() );
			}
		}
		
		if( typeof index !== 'undefined' ) {
			return data[index];
		}
		return data;
	}

	var Datepicker = function() {
		function Datepicker( elem, opts ) {
			var _this = this;
			classCallCheck( this, Datepicker );
			if ( typeof elem === 'string' ) {
				if ( '#' == elem.substr( 0, 1 ) ) {
					elem = document.getElementById( elem.substr( 1 ) );
				} else {
					return $$( elem ).map( function( el ) {
						return new _this.constructor( el, opts )
					} )
				}
			}
			if ( !elem ) {
				elem = document.createElement( 'input' );
			}
			if ( 'input' === elem.tagName.toLowerCase() && !/input|hidden/i.test( elem.type ) ) {
				elem.type = 'text';
			}
			this._initDOM( elem );
			this._initOptions( opts );
			this._initEvents();
			this.setValue( elem.value || elem.dataset.value || '' );
			if ( this._opts.onInit ) {
				this._opts.onInit( elem );
			}
		}
		createClass( Datepicker, [{
			key: '_initOptions',
			value: function _initOptions() {
				var _this2  = this;
				var opts    = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {};
				this._opts  = {};
				var minMax	= deserializeMinMax.bind( this );
				var withInOut = deserializeWithinWithout.bind( this );
				this._set = {
					openOn:	     deserializeOpenOn.bind( this ),
					inline:	     updateInline.bind( this ),
					weekstart:   constrainWeekstart.bind( this ),
					min:		 minMax,
					max:		 minMax,
					within:	     withInOut,
					without:	 withInOut,
					defaultTime: defaultTimeObject.bind( this ),
					classNames:  updateClassNames.bind( this ),
					templates:   createTemplateRenderers.bind( this )
				};
				var fns = ['serialize', 'deserialize', 'onInit', 'onChange', 'onRender', 'setValue', 'getValue'];
				fns.forEach( function( name ) {
					return _this2._set[name] = bindOptionFunctions.bind( this )
				} );
				this._renderers = {
					select: tmpl( [
						'<select data-<%= type %>="<%= value %>" data-index="<%= index %>">',
							'<% options.forEach( function( o ) { %>',
							'<option value="<%= o.value %>"',
							'<%= o.selected ? " selected" : "" %> <%= o.disabled ? " disabled" : "" %>',
							'><%= o.text %></option>',
							'<% } ); %>',
						'</select>'
					].join( '' ) ),
					input: tmpl( [
						'<input type="text" data-<%= type %>="<%= value %>" data-index="<%= index %>" value="<%= value %>">'
					].join( '' ) ),
				};
				this.set( deepExtend( {}, this.constructor.defaults, getDataAttributes( this._el ), opts ) );
			}
		}, {
			key: '_initDOM',
			value: function _initDOM( elem ) {
				if ( this.node ) return;
				this._el  = elem;
				this.node = document.createElement( 'div' );
				this.node.style.position = 'relative';
				this.wrapper = document.createElement( 'div' );
				this.wrapper.style.zIndex = 9999;
				if ( elem.parentNode ) {
					elem.parentNode.insertBefore( this.node, elem );
				}
				this.node.appendChild( elem );
				this.node.appendChild( this.wrapper );
			}
		}, {
			key: '_initEvents',
			value: function _initEvents() {
				var _this3 = this;
				if ( this._isInitialized ) return;
				this._highlighted = [];
				if ( 'input' !== this._el.tagName.toLowerCase() ) {
					this._el.addEventListener( 'click', function() {
						return _this3.toggle()
					} );
				} else {
					this._el.addEventListener( 'focus', function() {
						return _this3.open()
					} );
				}
				document.addEventListener( 'mousedown', function( e ) {
					if ( !_this3.node.contains( e.target ) ) _this3.hide();
				} );
				this.node.onselectstart = function() {
					return false
				};
				this.node.addEventListener( 'mousedown', this._onmousedown.bind( this ) );
				this.node.addEventListener( 'mousemove', this._onmousemove.bind( this ) );
				this.node.addEventListener( 'mouseup', this._onmouseup.bind( this ) );
				this.node.addEventListener( 'click', this._onclick.bind( this ) );
				this.node.addEventListener( 'input', this._oninput.bind( this ) );
				this._isInitialized = true;
			}
		}, {
			key: '_onmousedown',
			value: function _onmousedown( e ) {
				var opts     = this._opts,
					ranged   = opts.ranged,
					selector = opts.classNames.selected,
					dateNode = closest( e.target, '[data-day]', this.wrapper ),
					date     = dateNode ? parseInt( dateNode.dataset.day, 10 ) : null;
				
				if( e.which == 3 || e.button == 2 ) {
					return false;
				}
				if ( date ) {
					if ( !ranged || !this._dragStart ) {
						this._deselect	= !ranged && this.hasDate( new Date( date ) );
						this._highlighted = [date];
						this._dragStart   = date;
						if ( !opts.multiple ) {
							$$( '[data-day].' + selector, this.wrapper ).forEach( function( el ) {
								removeClass( el, selector );
								removeClass( el, opts.classNames.start );
								removeClass( el, opts.classNames.end );
							} );
						}
						$$( '[data-day="' + date + '"]', this.wrapper ).forEach( function( el ) {
							addClass( el, opts.classNames.start );
							addClass( el, opts.classNames.end );
						} );
					} else {
						$$( '[data-day="' + date + '"]', this.wrapper ).forEach( function( el ) {
							addClass( el, opts.classNames.end );
						} );
						this._onmousemove( e );
					}
				}
			}
		}, {
			key: '_onmousemove',
			value: function _onmousemove( e ) {				
				var _this5   = this,
					opts     = this._opts,
					ranged   = opts.ranged,
					selector = opts.classNames.selected;
					
				if ( !( ranged || opts.multiple ) ) return;
				
				var dateNode = closest( e.target, '[data-day]', this.wrapper ),
					date     = dateNode ? parseInt( dateNode.dataset.day, 10 ) : null;
				
				if ( date && this._dragStart ) {
					this._highlighted = dateRange( this._dragStart, date ).map( function( d ) {
						return d.getTime()
					} );
					this._isDragging = date !== this._dragStart;
					$$( '[data-day].' + selector, this.wrapper ).forEach( function( el, key, arr ) {
						var d = new Date( parseInt( el.dataset.day, 10 ) );
						toggleClass( el, selector, !ranged && _this5.hasDate( d ) );
					} );
					this._highlighted.forEach( function( t ) {
						$$( '[data-day="' + t + '"]', _this5.wrapper ).forEach( function( el ) {
							toggleClass( el, selector, !_this5._deselect );
						} );
					} );
				}
			}
		}, {
			key: '_onmouseup',
			value: function _onmouseup( e ) {
				var opts	 = this._opts,
					ranged   = opts.ranged,
					multiple = opts.multiple;
				if ( this._dragStart && closest( e.target, '[data-day]', this.node ) ) {
					var dates = this._highlighted.map( function( t ) {
						return new Date( t )
					} );
					if ( ranged || !multiple ) {
						this.setDate( dates );
					} else {
						this.toggleDate( dates, !this._deselect );
					}
					this.render();
					if ( !multiple && ( !ranged || this._isDragging ) ) {
						this.hide();
					}
				}
				if ( !ranged || this._isDragging ) {
					this._highlighted = [];
					this._dragStart = null;
				}
				this._isDragging = false;
			}
		}, {
			key: '_onclick',
			value: function _onclick( e ) {
				var _this6 = this;
				var el = e.target;
				if ( el.hasAttribute( 'data-prev' ) ) {
					this.prev( el.dataset.prev );
				} else if ( el.hasAttribute( 'data-next' ) ) {
					this.next( el.dataset.next );
				} else if ( el.hasAttribute( 'data-year' ) && !el.onchange ) {
					el.onchange = function() {
						var c = el.dataset.year;
						var y = _this6._month.getFullYear();
						_this6._month.setFullYear( parseInt( el.value ) - ( c - y ) );
						_this6.render();
					};
				} else if ( el.hasAttribute( 'data-month' ) && !el.onchange ) {
					el.onchange = function() {
						_this6._month.setMonth( el.value - el.dataset.index );
						_this6.render();
					};
				} else if ( el.hasAttribute( 'data-hour' ) && !el.onchange ) {
					el.onchange = function() {
						_this6.setTime( el.dataset.hour, el.value );
					};
				} else if ( el.hasAttribute( 'data-minute' ) && !el.onchange ) {
					el.onchange = function() {
						_this6.setTime( el.dataset.minute, null, el.value );
					};
				} else if ( el.hasAttribute( 'data-period' ) && !el.onchange ) {
					el.onchange = function() {
						var part = el.dataset.period;
						var diff = el.value === 'am' ? -12 : 12;
						$$( '[data-hour="' + part + '"] option', _this6.wrapper ).forEach( function( el ) {
							el.value = parseInt( el.value ) + diff;
						} );
						_this6.setTime( part, ( _this6._time ? _this6._time[part][0] : 0 ) + diff );
					};
				}
			}
		}, {
			key: '_oninput',
			value: function _oninput( e ) {
				var el = e.target;
				if( el.nodeName === 'INPUT' ) {
					var max = 23;
					if( el.dataset.hour && this._opts.timeAmPm ) {
						max = 12;
					}
					if( el.dataset.minute ) {
						max = 59;
					}
					validateTime( el, max );
				}
			}
		}, {
			key: 'set',
			value: function set$$1( key, val ) {
				if ( !key ) return;
				if ( isPlainObject( key ) ) {
					this._noRender = true;
					if ( key.serialize ) {
						this.set( 'serialize', key.serialize );
						delete key.serialize;
					}
					if ( key.deserialize ) {
						this.set( 'deserialize', key.deserialize );
						delete key.deserialize;
					}
					for ( var k in key ) {
						this.set( k, key[k] );
					}
					this._noRender = false;
					val = this._opts;
				} else {
					var opts = deepExtend( {}, this.constructor.defaults, this._opts );
					if ( key in this._set ) {
						val = this._set[key]( val, opts );
					}
					if ( isPlainObject( val ) ) {
						val = deepExtend( {}, opts[key], val );
					}
					this._opts[key] = val;
				}
				if ( this._isOpen && this.wrapper ) {
					this.render();
				}
				return val
			}
		}, {
			key: 'get',
			value: function get$$1( key ) {
				var _this7 = this;
				if ( arguments.length > 1 ) {
					return [].concat( Array.prototype.slice.call( arguments ) ).reduce( function( o, a ) {
						o[a] = _this7.get( a );
						return o
					}, {} )
				}
				var val = this._opts[key];
				if ( isPlainObject( val ) ) {
					val = deepExtend( {}, val );
				}
				return val
			}
		}, {
			key: 'open',
			value: function open( date ) {
				var selected = [].concat( this.getDate() );
				date = date || this._opts.openOn || this._month;
				if ( typeof date === 'string' ) {
					date = date.toLowerCase();
					if ( date === 'first' && selected.length ) {
						date = selected[0];
					} else if ( date === 'last' && selected.length ) {
						date = selected[selected.length - 1];
					} else if ( date !== 'today' ) {
						date = this._opts.deserialize( date );
					}
				}
				if ( !isValidDate( date ) ) {
					date = new Date;
				}
				this.setTime( !!this._selected.length );
				this.goToDate( date );
				this.render();
				this.show();
			}
		}, {
			key: 'show',
			value: function show() {
				if ( !this._opts.inline ) {
					this.wrapper.style.display = 'block';
					var nRect	 = this.node.getBoundingClientRect();
					var elRect   = this._el.getBoundingClientRect();
					var elBottom = elRect.bottom - nRect.top + 'px';
					var elTop	 = nRect.bottom - elRect.top + 'px';
					
 					Object.assign( this.wrapper.style, {
						top: elBottom,
						right: '',
						bottom: '',
						left: 0,
					} );
					
					var rect	 = this.wrapper.getBoundingClientRect();
					var posRight = rect.right > window.innerWidth;
					var posTop   = rect.bottom > window.innerHeight;
					
					Object.assign( this.wrapper.style, {
						top:    posTop   ? '' : elBottom,
						right:  posRight ? 0 : '',
						bottom: posTop   ? elTop : '',
						left:   posRight ? '' : 0,
					} );
					
					rect = this.wrapper.getBoundingClientRect();
					var fitLeft = rect.right >= rect.width;
					var fitTop  = rect.bottom > rect.height;
					
					Object.assign( this.wrapper.style, {
						top: posTop && fitTop ? '' : elBottom,
						right: posRight && fitLeft ? 0 : '',
						bottom: posTop && fitTop ? elTop : '',
						left: posRight && fitLeft ? '' : 0,
					} );
					this._isOpen = true;
				}
			}
		}, {
			key: 'hide',
			value: function hide() {
				if ( !this._opts.inline ) {
					this.wrapper.style.display = 'none';
					this._isOpen = false;
				}
			}
		}, {
			key: 'toggle',
			value: function toggle() {
				if ( this._isOpen ) {
					this.hide();
				} else {
					this.open();
				}
			}
		}, {
			key: 'next',
			value: function next( skip ) {
				var date = new Date( this._month.getTime() );
				skip = Math.max( skip || 1, 1 );
				date.setMonth( date.getMonth() + skip );
				this.goToDate( date );
			}
		}, {
			key: 'prev',
			value: function prev( skip ) {
				var date = new Date( this._month.getTime() );
				skip = Math.max( skip || 1, 1 );
				date.setMonth( date.getMonth() - skip );
				this.goToDate( date );
			}
		}, {
			key: 'goToDate',
			value: function goToDate( date ) {
				date = setToStart( this._opts.deserialize( date ) );
				date.setDate( 1 );
				this._month = date;
				if ( this._isOpen ) {
					this.render();
				}
				if ( this._opts.onNavigate ) {
					this._opts.onNavigate( date );
				}
			}
		}, {
			key: 'hasDate',
			value: function hasDate( date ) {
				date = setToStart( isValidDate( date ) ? date : this._opts.deserialize( date ) );
				return !!this._selected && this._selected.indexOf( date.getTime() ) > -1
			}
		}, {
			key: 'addDate',
			value: function addDate( date ) {
				this.toggleDate( date, true );
			}
		}, {
			key: 'removeDate',
			value: function removeDate( date ) {
				this.toggleDate( date, false );
			}
		}, {
			key: 'toggleDate',
			value: function toggleDate( date, force ) {
				var _this8   = this;
				var opts	 = this._opts,
					ranged   = opts.ranged,
					multiple = opts.multiple;
				var dates = [].concat( date );
				dates = dates.map( function( d ) {
					return isValidDate( d ) ? d : opts.deserialize( d )
				} );
				dates = dates.filter( function( d ) {
					return isValidDate( d ) && _this8.dateAllowed( d )
				} );
				if ( ranged ) {
					dates = dates.concat( this.getDate() ).sort( compareDates );
					dates = dates.length ? dateRange( dates[0], dates.pop() ) : [];
				} else if ( !multiple ) {
					dates = dates.slice( 0, 1 );
				}
								
				dates.map( function( d ) {
					return setToStart( d ).getTime()
				} ).forEach( function( t ) {
					var index   = _this8._selected.indexOf( t );
					var hasDate = index > -1;
					if ( !hasDate && force !== false ) {
						if ( ranged || multiple ) {
							_this8._selected.push( t );
						} else {
							_this8._selected = [t];
						}
					} else if ( hasDate && force !== true ) {
						_this8._selected.splice( index, 1 );
					}
				} );
				this._update();
			}
		}, {
			key: '_update',
			value: function _update() {
				var onChange = this._opts.onChange;
				if ( this._el.nodeName.toLowerCase() === 'input' ) {
					this._el.value = this.getValue();
				} else {
					this._el.dataset.value = this.getValue();
				}
				if ( onChange ) {
					onChange( this.getDate() );
				}
			}
		}, {
			key: 'getDate',
			value: function getDate() {
				var opts  = this._opts;
				var start = this._time ? this._time.start : [0, 0];
				this._selected = ( this._selected || [] ).sort();
				if ( opts.multiple || opts.ranged ) {
					var sel = this._selected.map( function( t ) {
						return new Date( t )
					} );
					if ( opts.time && sel.length ) {
						sel[0].setHours( start[0], start[1] );
						if ( sel.length > 1 ) {
							var end = this._time ? this._time.end : [0, 0];
							sel[sel.length - 1].setHours( end[0], end[1] );
						}
					}
					return sel
				}
				if ( this._selected.length ) {
					var d = new Date( this._selected[0] );
					d.setHours( start[0], start[1] );
					return d
				}
			}
		}, {
			key: 'setDate',
			value: function setDate( date ) {
				this._selected = [];
				this.addDate( date );
			}
		}, {
			key: 'setTime',
			value: function setTime( part, hour, minute ) {
				var opts = this._opts;
				if ( !opts.time ) return;
				if ( part === true || !this._time ) {
					this._time = deepExtend( {}, opts.defaultTime );
				}
				if ( part && part !== true ) {
					if ( typeof part === 'number' ) {
						minute = hour;
						hour   = part;
						part   = 'start';
					}
					part   = part === 'end' ? part : 'start';
					hour   = hour ? parseInt( hour, 10 ) : false;
					minute = minute ? parseInt( minute, 10 ) : false;
					if ( hour && !isNaN( hour ) ) {
						this._time[part][0] = hour;
					}
					if ( minute && !isNaN( minute ) ) {
						this._time[part][1] = minute;
					}
				}
				this._update();
			}
		}, {
			key: 'getValue',
			value: function getValue() {
				var opts	= this._opts,
					toValue = opts.toValue;
				var selected = [].concat( this.getDate() || [] );
				if ( opts.ranged && selected.length > 1 ) {
					selected = [selected[0], selected.pop()];
				}
				var value = selected.map( opts.serialize ).join( opts.separator );
				if ( toValue ) {
					value = toValue( value, selected );
				}
				return value
			}
		}, {
			key: 'setValue',
			value: function setValue( val ) {
				var opts	  = this._opts,
					fromValue = opts.fromValue;
				this._selected = [];
				var dates = fromValue ? fromValue( val ) : val.split( opts.separator ).filter( Boolean ).map( opts.serialize );
				this.addDate( dates );
				if ( opts.time && dates.length ) {
					var start = dates.sort( compareDates )[0];
					this.setTime( 'start', new Date( start ).getHours(), new Date( start ).getMinutes() );
					if ( opts.time === 'ranged' || opts.ranged ) {
						var end = dates[dates.length - 1];
						this.setTime( 'end', new Date( end ).getHours(), new Date( end ).getMinutes() );
					}
				}
			}
		}, {
			key: 'dateAllowed',
			value: function dateAllowed( date, dim ) {
				var opts	= this._opts,
					min	 = opts.min,
					max	 = opts.max,
					within  = opts.within,
					without = opts.without;
				var belowMax = void 0,
					aboveMin = belowMax = true;
				date = setToStart( isValidDate( date ) ? date : opts.deserialize( date ) );
				if ( dim == 'month' ) {
					aboveMin = !min || date.getMonth() >= min.getMonth();
					belowMax = !max || date.getMonth() <= max.getMonth();
				} else if ( dim == 'year' ) {
					aboveMin = !min || date.getFullYear() >= min.getFullYear();
					belowMax = !max || date.getFullYear() <= max.getFullYear();
				} else {
					aboveMin = !min || date >= min;
					belowMax = !max || date <= max;
				}
				return aboveMin && belowMax && ( !without || !dateInArray( date, without, dim ) ) && ( !within || dateInArray( date, within, dim ) )
			}
		}, {
			key: 'render',
			value: function render() {
				var _this9 = this;
				var opts   = this._opts,
					time   = opts.time,
					i	   = 0;
				if ( this._noRender || !this._renderers ) return;
				var renderCache = {};
				var getData = function getData( i ) {
					return renderCache[i] || ( renderCache[i] = _this9.getData( i ) )
				};
				
				this.wrapper.innerHTML = '';
				for( ; i < opts.months; i++ ) {
					this.wrapper.innerHTML += this._renderers.month( {
						renderHeader: function renderHeader() {
							return _this9._renderHeader( getData( i ) )
						},
						renderMonth: function renderMonth() {
							var data = getData( i );
							return _this9._renderers.calendar( Object.assign( {}, data, {
								renderHeader: function renderHeader() {
									return _this9._renderHeader( data )
								},
								renderDay: function renderDay( day ) {
									return _this9._renderers.day( day )
								}
							} ) )
						}
					} );
				}
				this.wrapper.innerHTML = '<div class="' + opts.classNames.calendar + '">' + this.wrapper.innerHTML + '</div>';

				this.wrapper.innerHTML += this._renderers.time( {
					renderTimepicker: function renderTimepicker() {
						if ( time ) {
							var html = '' + _this9._renderTimepicker( 'start' );
							if ( time === 'ranged' || opts.ranged ) {
								html += _this9._renderTimepicker( 'end' );
							}
							return html;
						}
					}
				} );

				if ( opts.onRender ) {
					opts.onRender( this.wrapper.firstChild );
				}
			}
		}, {
			key: 'getData',
			value: function getData() {
				var index = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : 0;
				var opts  = this._opts,
					i18n  = opts.i18n;
					
				var date = new Date( this._month.getTime() );
					date.setMonth( date.getMonth() + index );
				var month = date.getMonth(),
					year  = date.getFullYear();
				var nextMonth = new Date( date.getTime() );
					nextMonth.setMonth( nextMonth.getMonth() + 1 );
					nextMonth.setDate( 1 );
				var prevMonth = new Date( date.getTime() );
					prevMonth.setMonth( prevMonth.getMonth() - 1 );
					prevMonth.setDate( getDaysInMonth( prevMonth ) );
				var days = [];
				var start = date.getDay() - opts.weekStart;
				while ( start < 0 ) {
					start += 7;
				}
				var dayCount = getDaysInMonth( year, month ) + start;
				while ( dayCount % 7 ) {
					dayCount += 1;
				}
				var today = setToStart( new Date );
				
				var selected = this._selected;
				
				i18n.weekdays = i18n.weekdays[weekday] || translate( 'weekdays', opts );
				for ( var i = 0; i < dayCount; i++ ) {
					var day         = new Date( year, month, 1 + ( i - start ) ),
						dayMonth	= day.getMonth(),
						weekday	    = day.getDay(),
						isSelected  = this.hasDate( day ),
						isDisabled  = !this.dateAllowed( day ),
						isPrevMonth = dayMonth < month,
						isNextMonth = dayMonth > month,
						isThisMonth = !isPrevMonth && !isNextMonth,
						isWeekend   = weekday === 0 || weekday === 6,
						isToday	    = day.getTime() === today.getTime(),
						classNames  = [];
						
					if ( isSelected )   classNames.push( opts.classNames.selected );
					if ( isDisabled )   classNames.push( opts.classNames.disabled );
					if ( !isThisMonth ) classNames.push( opts.classNames.otherMonth );
					if ( isWeekend )	classNames.push( opts.classNames.weekend );
					if ( isToday )	    classNames.push( opts.classNames.today );
					
					if ( typeof selected !== 'undefined' ) {
						if( selected[0] === day.getTime() ) {
							classNames.push( opts.classNames.start );
						}
						if ( selected[selected.length - 1] === day.getTime() ) {
							classNames.push( opts.classNames.end );
						}
					}
					
					days.push( {
						_date: day,
						date: opts.serialize( day ),
						daynum: day.getDate(),
						timestamp: day.getTime(),
						weekday: i18n.weekdays[weekday],
						isSelected: isSelected,
						isDisabled: isDisabled,
						isPrevMonth: isPrevMonth,
						isNextMonth: isNextMonth,
						isThisMonth: isThisMonth,
						isWeekend: isWeekend,
						isToday: isToday,
						classNames: classNames
					} );
				}
				return {
					_date: date,
					index: index,
					year: year,
					month: i18n.months[month] || translate( 'months', opts, month ),
					days: days,
					weekdays: i18n.weekdays,
					hasNext: !opts.max || nextMonth <= opts.max,
					hasPrev: !opts.min || prevMonth >= opts.min
				}
			}
		}, {
			key: '_renderHeader',
			value: function _renderHeader( data ) {
				var _this10   = this;
				var opts	  = this._opts,
					yearRange = opts.yearRange,
					i18n	  = opts.i18n;
				var _date = data._date,
					index = data.index,
					year  = data.year;
				var month = _date.getMonth();
				return this._renderers.header( Object.assign( {}, data, {
					renderMonthSelect: function renderMonthSelect() {
						var i = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : index;
						var d = new Date( _date.getTime() );
						var options = [];
						for ( var m = 0; m < 12; m++ ) {
							d.setMonth( m );
							options.push( {
								text: i18n.months[m] || translate( 'months', opts, m ),
								disabled: !_this10.dateAllowed( d, 'month' ),
								selected: m === month,
								value: m
							} );
						}
						return _this10._renderers.select( {
							index: i,
							type: 'month',
							text: i18n.months[month] || translate( 'months', opts, month ),
							value: month,
							options: options
						} )
					},
					renderYearSelect: function renderYearSelect() {
						var i   = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : index;
						var d   = new Date( _date.getTime() );
						var y   = year - yearRange;
						var max = year + yearRange;
						var options = [];
						for ( ; y <= max; y++ ) {
							d.setFullYear( y );
							options.push( {
								disabled: !_this10.dateAllowed( d, 'year' ),
								selected: y === year,
								value: y,
								text: y
							} );
						}
						return _this10._renderers.select( {
							index: i,
							type: 'year',
							text: year,
							value: year,
							options: options
						} )
					}
				} ) )
			}
		}, {
			key: '_renderTimepicker',
			value: function _renderTimepicker( name ) {
				var _this11 = this,
					opts    = this._opts,
					i18n    = opts.i18n;
				if ( !opts.time ) return;
				if ( !this._time ) {
					this.setTime( true );
				}
				var time = this._time[name];
				
				return this._renderers.timepicker( {
					renderHourSelect: function renderHourSelect() {
						var long	= arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : false;
						var options = [],
							hour	= time[0],
							end	    = long ? 24 : 12;
						
						if( _this11._opts.timeInput ) {
							return _this11._renderers.input( {
								index: 0,
								type: 'hour',
								value: hour < 10 ? '0' + hour : hour
							} )
						} else {
							if( !_this11._opts.timeAmPm ) {
								for ( var h = 0; h < 24; h++ ) {
									options.push( {
										text: h,
										selected: hour === h,
										disabled: false,
										value: h
									} );
								}
							} else {
								for ( var h = 0; h < end; h++ ) {
									options.push( {
										text: long || h ? h : '12',
										selected: hour === h,
										disabled: false,
										value: h
									} );
								}
								if ( !long && hour >= 12 ) {
									options.forEach( function( o ) {
										return o.selected = ( o.value += 12 ) === hour
									} );
								} else if ( !long ) {
									//options.push( options.shift() );
								}
							}
							
							console.log( options );
							var text = options.filter( function( o ) {
								return o.selected
							} )[0].text;
							
							return _this11._renderers.select( {
								index: 0,
								type: 'hour',
								value: name,
								options: options,
								text: text
							} )
						}
					},
					renderMinuteSelect: function renderMinuteSelect() {
						var step    = _this11._opts.minutesStep,
							incr    = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : ( step > 0 && step < 31 ? step : 15 ),
							options = [],
							minute  = time[1];
						for ( var i = 0; i < 60; i += incr ) {
							options.push( {
								text: i < 10 ? '0' + i : i,
								selected: minute === i,
								disabled: false,
								value: i
							} );
						}
						
						if( _this11._opts.timeInput ) {
							return _this11._renderers.input( {
								index: null,
								type: 'minute',
								value: minute, //minute < 10 ? '0' + minute : minute
							} )
						} else {
							var text = options.filter( function( o ) {
								return o.selected
							} )[0].text;
							return _this11._renderers.select( {
								index: null,
								type: 'minute',
								value: name,
								options: options,
								text: text
							} )
						}
					},
					renderPeriodSelect: function renderPeriodSelect() {
						if( opts.timeAmPm ) {
							var ampm = i18n.ampm.length !== 0 ? i18n.ampm : translate( 'ampm', opts );
							return _this11._renderers.select( {
								index: null,
								type: 'period',
								text: time[0] >= 12 ? ampm[1] : ampm[0],
								value: name,
								options: [{
									text: ampm[0],
									value: 'am',
									selected: time[0] < 12
								}, {
									text: ampm[1],
									value: 'pm',
									selected: time[0] >= 12
								}]
							} )
						}
					}
				} )
			}
		}] );
		return Datepicker
	}();
	Datepicker.defaults = defaultOptions;

	return Datepicker;

} ) ) );