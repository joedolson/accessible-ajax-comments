<?php
/*
Plugin Name: Accessible AJAX Comments
Plugin URI: http://www.joedolson.com/accessible-ajax-comments/
Description: Sets up accessible AJAX handling of comment forms.
Author: Joseph C Dolson
Author URI: http://www.joedolson.com
Text Domain: accessible-ajax-comments
Domain Path: lang
Version: 1.1.0
*/
/*  Copyright 2015-2019  Joe Dolson (email : joe@joedolson.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
 
add_action( 'wp_enqueue_scripts','aac_enqueue_scripts' );
/**
 * Enqueue scripts to handle AJAX comments. Localize script to pass translated responses.
 * Enqueue comment-reply scripts.
 */
function aac_enqueue_scripts() {
	if ( is_singular() && comments_open() ) {
		wp_enqueue_style( 'aac.style', plugins_url( "/css/aac.css", __FILE__ ) );
		wp_enqueue_script( 'aac.comments', plugins_url( "/js/comments.js", __FILE__ ), array('jquery'), '1.1.0', true );
		$comment_i18n = array( 
			'processing' => __( 'Processing...', 'accessible-ajax-comments' ),
			'flood' => sprintf( __( 'Your comment was either a duplicate or you are posting too rapidly. <a href="%s">Edit your comment</a>', 'accessible-ajax-comments' ), '#comment' ),
			'error' => __( 'There were errors in submitting your comment; complete the missing fields and try again!', 'accessible-ajax-comments' ),
			'emailInvalid' => __( 'That email appears to be invalid.', 'accessible-ajax-comments' ),
			'required' => __( 'This is a required field.', 'accessible-ajax-comments' )
		);
		wp_localize_script( 'aac.comments', 'aac', $comment_i18n );
	}
}

add_action( 'comment_post', 'aac_ajax_comments', 20, 2 );
/**
 * Provide responses to comments.js based on detecting an XMLHttpRequest parameter.
 *
 * @param $comment_ID     ID of new comment.
 * @param $comment_status Status of new comment. 
 *
 * @return echo JSON encoded responses with HTML structured comment, success, and status notice.
 */
function aac_ajax_comments( $comment_ID, $comment_status ) {
	if ( !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower( $_SERVER['HTTP_X_REQUESTED_WITH'] ) == 'xmlhttprequest' ) {
		// This is an AJAX request. Handle response data. 
		switch ( $comment_status ) {
			case '0':
				// Comment needs moderation; notify comment moderator.
				wp_notify_moderator( $comment_ID );
				$return = array( 
					'response' => '', 
					'success'  => 1, 
					'status'   => __( 'Your comment has been sent for moderation. It should be approved soon!', 'accessible-ajax-comments' ) 
				);
				wp_send_json( $return );
				break;
			case '1':
				// Approved comment; generate comment output and notify post author.
				$comment            = get_comment( $comment_ID );
				$comment_class      = comment_class( 'accessible-ajax-comment', $comment_ID, $comment->comment_post_ID, false );
				
				$comment_output     = '
						<li id="comment-' . $comment->comment_ID . '"' . $comment_class . ' tabindex="-1">
							<article id="div-comment-' . $comment->comment_ID . '" class="comment-body">
								<footer class="comment-meta">
								<div class="comment-author vcard">'.
									get_avatar( $comment->comment_author_email )
									.'<b class="fn">' . __( 'You said:', 'accessible-ajax-comments' ) . '</b> </div>

								<div class="comment-meta commentmetadata"><a href="#comment-'. $comment->comment_ID .'">' . 
									get_comment_date( 'F j, Y \a\t g:i a', $comment->comment_ID ) .'</a>
								</div>
								</footer>
								
								<div class="comment-content">' . $comment->comment_content . '</div>
							</article>
						</li>';
				
				if ( $comment->comment_parent == 0 ) {
					$output = $comment_output;
				} else {
					$output = "<ul class='children'>$comment_output</ul>";
				}

				wp_notify_postauthor( $comment_ID );
				$return = array( 
					'response'=>$output, 
					'success' => 1, 
					'status'=> sprintf( __( 'Thanks for commenting! Your comment has been approved. <a href="%s">Read your comment</a>', 'accessible-ajax-comments' ), "#comment-$comment_ID" ) 
				);
				wp_send_json( $return );
				break;
			default:
				// The comment status was not a valid value. Only 0 or 1 should be returned by the comment_post action.
				$return = array( 
					'response' => '', 
					'success'  => 0, 
					'status'   => __( 'There was an error posting your comment. Try again later!', 'accessible-ajax-comments' ) 
				);
				wp_send_json( $return );
		}
	}
}