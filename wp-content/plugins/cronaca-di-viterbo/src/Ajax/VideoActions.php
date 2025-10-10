<?php
/**
 * AJAX: Video Actions
 *
 * Gestisce like e views per video stories
 *
 * @package CdV
 * @subpackage Ajax
 * @since 2.0.0
 */

namespace CdV\Ajax;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class VideoActions
 */
class VideoActions {
	/**
	 * Initialize
	 */
	public static function init(): void {
		add_action( 'wp_ajax_cdv_like_video', [ self::class, 'like_video' ] );
		add_action( 'wp_ajax_nopriv_cdv_like_video', [ self::class, 'like_video' ] );
		add_action( 'wp_ajax_cdv_track_video_view', [ self::class, 'track_view' ] );
		add_action( 'wp_ajax_nopriv_cdv_track_video_view', [ self::class, 'track_view' ] );
	}

	/**
	 * Handle video like
	 */
	public static function like_video(): void {
		// Verify nonce
		check_ajax_referer( 'cdv_ajax_nonce', 'nonce' );

		// Get video ID
		$video_id = isset( $_POST['video_id'] ) ? absint( $_POST['video_id'] ) : 0;

		if ( ! $video_id ) {
			wp_send_json_error( array(
				'message' => __( 'ID video non valido.', 'cronaca-di-viterbo' ),
			) );
		}

		// Verify post type
		if ( 'cdv_video' !== get_post_type( $video_id ) ) {
			wp_send_json_error( array(
				'message' => __( 'Tipo di post non valido.', 'cronaca-di-viterbo' ),
			) );
		}

		// Check if already liked (by IP or user)
		$user_id = get_current_user_id();
		$ip = self::get_client_ip();
		$like_key = $user_id ? 'user_' . $user_id : 'ip_' . md5( $ip );
		$liked_videos = get_transient( 'cdv_liked_videos_' . $like_key ) ?: array();

		if ( in_array( $video_id, $liked_videos, true ) ) {
			wp_send_json_error( array(
				'message' => __( 'Hai già messo mi piace a questo video.', 'cronaca-di-viterbo' ),
			) );
		}

		// Increment likes
		\CdV\PostTypes\VideoStory::increment_likes( $video_id );

		// Save like to prevent duplicates (24h)
		$liked_videos[] = $video_id;
		set_transient( 'cdv_liked_videos_' . $like_key, $liked_videos, DAY_IN_SECONDS );

		// Award reputation points
		$author_id = get_post_field( 'post_author', $video_id );
		if ( $author_id ) {
			\CdV\Services\Reputazione::add_points( $author_id, 5, 'video_like' );
		}

		// Get new count
		$likes = (int) get_post_meta( $video_id, '_cdv_video_likes', true );

		wp_send_json_success( array(
			'message' => __( 'Mi piace aggiunto!', 'cronaca-di-viterbo' ),
			'likes'   => $likes,
		) );
	}

	/**
	 * Track video view
	 */
	public static function track_view(): void {
		// Verify nonce
		check_ajax_referer( 'cdv_ajax_nonce', 'nonce' );

		// Get video ID
		$video_id = isset( $_POST['video_id'] ) ? absint( $_POST['video_id'] ) : 0;

		if ( ! $video_id ) {
			wp_send_json_error( array(
				'message' => __( 'ID video non valido.', 'cronaca-di-viterbo' ),
			) );
		}

		// Check if already viewed (by IP or user) in last hour
		$user_id = get_current_user_id();
		$ip = self::get_client_ip();
		$view_key = 'cdv_video_view_' . $video_id . '_' . ( $user_id ? 'user_' . $user_id : 'ip_' . md5( $ip ) );

		if ( get_transient( $view_key ) ) {
			// Already viewed in last hour
			wp_send_json_success( array(
				'message' => __( 'Visualizzazione già registrata.', 'cronaca-di-viterbo' ),
			) );
		}

		// Increment views
		\CdV\PostTypes\VideoStory::increment_views( $video_id );

		// Set transient to prevent duplicate counts (1 hour)
		set_transient( $view_key, true, HOUR_IN_SECONDS );

		// Award reputation points to author (first time only, not on repeated views)
		$author_id = get_post_field( 'post_author', $video_id );
		if ( $author_id ) {
			\CdV\Services\Reputazione::add_points( $author_id, 1, 'video_view' );
		}

		wp_send_json_success( array(
			'message' => __( 'Visualizzazione registrata.', 'cronaca-di-viterbo' ),
		) );
	}

	/**
	 * Get client IP (proxy-aware)
	 *
	 * @return string IP address.
	 */
	private static function get_client_ip(): string {
		$ip = '';

		if ( ! empty( $_SERVER['HTTP_CLIENT_IP'] ) ) {
			$ip = sanitize_text_field( wp_unslash( $_SERVER['HTTP_CLIENT_IP'] ) );
		} elseif ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
			$ip = sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_FORWARDED_FOR'] ) );
		} elseif ( ! empty( $_SERVER['REMOTE_ADDR'] ) ) {
			$ip = sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) );
		}

		return $ip;
	}
}
