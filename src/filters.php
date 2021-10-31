<?php

namespace Livy\Plumbing\Templates;

/**
 * When composer builds its autoloader, `add_filter` doesn't exist but Composer
 * will try to run it, so let's avoid that error.
 */
if ( function_exists( 'add_filter' ) ) {

	/**
	 * Acorn (often used in Sage themes) has a different path to views that we
	 * can predict, so lets set that up right now. It's hooked up a bit early
	 * to make it easier to unhook if necessary.
	 */
	add_filter( 'livy.plumbing.templates.args.default', __NAMESPACE__ . '\\set_acorn_defaults', 9 );
}

/**
 * Adjust defaults for Acorn, if Acorn is detected.
 *
 * @param array $args
 *
 * @return array
 */
function set_acorn_defaults( array $args ) : array {
	if ( class_exists( '\\Roots\\Acorn\\View\\ViewServiceProvider' ) ) {
		// This is probably a theme using Acorn, so change the default roots.
		$args['template_root'] = get_theme_file_path( '/resources/views' );
		$args['parent_template_root'] = get_parent_theme_file_path( '/resources/views' );
	}

	return $args;
}
