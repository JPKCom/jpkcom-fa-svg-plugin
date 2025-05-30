# JPKCom FA inline SVG shortcode

**Plugin Name:** JPKCom FA inline SVG shortcode  
**Plugin URI:** https://github.com/JPKCom/jpkcom-fa-svg-plugin  
**Description:** A plugin for loading inline SVGs from Font Awesome (Pro) v5.15.4 using a shortcode  
**Version:** 2.0.8  
**Author:** Jean Pierre Kolb <jpk@jpkc.com>  
**Author URI:** https://www.jpkc.com/  
**Contributors:** JPKCom  
**Tags:** FontAwesome, SVG, Inline, Shortcode, Gutenberg  
**Requires at least:** 6.7  
**Tested up to:** 6.8  
**Requires PHP:** 8.3  
**Network:** true  
**Stable tag:** 2.0.8  
**License:** GPL-2.0+  
**License URI:** http://www.gnu.org/licenses/gpl-2.0.txt

A plugin for loading inline SVGs from Font Awesome (Pro) v5.15.4 using a shortcode.


## Description

A plugin for loading inline SVGs from Font Awesome (Pro) v5.15.4 using a shortcode.

This is not an official plugin of Font Awesome, nor is it directly affiliated with Font Awesome or its publisher/owner.

This plugin is intended for users who want to quickly and unbureaucratically integrate the output of the SVG version of Font Awesome, fast and resource-efficient, into their WordPress site.

Get your Font Awesome or much better your Font Awesome Pro license here: https://fontawesome.com/


## Installation

1. In your admin panel, go to 'Plugins' > and click the 'Add New' button.
2. Click Upload Plugin and 'Choose File', then select the Plugin's .zip file. Click 'Install Now'.
3. Download your version of Font Awesome (Pro) v5.15.4 from https://fontawesome.com/
4. Unpack/Upload the content of the Font Awesome zip file directly into wp-content/uploads folder named "`jpkcom_fasvg/`"
5. Make sure that the following files/folders and paths are present: "`wp-content/uploads/jpkcom_fasvg/css/svg-with-js.min.css`", "`wp-content/uploads/jpkcom_fasvg/svgs/*`"
6. Click 'Activate' to use the plugin right away.


## Frequently Asked Questions

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
