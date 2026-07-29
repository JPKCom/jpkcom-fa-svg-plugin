# JPKCom FA inline SVG shortcode

**Plugin Name:** JPKCom FA inline SVG shortcode  
**Plugin URI:** https://github.com/JPKCom/jpkcom-fa-svg-plugin  
**Description:** A plugin for loading inline SVGs from Font Awesome (Pro) v5.15.4 using a shortcode  
**Version:** 2.0.14  
**Author:** Jean Pierre Kolb <jpk@jpkc.com>  
**Author URI:** https://www.jpkc.com/  
**Contributors:** JPKCom  
**Tags:** FontAwesome, SVG, Inline, Shortcode, Gutenberg  
**Requires at least:** 6.9  
**Tested up to:** 7.1  
**Requires PHP:** 8.3  
**Network:** true  
**Stable tag:** 2.0.14  
**License:** GPL-2.0-or-later  
**License URI:** https://www.gnu.org/licenses/gpl-2.0.html

A plugin for loading inline SVGs from Font Awesome (Pro) v5.15.4 using a shortcode.


## Description

A plugin for loading inline SVGs from Font Awesome (Pro) v5.15.4 using a shortcode.

This is not an official plugin of Font Awesome, nor is it directly affiliated with Font Awesome or its publisher/owner.

This plugin is intended for users who want to quickly and unbureaucratically integrate the output of the SVG version of Font Awesome, fast and resource-efficient, into their WordPress site.

Get your Font Awesome or much better your Font Awesome Pro license here: https://fontawesome.com/


### Documentation

**API Documentation:** Complete PHPDoc-generated API documentation is available at:
[https://jpkcom.github.io/jpkcom-fa-svg-plugin/docs/](https://jpkcom.github.io/jpkcom-fa-svg-plugin/docs/)


## Installation

1. In your admin panel, go to 'Plugins' > and click the 'Add New' button.
2. Click Upload Plugin and 'Choose File', then select the Plugin's .zip file. Click 'Install Now'.
3. Download your version of Font Awesome (Pro) v5.15.4 from https://fontawesome.com/
4. Unpack/Upload the content of the Font Awesome zip file directly into wp-content/uploads folder named "`jpkcom_fasvg/`"
5. Make sure that the following files/folders and paths are present: "`wp-content/uploads/jpkcom_fasvg/css/svg-with-js.min.css`", "`wp-content/uploads/jpkcom_fasvg/svgs/*`"
6. Click 'Activate' to use the plugin right away.


## FAQ

### Usage

Use `[jsvg type="" name="" style="" class="" title=""]` in your content or menu item title.

**For example:**

```
[jsvg type="fal" name="jedi" style="margin:2rem" class="fa-10x" title="Obi-Wan Kenobi"]
```

You can also use this together with `do_shortcode()`.

See https://developer.wordpress.org/reference/functions/do_shortcode/ for more information about this.

**For example:**

```php
<?php
echo do_shortcode( '[jsvg type="fas" name="snowboarding" class="fa-4x fa-rotate-270" title="Snowboarding"]' );
```


## Changelog

### 2.0.14
* Changed: `Tested up to` raised to WordPress 7.1
* Changed: the bundled updater's runtime floor now matches the plugin's own minimum. It bailed out below WordPress 6.8 while the plugin header has required 6.9 for several releases, so the check could never fire on a supported installation
* Fixed: the Font Awesome stylesheet now reaches the block editor **canvas**. It was hooked to `enqueue_block_editor_assets`, which only loads into the surrounding admin document. From WordPress 7.1 the post editor always renders its canvas in an iframe, so inline SVGs shown in a block preview would have lost the Font Awesome sizing rules (`height: 1em`, `display: inline-block`, `overflow: visible`) and rendered at their intrinsic size. The hook is now `enqueue_block_assets`, which WordPress runs both when it assembles the iframe assets and on block editor admin screens — the non-iframed editor of WordPress 7.0 and earlier behaves exactly as before
* Changed: one shared style handle `jpkcom-fasvg-style` for front end and editor; the separate editor handle `jpkcom-fasvg-gutenberg-style` is gone. Registration is idempotent, so the inline `.svg-inline--fa` rule is emitted once per document
* The front end is untouched: it keeps its own `wp_enqueue_scripts` registration and deliberately does not rely on `enqueue_block_assets`, which optimisation plugins unhook to strip the core block library
* CI: the release manifest's fallback values for `requires` and `tested` now say 6.9 and 7.1. They only apply when the README metadata cannot be read, but a stale fallback would have published a minimum the plugin no longer supports

### 2.0.13
* Added: plugin banners (`assets/banner-1544x500.avif`, `assets/banner-772x250.avif`) — a plain `#3c4955` surface with no lettering. The update manifest already advertised these two URLs, but nothing was published under them, so the plugin card in wp-admin had a broken banner

### 2.0.12
* CI: the release step no longer copies the staging directory into itself, so the ZIP has no empty `jpkcom-fa-svg-plugin/jpkcom-fa-svg-plugin/` folder
* CI: bumped the pinned GitHub Actions (checkout v7.0.1, setup-python v7.0.0, action-gh-release v3.0.2, fetch-metadata v3.1.0), still pinned to full commit SHAs
* CI: the release ZIP now excludes the development-only `tests/` and `tools/` directories
* CI: security and regression tests now run on every pull request, where a plugin has them

### 2.0.11
* Security: update packages are now verified *before* installation — the verified file is handed to WordPress instead of being downloaded a second time, so the bytes that were checked are the bytes that get installed
* Security: a missing or unfetchable SHA-256 checksum now aborts the update instead of installing unverified code (previously it silently skipped verification)
* Security: pinned every GitHub Action to a full commit SHA and added Dependabot with a 7-day cooldown, so a moved tag can no longer change the release build
* Security: tightened which download the updater claims, so sibling plugins cannot match each other's package
* Fixed: `sprintf()` calls in the updater bound named arguments to a variadic parameter, which raises `ArgumentCountError` on PHP 8.3
* Fixed: the "View Details" modal could fail with a `TypeError` when the manifest omitted `requires_plugins`
* Performance: a failed manifest fetch is now cached for an hour instead of being retried on every admin request
* Added: CI workflow on every pull request (PHP lint, named-argument check, YAML validation, action-pinning guard)

### 2.0.10
* Docs: corrected the FAQ section heading so it is included in the release manifest, and linked the published PHPDoc API documentation

### 2.0.9
* Added secure self-hosted plugin updates via GitHub with SHA256 checksum verification
* Added an automated release workflow (builds the ZIP, generates the manifest and deploys to gh-pages on tag push)
* Raised the minimum WordPress version to 6.9 and "Tested up to" to WordPress 7.0
* Switched license metadata to the SPDX identifier `GPL-2.0-or-later` with the HTTPS license URI
* Added PHPDoc-generated API documentation, built and deployed to gh-pages on release
* Security: prevented path traversal in the `[jsvg]` `name` attribute (now sanitized with `sanitize_file_name()`/`basename()`)
* Hardening: enabled `declare(strict_types=1)`, tightened parameter/return types, guarded the SVG file read and removed dead code

### 2.0.8
* PHP warnings fixed "Undefined variable ..."

### 2.0.7
* Tested up to WP v6.8

### 2.0.6
* Making use of wp_rand()
* Fix Stable tag

### 2.0.5
* README.md version update

### 2.0.4
* README.md meta data update

### 2.0.3
* Plugin meta data update

### 2.0.2
* README.md update

### 2.0.1
* Network support

### 2.0.0
* PHP 8.3+
* WP v6.7+
* wp-content/uploads folder support

### 1.0.0
* Initial Release
