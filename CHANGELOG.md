# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/en/1.0.0/)
and this project adheres to [Semantic Versioning](http://semver.org/spec/v2.0.0.html).

## [2.0.0] - 2021-10-31

This adds a number of features, but is definitely a **breaking change**.

### Added

- Capability to define multiple directories.
- Support for child themes (loads templates from child and parent, but child templates override parent ones).
- Built-in support for Acorn.
- Better internal documentation.

### Changed

- Internal behavior and functions. `register_template_directory()` should work as before, but all other functions are likely gone, different, or produce different output.
- Relies more heavily on Finder to locate, filter templates.
- Update names for filter hooks to be more reasonable and extensible.

## [1.0.0] - 2018-03-14
### Added

- Add capability to define single directory to search for templates for a post type.
