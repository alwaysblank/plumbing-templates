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

## Limitations

The tool looks in your template directory (i.e. the result of `get_template_directory()`) when looking for the directory you specify. If will silently fail if it can't find the directory, or if you try to do something clever (i.e. `../../../usr/bin`) for your template path.

Currently this tool can only point to a single directory per post type.
