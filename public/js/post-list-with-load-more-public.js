(function ( $ ) {
	'use strict';

	/**
	 * All of the code for your public-facing JavaScript source
	 * should reside in this file.
	 *
	 * Note: It has been assumed you will write jQuery code here, so the
	 * $ function reference has been prepared for usage within the scope
	 * of this function.
	 *
	 * This enables you to define handlers, for when the DOM is ready:
	 *
	 * $(function() {
	 *
	 * });
	 *
	 * When the window is loaded:
	 *
	 * $( window ).load(function() {
	 *
	 * });
	 *
	 * ...and/or other possibilities.
	 *
	 * Ideally, it is not considered best practise to attach more than a
	 * single DOM-ready or window-load handler for a particular page.
	 * Although scripts in the WordPress core, Plugins and Themes may be
	 * practising this, we should strive to set a better example in our own work.
	 */
	$( document ).ready( function () {
		$( document ).on( 'click', '#custom-post-form button', function ( e ) {
			e.preventDefault();
			$( '#load-more-image' ).css( 'display', 'block' );
			$( this ).css( 'display', 'none' );
			var page = $( this ).data( 'page' );
			var limit = $( this ).data( 'limit' );
			var args = $( '#post-args' ).val();
			$.ajax( {
				url: myAjax.ajaxurl,
				type: 'post',
				data: { action: 'fetch_posts_list', page: page, limit: limit, args: args },
				success: function ( response ) {
					$( '.custom-post-list' ).append( response );
					$( '#custom-post-form button' ).data( 'page', page + 1 );
					if ( response.indexOf( 'remove-view-more' ) > -1 ) {
						$( '#custom-post-form' ).remove();
						$( '#load-more-image' ).css( 'display', 'none' );
					} else {
						$( '#load-more-image' ).css( 'display', 'none' );
						$( '#custom-post-form button' ).css( 'display', 'block' );
					}
					//alert(response);
				}
			} )
		} );
		let debounce = true;
		var button = $('#custom-post-form.more_on_scroll button');
		if( button.length ) {
			$(window).scroll( function () {
				if( button.isInViewport() && debounce ) {
					$(button).trigger('click');
					debounce = false;
					setTimeout(function () {
						debounce = true;
					},2000);
				}
			});
		}
	} );
	$.fn.isInViewport = function() {
		var elementTop = $(this).offset().top;
		var elementBottom = elementTop + $(this).outerHeight();

		var viewportTop = $(window).scrollTop();
		var viewportBottom = viewportTop + $(window).height();

		return elementBottom > viewportTop && elementTop < viewportBottom;
	};
})( jQuery );
