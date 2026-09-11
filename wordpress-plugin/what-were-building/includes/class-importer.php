<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class QNL_Importer {

	/**
	 * Import a GitHub Pages issue as a blank WordPress landing page.
	 *
	 * @param array $args {
	 *     @type string $source_url
	 *     @type string $issue_file
	 *     @type string $edition
	 *     @type string $page_title
	 * }
	 * @return array|WP_Error
	 */
	public static function import( array $args ) {
		if ( function_exists( 'set_time_limit' ) ) {
			set_time_limit( 120 );
		}

		$source = QNL_Source::normalize_base_url( isset( $args['source_url'] ) ? $args['source_url'] : '' );
		if ( is_wp_error( $source ) ) {
			return $source;
		}

		$issue_file = QNL_Source::issue_filename( isset( $args['issue_file'] ) ? $args['issue_file'] : '' );
		if ( '' === $issue_file ) {
			return new WP_Error( 'qnl_issue', __( 'Please choose a newsletter issue.', 'what-were-building' ) );
		}

		$edition = strtoupper( sanitize_key( isset( $args['edition'] ) ? $args['edition'] : '' ) );
		if ( 'ACC' === $edition ) {
			$edition = 'ACA';
		}
		if ( ! isset( QNL_Source::EDITIONS[ $edition ] ) ) {
			return new WP_Error( 'qnl_edition', __( 'Please choose a valid edition.', 'what-were-building' ) );
		}

		$page_title = sanitize_text_field( isset( $args['page_title'] ) ? $args['page_title'] : '' );
		if ( '' === $page_title ) {
			return new WP_Error( 'qnl_title', __( 'Please enter a name for the WordPress page.', 'what-were-building' ) );
		}

		$issue_url = $source . $issue_file;
		$html      = QNL_Source::request( $issue_url );
		if ( is_wp_error( $html ) ) {
			return $html;
		}

		$theme_js = QNL_Source::request( $source . 'theme.js' );
		if ( is_wp_error( $theme_js ) ) {
			return $theme_js;
		}

		$issue_title = self::document_title( $html );
		$relative    = self::collect_relative_assets( $html, $theme_js );

		$post_id = wp_insert_post(
			array(
				'post_type'    => 'page',
				'post_status'  => 'publish',
				'post_title'   => $page_title,
				'post_content' => self::editor_placeholder(),
				'post_name'    => sanitize_title( $page_title ),
			),
			true
		);

		if ( is_wp_error( $post_id ) ) {
			return $post_id;
		}

		$post_id = (int) $post_id;
		update_post_meta( $post_id, QNL_Landing::META_LANDING, '1' );

		$dir = QNL_Landing::upload_dir( $post_id );

		if ( ! wp_mkdir_p( $dir['path'] ) ) {
			wp_delete_post( $post_id, true );
			return new WP_Error( 'qnl_mkdir', __( 'Could not create an uploads folder for this landing page.', 'what-were-building' ) );
		}

		$map   = array();
		$saved = array();
		$failed = array();

		foreach ( $relative as $rel ) {
			$safe = self::safe_relative_path( $rel );
			if ( ! $safe ) {
				continue;
			}

			if ( 'theme.js' === $safe ) {
				continue;
			}

			$body = QNL_Source::request( $source . $safe, 60 );
			if ( is_wp_error( $body ) ) {
				$failed[] = $safe;
				continue;
			}

			$dest     = $dir['path'] . '/' . $safe;
			$dest_dir = dirname( $dest );
			if ( ! wp_mkdir_p( $dest_dir ) ) {
				$failed[] = $safe;
				continue;
			}

			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
			if ( false === file_put_contents( $dest, $body ) ) {
				$failed[] = $safe;
				continue;
			}

			$map[ $safe ] = esc_url_raw( $dir['url'] . '/' . $safe );
			$saved[]      = $safe;
		}

		if ( is_string( $theme_js ) && '' !== $theme_js ) {
			$theme_js = self::rewrite_paths( $theme_js, $map );
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
			file_put_contents( $dir['path'] . '/theme.js', $theme_js );
			$map['theme.js'] = esc_url_raw( $dir['url'] . '/theme.js' );
			$saved[]         = 'theme.js';
		}

		$html = self::rewrite_paths( $html, $map );
		$html = self::inject_edition_lock( $html, $edition );

		$html_path = QNL_Landing::html_path( $post_id );
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
		if ( false === file_put_contents( $html_path, $html ) ) {
			wp_delete_post( $post_id, true );
			return new WP_Error( 'qnl_write', __( 'Could not save the landing page HTML.', 'what-were-building' ) );
		}

		update_post_meta( $post_id, QNL_Landing::META_LANDING, '1' );
		update_post_meta( $post_id, QNL_Landing::META_EDITION, $edition );
		update_post_meta( $post_id, QNL_Landing::META_ISSUE, $issue_file );
		update_post_meta( $post_id, QNL_Landing::META_TITLE, $issue_title );
		update_post_meta( $post_id, QNL_Landing::META_SOURCE, $source );

		return array(
			'post_id'     => $post_id,
			'title'       => get_the_title( $post_id ),
			'permalink'   => get_permalink( $post_id ),
			'edit_link'   => get_edit_post_link( $post_id, 'raw' ),
			'assets'      => count( $saved ),
			'failed'      => $failed,
			'edition'     => $edition,
			'issue_file'  => $issue_file,
			'issue_title' => $issue_title,
		);
	}

	/**
	 * Collect relative asset paths from the issue HTML and theme.js.
	 *
	 * @param string $html
	 * @param string $theme_js
	 * @return string[]
	 */
	public static function collect_relative_assets( $html, $theme_js = '' ) {
		$found = array();
		$blob  = $html . "\n" . $theme_js;

		if ( preg_match_all( '#(?:src|href)=["\']([^"\']+)["\']#i', $blob, $matches ) ) {
			foreach ( $matches[1] as $url ) {
				$rel = self::relative_asset( $url );
				if ( $rel ) {
					$found[ $rel ] = true;
				}
			}
		}

		if ( preg_match_all( '#url\(\s*[\'"]?([^\'")]+)[\'"]?\s*\)#i', $blob, $matches ) ) {
			foreach ( $matches[1] as $url ) {
				$rel = self::relative_asset( $url );
				if ( $rel ) {
					$found[ $rel ] = true;
				}
			}
		}

		if ( preg_match_all( '#logo:\s*[\'"]([^\'"]+)[\'"]#', $theme_js, $matches ) ) {
			foreach ( $matches[1] as $url ) {
				$rel = self::relative_asset( $url );
				if ( $rel ) {
					$found[ $rel ] = true;
				}
			}
		}

		$paths = array_keys( $found );
		usort(
			$paths,
			static function ( $a, $b ) {
				return strlen( $b ) - strlen( $a );
			}
		);

		return $paths;
	}

	public static function relative_asset( $url ) {
		$url = trim( html_entity_decode( (string) $url, ENT_QUOTES | ENT_HTML5, 'UTF-8' ) );
		if ( '' === $url ) {
			return '';
		}

		$lower = strtolower( $url );
		if (
			'#' === $url[0]
			|| 0 === strpos( $lower, 'mailto:' )
			|| 0 === strpos( $lower, 'tel:' )
			|| 0 === strpos( $lower, 'data:' )
			|| 0 === strpos( $lower, 'javascript:' )
			|| 0 === strpos( $lower, 'https://fonts.googleapis.com' )
			|| 0 === strpos( $lower, 'https://fonts.gstatic.com' )
		) {
			return '';
		}

		if ( preg_match( '#^https?://#i', $url ) || 0 === strpos( $url, '//' ) ) {
			return '';
		}

		$path = strtok( $url, '?#' );
		$path = preg_replace( '#^\./#', '', $path );
		$path = ltrim( $path, '/' );

		return self::safe_relative_path( $path );
	}

	public static function safe_relative_path( $path ) {
		$path = str_replace( '\\', '/', (string) $path );
		$path = ltrim( $path, '/' );
		if ( '' === $path || false !== strpos( $path, '..' ) ) {
			return '';
		}
		if ( ! preg_match( '/^[A-Za-z0-9._\/-]+$/', $path ) ) {
			return '';
		}
		return $path;
	}

	/**
	 * Rewrite relative asset paths to local WordPress URLs.
	 *
	 * @param string               $content
	 * @param array<string,string> $map
	 * @return string
	 */
	public static function rewrite_paths( $content, array $map ) {
		foreach ( $map as $from => $to ) {
			$content = str_replace(
				array(
					'src="' . $from . '"',
					"src='" . $from . "'",
					'href="' . $from . '"',
					"href='" . $from . "'",
					"logo: '" . $from . "'",
					'logo: "' . $from . '"',
				),
				array(
					'src="' . $to . '"',
					"src='" . $to . "'",
					'href="' . $to . '"',
					"href='" . $to . "'",
					"logo: '" . $to . "'",
					'logo: "' . $to . '"',
				),
				$content
			);
		}

		return $content;
	}

	public static function inject_edition_lock( $html, $edition ) {
		$edition = strtoupper( $edition );
		$script  = "<script>\n(function(){try{var e=" . wp_json_encode( $edition ) . ";var u=new URL(window.location.href);if(u.searchParams.get('edition')!==e){u.searchParams.set('edition',e);window.history.replaceState(null,'',u);}}catch(err){}})();\n</script>\n";

		if ( preg_match( '/<script\b/i', $html, $m, PREG_OFFSET_CAPTURE ) ) {
			$pos = $m[0][1];
			return substr( $html, 0, $pos ) . $script . substr( $html, $pos );
		}

		if ( false !== stripos( $html, '</body>' ) ) {
			return str_ireplace( '</body>', $script . '</body>', $html );
		}

		return $html . $script;
	}

	public static function document_title( $html ) {
		if ( preg_match( '#<title[^>]*>(.*?)</title>#is', $html, $m ) ) {
			return QNL_Source::plain_text( $m[1] );
		}
		return '';
	}

	public static function editor_placeholder() {
		return "<!-- wp:paragraph -->\n<p>This page is a standalone newsletter landing page. WordPress will not wrap it in your theme header, footer, or navigation. Change the title or slug here if you want; to replace the content, delete the page and import it again from <strong>What We're Building</strong>.</p>\n<!-- /wp:paragraph -->";
	}
}
