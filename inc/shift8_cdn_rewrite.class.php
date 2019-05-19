<?php

/**
Shift8 CDN Rewriter
 */
class Shift8_CDN {

	/**
	 * Class constructor.
	 */
	public function __construct() {

		// Allow for ignoring CDN during testing
		if ( defined( 'IGNORE_RYANS_CDN' ) ) {
			return;
		}

		add_action( 'template_redirect', array( $this, 'template_redirect' ) );
		add_filter( 'rewrite_urls',      array( $this, 'filter' ) );

	}

	/*
	 * Filtering URLs.
	 *
	 * @param   string   $content   The content to be filtered
	 * @return  string   $content   The modified content after URL rewriting
	 * 
	 */
	public function filter( $content ) {

		// This is a critical point at which you must add rules for rewriting URL's
		$rewrites = apply_filters( 'shift8_cdn_rewrites', array() );

		// Loop through each rule and process it
		foreach( $rewrites as $origin => $destination ) {
			$content = str_replace( $origin, $destination, $content );
		}

		return $content;
	}

	/*
	 * Starting page buffer.
	 */
	public function template_redirect() {
		ob_start( array( $this, 'ob' ) );
	}

	/*
	 * Rewriting URLs once buffer ends.
	 *
	 * @return  string  The filtered page output including rewritten URLs.
	 */
	public function ob( $contents ) {
		return apply_filters( 'rewrite_urls', $contents, $this );
	}

}
new Shift8_CDN;