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
		add_filter( 'script_loader_src', array( $this, 'cdn_script_loader_src' ), 10, 2 );

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
				if (shift8_cdn_check_paid_transient() === S8CDN_SUFFIX_PAID) {
					$subst = 'https://' . $shift8_options['cdn_prefix'] . S8CDN_SUFFIX_PAID . '\1\2\3';
				} else if (shift8_cdn_check_paid_transient() === S8CDN_SUFFIX){
					$subst = 'https://' . $shift8_options['cdn_prefix'] . S8CDN_SUFFIX . '\1\2\3';
				} else {
					$subst = 'https://' . $shift8_options['cdn_prefix'] . S8CDN_SUFFIX_SECOND . '\1\2\3';
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
            if (shift8_cdn_check_paid_transient() === S8CDN_SUFFIX_PAID) {
                $subst = 'https://' . $shift8_options['cdn_prefix'] . S8CDN_SUFFIX_PAID;
            } else if (shift8_cdn_check_paid_transient() === S8CDN_SUFFIX) {
                $subst = 'https://' . $shift8_options['cdn_prefix'] . S8CDN_SUFFIX;
            } else {
                $subst = 'https://' . $shift8_options['cdn_prefix'] . S8CDN_SUFFIX_SECOND;
            }
            foreach ( $sources as $i => $source ) {
                $sources[ $i ]['url'] = str_replace( [ $origin_host ], $subst, $source['url'] );
            }
        }
        return $sources;
    }

    /**
     * Rewrites the emoji js file to go through the CDN
     * @param string 
     * @return string
     */
	public function cdn_script_loader_src( $source, $scriptname ) {		
		if ($scriptname == 'concatemoji') {
			$uri = parse_url((empty(esc_attr(get_option('shift8_cdn_url'))) ? get_site_url() : esc_attr(get_option('shift8_cdn_url'))));
	        $origin_host = $uri['scheme'] . '://' . $uri['host'];
	        $shift8_options = shift8_cdn_check_options();
	        if (shift8_cdn_check_paid_transient() === S8CDN_SUFFIX_PAID) {
	            $subst = 'https://' . $shift8_options['cdn_prefix'] . S8CDN_SUFFIX_PAID;
	        } else if (shift8_cdn_check_paid_transient() === S8CDN_SUFFIX) {
	            $subst = 'https://' . $shift8_options['cdn_prefix'] . S8CDN_SUFFIX;
	        } else {
	            $subst = 'https://' . $shift8_options['cdn_prefix'] . S8CDN_SUFFIX_SECOND;
	        }
			$source = str_replace($origin_host, $subst, $source);
		}
		return $source;
	}

	/**
	 * Checks if the provided URL should be allowed to be rewritten with the CDN URL
	 * @param string $url URL to check.
	 * @return boolean
	 */
	public function is_excluded( $url ) {
		$path = wp_parse_url( $url, PHP_URL_PATH );

		$excluded_extensions = [
			'php',
			'html',
			'htm',
		];

		if ( in_array( pathinfo( $path, PATHINFO_EXTENSION ), $excluded_extensions, true ) ) {
			return true;
		}

		if ( ! $path ) {
			return true;
		}

		if ( '/' === $path ) {
			return true;
		}

		if ( preg_match( '#^(' . $this->get_excluded_files( '#' ) . ')$#', $path ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Get all files we dont want to be passed through the CDN
	 * @param string $delimiter RegEx delimiter.
	 * @return string A pipe-separated list of excluded files.
	 */
	private function get_excluded_files( $delimiter ) {
		$files = $this->options->get( 'cdn_reject_files', [] );

		$files = (array) apply_filters( 'shift8_cdn_reject_files', $files );
		$files = array_filter( $files );

		if ( ! $files ) {
			return '';
		}

		$files = array_flip( array_flip( $files ) );
		$files = array_map(
			function ( $file ) use ( $delimiter ) {
				return str_replace( $delimiter, '\\' . $delimiter, $file );
			},
			$files
		);

		return implode( '|', $files );
	}

}

// Dont do anything unless we're enabled
if (shift8_cdn_check_enabled()) {
	new Shift8_CDN;
}