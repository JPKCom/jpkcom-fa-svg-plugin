# JPKCom FA inline SVG shortcode – Developer Reference

## Plugin Overview

Loads inline SVGs from Font Awesome (Pro) v5.15.4 through a `[jsvg]` shortcode and runs the shortcode inside navigation menu item titles. The SVG markup is injected inline so it can inherit `currentColor` and be styled with CSS.

- **Text Domain:** none declared (defaults to slug `jpkcom-fa-svg-plugin`)
- **Min PHP:** 8.3 | **Min WP:** 6.9
- **Network:** `true` (can be network-activated)

The Font Awesome assets are expected in the uploads directory, **not** bundled in the plugin:

```
wp-content/uploads/jpkcom_fasvg/
├── css/svg-with-js.min.css
└── svgs/{solid,light,regular,duotone,brands}/<icon>.svg
```

---

## Architecture

```
Main file (jpkcom-fa-svg-plugin.php)
├── declare(strict_types=1)
├── Plugin header (Network: true)
├── JPKCOM_FASVG_VERSION constant
├── init @ priority 5: boot JPKComGitPluginUpdater
├── Path/URL constants (IIFE, single wp_upload_dir() call — no globals)
├── jpkcom_fasvg_enqueue_files()           → wp_enqueue_scripts
├── jpkcom_fasvg_enqueue_gutenberg_files() → enqueue_block_editor_assets
├── jpkcom_fasvg_navigation_fa()           → wp_nav_menu_objects
└── jsvg_code()                            → add_shortcode( 'jsvg' )
```

---

## Constants

| Constant | Value | Purpose |
|----------|-------|---------|
| `JPKCOM_FASVG_VERSION` | `'2.0.9'` | Plugin version (kept in sync with header/README/phpdoc.xml) |
| `JPKCOM_FASVG_PLUGIN_PATH` | `plugin_dir_path(__FILE__)` | Absolute plugin path |
| `JPKCOM_FASVG_PLUGIN_URL` | `plugin_dir_url(__FILE__)` | Plugin URL |
| `JPKCOM_FASVG_PATH` | `<uploads>/jpkcom_fasvg/` | Filesystem base for CSS + SVGs |
| `JPKCOM_FASVG_URL` | `<uploads-url>/jpkcom_fasvg/` | URL base for CSS + SVGs |

---

## `[jsvg]` Shortcode

```
[jsvg type="fas" name="house" class="my-icon" style="width:1em" title="Home"]
```

| Attribute | Default | Notes |
|-----------|---------|-------|
| `type` | `fas` | Style folder: `fas` (solid), `fal` (light), `far` (regular), `fad` (duotone), `fab` (brands). Unknown → `solid/` |
| `name` | `square-full` | Icon file name without extension. **Sanitized** with `sanitize_file_name( basename() )` (no path traversal) |
| `class` | – | Added after `svg-inline--fa fa-<name>` (escaped with `esc_attr`) |
| `style` | – | Inline CSS on the `<svg>` (escaped) |
| `title` | – | Adds a `<title>` element and `aria-labelledby`; otherwise `aria-hidden="true"` |

The raw SVG file contents are returned inline. The trust boundary is the local `uploads/jpkcom_fasvg/svgs/` directory — only place vetted Font Awesome SVGs there.

---

## File Structure

```
jpkcom-fa-svg-plugin/
├── jpkcom-fa-svg-plugin.php   ← Main: header, constants, enqueue, shortcode, updater bootstrap
├── includes/
│   └── class-plugin-updater.php  ← GitHub auto-updater (namespace: JPKComFaSvgPluginGitUpdate)
├── .github/workflows/release.yml ← Build ZIP, manifest, PHPDoc, deploy to gh-pages (on tag push)
├── phpdoc.xml                 ← phpDocumentor config
├── README.md                  ← Public readme (source for the WP plugin modal)
├── CLAUDE.md                  ← This file
├── LICENSE                    ← GPL-2.0-or-later
└── .gitignore
```

---

## Plugin Updater

- **Namespace:** `JPKComFaSvgPluginGitUpdate\JPKComGitPluginUpdater`
- **Manifest URL:** `https://jpkcom.github.io/jpkcom-fa-svg-plugin/plugin_jpkcom-fa-svg-plugin.json`
- Shared JPKCom updater (do **not** edit per-plugin; it is a downstream copy of the upstream `jpkcom-post-filter` updater). Features: SHA256 checksum verification (`upgrader_pre_download`), `wp_safe_remote_get()` manifest fetch, `wp_http_validate_url()` on every URL, 30 s race-condition lock, 24 h transient cache, timing-safe `hash_equals()` comparison.
- Hooks: `plugins_api`, `site_transient_update_plugins`, `upgrader_process_complete`, `upgrader_pre_download`.

---

## Release Workflow

Triggered by **pushing a `v*` tag** (`.github/workflows/release.yml`). The workflow creates the GitHub release itself — no manual "Publish release" step needed. Pipeline: setup PHP 8.3 + Python + Pandoc + GraphViz → extract README metadata → build slug-named ZIP → SHA256 → upload ZIP + `.sha256` → generate `plugin_<slug>.json` manifest → generate PHPDoc → deploy manifest/HTML/docs to `gh-pages`.

---

## Security Checklist

- `declare(strict_types=1)` in every PHP file
- `[jsvg] name` sanitized via `sanitize_file_name( basename() )` (path-traversal safe)
- `file_get_contents()` guarded against a `false` return
- Shortcode attributes escaped with `esc_attr()` in HTML-attribute context
- Updater: SHA256 verification + URL validation (audited separately)

---

## Release Checklist

1. Bump version in: header `Version:` + `Stable tag:`, `JPKCOM_FASVG_VERSION`, `README.md` (`**Version:**`, `**Stable tag:**`), `phpdoc.xml` `<version>`
2. Add a `### x.y.z` block to `## Changelog` in `README.md`
3. Commit, tag `vx.y.z`, push the tag → the workflow builds and publishes everything
