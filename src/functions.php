<?php

namespace Livy\Plumbing\Templates;

use Symfony\Component\Finder\Finder;

function register_template_directory(string $path, $post_type = 'page')
{
    add_filter("theme_{$post_type}_templates", function ($templates) use ($path, $post_type) {
        return array_merge($templates, set_template_directory($path, $post_type));
    });
}

function set_template_directory(string $path, string $post_type = 'page')
{
    $checked_path = check_path_segment($path);
    $full_path    = get_wordpress_template_directory($post_type) . DIRECTORY_SEPARATOR . $checked_path ?: '';

    // Make sure you're not trying to go somewhere weird
    if (0 !== strpos($full_path, get_wordpress_template_directory($post_type))) {
        return [];
    }

    $templates = [];

    if (is_dir($full_path)) {
        $inline_identifier = get_inline_identifier($post_type);
        $finder            = new Finder();
        $finder->files()->in($full_path)->name(get_file_extenstion($post_type));
        foreach ($finder as $file) {

            // Set $name so it'll be sure to have a value.
            $name = false;

            // Look in each file to find the name; leave as soon as we have it.
            if ($opened_file = fopen($file->getRealPath(), 'r')) {
                while (($line = fgets($opened_file)) !== false) {
                    if (strpos($line, $inline_identifier)) {
                        $name = trim(str_replace($inline_identifier, '', $line));
                        break;
                    }
                }
            }
            fclose($opened_file);

            // Only add to list if we have a viable $name.
            if ($name) {
                $templates[$checked_path . $file->getFilename()] = $name;
            }

            // Don't want to accidentally pass these to the next iteration.
            unset($name, $opened_file);
        }
    }

    return $templates;
}

/**
 * @return string
 */
function get_wordpress_template_directory($post_type)
{
    return apply_filters('template-dir/theme-directory', get_template_directory(), $post_type);
}

function get_inline_identifier($post_type)
{
    return apply_filters('template-dir/inline-identifier', 'Template Name:', $post_type);
}

function get_file_extenstion($post_type)
{
    return apply_filters('template-dir/file-extension', '*.php', $post_type);
}

function check_path_segment(string $path)
{
    // Need a trailing slash.
    $trailingslashed = trailingslashit($path);

    // Don't want a leading slash.
    $frontchecked = ltrim($trailingslashed, '\\/');
    return $frontchecked;
}