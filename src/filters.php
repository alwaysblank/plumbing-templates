<?php

add_filter( 'template-dir/default-args', function ( $args ) {
	if ( class_exists( '\\Roots\\Acorn\\View\\ViewServiceProvider' ) ) {
		// This is probably a theme using Acorn, so change the default roots.
		$args['template_root'] = get_theme_file_path( '/resources/views' );
		$args['parent_template_root'] = get_parent_theme_file_path( '/resources/views' );
	}

	return $args;
}, 9 );
