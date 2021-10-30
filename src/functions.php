<?php

namespace Livy\Plumbing\Templates;

use Symfony\Component\Finder\Finder;

/**
 * @param array|string $path
 * @param string $post_type
 */
function register_template_directory($path, string $post_type = 'page')
{
    $paths = is_string($path) ? [$path] : $path;

	$args = [
		'paths' => $paths,
		'post_type' => $post_type,
	];

    add_filter("theme_{$post_type}_templates", function ($templates) use ($args) {
        return array_merge($templates, compile_template_list($args));
    });
}

function setup_template_location(array $args)
{
	if (!isset($args['post_type']) || !isset($args['paths'])) {
		// Can't do anything without these.
		return;
	}

	add_filter("theme_{$args['post_type']}_templates", function ($templates) use ($args) {
		return array_merge($templates, compile_template_list($args));
	});
}

function compile_template_list(array $args): array
{
	$defaults = apply_filters('template-dir/default-compile-arguments', [
		'paths' => [],
		'post_type' => 'page',
		'filename' => '*.php',
		'contains' => 'Template Name:',
		'name_regex' => '/Template Name: ?(.+)/',
		'post_type_regex' => '/Template Post Type:.*%post_type%(?=(?:,|$))/m',
	], $args);

	$settings = resolve_arguments($args, $defaults);

	$settings['paths'] = array_filter($settings['paths'], 'is_dir');

	// Without valid paths, we can't do anything.
	if (count($settings['paths']) === 0) {
		return [];
	}

	// Add post type to post type regex.
	$settings['post_type_regex'] = str_replace(
		'%post_type%',
		$settings['post_type'],
		$settings['post_type_regex']
	);

	$settings = apply_filters('template-dir/resolved-compile-arguments', $settings, $args);

	$templates = [];
	foreach (get_finder($settings) as $file) {
		$matches = get_match_in_file($file->getRealPath() ?: '', $settings['name_regex']);
		if (isset($matches[1])) {
			$templates[ $file->getRelativePathname() ] = $matches[1];
		}
	}

	return apply_filters('template-dir/collected-templates', $templates, $settings, $args);
}

function get_finder(array $args): Finder
{
	$finder = new Finder();
	$finder->ignoreUnreadableDirs();
	foreach($args['paths'] as $path) {
		$finder->in($path);
	}
	$finder->files()
	       ->name($args['contains'])
	       ->contains($args['contains'])
	       ->sortByName();

	if (version_compare($GLOBALS['wp_version'], '4.7', '>=')) {
		$finder->contains($args['post_type_regex']);
	}

	return $finder;
}

function resolve_arguments(array $args, array $defaults) : array
{
	return apply_filters('template-dir/resolve-arguments', array_merge($defaults, $args), $args, $defaults);
}

function get_match_in_file(string $path, string $regex): array
{
	$results = [];
	if ($path === '' || !file_exists($path)) {
		return $results;
	}
	if ($opened_file = fopen($path, 'r')) {
		while (($line = fgets($opened_file)) !== false) {
			$result = preg_match($regex, $line, $matches, PREG_OFFSET_CAPTURE, 0);
			if ($result === 1) {
				// We found it; stop.
				$results = $matches;
				break;
			}
		}
	}
	fclose($opened_file);

	return $results;
}
