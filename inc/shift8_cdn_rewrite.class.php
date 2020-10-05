<?php
/**
 * Shift8 CDN Rewrite Class
 *
 * Class used to rewrite assets to go through CDN urls
 *
 */

if ( !defined( 'ABSPATH' ) ) {
    die();
}


class Shift8_CDN {

	/**
	 * Class constructor.
	 */
	public function __construct() {

		add_action( 'template_redirect', array( $this, 'template_redirect' ) );
		add_filter( 'rewrite_urls',      array( $this, 'filter' ) );
		add_filter( 'wp_calculate_image_srcset', array( $this, 'rewrite_srcset'), PHP_INT_MAX );

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
		$shift8_options = shift8_cdn_check_options();

		$extensions = array();
		$extension_re = null;

		// Build regex extensions
		if($shift8_options['static_css'] === 'on') $extensions[] = 'css';
		if($shift8_options['static_js'] === 'on') $extensions[] = 'js';
		if($shift8_options['static_media'] === 'on') $extensions[] = 'jpg|jpeg|png|gif|bmp|pdf|mp3|m4a|ogg|wav|mp4|m4v|mov|wmv|avi|mpg|ogv|3gp|3g2|webp|svg';


		// Only apply the regex if at least one static file type option is selected
		if (!empty($extensions)) {
			foreach ($extensions as $extension) {
				if ($extension === end($extensions)) {
					$extension_re .= $extension;
				} else {
					$extension_re .= $extension . '|';
				}
			}
			// Loop through each rule and process it using regex to pattern match static files
			foreach( $rewrites as $origin => $destination ) {
				$uri = parse_url($origin);
				$origin_host = $uri['scheme'] . '://' . $uri['host'];
				$re = '/' . preg_quote( $origin_host, '/' ) . '(' . preg_quote($uri['path'], '/') . '\/)(.*?\.)(' . $extension_re . ')/i';

				// Determine which CDN suffix to use
				if (get_transient(S8CDN_PAID_CHECK) && get_transient(S8CDN_PAID_CHECK) === S8CDN_SUFFIX_PAID) {
					$subst = 'https://' . $shift8_options['cdn_prefix'] . S8CDN_SUFFIX_PAID . '\1\2\3';
				} else {
					$subst = 'https://' . $shift8_options['cdn_prefix'] . S8CDN_SUFFIX . '\1\2\3';
				}
				$content = preg_replace( $re, $subst, $content);
			}

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

	/**
	 * Rewrites URLs in srcset attributes to the CDN URLs 
	 * @param string $html HTML content.
	 * @return string
	 */
    function rewrite_srcset( $sources ) {
        if ( (bool) $sources ) {
            $uri = parse_url((empty(esc_attr(get_option('shift8_cdn_url'))) ? get_site_url() : esc_attr(get_option('shift8_cdn_url'))));
            $origin_host = $uri['scheme'] . '://' . $uri['host'];
            $shift8_options = shift8_cdn_check_options();
            if (get_transient(S8CDN_PAID_CHECK) && get_transient(S8CDN_PAID_CHECK) === S8CDN_SUFFIX_PAID) {
                $subst = 'https://' . $shift8_options['cdn_prefix'] . S8CDN_SUFFIX_PAID;
            } else {
                $subst = 'https://' . $shift8_options['cdn_prefix'] . S8CDN_SUFFIX;
            }
            foreach ( $sources as $i => $source ) {
                $sources[ $i ]['url'] = str_replace( [ $origin_host ], $subst, $source['url'] );
            }
        }
        return $sources;
    }
}

// Dont do anything unless we're enabled
if (shift8_cdn_check_enabled()) {
	new Shift8_CDN;
}