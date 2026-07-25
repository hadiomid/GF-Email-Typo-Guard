/**
 * GF Email Typo Guard - front end.
 *
 * Watches only the <input> elements explicitly flagged by the PHP
 * side (data-gfetg-domain-check="1"), so this never touches email
 * fields that don't have the feature enabled. No external library:
 * a small Levenshtein-distance check is enough for short domain
 * strings and keeps the payload minimal.
 */
( function () {
	'use strict';

	if ( typeof gfetgData === 'undefined' ) {
		return;
	}

	var domains   = gfetgData.domains || [];
	var threshold = gfetgData.threshold || 0.3;
	var template  = ( gfetgData.strings && gfetgData.strings.suggestionTemplate ) || 'Did you mean %s?';

	function levenshtein( a, b ) {
		var alen = a.length;
		var blen = b.length;
		if ( alen === 0 ) return blen;
		if ( blen === 0 ) return alen;

		var matrix = [];
		var i, j;

		for ( i = 0; i <= blen; i++ ) matrix[ i ] = [ i ];
		for ( j = 0; j <= alen; j++ ) matrix[ 0 ][ j ] = j;

		for ( i = 1; i <= blen; i++ ) {
			for ( j = 1; j <= alen; j++ ) {
				if ( b.charAt( i - 1 ) === a.charAt( j - 1 ) ) {
					matrix[ i ][ j ] = matrix[ i - 1 ][ j - 1 ];
				} else {
					matrix[ i ][ j ] = Math.min(
						matrix[ i - 1 ][ j - 1 ] + 1,
						matrix[ i ][ j - 1 ] + 1,
						matrix[ i - 1 ][ j ] + 1
					);
				}
			}
		}

		return matrix[ blen ][ alen ];
	}

	/**
	 * Compares the domain (including TLD) against the known-good list
	 * as a single string, so both "gamil.com" (wrong provider name)
	 * and "gmail.con" (wrong TLD) are caught by the same check.
	 */
	function suggestDomain( email ) {
		var atPos = email.lastIndexOf( '@' );
		if ( atPos === -1 ) return null;

		var local  = email.substring( 0, atPos );
		var domain = email.substring( atPos + 1 ).toLowerCase();

		if ( ! domain ) return null;
		if ( domains.indexOf( domain ) !== -1 ) return null; // Exact match already.

		var bestDistance = null;
		var bestDomain   = null;
		var i, d;

		for ( i = 0; i < domains.length; i++ ) {
			d = levenshtein( domain, domains[ i ] );
			if ( bestDistance === null || d < bestDistance ) {
				bestDistance = d;
				bestDomain   = domains[ i ];
			}
		}

		if ( bestDomain === null || bestDistance === 0 ) return null;

		var ratio = bestDistance / Math.max( bestDomain.length, 1 );
		if ( ratio > threshold || bestDistance > 3 ) return null;

		return local + '@' + bestDomain;
	}

	function buildHintElement( input ) {
		var hint = document.createElement( 'div' );
		hint.className = 'gfetg-hint';
		input.insertAdjacentElement( 'afterend', hint );
		return hint;
	}

	function clearHint( input ) {
		var hint = input._gfetgHint;
		if ( hint ) {
			hint.textContent = '';
			hint.style.display = 'none';
		}
	}

	function showHint( input, suggestion ) {
		var hint = input._gfetgHint || ( input._gfetgHint = buildHintElement( input ) );
		hint.innerHTML = '';

		var idx    = template.indexOf( '%s' );
		var prefix = idx !== -1 ? template.substring( 0, idx ) : template;
		var suffix = idx !== -1 ? template.substring( idx + 2 ) : '';

		var link = document.createElement( 'button' );
		link.type = 'button';
		link.className = 'gfetg-hint-link';
		link.textContent = suggestion;
		link.addEventListener( 'click', function () {
			input.value = suggestion;
			clearHint( input );
			input.focus();
			input.dispatchEvent( new Event( 'change', { bubbles: true } ) );
		} );

		hint.appendChild( document.createTextNode( prefix ) );
		hint.appendChild( link );
		hint.appendChild( document.createTextNode( suffix ) );
		hint.style.display = 'block';
	}

	function handleBlur( e ) {
		var input = e.target;
		var value = input.value.trim();

		if ( ! value ) {
			clearHint( input );
			return;
		}

		var suggestion = suggestDomain( value );

		if ( suggestion ) {
			showHint( input, suggestion );
		} else {
			clearHint( input );
		}
	}

	function handleInput( e ) {
		// Don't leave a stale suggestion visible once the user starts
		// editing the field again.
		clearHint( e.target );
	}

	function bindInputs( container ) {
		var inputs = container.querySelectorAll( 'input[data-gfetg-domain-check="1"]' );
		var i;
		for ( i = 0; i < inputs.length; i++ ) {
			if ( inputs[ i ]._gfetgBound ) continue;
			inputs[ i ]._gfetgBound = true;
			inputs[ i ].addEventListener( 'blur', handleBlur );
			inputs[ i ].addEventListener( 'input', handleInput );
		}
	}

	document.addEventListener( 'DOMContentLoaded', function () {
		bindInputs( document );
	} );

	// Gravity Forms re-renders form markup after AJAX-enabled
	// submissions and multi-page navigation, firing this jQuery event.
	// Re-bind any newly rendered inputs when that happens.
	if ( window.jQuery ) {
		window.jQuery( document ).on( 'gform_post_render', function () {
			bindInputs( document );
		} );
	}
} )();
