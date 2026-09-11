<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class QNL_Source {

	const DEFAULT_URL = 'https://rickatquorum.github.io/rick-newsletter/';

	const EDITIONS = array(
		'QDMS' => 'Quorum DMS',
		'DM'   => 'DealerMine',
		'AV'   => 'Autovance',
		'ACA'  => 'Accessible Accessories',
	);

	/**
	 * Normalize a GitHub Pages base URL.
	 *
	 * @param string $url Raw URL.
	 * @return string|WP_Error
	 */
	public static function normalize_base_url( $url ) {
		$url = trim( (string) $url );
		if ( '' === $url ) {
			$url = self::DEFAULT_URL;
		}

		if ( ! preg_match( '#^https?://#i', $url ) ) {
			$url = 'https://' . $url;
		}

		$parts = wp_parse_url( $url );
		if ( ! $parts || empty( $parts['host'] ) ) {
			return new WP_Error( 'qnl_bad_url', __( 'That GitHub Pages URL is not valid.', 'what-were-building' ) );
		}

		$scheme = isset( $parts['scheme'] ) ? $parts['scheme'] : 'https';
		$host   = $parts['host'];
		$path   = isset( $parts['path'] ) ? $parts['path'] : '/';
		$path   = preg_replace( '#/index\.html?$#i', '/', $path );
		$path   = trailingslashit( $path );

		return $scheme . '://' . $host . $path;
	}

	/**
	 * Fetch and parse the publication index for newsletter issues.
	 *
	 * @param string $base_url Normalized Pages URL.
	 * @return array|WP_Error
	 */
	public static function fetch_issues( $base_url ) {
		$html = self::request( $base_url );
		if ( is_wp_error( $html ) ) {
			return $html;
		}

		$issues = self::parse_issues( $html );
		if ( empty( $issues ) ) {
			return new WP_Error(
				'qnl_no_issues',
				__( 'Connected, but no newsletter issues were found on that page.', 'what-were-building' )
			);
		}

		return $issues;
	}

	/**
	 * @param string $html Index HTML.
	 * @return array<int, array{file:string,title:string,description:string}>
	 */
	public static function parse_issues( $html ) {
		$issues = array();
		$seen   = array();

		if ( preg_match_all(
			'#<a[^>]*class="[^"]*issue-card[^"]*"[^>]*href="([^"]+)"[^>]*>(.*?)</a>#is',
			$html,
			$cards,
			PREG_SET_ORDER
		) ) {
			foreach ( $cards as $card ) {
				$file = self::issue_filename( $card[1] );
				if ( ! $file || isset( $seen[ $file ] ) ) {
					continue;
				}
				$body = $card[2];
				$title = '';
				$desc  = '';
				if ( preg_match( '#class="issue-title"[^>]*>(.*?)</div>#is', $body, $m ) ) {
					$title = self::plain_text( $m[1] );
				}
				if ( preg_match( '#class="issue-desc"[^>]*>(.*?)</div>#is', $body, $m ) ) {
					$desc = self::plain_text( $m[1] );
				}
				if ( '' === $title ) {
					$title = self::title_from_filename( $file );
				}
				$seen[ $file ] = true;
				$issues[]      = array(
					'file'        => $file,
					'title'       => $title,
					'description' => $desc,
				);
			}
		}

		if ( preg_match_all( '#href=["\'](newsletter-[^"\'?#]+\.html)#i', $html, $links ) ) {
			foreach ( $links[1] as $file ) {
				$file = self::issue_filename( $file );
				if ( ! $file || isset( $seen[ $file ] ) ) {
					continue;
				}
				$seen[ $file ] = true;
				$issues[]      = array(
					'file'        => $file,
					'title'       => self::title_from_filename( $file ),
					'description' => '',
				);
			}
		}

		return $issues;
	}

	/**
	 * Fetch a URL and return the body.
	 *
	 * @param string $url Absolute URL.
	 * @return string|WP_Error
	 */
	public static function request( $url, $timeout = 30 ) {
		$response = wp_remote_get(
			$url,
			array(
				'timeout'     => (int) $timeout,
				'redirection' => 5,
				'sslverify'   => true,
				'user-agent'  => 'WhatWereBuilding-WordPress-Plugin/' . QNL_VERSION,
			)
		);

		if ( is_wp_error( $response ) ) {
			return new WP_Error(
				'qnl_http',
				sprintf(
					/* translators: 1: URL, 2: error message */
					__( 'Could not reach %1$s: %2$s', 'what-were-building' ),
					$url,
					$response->get_error_message()
				)
			);
		}

		$code = (int) wp_remote_retrieve_response_code( $response );
		if ( 200 !== $code ) {
			return new WP_Error(
				'qnl_http_code',
				sprintf(
					/* translators: 1: HTTP status, 2: URL */
					__( 'GitHub Pages returned HTTP %1$d for %2$s', 'what-were-building' ),
					$code,
					$url
				)
			);
		}

		$body = wp_remote_retrieve_body( $response );
		if ( '' === $body ) {
			return new WP_Error( 'qnl_empty', __( 'The remote file was empty.', 'what-were-building' ) );
		}

		return $body;
	}

	public static function issue_filename( $href ) {
		$path = wp_parse_url( $href, PHP_URL_PATH );
		if ( ! is_string( $path ) || '' === $path ) {
			$path = $href;
		}
		$file = basename( $path );
		if ( ! preg_match( '/^newsletter-[a-z0-9.-]+\.html$/i', $file ) ) {
			return '';
		}
		return $file;
	}

	public static function title_from_filename( $file ) {
		$name = preg_replace( '/^newsletter-|\.html$/i', '', $file );
		$name = str_replace( array( '-', '_' ), ' ', $name );
		return ucwords( $name );
	}

	public static function plain_text( $html ) {
		$text = wp_strip_all_tags( html_entity_decode( $html, ENT_QUOTES | ENT_HTML5, 'UTF-8' ) );
		$text = preg_replace( '/\s+/u', ' ', $text );
		return trim( $text );
	}
}
