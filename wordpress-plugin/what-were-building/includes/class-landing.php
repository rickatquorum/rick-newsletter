<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class QNL_Landing {

	const META_LANDING = '_qnl_landing';
	const META_EDITION = '_qnl_edition';
	const META_ISSUE   = '_qnl_issue';
	const META_TITLE   = '_qnl_issue_title';
	const META_SOURCE  = '_qnl_source';

	public static function init() {
		add_action( 'template_redirect', array( __CLASS__, 'serve' ), 0 );
		add_action( 'before_delete_post', array( __CLASS__, 'cleanup' ) );
	}

	public static function is_landing( $post_id ) {
		return (bool) get_post_meta( (int) $post_id, self::META_LANDING, true );
	}

	public static function html_path( $post_id ) {
		$dir = self::upload_dir( $post_id );
		return $dir['path'] . '/index.html';
	}

	public static function upload_dir( $post_id ) {
		$uploads = wp_upload_dir();
		$relative = 'what-were-building/' . (int) $post_id;
		$path = trailingslashit( $uploads['basedir'] ) . $relative;
		$url  = trailingslashit( $uploads['baseurl'] ) . $relative;
		return array(
			'path' => $path,
			'url'  => $url,
		);
	}

	public static function serve() {
		if ( is_admin() || ! is_singular( 'page' ) ) {
			return;
		}

		$post_id = get_queried_object_id();
		if ( ! $post_id || ! self::is_landing( $post_id ) ) {
			return;
		}

		$path = self::html_path( $post_id );
		if ( ! is_readable( $path ) ) {
			return;
		}

		status_header( 200 );
		header( 'Content-Type: text/html; charset=utf-8' );
		nocache_headers();

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_readfile -- serving a stored HTML snapshot.
		readfile( $path );
		exit;
	}

	public static function cleanup( $post_id ) {
		$post_id = (int) $post_id;
		if ( $post_id <= 0 || ! self::is_landing( $post_id ) ) {
			return;
		}

		$dir = self::upload_dir( $post_id );
		self::rrmdir( $dir['path'] );
	}

	public static function rrmdir( $dir ) {
		if ( ! is_dir( $dir ) ) {
			return;
		}

		$items = scandir( $dir );
		if ( ! $items ) {
			return;
		}

		foreach ( $items as $item ) {
			if ( '.' === $item || '..' === $item ) {
				continue;
			}
			$path = $dir . DIRECTORY_SEPARATOR . $item;
			if ( is_dir( $path ) ) {
				self::rrmdir( $path );
			} else {
				wp_delete_file( $path );
			}
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_rmdir
		rmdir( $dir );
	}
}
