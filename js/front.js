( function ( $ ) {

	// set Cookie Notice
	$.fn.setCookieNotice = function ( cookie_value ) {
		if ( acnArgs.onScroll === 'yes' ) {
			$( window ).off( 'scroll', acnHandleScroll );
		}

		var acnTime = new Date(),
			acnLater = new Date(),
			acnDomNode = $( '#adsimple-cookie-notice' ),
			tabDomNode = $( '#adsimple-readmore-tab' ),
			acnSelf = this;

		// set expiry time in seconds
		acnLater.setTime( parseInt( acnTime.getTime() ) + parseInt( acnArgs.cookieTime ) * 1000 );

		// set cookie
		cookie_value = cookie_value === 'accept' ? true : false;
		document.cookie = acnArgs.cookieName + '=' + cookie_value + ';expires=' + acnLater.toGMTString() + ';' + ( acnArgs.cookieDomain !== undefined && acnArgs.cookieDomain !== '' ? 'domain=' + acnArgs.cookieDomain + ';' : '' ) + ( acnArgs.cookiePath !== undefined && acnArgs.cookiePath !== '' ? 'path=' + acnArgs.cookiePath + ';' : '' );

		// trigger custom event
		$.event.trigger( {
			type: 'setCookieNotice',
			value: cookie_value,
			time: acnTime,
			expires: acnLater
		} );

		// hide message container
		$.event.trigger('acn-before-hide-notice');
		if ( acnArgs.hideEffect === 'fade' ) {
			acnDomNode.fadeOut( 300, function () {
				acnSelf.removeCookieNotice();
			} );
		} else if ( acnArgs.hideEffect === 'slide' ) {
			acnDomNode.slideUp( 300, function () {
				acnSelf.removeCookieNotice();
			} );
		} else {
			acnDomNode.hide(300, function() {
				acnSelf.removeCookieNotice();
			});
		}
		$.event.trigger('acn-after-hide-notice');

		if ( cookie_value && acnArgs.redirection === '1' ) {
			var url = window.location.protocol + '//',
				hostname = window.location.host + '/' + window.location.pathname;

			if ( acnArgs.cache === '1' ) {
				url = url + hostname.replace( '//', '/' ) + ( window.location.search === '' ? '?' : window.location.search + '&' ) + 'acn-reloaded=1' + window.location.hash;

				window.location.href = url;
			} else {
				url = url + hostname.replace( '//', '/' ) + window.location.search + window.location.hash;

				window.location.reload( true );
			}

			return;
		}
	};

	// remove Cookie Notice
	$.fn.removeCookieNotice = function ( cookie_value ) {
		//$( '#adsimple-cookie-notice' ).remove();
		var tabDomNode = $( '#adsimple-readmore-tab' );

		$.event.trigger('acn-before-hide-notice');

		if ( acnArgs.hideEffect === 'fade' ) {
			tabDomNode.fadeIn( 300 );
		} else if ( acnArgs.hideEffect === 'slide' ) {
			tabDomNode.slideDown( 300 );
		} else {
			tabDomNode.show();
		}

		$( 'body' ).removeClass( 'cookies-not-accepted' );

		$.event.trigger('acn-after-hide-notice');
	};

	$( window ).on('load', function() {

		if ( document.cookie.indexOf( 'adsimple_cookie_notice_accepted' ) === -1 ) {

			var acnDomNode = $( '#adsimple-cookie-notice' );
			var tabDomNode = $( '#adsimple-readmore-tab' );

			$.event.trigger('acn-before-show-notice');

			if(tabDomNode.length == 0) {
				if ( acnArgs.hideEffect === 'fade' ) {
					acnDomNode.fadeIn( 300 );
				} else if ( acnArgs.hideEffect === 'slide' ) {
					acnDomNode.slideDown( 300 );
				} else {
					acnDomNode.show();
				}
			}

			$.event.trigger('acn-after-show-notice');

		}

	});

	$( document ).ready( function () {
		var acnDomNode = $( '#adsimple-cookie-notice' );
		var tabDomNode = $( '#adsimple-readmore-tab' );

		if ( acnArgs.autoHide ) {
			setTimeout(function(){

				$.event.trigger('acn-before-hide-readmore');

				if ( acnArgs.hideEffect === 'fade' ) {
					tabDomNode.fadeOut( 500, function(){tabDomNode.hide()} );
					acnDomNode.fadeOut( 500, function(){acnDomNode.hide()} );
				} else if ( acnArgs.hideEffect === 'slide' ) {
					tabDomNode.slideUp( 500, function(){tabDomNode.hide()});
					acnDomNode.slideUp( 500, function(){acnDomNode.hide()});
				} else {
					tabDomNode.hide();
					acnDomNode.hide();
				}

				$.event.trigger('acn-after-hide-readmore');

			}, acnArgs.autoHide);
		}

		tabDomNode.on('click', function(e) {
			e.preventDefault();

			$.event.trigger('acn-before-hide-readmore');

			if ( acnArgs.hideEffect === 'fade' ) {
				$(this).fadeOut( 300, function(){
					acnDomNode.fadeIn( 300 );
				});
			} else if ( acnArgs.hideEffect === 'slide' ) {
				$(this).slideDown( 300, function(){
					acnDomNode.slideUp( 300 );
				});
			} else {
				$(this).hide();
				acnDomNode.show();
			}

			$.event.trigger('acn-after-hide-readmore');


			$.event.trigger('acn-before-show-notice');

			$(this).slideUp(500, function() {
				$("#adsimple-cookie-notice").slideDown(600);
			});

			$.event.trigger('acn-after-show-notice');
		});

		// handle on scroll
		if ( acnArgs.onScroll === 'yes' ) {
			acnHandleScroll = function () {
				var win = $( this );

				if ( win.scrollTop() > parseInt( acnArgs.onScrollOffset ) ) {
					// accept cookie
					win.setCookieNotice( 'accept' );

					// remove itself after cookie accept
					win.off( 'scroll', acnHandleScroll );
				}
			};
		}

		// handle set-cookie button click
		$( document ).on( 'click', '.acn-set-cookie', function ( e ) {
			e.preventDefault();

			$( this ).setCookieNotice( $( this ).data( 'cookie-set' ) );
		} );

		$.event.trigger('acn-before-show-readmore');

		if ( acnArgs.hideEffect === 'fade' ) {
			tabDomNode.fadeIn( 300 );
		} else if ( acnArgs.hideEffect === 'slide' ) {
			tabDomNode.slideDown( 300 );
		} else {
			tabDomNode.show();
		}

		$.event.trigger('acn-after-show-readmore');

		// display cookie notice
		if ( document.cookie.indexOf( 'adsimple_cookie_notice_accepted' ) === -1 ) {
			// handle on scroll
			if ( acnArgs.onScroll === 'yes' ) {
				$( window ).on( 'scroll', acnHandleScroll );
			}

			$( 'body' ).addClass( 'cookies-not-accepted' );
		} else {
			acnDomNode.removeCookieNotice();
		}
	} );

} )( jQuery );