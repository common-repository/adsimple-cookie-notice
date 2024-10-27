( function ( $ ) {

    $( document ).ready( function () {
        function initAcnId(_this) {
            _this.closest('tr').addClass('js-acn_id-switcher');
            _this.closest('table').find('tr:not(.js-acn_id-switcher)').addClass('js-acn_other');
           if(_this.val()=="1") {
               $('.js-acn_id').removeClass('acn_id').addClass('acn_id--active');
               $('.js-acn_other').css({
                   'display':'none'
               }); 
               _this.closest('table').nextAll('h2,table').hide();
               $('#reset_adsimple_cookie_notice_options').hide();
           } else {
               $('.js-acn_id').removeClass('acn_id--active').addClass('acn_id');
               $('.js-acn_other').css({
                   'display':'table-row'
               });  
               _this.closest('table').nextAll('h2,table').show();
               $('#reset_adsimple_cookie_notice_options').show();
           }
        }
        $(document).on('change','.js-acn_id-radio',function(){
            var _this = $(this);
            initAcnId(_this);
        });
        setTimeout(function(){
            initAcnId($('.js-acn_id-radio:checked'));
        },10);

	// initialize color picker
	$( '.acn_color' ).wpColorPicker();

	// refuse option
	$( '#acn_refuse_opt' ).change( function () {
	    if ( $( this ).is( ':checked' ) ) {
		$( '#acn_refuse_opt_container' ).slideDown( 'fast' );
	    } else {
		$( '#acn_refuse_opt_container' ).slideUp( 'fast' );
	    }
	} );

	// read more option
	$( '#acn_see_more' ).change( function () {
	    if ( $( this ).is( ':checked' ) ) {
		$( '#acn_see_more_opt' ).slideDown( 'fast' );
	    } else {
		$( '#acn_see_more_opt' ).slideUp( 'fast' );
	    }
	} );

	// read more option
	$( '#acn_on_scroll' ).change( function () {
	    if ( $( this ).is( ':checked' ) ) {
		$( '#acn_on_scroll_offset' ).slideDown( 'fast' );
	    } else {
		$( '#acn_on_scroll_offset' ).slideUp( 'fast' );
	    }
	} );

	// read more link
	$( '#acn_see_more_link-custom, #acn_see_more_link-page' ).change( function () {
	    if ( $( '#acn_see_more_link-custom:checked' ).val() === 'custom' ) {
		$( '#acn_see_more_opt_page' ).slideUp( 'fast', function () {
		    $( '#acn_see_more_opt_link' ).slideDown( 'fast' );
		} );
	    } else if ( $( '#acn_see_more_link-page:checked' ).val() === 'page' ) {
		$( '#acn_see_more_opt_link' ).slideUp( 'fast', function () {
		    $( '#acn_see_more_opt_page' ).slideDown( 'fast' );
		} );
	    }
	} );

	$( document ).on( 'click', 'input#reset_adsimple_cookie_notice_options', function () {
	    return confirm( acnArgs.resetToDefaults );
	} );

    } );

} )( jQuery );