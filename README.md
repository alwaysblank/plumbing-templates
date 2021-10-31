# Template 👩‍🔧

This tool gives you a way to improve the file organization of your [custom page templates](https://developer.wordpress.org/themes/template-files-section/page-template-files/#creating-custom-page-templates-for-global-use). For Wordpress 4.7+ you can also use templates for other post types.

It provides reasonable defaults that should work in most situations, but is also heavily configurable. Ships with built-in support for Sage 10. If you'd like to add built-in support for other frameworks with non-standard template organization, please file a PR or issue.

## Usage

First, install the package:

```bash
composer require livy/plumbing-templates
```

Then call the tool like this, somewhere where it'll be run early (i.e. `functions.php`):

```php
Livy\Plumbing\Templates\register_template_directory('custom-templates');
```

By default, the package assumes you're changing the template for the `page` post type, but you can specify _any_ post type by passing a second parameter:

```php
// Define templates for the `event` post type:
Livy\Plumbing\Templates\register_template_directory('event-templates', 'event');
```

Usually, you'll want to wrap this in an action call to make sure it runs early enough, i.e.:

```php
add_action('init', function () {
    Livy\Plumbing\Templates\register_template_directory('event-templates', 'event');
});
```

### Advanced Usage

This package has a number of other options that allow you to customize its behavior. You can use the project's filters to do this, but it may be easier to pass your arguments all at once. To do this, use `setup_template_location()` instead of `register_template_directory()`:

```php
Livy\Plumbing\Templates\setup_template_location([
    'paths' => [ 'event-templates' ],
    'post_type' => 'event',
]);
```
...will have the same effect as the example above.

> The keys `paths` and `post_type` must be defined, or nothing will happen.

#### Options

The following is a list of all the options this package understands.

- `paths` _array_ - An array of paths (relative to your template root) to search for templates. Default: empty array.
- `post_type` _string_ - The post type to use these templates for. Default: `page`.
- `filename` _string_ - Matched against filenames when searching for templates. Can be a string, simple glob (i.e. `*.ext`) or a regular expression. Default: `*.php`.
- `template_root` _string_ - The template root, which is the directory that all template paths are relative to. It is unlikely you'll need to modify this value. Default: output of [`get_stylesheet_directory()`](https://developer.wordpress.org/reference/functions/get_stylesheet_directory/).
- `parent_template_root` _string_ - Same as `template_root`, but for templates in a potential parent theme. Ignored if there is no parent theme. It is unlikely you'll need to modify this value. Default: Output of [`get_template_directory()`](https://developer.wordpress.org/reference/functions/get_template_directory()/).
- `contains` _string_ - A string templates must contain. Can also be a regular expression. Used to identify what files are templates. Default: `Template Name:`.
- `name_regex` _string_ - A regular expression to get the name of a template from its contents. Default: `/Template Name: ?(.+)/`.
- `post_type_regex` _string_ - A regular expression to determine if a template applies to a particular post type. `%post_type%` will be replaced with the value of `post_type` option at runtime. Default: `/Template Post Type:.*%post_type%(?=(?:,|$))/m`.

### Acorn and Sage

If you use the [v10+ of the Sage starter theme](https://github.com/roots/sage) or are using [Acorn](https://github.com/roots/acorn) in some other capacity, you know that your templates are stored in a slightly non-standard location. Fortunately this package [helpfully alters the defaults](https://github.com/alwaysblank/plumbing-templates/blob/56d06ec8ba7fa9dae52a047ef5893bc25ebdd81f/src/filters.php#L11-L17) if it detects that you're using Acorn.
