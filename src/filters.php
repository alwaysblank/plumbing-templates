<?php
/**
 * When composer builds its autoloader, `add_filter` doesn't exist but Composer
 * will try to run it, so let's avoid that error.
 */
if ( function_exists( 'add_filter' ) ) {

	/**
	 * Acorn (often used in Sage themes) has a different path to views that we
	 * can predict, so lets set that up right now.
	 */
	add_filter( 'template-dir/default-args', function ( $args ) {
		if ( class_exists( '\\Roots\\Acorn\\View\\ViewServiceProvider' ) ) {
			// This is probably a theme using Acorn, so change the default roots.
			$args['template_root'] = get_theme_file_path( '/resources/views' );
			$args['parent_template_root'] = get_parent_theme_file_path( '/resources/views' );
		}

		return $args;
	}, 9 );
}
