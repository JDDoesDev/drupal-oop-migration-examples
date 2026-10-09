# Build brief: the `jddoesdev_core` demo module

You are building a small custom Drupal module that I will use on stage. Read this whole brief before touching anything, then start with the "Before you write code" section.

## What this is for

I'm giving a 45-minute conference talk on 13 November 2026 called "The Hook Migration". It teaches people to convert procedural hook implementations (`mymodule_form_alter()` in a `.module` file) to attribute-based hook classes (`#[Hook('form_alter')]` on a method in `src/Hook`), one hook at a time.

The module is the teaching prop. It pretends to be a ten-year-old catch-all module that collected every hook and preprocess that didn't fit anywhere else. On a projector, I will switch between git branches, one per teaching point, with a live site beside it to show each hook still fires.

That purpose drives every decision below:

- The audience includes beginners, and they read diffs from the back of a room. Each stage's diff has to be small and about one thing.
- The git history is the deliverable as much as the final code. A correct module with a messy history is a failure.
- I have to explain every line and answer questions about it, so plain and obvious beats clever.

## Environment

- This repo is a Drupal CMS site installed with the Forma site template and its default content, on Drupal 11.4.8 (PHP 8.4). The front-end theme is `forma_theme` and the admin theme is `gin`.
- Local tooling is DDEV: use `ddev drush`. Mail goes to Mailpit at `https://dcms.ddev.site:8026`.
- The module goes in `web/modules/custom/jddoesdev_core`.
- Content types:
  - `article`: `title`, `field_content` (text_long), `field_description` (string_long), `field_author`, `field_featured_image`, SEO fields. There is no `body` field and no summary.
  - `project`: `title`, `field_content`, `field_description`, `field_eyebrow`, `field_name`, `field_position`, `field_location`, `field_quote_text`, `field_featured_image`, SEO fields.
  - `page`: like article, plus `field_tags`.
  - `blog_post`: `title`, `field_content`, `field_link`, `field_tags`, SEO fields. It has no Canvas content template, so its `full` view mode renders through `node.html.twig`. Node 7, "Moving hooks into classes", is at `/moving-hooks-classes`.
- Canvas content templates render the `full` and `card` view modes of `article`, `project` and `page`, and remove `#theme => 'node'` from the render array. `hook_preprocess_node` therefore never runs for those pages. Use `blog_post` for anything that needs a node template.
- The Token module is installed. The core Help module is enabled, so `/admin/help/token` and `/admin/help/jddoesdev_core` exist.
- Drupal CMS sends all mail through `easy_email_override`, which calls `hook_mail_alter` twice per message and builds its own plain-text part. Anything a mail alter adds must guard against being added twice, and only shows in the HTML part.
- The Trash module turns `delete()` into a soft delete, which saves the entity and runs presave hooks again. To delete test content for real, wrap the delete in `\Drupal::service('trash.manager')->executeInTrashContext('ignore', ...)`.
- `system.logging:error_level` is `verbose` on this site, so a runtime requirement that warns about it is always visible.
- Users: uid 1 `admin`, who bypasses node access. Roles: `content_editor` and `administrator`. Test access as a `content_editor` from `drush php:eval`, without creating users.

## Before you write code

Inspect the site and report back to me. Do not start commit A until I reply.

1. The exact Drupal core version installed.
2. The node bundles and their fields, with machine names. Confirm they still match the Environment section.
3. How each node type's `full` view mode is rendered: through a node template that `hook_preprocess_node` can affect, or through Canvas in a way that bypasses it.
4. Whether the Token module (or anything else that makes a custom token visible in the UI) is installed.
5. For each inventory entry below, whether it can have an effect I can see or check on this site. If one can't, propose a substitute that teaches the same point and wait for my answer.

## Rules that apply to every commit

**Check the installed core, not your memory.** The hook attribute APIs changed across 11.1, 11.2, 11.3 and 11.4. Before using any attribute, enum or hook name from this brief, confirm it exists in the installed version by reading `core/lib/Drupal/Core/Hook/`, `core/core.api.php` and `core/lib/Drupal/Core/Extension/module.api.php`. If something in this brief doesn't match the installed core, stop and tell me rather than working around it. A wrong claim on stage is worse than a late module.

**No backwards-compatibility shims.** Set `core_version_requirement: ^11.3 || ^12`. Do not add `#[LegacyHook]`, `#[LegacyModuleImplementsAlter]`, `#[LegacyRequirementsHook]` or a `services.yml` entry for hook classes. Core registers hook classes as autowired services on its own. The talk covers shims on a slide; the code should show the simple path.

**One teaching point per commit.** Change only what that commit's row in the inventory describes. If you notice something else worth fixing, leave it and mention it in your report. Unrelated tidying makes the diff harder to read on stage.

**Every commit leaves the site working.** After each commit: rebuild caches, confirm no new errors in the log, and confirm the hook that commit touched still fires. Say how you confirmed it in the commit body. Where the effect is visible on the site, tell me the URL and what to look for, because I'll repeat that check live. Check anonymous pages after `drush cr`: the page cache can serve a stale page that hides a broken hook.

**Commit format.** Subject: `Demo X: <what changed>`, where X is the letter. Body: two or three sentences on what changed and how it was verified. Do not add `Co-Authored-By` trailers.

**Branch layout.** `main` is the baseline: commit A, tagged `demo-a`, plus the deprecations doc. Stages B to J are stacked branches named `demo-b` to `demo-j`. `demo-b` comes off `main`, `demo-c` off `demo-b`, and so on. Each branch adds exactly one commit, so `git diff demo-c demo-d` shows one teaching step and `git switch demo-d` jumps to that stage. Don't tag B to J; a tag and a branch with the same name are ambiguous to git. After every branch switch, run `ddev drush cr`, because Drupal caches the list of hook implementations. If you change `main` or an earlier stage, re-stack the later branches so each one stays a single commit on the one before it.

**Stay inside the module.** Don't change site configuration, other modules, or `composer.json` without asking. The one exception is the Rector step, which has its own section.

**Converted code follows Drupal coding standards.** Name hook classes `JddoesdevCore{Group}Hooks`, grouped by what they deal with (form, theme, entity, token and so on), in the `Drupal\jddoesdev_core\Hook` namespace. Use constructor injection in place of `\Drupal::` calls. Add `declare(strict_types=1);` and parameter and return types to new classes, but check each type against what core actually passes: strict types turn a NULL into a TypeError. For example, `hook_help` can receive a NULL route name, and `Node::getTitle()` can return NULL during presave.

## Phase 1: commit A, the baseline, then stop

Commit A is the whole module written the old way: every entry in the inventory as procedural code. Nothing in `src/` yet.

**It should look like it has been maintained by several people over ten years.** Left to your defaults you'll write tidy code, and that undercuts the demo. Specifically:

- `\Drupal::` static calls throughout, no injected services.
- Inconsistent docblocks: most hooks have a correct `Implements hook_foo().` comment, one has none at all (see the inventory), one has an outdated or vague comment.
- A private-style helper function with a leading underscore.
- Mixed vintage: some functions with type hints, some without; an old-style `array()` or two is fine.
- A `hook_update_N` with an old-looking number.
- Comments that hint at history, like a ticket number or "temporary fix".

Keep it believable rather than a parody, and keep it working: aged style, not broken behaviour. Each function should still be short enough to fit on a slide.

After committing A:

1. Enable the module and exercise every hook.
2. Collect every deprecation notice the module triggers on this core version and save the exact text, unparaphrased, to `docs/deprecations-baseline.md` in the module, with a note on how each was produced. Commit it on `main` as its own commit after A. I quote these on stage. Core raises these notices with `@trigger_error()`, so they never reach dblog: capture them with your own `set_error_handler()`. If the module triggers none, say so plainly; that is a finding I need.
3. Tell me which hooks are actually firing, including the conditional hook in the inventory. Don't fix anything; report it.

Then stop and wait for my review.

## Phase 2: Rector trial, on a throwaway branch

When I say go, branch from `demo-a` as `rector-trial`. Add `palantirnet/drupal-rector` as a dev dependency on that branch only. Follow its README, which requires two passes: first the deprecation sets with its sample `rector.php`, then `HookConvertRector` alone with `--config=vendor/palantirnet/drupal-rector/rector-hook-convert.php --clear-cache`. Without `--clear-cache` the second pass changes nothing and still reports success. Commit each pass separately, without hand edits.

Then report: which hooks it converted, which it skipped and why, what it generated that you'd change by hand, and anything it broke. Don't merge this branch. I'm deciding from your report whether Rector gets a live demo or one slide, so an honest account of its rough edges is more useful than a clean result.

When you switch back to `main`, run `ddev composer install && ddev drush cr`, because `vendor/` isn't in git and still holds the Rector packages.

## Phase 3: stages B through J

Build the stacked branches described under "Branch layout", one commit per letter, in order. Ask me before starting B: I may write that one myself, since it's the conversion I walk through line by line.

## Inventory

| Stage | Hook or function                                  | Baseline location           | What it does                                                                                                                                                     | What the stage does                                                                                            |
| ----- | ------------------------------------------------- | --------------------------- | ---------------------------------------------------------------------------------------------------------------------------------------------------------------- | -------------------------------------------------------------------------------------------------------------- |
| B     | `hook_form_alter`                                 | `.module`                   | Adds help text under the title on the article node form; reads the current user with `\Drupal::currentUser()`                                                    | Move to a form hooks class with the current user injected                                                      |
| C     | `hook_preprocess_node`                            | `.module`                   | Adds a reading-time element to `blog_post` nodes in the `full` view mode                                                                                         | Move to a theme hooks class                                                                                    |
| D     | `hook_entity_presave`                             | `.module`                   | One function doing three unrelated jobs: trims whitespace from node titles, fills an empty article `field_description` from `field_content`, logs which editor saved | Split into three methods, each with its own attribute and a name that says what it does                        |
| D     | `_jddoesdev_core_clean_title()`                   | `.module`                   | Helper called only by the presave                                                                                                                                | Becomes a private method on the hook class                                                                     |
| E     | `hook_cron`                                       | `.module`                   | Purges rows from a table this module no longer creates; guarded so it does nothing rather than erroring                                                          | Delete it. The point is that some hooks should be removed, not converted                                       |
| F     | `hook_module_implements_alter`                    | `.module`                   | Moves this module's form alter to the end of the list                                                                                                            | Delete the function; put `order: Order::Last` on the `#[Hook]` attribute from stage B                          |
| G     | `hook_requirements`                               | `.install`                  | Adds a runtime warning to the status report when error display is verbose, using the old `REQUIREMENT_*` constants                                               | Convert the runtime check to `#[Hook('runtime_requirements')]` with the `RequirementSeverity` enum; leave the rest of `.install` alone |
| H     | `hook_token_info`, `hook_tokens`                  | `jddoesdev_core.tokens.inc` | Defines one custom site token, `[site:jddoesdev-copyright]`                                                                                                      | Move both to a token hooks class and delete the include file                                                   |
| I     | `hook_help`, `hook_page_attachments`              | `.module`                   | Ordinary boilerplate with correct `Implements` docblocks; the help page needs the core Help module                                                               | Convert. If the Rector trial handled these cleanly, use its output and say so in the commit body               |
| I     | `hook_mail_alter`                                 | `.module`                   | Appends a footer to outgoing mail; has no `Implements` docblock                                                                                                  | Convert by hand                                                                                                |
| J     | `hook_node_access` wrapped in an `if`             | `.module`                   | Forbids deleting project nodes without `administer nodes`; only defined when a condition is true at file load                                                    | Rewrite as an unconditional hook method with the condition checked inside it                                   |
| J     | `hook_install`, `hook_update_N`, `hook_uninstall` | `.install`                  | Sets a default value; one old update                                                                                                                             | Leave procedural. Add a one-line comment saying these have no attribute equivalent                             |

Notes on specific entries:

- **F, ordering.** I need to show the order, not just assert it. Display the order in which implementations of that hook run before and after the stage, and tell me how to reproduce it. On this site `Order::Last` does not put the hook last: `eca_form` also declares `Order::Last` and has module weight 1, so its ordering is applied after ours. The hook is 21st of 23 both before and after F, and 13th with no ordering at all. Show all three states.
- **J, the conditional hook.** Conditionally defined hook functions are documented as unsupported since object-oriented hooks arrived. On 11.4.8 core finds procedural hooks by reading the file text, so the function is registered even inside an `if`. When the condition is true at load, it fires normally. When it is false, every node page returns 500 with `InvalidArgumentException: Class "jddoesdev_core_node_access" does not exist.`
- **After J**, if the `.module` file has nothing left in it, delete it in stage J and say so in the message. The `.install` file stays.

## When you finish

Give me a short table: branch, one-line summary, and the on-site check for each stage. Then list anything in this brief that turned out to be wrong for the installed core version, and anything you were unsure about. I'd rather hear about a doubt now than discover it in front of an audience.

## References

Use these to check behaviour; prefer the installed core's own source when they disagree.

- Object-oriented hooks, the original change record: https://www.drupal.org/node/3442349
- The `.module` file extension deprecation: https://www.drupal.org/node/3619765
- Manual conversion walkthrough and class naming: https://www.drupal.org/docs/develop/creating-modules/converting-from-module-file-to-an-object-oriented-class-method
- Ordering with the `order` parameter: https://www.drupal.org/node/3493962
- Replacing `hook_module_implements_alter`: https://www.drupal.org/node/3496788
- Preprocess hooks in classes: https://www.drupal.org/node/3496491
- Runtime requirements hook: https://www.drupal.org/node/3490851
- `hook_requirements` without `#[LegacyRequirementsHook]`: https://www.drupal.org/node/3549685
- Include files and `hook_hook_info`: https://www.drupal.org/node/3489765
- Drupal 12 and hooks outside the main extension file: https://www.drupal.org/node/3613767
