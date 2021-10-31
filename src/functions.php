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
		'template_root' => get_stylesheet_directory(),
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
		'template_root' => get_stylesheet_directory(),
		'contains' => 'Template Name:',
		'name_regex' => '/Template Name: ?(.+)/',
		'post_type_regex' => '/Template Post Type:.*%post_type%(?=(?:,|$))/m',
	], $args);

	$settings = resolve_arguments($args, $defaults);

	$settings['template_root'] = trailingslashit($settings['template_root']);

	// We want to make sure we won't have double slashes when we concatenate with template_root.
	$settings['paths'] = array_map(function($path) {
		return trailingslashit(rtrim($path, DIRECTORY_SEPARATOR));
	}, $settings['paths']);

	// Only allow paths that exist.
	$settings['paths'] = array_filter($settings['paths'], function($path) use ($settings) {
		return is_dir($settings['template_root'] . $path);
	});

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
		$name_matches = get_match_in_file($file->getRealPath() ?: '', $settings['name_regex']);
		preg_match(get_relative_filename_regex($settings), $file->getRealPath(), $path_matches);
		if (isset($name_matches[1])) {
			$templates[ rebase_path($file->getRealPath(), $settings['template_root']) ] = $name_matches[1];
		}
	}

	return apply_filters('template-dir/collected-templates', $templates, $settings, $args);
}

function get_finder(array $args): Finder
{
	$finder = new Finder();
	$finder->ignoreUnreadableDirs();
	foreach($args['paths'] as $path) {
		$finder->in($args['template_root'] . $path);
	}
	$finder->files()
	       ->name($args['filename'])
	       ->contains($args['contains'])
	       ->sortByName();

	if (version_compare($GLOBALS['wp_version'], '4.7', '>=') && $args['post_type'] !== 'page') {
		$finder->contains($args['post_type_regex']);
	}

	return $finder;
}

function clean_path_dir_segment($segment, $trailing_slash = true): string {
	$segment = trim($segment);
	$segment = ltrim($segment, DIRECTORY_SEPARATOR);
	if ($trailing_slash) {
		$segment = trailingslashit($segment);
	}
	return $segment;
}

function resolve_arguments(array $args, array $defaults) : array
{
	return apply_filters('template-dir/resolve-arguments', array_merge($defaults, $args), $args, $defaults);
}

function get_relative_filename_regex($args): string
{
	$paths = join('|', array_map(function($path) {
		return preg_quote($path, '/');
	}, $args['paths']));

	return sprintf('/(%s)(.*)/', $paths);
}

function rebase_path(string $path, string $base): string
{
	$strpos = strpos($path, $base);
	if ($strpos === false) {
		return '';
	}
	$length = strlen($base);
	$offset = $length + $strpos;
	$rebased = substr($path, $offset);
	return clean_path_dir_segment($rebased, false);
}

function get_match_in_file(string $path, string $regex): array
{
	$results = [];
	if ($path === '' || !file_exists($path)) {
		return $results;
	}
	if ($opened_file = fopen($path, 'r')) {
		while (($line = fgets($opened_file)) !== false) {
			$result = preg_match($regex, $line, $matches);
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
