/**
 * Handle submission of comment form. Checks field values before submitting.
 */
(function( $ ) { 'use strict';
	$('document').ready( function( $ ) {
		var commentForm = $('#commentform');
		/**
		 * Add aria-live attribute so message errors appended to input fields will be read by assistive technology.
		 */
		commentForm.attr( 'aria-live', 'polite' );
		/**
		 * Add a comment status region with role=status to provide feedback on errors or successes.
		 */
		commentForm.prepend('<div id="comment-status" aria-live="assertive" role="status"></div>');
		var statusdiv = $('#comment-status');
		var list;
		$('a.comment-reply-link').click( function() {
			list = $(this).parents( '.comment' ).attr('id');
		} );

		commentForm.submit(function(){
			//serialize and store form data in a variable
			var formdata = commentForm.serialize();

			$( '#commentform .aac-comment-error' ).remove();
			$( '#commentform .aac-field-error' ).remove();
			var hasError = false;
			/**
			 * Find all form fields with aria-required=true to test values before submitting.
			 * If error generated, print error.
			 */
			$( '#commentform [aria-required=true]' ).each( function() {
				var id = $(this).attr( 'id' ) + '-error';
				if ( $.trim( $(this).val() ) == '' ) {
					var labelText = $(this).prev('label').html();
					// add aria-describedby with reference ID for error message.
					$(this).attr( 'aria-describedby', id );
					$(this).parent().append( ' <span class="aac-field-error" id="' + id + '">' + labelText + ': ' + aac.required + '</span>' );
					hasError = true;
				}
				if ( $(this).attr( 'name' ) == 'email' && $.trim( $(this).val() ) != '' ) {
					var value = $(this).val();
					var valid = yourthemeValidateEmail( value );
					if ( !valid ) {
						$(this).attr( 'aria-describedby', id );
						$(this).parent().append( ' <span class="aac-field-error"id="' + id + '">' + aac.emailInvalid + '</span>' );
						hasError = true;
					}
				}
			});

			if ( hasError ) {
				statusdiv.html( '<p class="aac-comment-error">' + aac.error + '</p>' );
				return false;
			}

			/**
			 * If no field errors, notify user that message is processing.
			 */
			statusdiv.html( '<p class="aac-comment-processing">' + aac.processing + '</p>' );

			//Extract action URL from commentform
			var formurl = commentForm.attr('action');

			//Post Form with data
			$.ajax({
				type: 'post',
				url: formurl,
				data: formdata,
				dataType: 'json',
				error: function( XMLHttpRequest, textStatus, errorThrown ) { // [localize]
					statusdiv.html( '<p class="aac-comment-error">' + aac.flood + '</p>' );
				},
				success: function( data, textStatus ){
					var success = data.success;
					var message = data.response;
					var status = data.status;
					console.log( data.success );
					if ( success ) {
						statusdiv.html('<p class="aac-comment-success" >'+status+'</p>');
						//alert(data);

						if ( $( '#comments' ).has( "ol.commentlist" ).length > 0 ) {
							if ( list != null ) {
								$( '#' + list ).prepend( message );
							} else {
								$( '.commentlist' ).append( message );
							}
						} else {
							$( '#comments' ).append( '<ol class="commentlist"></ol>' );
							$( 'ol.commentlist' ).html( message );
						}
						commentForm.find('textarea[name=comment]').val('');
					} else {
						statusdiv.html('<p class="aac-comment-error" >'+status+'</p>');
					}
				}
			} );
		return false;
	});

	/**
	 * Function to validate email format.
	 *
	 * @param value Email address to test
	 *
	 * @return boolean
	 */
	function yourthemeValidateEmail( value ) {
		var filter = /^([\w-\+.]+)@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.)|(([\w-]+\.)+))([a-zA-Z]{2,4}|[0-9]{1,3})(\]?)$/;
		if ( filter.test( value ) ) {
			return true;
		} else {
			return false;
		}
	}
});
}(jQuery));
