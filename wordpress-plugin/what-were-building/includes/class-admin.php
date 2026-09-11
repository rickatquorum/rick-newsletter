<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class QNL_Admin {

	const MENU_SLUG   = 'what-were-building';
	const OPTION_URL  = 'qnl_source_url';
	const NONCE_FETCH = 'qnl_fetch_issues';
	const NONCE_IMPORT = 'qnl_import';

	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'assets' ) );
		add_action( 'wp_ajax_qnl_fetch_issues', array( __CLASS__, 'ajax_fetch_issues' ) );
		add_action( 'wp_ajax_qnl_import', array( __CLASS__, 'ajax_import' ) );
		add_action( 'add_meta_boxes', array( __CLASS__, 'metabox' ) );
	}

	public static function menu() {
		add_menu_page(
			__( "What We're Building", 'what-were-building' ),
			__( "What We're Building", 'what-were-building' ),
			'publish_pages',
			self::MENU_SLUG,
			array( __CLASS__, 'render' ),
			'dashicons-email-alt',
			58
		);
	}

	public static function assets( $hook ) {
		if ( 'toplevel_page_' . self::MENU_SLUG !== $hook ) {
			return;
		}

		wp_enqueue_style(
			'qnl-admin',
			QNL_URL . 'assets/admin.css',
			array(),
			QNL_VERSION
		);

		wp_enqueue_script(
			'qnl-admin',
			QNL_URL . 'assets/admin.js',
			array(),
			QNL_VERSION,
			true
		);

		wp_localize_script(
			'qnl-admin',
			'QNLAdmin',
			array(
				'ajaxUrl'  => admin_url( 'admin-ajax.php' ),
				'fetchNonce' => wp_create_nonce( self::NONCE_FETCH ),
				'importNonce' => wp_create_nonce( self::NONCE_IMPORT ),
				'editions' => QNL_Source::EDITIONS,
				'i18n'     => array(
					'loading'   => __( 'Loading issues…', 'what-were-building' ),
					'importing' => __( 'Downloading the page and assets. This can take a minute…', 'what-were-building' ),
					'choose'    => __( 'Choose an issue', 'what-were-building' ),
					'error'     => __( 'Something went wrong. Please try again.', 'what-were-building' ),
				),
			)
		);
	}

	public static function render() {
		if ( ! current_user_can( 'publish_pages' ) ) {
			wp_die( esc_html__( 'You do not have permission to create pages.', 'what-were-building' ) );
		}

		$source = get_option( self::OPTION_URL, QNL_Source::DEFAULT_URL );
		$pages  = self::imported_pages();
		include QNL_DIR . 'views/admin.php';
	}

	public static function ajax_fetch_issues() {
		if ( ! current_user_can( 'publish_pages' ) ) {
			wp_send_json_error( array( 'message' => __( 'You do not have permission to do that.', 'what-were-building' ) ), 403 );
		}

		check_ajax_referer( self::NONCE_FETCH, 'nonce' );

		$source = QNL_Source::normalize_base_url( isset( $_POST['source_url'] ) ? wp_unslash( $_POST['source_url'] ) : '' );
		if ( is_wp_error( $source ) ) {
			wp_send_json_error( array( 'message' => $source->get_error_message() ) );
		}

		update_option( self::OPTION_URL, $source, false );

		$issues = QNL_Source::fetch_issues( $source );
		if ( is_wp_error( $issues ) ) {
			wp_send_json_error( array( 'message' => $issues->get_error_message() ) );
		}

		wp_send_json_success(
			array(
				'source'   => $source,
				'issues'   => $issues,
				'editions' => QNL_Source::EDITIONS,
			)
		);
	}

	public static function ajax_import() {
		if ( ! current_user_can( 'publish_pages' ) ) {
			wp_send_json_error( array( 'message' => __( 'You do not have permission to create pages.', 'what-were-building' ) ), 403 );
		}

		check_ajax_referer( self::NONCE_IMPORT, 'nonce' );

		$result = QNL_Importer::import(
			array(
				'source_url' => isset( $_POST['source_url'] ) ? wp_unslash( $_POST['source_url'] ) : '',
				'issue_file' => isset( $_POST['issue_file'] ) ? wp_unslash( $_POST['issue_file'] ) : '',
				'edition'    => isset( $_POST['edition'] ) ? wp_unslash( $_POST['edition'] ) : '',
				'page_title' => isset( $_POST['page_title'] ) ? wp_unslash( $_POST['page_title'] ) : '',
			)
		);

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		wp_send_json_success( $result );
	}

	public static function metabox() {
		add_meta_box(
			'qnl-landing',
			__( "What We're Building", 'what-were-building' ),
			array( __CLASS__, 'render_metabox' ),
			'page',
			'side',
			'high'
		);
	}

	public static function render_metabox( $post ) {
		if ( ! QNL_Landing::is_landing( $post->ID ) ) {
			echo '<p>' . esc_html__( 'This is a normal WordPress page. Import a newsletter from What We\'re Building in the admin menu to create a standalone landing page.', 'what-were-building' ) . '</p>';
			return;
		}

		$edition = get_post_meta( $post->ID, QNL_Landing::META_EDITION, true );
		$issue   = get_post_meta( $post->ID, QNL_Landing::META_TITLE, true );
		$file    = get_post_meta( $post->ID, QNL_Landing::META_ISSUE, true );
		$label   = isset( QNL_Source::EDITIONS[ $edition ] ) ? QNL_Source::EDITIONS[ $edition ] : $edition;

		echo '<p><strong>' . esc_html__( 'Standalone landing page', 'what-were-building' ) . '</strong></p>';
		echo '<p>' . esc_html__( 'This page is served as an exact copy of the GitHub Pages issue. Your theme header, footer, and navigation are not used.', 'what-were-building' ) . '</p>';
		echo '<p>' . esc_html__( 'Edition:', 'what-were-building' ) . ' ' . esc_html( $label ) . '<br>';
		echo esc_html__( 'Issue:', 'what-were-building' ) . ' ' . esc_html( $issue ? $issue : $file ) . '</p>';
		echo '<p><a href="' . esc_url( get_permalink( $post->ID ) ) . '" target="_blank" rel="noopener">' . esc_html__( 'View landing page', 'what-were-building' ) . '</a></p>';
	}

	/**
	 * @return WP_Post[]
	 */
	public static function imported_pages() {
		$query = new WP_Query(
			array(
				'post_type'      => 'page',
				'post_status'    => array( 'publish', 'draft', 'pending', 'private' ),
				'posts_per_page' => 50,
				'meta_key'       => QNL_Landing::META_LANDING,
				'meta_value'     => '1',
				'orderby'        => 'date',
				'order'          => 'DESC',
				'no_found_rows'  => true,
			)
		);

		return $query->posts;
	}
}
