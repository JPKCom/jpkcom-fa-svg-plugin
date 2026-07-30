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
├── jpkcom_fasvg_path() / jpkcom_fasvg_url()  → resolved per call, switch_to_blog-safe
├── Path/URL constants (guarded, derived from those helpers)
├── jpkcom_fasvg_register_style()          → idempotent, one handle for both contexts
├── jpkcom_fasvg_enqueue_files()           → wp_enqueue_scripts
├── jpkcom_fasvg_enqueue_gutenberg_files() → enqueue_block_assets (is_admin guard)
├── jpkcom_fasvg_navigation_fa()           → wp_nav_menu_objects
├── jpkcom_fasvg_shortcode()               → add_shortcode( 'jsvg' )
└── jsvg_code()                            → deprecated shim, calls the above
```

Every function is `jpkcom_fasvg_`-prefixed and wrapped in `function_exists()`. Until 2.0.16 the shortcode callback was the exception — a global, unguarded `jsvg_code()`, a name generic enough that a theme declaring it would have caused a fatal redeclare on load. It survives as a deprecated shim for anything calling it directly.

---

## Editor assets: `enqueue_block_assets`, not `enqueue_block_editor_assets`

As of WordPress 7.1 the post editor always renders its canvas inside an iframe, regardless of theme and `apiVersion`. `enqueue_block_editor_assets` only loads into the surrounding admin document and no longer reaches the canvas. That is exactly what this plugin depended on up to 2.0.13.

The replacement is `enqueue_block_assets`. The hook fires in three places, all of them either intended or deliberately skipped:

| Fires from | Context | Behaviour here |
|---|---|---|
| `_wp_get_iframed_editor_assets()` (`wp-includes/block-editor.php`) | builds `$settings['__unstableResolvedAssets']`, which the iframe injects into its `<head>` | **the actual fix** — CSS reaches the canvas |
| `wp_common_block_scripts_and_styles()` via `admin_enqueue_scripts` | admin document on block editor screens | preserves the previous behaviour for the non-iframed editor (WP ≤ 7.0) |
| `wp_common_block_scripts_and_styles()` via `wp_enqueue_scripts` | front end | skipped by the `is_admin()` guard |

**Why the front end does not go through this hook.** On the front end `enqueue_block_assets` only fires while `wp_common_block_scripts_and_styles` is attached to `wp_enqueue_scripts`. Performance plugins unhook precisely that function to get rid of `wp-block-library` — `speed-booster-pack` is active on the test system. The front end therefore keeps its own `wp_enqueue_scripts` registration.

**Why `jpkcom_fasvg_register_style()` must be idempotent.** `_wp_get_iframed_editor_assets()` swaps `$wp_styles` for a fresh `WP_Styles` instance before firing the hook, so the callback runs several times per request. Without the `wp_style_is( …, 'registered' )` check, `wp_add_inline_style()` would attach the `.svg-inline--fa` rule to the handle repeatedly.

**Evidence (empirical, DDEV `posts`, WP 7.0.2, full plugin stack):** in a real `post-new.php` request `__unstableResolvedAssets.styles` contains **no** `jpkcom-fasvg` on 2.0.13, and on 2.0.14 the `<link>` plus the inline rule exactly once; the admin document has them on both. Reproducible without a browser:

```bash
ddev wp eval 'define("WP_ADMIN", true);
$a = _wp_get_iframed_editor_assets();
echo str_contains( $a["styles"], "jpkcom_fasvg" ) ? "in canvas\n" : "MISSING from canvas\n";'
```

---

## Constants

| Constant | Value | Purpose |
|----------|-------|---------|
| `JPKCOM_FASVG_VERSION` | matches the header `Version:` | Plugin version (kept in sync with header/README/phpdoc.xml) |
| `JPKCOM_FASVG_PLUGIN_PATH` | `plugin_dir_path(__FILE__)` | Absolute plugin path |
| `JPKCOM_FASVG_PLUGIN_URL` | `plugin_dir_url(__FILE__)` | Plugin URL |
| `JPKCOM_FASVG_PATH` | `<uploads>/jpkcom_fasvg/` | Filesystem base for CSS + SVGs — **prefer `jpkcom_fasvg_path()`** |
| `JPKCOM_FASVG_URL` | `<uploads-url>/jpkcom_fasvg/` | URL base for CSS + SVGs — **prefer `jpkcom_fasvg_url()`** |

All five are `defined()`-guarded, so a stray second copy of the file cannot raise "constant already defined".

### Why the two path helpers exist

The plugin is `Network: true`, and on multisite every site has its own upload directory. A value captured while the file was being included keeps pointing at whichever site was active then, which is wrong for anything rendered after `switch_to_blog()`. `jpkcom_fasvg_path()` and `jpkcom_fasvg_url()` resolve on every call and are what the shortcode and the stylesheet registration use; the two constants remain only for backwards compatibility, since they are part of the documented surface.

They call `wp_get_upload_dir()` — that is `wp_upload_dir( null, false )`, the variant that does **not** attempt to create the directory. Reading icons never needs that side effect, and the previous load-time `wp_upload_dir()` took the creating path on every request. Both are statically cached in core; measured 0.38 µs per call, so resolving per call costs nothing worth optimising.

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

The raw SVG file contents are returned inline. The trust boundary is the local `uploads/jpkcom_fasvg/svgs/` directory — only place vetted Font Awesome SVGs there. Anyone able to write into it gets stored XSS on every page using the shortcode.

**Path traversal is closed, and tested.** `basename()` plus `sanitize_file_name()` reduce `name` to a flat string, `.svg` is always appended, and `type` is whitelisted against five folders with a fallback to `solid/`. Measured:

| `name=` | resolved file |
|---|---|
| `../../../../etc/passwd` | `passwd.svg` |
| `solid/../../wp-config` | `wp-config.svg` |
| `..` | `.svg` |
| `a\0b` (null byte) | `ab.svg` |

`sanitize_file_name()` trims `.-_` from both ends, which is what turns `..` into an empty string. `tests/test-hooks.php` asserts five such inputs return the built-in fallback square and never read a file placed outside the icon folder.

The `<title>` id comes from `wp_unique_id()`. Until 2.0.16 it was `wp_rand( 10, 500000 )` — with a few dozen titled icons on one page the collision chance was small but real, and a duplicate id silently breaks `aria-labelledby` and is invalid HTML.

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
- Shared JPKCom updater (do **not** edit per-plugin; it is a downstream copy of the upstream `jpkcom-post-filter` updater). Features: SHA256 checksum verification (`upgrader_pre_download`), `wp_safe_remote_get()` manifest fetch, `wp_http_validate_url()` on every URL, 30 s race-condition lock, 24 h transient cache, timing-safe `hash_equals()` comparison. Checksum verification is **mandatory**: a missing or unfetchable `checksum_sha256` aborts the update instead of installing unverified code. The verified temp file is returned from `upgrader_pre_download`, so WordPress installs exactly the bytes that were hashed (no second download). Failed manifest fetches are negatively cached for 1 h.
- Hooks: `plugins_api`, `site_transient_update_plugins`, `upgrader_process_complete`, `upgrader_pre_download`.

---

## Release Workflow

**Actions are pinned to commit SHAs.** Every `uses:` line in `.github/workflows/` references a 40-character commit SHA instead of a tag (`@v4`), with the version as a trailing comment. A tag is a movable pointer and can be repointed; a SHA cannot. Since the release workflow builds the plugin ZIP **and** the SHA256 checksum the auto-updater trusts, a compromised action would ship a tampered ZIP together with a matching checksum — the checksum secures the transport, the pinning secures the build. `.github/dependabot.yml` keeps the pins current weekly in one combined PR; when updating, always change the SHA *and* the version comment together.

**CI** (`.github/workflows/ci.yml`) runs on every pull request *and* on every push to `main` — a required status check only covers pull requests, so a direct push with bypass rights would otherwise skip the checks entirely. It runs `php -l` over all PHP files; flags invalid named arguments to internal PHP functions (catches `sprintf(format:, values:)` → `ArgumentCountError`, which `php -l` does not see); validates the YAML of every `.github` file; asserts every action is pinned to a 40-character commit SHA; and executes `tests/test-*.php` where present.

**Dependabot auto-merge** (`.github/workflows/dependabot-auto-merge.yml`) merges only `semver-patch` and `semver-minor`, and only PRs from `dependabot[bot]` in this repo — never from forks. Major updates get a comment and stay manual. Two repo settings are prerequisites, otherwise this is useless or outright dangerous: "Allow auto-merge" must be enabled, and branch protection must list `CI / Lint & Guards` as a **required status check** — without it `gh pr merge --auto` merges *immediately*, since there is nothing left to wait for. Together with `cooldown: default-days: 7` no action release is adopted during its first week.

Triggered by **pushing a `v*` tag** (`.github/workflows/release.yml`). The workflow creates the GitHub release itself — no manual "Publish release" step needed. Pipeline: setup PHP 8.3 + Python + Pandoc + GraphViz → extract README metadata → build slug-named ZIP → SHA256 → upload ZIP + `.sha256` → generate `plugin_<slug>.json` manifest → generate PHPDoc → deploy manifest/HTML/docs to `gh-pages`.

---

## Security Checklist

- `declare(strict_types=1)` in every PHP file
- `[jsvg] name` sanitized via `sanitize_file_name( basename() )`, `type` whitelisted — path traversal closed and covered by tests
- `file_get_contents()` guarded against a `false` return
- Shortcode attributes escaped with `esc_attr()` in attribute context, `esc_html()` for the `<title>` element text
- Every function prefixed and `function_exists()`-guarded; no unprefixed global names
- Updater: SHA256 verification + URL validation (audited separately)

**Known and accepted:** `jpkcom_fasvg_navigation_fa()` expands *every* shortcode in a menu title that contains `[jsvg`, not only that one, because it hands the whole title to `do_shortcode()`. Editing menus requires `edit_theme_options`, so this is admin-only — but the scope is wider than the function name suggests.

---

## Tests

`tests/test-hooks.php` runs standalone — no WordPress. It stubs the functions the main file touches, creates a throwaway icon directory under the system temp dir (including a file *outside* the icon folder that must stay unreachable), requires the plugin and renders the shortcode directly. 26 cases covering the prefixed callback and the deprecated shim, the switch-safe path helpers, the rendered markup, title escaping, unique ids, the style whitelist, five path-traversal attempts, and the hook registrations. 5 of them fail against 2.0.15.

```bash
php tests/test-hooks.php   # exit 0 = green
```

Two notes on the suite itself, so it is not misread:

- The `wp_rand()` stub returns a **constant** on purpose. Against 2.0.15 that makes the "distinct ids" case fail deterministically instead of depending on a collision happening to occur — with 200 random draws from 500 000 the collision chance is only about 4 %, which would have made the test flaky.
- The title-escaping case does **not** discriminate between 2.0.15 and 2.0.16: in WordPress `esc_attr()` and `esc_html()` produce identical output. The change to `esc_html()` is a correctness-of-intent fix, and the assertion guards the escaping itself, not the switch.

---

## Release Checklist

1. Bump the version in five places:
   - Plugin header `Version:`
   - Plugin header `Stable tag:`
   - Constant `JPKCOM_FASVG_VERSION`
   - `phpdoc.xml` `<version number="…">`
   - `README.md` — `**Version:**` and `**Stable tag:**`
2. Add a `### x.y.z` block to `## Changelog` in `README.md`
3. Run `php tests/test-hooks.php`
4. Commit, then push the tag `vx.y.z` → the workflow builds and publishes everything
