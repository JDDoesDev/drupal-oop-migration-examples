# The Hook Migration: Drupal hook-to-class examples

Example code for **"The Hook Migration"**, a conference talk given on 13 November 2026. The talk shows how to convert procedural Drupal hooks (`mymodule_form_alter()` in a `.module` file) into attribute-based hook classes (`#[Hook('form_alter')]` on a method in `src/Hook`), one hook at a time.

The repo is a Drupal CMS site (Drupal 11.4.8, Forma site template). The interesting part is one custom module, [`web/modules/custom/jddoesdev_core`](web/modules/custom/jddoesdev_core). It pretends to be a ten-year-old catch-all module, with every hook written the old way. Each branch converts one more piece of it.

> This is teaching code. It is deliberately written in an old style on `main`, and each step is kept small so it reads well on a projector. Don't copy the baseline into a real project.

## The stages

Every stage is a branch. `main` is the starting point, and each branch adds exactly one commit to the branch before it, so you can compare any two neighbours to see a single step.

| Branch | What changes | Compare |
| --- | --- | --- |
| `main` | Baseline: every hook is procedural, in `.module`, `.install` and `.tokens.inc`, with `\Drupal::` calls throughout | |
| `demo-b` | `hook_form_alter` moves to a form hooks class, with the current user injected | [main → demo-b](https://github.com/JDDoesDev/drupal-oop-migration-examples/compare/main...demo-b) |
| `demo-c` | `hook_preprocess_node` moves to a theme hooks class | [demo-b → demo-c](https://github.com/JDDoesDev/drupal-oop-migration-examples/compare/demo-b...demo-c) |
| `demo-d` | One `hook_entity_presave` doing three jobs is split into three named methods, each with its own `#[Hook]` | [demo-c → demo-d](https://github.com/JDDoesDev/drupal-oop-migration-examples/compare/demo-c...demo-d) |
| `demo-e` | `hook_cron` is deleted, not converted: some hooks should go | [demo-d → demo-e](https://github.com/JDDoesDev/drupal-oop-migration-examples/compare/demo-d...demo-e) |
| `demo-f` | `hook_module_implements_alter` is replaced by `order: Order::Last` on the attribute | [demo-e → demo-f](https://github.com/JDDoesDev/drupal-oop-migration-examples/compare/demo-e...demo-f) |
| `demo-g` | `hook_requirements` becomes `#[Hook('runtime_requirements')]` with the `RequirementSeverity` enum | [demo-f → demo-g](https://github.com/JDDoesDev/drupal-oop-migration-examples/compare/demo-f...demo-g) |
| `demo-h` | Token hooks move to a class and the `.tokens.inc` include file is deleted | [demo-g → demo-h](https://github.com/JDDoesDev/drupal-oop-migration-examples/compare/demo-g...demo-h) |
| `demo-i` | `hook_help`, `hook_page_attachments` and `hook_mail_alter` move to classes | [demo-h → demo-i](https://github.com/JDDoesDev/drupal-oop-migration-examples/compare/demo-h...demo-i) |
| `demo-j` | A `hook_node_access` defined inside an `if` becomes an unconditional method, and the now-empty `.module` file is deleted | [demo-i → demo-j](https://github.com/JDDoesDev/drupal-oop-migration-examples/compare/demo-i...demo-j) |

The whole migration in one view: [main → demo-j](https://github.com/JDDoesDev/drupal-oop-migration-examples/compare/main...demo-j).

To compare stages locally:

```sh
git diff demo-c demo-d
```

Each commit message explains what changed and how it was checked.

## Things this project found on Drupal 11.4.8

- **Plain procedural hooks in `.module` trigger no deprecation notice** on 11.4.8. Only `hook_module_implements_alter`, `hook_requirements` and hooks in include files like `.tokens.inc` do. The exact messages, and how each was produced, are in [`docs/deprecations-baseline.md`](web/modules/custom/jddoesdev_core/docs/deprecations-baseline.md).
- **A hook function defined inside an `if` is not ignored.** Core finds procedural hooks by reading the file text, so it registers the function either way. If the condition is false when the file loads, every node page fails with `InvalidArgumentException: Class "jddoesdev_core_node_access" does not exist.`
- **`Order::Last` doesn't always mean last.** If another module also uses `Order::Last` and has a higher module weight, its ordering is applied after yours. On this site `eca_form` does exactly that, so `demo-f` keeps the hook in the same place the old `hook_module_implements_alter` put it, which is 21st of 23.
- **Canvas content templates bypass `node.html.twig`**, so `hook_preprocess_node` never runs for content types that use one. Stage C uses a `blog_post` type that has no content template.

## Trying Drupal Rector

The [`rector-trial`](https://github.com/JDDoesDev/drupal-oop-migration-examples/tree/rector-trial) branch is a throwaway experiment. It runs [`palantirnet/drupal-rector`](https://github.com/palantirnet/drupal-rector) 1.1.3 against the baseline and commits its output without hand edits, one commit per pass. In short:

- It converted 8 of the 12 hook functions.
- It skipped `hook_entity_presave` because its docblock named the wrong hook, and `hook_mail_alter` because it had no docblock. Rector finds hooks through the `Implements hook_…()` docblock, not the function name.
- It left `#[LegacyHook]` wrappers behind and wrote a `services.yml`. That's the backward-compatible path, which a site on Drupal 11.3 or later doesn't need.
- The hook-conversion pass changed nothing until it was run with `--clear-cache`, and it still reported success.

## Running it locally

You don't need to run anything to follow the talk: the compare links above show every step.

**This repo can't rebuild the site on its own.** It holds the code and configuration but not the database, which contains user accounts and isn't published. Two shortcuts that look like they should work don't:

- `drush site:install --existing-config` fails on this Drupal CMS site with `Field user_picture is unknown`.
- The Forma site template recipe was unpacked into the project when the site was first built, so it's no longer a Composer dependency you can install again.

To try the module in a Drupal 11.3 or later site of your own, copy `web/modules/custom/jddoesdev_core` into `web/modules/custom/` and enable it. Then enable the core Help module, which `hook_help` needs to show anything. The module is written for this site's content model, so some hooks need matching content to have a visible effect:

| Hook | Needs |
| --- | --- |
| `form_alter` | An `article` content type |
| `entity_presave` | Title trimming and logging work on any node. Filling an empty description needs an `article` type with `field_content` (text) and `field_description` (plain text) |
| `preprocess_node` | A `blog_post` content type with `field_content`, rendered through `node.html.twig` (not a Canvas content template) |
| `node_access` | A `project` content type |
| Runtime requirements | Error display set to "All messages, with backtrace information" (verbose); otherwise the warning has nothing to report |
| Tokens, help, page attachments, mail | Nothing extra |

The site was built and presented with [DDEV](https://ddev.com): `ddev start`, `ddev composer install`, then import a database. After switching branches, run `ddev drush cr`, because Drupal caches the list of hook implementations.

## License

GPL-2.0-or-later, the same as Drupal. See [LICENSE.txt](LICENSE.txt).
