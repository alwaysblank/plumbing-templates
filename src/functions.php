<?php

namespace Livy\Plumbing\Templates;

use Symfony\Component\Finder\Finder;

/**
 * Register a single path for a post type.
 *
 * This is a simplistic implementation and allows little customization.
 * It is recommended to used setup_template_location() instead.
 *
 * @param array|string $path
 * @param string $post_type
 */
function register_template_directory( $path, string $post_type = 'page' ) : void {
	$paths = is_string( $path ) ? [ $path ] : $path;

	$args = [
		'paths' => $paths,
		'post_type' => $post_type,
		'template_root' => get_stylesheet_directory(),
	];

	add_filter( "theme_{$post_type}_templates", function ( $templates ) use ( $args ) {
		return array_merge( $templates, compile_template_list( $args ) );
	} );
}

/**
 * Set up a template location based on an argument array.
 *
 * The array *must* contain at least the keys `post_type` and `paths`.
 *
 * @param array $args
 */
function setup_template_location( array $args ) : void {
	if ( ! isset( $args['post_type'] ) || ! isset( $args['paths'] ) ) {
		// Can't do anything without these.
		return;
	}

	add_filter( "theme_{$args['post_type']}_templates", function ( $templates ) use ( $args ) {
		return array_merge( $templates, compile_template_list( $args ) );
	} );
}

/**
 * Generate a list of possible templates.
 *
 * @param array $args
 *
 * @return array
 */
function compile_template_list( array $args ) : array {
	$defaults = apply_filters( 'template-dir/compile-arguments', get_default_args(), $args );

	$settings = resolve_arguments( $args, $defaults );

	$settings['template_root'] = trailingslashit( $settings['template_root'] );

	// We want to make sure we won't have double slashes when we concatenate with template_root.
	$settings['paths'] = array_map( function ( $path ) {
		return trailingslashit( rtrim( $path, DIRECTORY_SEPARATOR ) );
	}, $settings['paths'] );

	// Only allow paths that exist.
	$settings['paths'] = array_filter( $settings['paths'], function ( $path ) use ( $settings ) {
		return is_dir( $settings['template_root'] . $path );
	} );

	// Without valid paths, we can't do anything.
	if ( count( $settings['paths'] ) === 0 ) {
		return [];
	}

	// Add post type to post type regex.
	$settings['post_type_regex'] = str_replace(
		'%post_type%',
		$settings['post_type'],
		$settings['post_type_regex']
	);

	$settings = apply_filters( 'template-dir/resolved-compile-arguments', $settings, $args );

	$templates = [];
	foreach ( get_finder( $settings ) as $file ) {
		$name_matches = get_match_in_file( $file->getRealPath() ?: '', $settings['name_regex'] );
		if ( isset( $name_matches[1] ) ) {
			$templates[ rebase_path( $file->getRealPath(), $settings['template_root'] ) ] = $name_matches[1];
		}
	}

	return apply_filters( 'template-dir/collected-templates', $templates, $settings, $args );
}

/**
 * Get an instance of Finder that has found relevant templates.
 *
 * @param array $args
 *
 * @return Finder
 */
function get_finder( array $args ) : Finder {
	$finder = new Finder();
	$finder->ignoreUnreadableDirs();
	foreach ( $args['paths'] as $path ) {
		$finder->in( $args['template_root'] . $path );
	}
	$finder->files()
	       ->name( $args['filename'] )
	       ->contains( $args['contains'] )
	       ->sortByName();

	if ( version_compare( $GLOBALS['wp_version'], '4.7', '>=' ) && $args['post_type'] !== 'page' ) {
		$finder->contains( $args['post_type_regex'] );
	}

	return $finder;
}

/**
 * Make sure $segment does *not* start with a directory separator.
 * If $trailing_slash is true, make sure $segment ends with a directory separator.
 *
 * @param $segment
 * @param bool $trailing_slash
 *
 * @return string
 */
function clean_path_dir_segment( $segment, bool $trailing_slash = true ) : string {
	$segment = trim( $segment );
	$segment = ltrim( $segment, DIRECTORY_SEPARATOR );
	if ( $trailing_slash ) {
		$segment = trailingslashit( $segment );
	}

	return $segment;
}

/**
 * Combine arguments with defaults.
 *
 * @param array $args
 * @param array $defaults
 *
 * @return array
 */
function resolve_arguments( array $args, array $defaults ) : array {
	return apply_filters( 'template-dir/resolve-arguments', array_merge( $defaults, $args ), $args, $defaults );
}

/**
 * Remove $base and any preceding characters from $path.
 *
 * Ex.
 * ```
 * $path = '/path/to/a/directory/and/file';
 * $base = '/to/a';
 * rebase_path( $path, $base );
 * // directory/and/file
 * ```
 *
 * @param string $path
 * @param string $base
 *
 * @return string
 */
function rebase_path( string $path, string $base ) : string {
	$strpos = strpos( $path, $base );
	if ( $strpos === false ) {
		return '';
	}
	$length = strlen( $base );
	$offset = $length + $strpos;
	$rebased = substr( $path, $offset );

	return clean_path_dir_segment( $rebased, false );
}

/**
 * Return first match(es) for $regex in $path.
 *
 * Goes line-by-line to conserve memory on large files.
 *
 * @param string $path
 * @param string $regex
 *
 * @return array
 */
function get_match_in_file( string $path, string $regex ) : array {
	$results = [];
	if ( $path === '' || ! file_exists( $path ) ) {
		return $results;
	}
	if ( $opened_file = fopen( $path, 'r' ) ) {
		while ( ( $line = fgets( $opened_file ) ) !== false ) {
			$result = preg_match( $regex, $line, $matches );
			if ( $result === 1 ) {
				// We found it; stop.
				$results = $matches;
				break;
			}
		}
	}
	fclose( $opened_file );

	return $results;
}

/**
 * Return the default arguments for template generation.
 *
 * @return array
 */
function get_default_args() : array {
	return apply_filters( 'template-dir/default-args', [
		'paths' => [],
		'post_type' => 'page',
		'filename' => '*.php',
		'template_root' => get_stylesheet_directory(),
		'contains' => 'Template Name:',
		'name_regex' => '/Template Name: ?(.+)/',
		'post_type_regex' => '/Template Post Type:.*%post_type%(?=(?:,|$))/m',
	] );
}
