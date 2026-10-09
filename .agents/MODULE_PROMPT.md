# Build brief: the `jddoesdev_core` demo module

You are building a small custom Drupal module that I will use on stage. Read this whole brief before touching anything, then start with the "Before you write code" section.

## What this is for

I'm giving a 45-minute conference talk on 13 November 2026 called "The Hook Migration". It teaches people to convert procedural hook implementations (`mymodule_form_alter()` in a `.module` file) to attribute-based hook classes (`#[Hook('form_alter')]` on a method in `src/Hook`), one hook at a time.

The module is the teaching prop. It pretends to be a ten-year-old catch-all module that collected every hook and preprocess that didn't fit anywhere else. I will step through its git history on a projector, one commit per teaching point, with a live site beside it to show each hook still fires.

That purpose drives every decision below:

- The audience includes beginners, and they read diffs from the back of a room. Each commit's diff has to be small and about one thing.
- The git history is the deliverable as much as the final code. A correct module with a messy history is a failure.
- I have to explain every line and answer questions about it, so plain and obvious beats clever.

## Environment

- This repo is a Drupal CMS site installed with the Forma site template and its default content, on the latest stable Drupal 11.
- The module goes in `web/modules/custom/jddoesdev_core` (adjust if the docroot differs).
- Use whatever local tooling the repo already has. Check for `.ddev` and use `ddev drush` if it's there.

## Before you write code

Inspect the site and report back to me. Do not start commit A until I reply.

1. The exact Drupal core version installed.
2. The node bundles and their fields, with machine names. I expect an article-like type and a portfolio or project type, but confirm it.
3. How a single article page is rendered: through a node template that `hook_preprocess_node` can affect, or through Canvas in a way that bypasses it.
4. Whether the Token module (or anything else that makes a custom token visible in the UI) is installed.
5. For each inventory entry below, whether it can have an effect I can see or check on this site. If one can't, propose a substitute that teaches the same point and wait for my answer.

## Rules that apply to every commit

**Check the installed core, not your memory.** The hook attribute APIs changed across 11.1, 11.2 and 11.3. Before using any attribute, enum or hook name from this brief, confirm it exists in the installed version by reading `core/lib/Drupal/Core/Hook/` and `core/core.api.php`. If something in this brief doesn't match the installed core, stop and tell me rather than working around it. A wrong claim on stage is worse than a late module.

**No backwards-compatibility shims.** Set `core_version_requirement: ^11.3 || ^12`. Do not add `#[LegacyHook]`, `#[LegacyModuleImplementsAlter]`, `#[LegacyRequirementsHook]` or a `services.yml` entry for hook classes. The talk covers shims on a slide; the code should show the simple path.

**One teaching point per commit.** Change only what that commit's row in the inventory describes. If you notice something else worth fixing, leave it and mention it in your report. Unrelated tidying makes the diff harder to read on stage.

**Every commit leaves the site working.** After each commit: rebuild caches, confirm no new errors in the log, and confirm the hook that commit touched still fires. Say how you confirmed it in the commit body. Where the effect is visible on the site, tell me the URL and what to look for, because I'll repeat that check live.

**Commit format.** Subject: `Demo X: <what changed>`, where X is the letter. Body: two or three sentences on what changed and how it was verified. Tag each commit `demo-a` through `demo-j` so I can jump between them.

**Stay inside the module.** Don't change site configuration, other modules, or `composer.json` without asking. The one exception is the Rector step, which has its own section.

**Converted code follows Drupal coding standards.** Name hook classes `JddoesdevCore{Group}Hooks`, grouped by what they deal with (form, theme, entity, token and so on), in the `Drupal\jddoesdev_core\Hook` namespace. Use constructor injection in place of `\Drupal::` calls. Add `declare(strict_types=1);` and parameter and return types to new classes.

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
2. Collect every deprecation notice the module triggers on this core version and save the exact text, unparaphrased, to `docs/deprecations-baseline.md` in the module, with a note on how each was produced. I quote these on stage. If the module triggers none, say so plainly; that is a finding I need.
3. Tell me which hooks are actually firing. I expect at least one may already be dead on this version (see the conditional hook in the inventory). Don't fix it; report it.

Then stop and wait for my review.

## Phase 2: Rector trial, on a throwaway branch

When I say go, branch from `demo-a` as `rector-trial`. Add `palantirnet/drupal-rector` as a dev dependency on that branch only, run its hook conversion rule against the module, and commit whatever it produces without hand edits.

Then report: which hooks it converted, which it skipped and why, what it generated that you'd change by hand, and anything it broke. Don't merge this branch. I'm deciding from your report whether Rector gets a live demo or one slide, so an honest account of its rough edges is more useful than a clean result.

## Phase 3: commits B through J

Work from `demo-a` on the main branch, one commit per letter, in order. Ask me before starting B: I may write that one myself, since it's the conversion I walk through line by line.

## Inventory

| Commit | Hook or function                                  | Baseline location           | What it does                                                                                                                            | What the commit does                                                                                           |
| ------ | ------------------------------------------------- | --------------------------- | --------------------------------------------------------------------------------------------------------------------------------------- | -------------------------------------------------------------------------------------------------------------- |
| B      | `hook_form_alter`                                 | `.module`                   | Adds help text to the article node form; reads the current user with `\Drupal::currentUser()`                                           | Move to a form hooks class with the current user injected                                                      |
| C      | `hook_preprocess_node`                            | `.module`                   | Adds a reading-time variable to article nodes                                                                                           | Move to a theme hooks class                                                                                    |
| D      | `hook_entity_presave`                             | `.module`                   | One function doing three unrelated jobs: trims whitespace from the title, fills an empty summary from the body, logs which editor saved | Split into three methods, each with its own attribute and a name that says what it does                        |
| D      | `_jddoesdev_core_clean_title()`                   | `.module`                   | Helper called only by the presave                                                                                                       | Becomes a private method on the hook class                                                                     |
| E      | `hook_cron`                                       | `.module`                   | Purges rows from a table this module no longer creates; guarded so it does nothing rather than erroring                                 | Delete it. The point is that some hooks should be removed, not converted                                       |
| F      | `hook_module_implements_alter`                    | `.module`                   | Moves this module's form alter to run last                                                                                              | Delete the function; put the ordering on the `#[Hook]` attribute from commit B                                 |
| G      | `hook_requirements`                               | `.install`                  | Adds a runtime warning to the status report, using the old `REQUIREMENT_*` constants                                                    | Convert the runtime check to the object-oriented runtime requirements hook; leave the rest of `.install` alone |
| H      | `hook_token_info`, `hook_tokens`                  | `jddoesdev_core.tokens.inc` | Defines one custom site token                                                                                                           | Move both to a token hooks class and delete the include file                                                   |
| I      | `hook_help`, `hook_page_attachments`              | `.module`                   | Ordinary boilerplate with correct `Implements` docblocks                                                                                | Convert. If the Rector trial handled these cleanly, use its output and say so in the commit body               |
| I      | `hook_mail_alter`                                 | `.module`                   | Appends a footer to outgoing mail; has no `Implements` docblock                                                                         | Convert by hand                                                                                                |
| J      | `hook_node_access` wrapped in an `if`             | `.module`                   | Function is only defined when a condition is true at file load                                                                          | Rewrite as an unconditional hook method with the condition checked inside it                                   |
| J      | `hook_install`, `hook_update_N`, `hook_uninstall` | `.install`                  | Sets a default value; one old update                                                                                                    | Leave procedural. Add a one-line comment saying these have no attribute equivalent                             |

Notes on specific entries:

- **F, ordering.** I need to show the order changed, not just assert it. Find a way to display the order in which implementations of that hook run before and after the commit, and tell me how to reproduce it.
- **J, the conditional hook.** Conditionally defined hook functions are documented as unsupported since object-oriented hooks arrived. Test whether the baseline version fires at all on this core. Either answer is useful to me; I need to know which is true.
- **After J**, if the `.module` file has nothing left in it, delete it in commit J and say so in the message. The `.install` file stays.

## When you finish

Give me a short table: tag, one-line summary, and the on-site check for each commit. Then list anything in this brief that turned out to be wrong for the installed core version, and anything you were unsure about. I'd rather hear about a doubt now than discover it in front of an audience.

## References

Use these to check behaviour; prefer the installed core's own source when they disagree.

- Object-oriented hooks, the original change record: https://www.drupal.org/node/3442349
- The `.module` file extension deprecation: https://www.drupal.org/node/3619765
- Manual conversion walkthrough and class naming: https://www.drupal.org/docs/develop/creating-modules/converting-from-module-file-to-an-object-oriented-class-method
- Ordering with the `order` parameter: https://www.drupal.org/node/3493962
- Replacing `hook_module_implements_alter`: https://www.drupal.org/node/3496788
- Preprocess hooks in classes: https://www.drupal.org/node/3496491
- Runtime requirements hook: https://www.drupal.org/node/3490851
- Include files and `hook_hook_info`: https://www.drupal.org/node/3489765
- Drupal 12 and hooks outside the main extension file: https://www.drupal.org/node/3613767
