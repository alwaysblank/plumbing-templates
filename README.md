# Template 👩‍🔧

This little tool gives you an easy way to organize your page (or custom post type) templates better. By "templates" I mean the templates you can select from the "Templates" dropdown in the editor.

## Usage

First, install the package:

```bash
composer require livy/plumbing-templates
```

Then call the tool like this, somewhere where it'll be run early (i.e. `functions.php`):

```php
Livy\Plumbing\Templates\register_template_directory('custom-templates');
```

By default, the plugin assumes you're changing the template for the `page` post type, but you can specify _any_ post type by passing a second parameter:

```php
// Define templates for the `event` post type:
Livy\Plumbing\Templates\register_template_directory('event-templates', 'event');
```

Usually, you'll want to wrap this in an action call to make sure it runs early enough, i.e.:

```
add_action('init', function () {
    Livy\Plumbing\Templates\register_template_directory('event-templates', 'event');
});
```

### Sage Starter Theme

If you're using the Sage starter theme, your "theme" directory is in a little different location than usual. Fortunately, there's an easy way to address this with a filter:

```php
add_action('init', function () {
    add_filter('template-dir/theme-directory', function ($dir) {
        return get_theme_file_path('/resources/views');
    });
    register_template_directory('pages');
});
```



## Limitations

The tool looks in your template directory (i.e. the result of `get_template_directory()`) when looking for the directory you specify. If will silently fail if it can't find the directory, or if you try to do something clever (i.e. `../../../usr/bin`) for your template path.

Currently this tool can only point to a single directory per post type.
