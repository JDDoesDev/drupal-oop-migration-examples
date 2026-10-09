# Deprecations triggered by jddoesdev_core at demo-a

Drupal 11.4.8, PHP 8.4. Captured on 2026-10-09.

All four notices below are raised with `@trigger_error()`, so they do **not**
appear in dblog or on screen. They were captured by registering a
`set_error_handler()` for `E_USER_DEPRECATED` inside a `drush php:script`,
then doing the action listed under each notice. Messages are copied exactly.

## 1. hook_module_implements_alter()

```
jddoesdev_core_module_implements_alter without a #[LegacyModuleImplementsAlter] attribute is deprecated in drupal:11.2.0 and removed in drupal:12.0.0. See https://www.drupal.org/node/3496788
```

Produced by: enabling the module, and again on every container rebuild
(`drupal_flush_all_caches()`, which is what `drush cr` does). Raised by
`HookCollectorPass::addProceduralImplementation()`.

## 2. hook_requirements()

```
jddoesdev_core_requirements without a #[LegacyRequirementsHook] attribute is deprecated in drupal:11.3.0 and removed in drupal:13.0.0. See https://www.drupal.org/node/3549685
```

Produced by: enabling the module, and again on every container rebuild.
Raised by `HookCollectorPass::addProceduralImplementation()`.

## 3. REQUIREMENT_WARNING severity (integer instead of enum)

```
Calling Drupal\system\Element\StatusReportPage::preRenderCounters() with an array of $requirements with 'severity' with values not of type Drupal\Core\Extension\Requirement\RequirementSeverity enums is deprecated in drupal:11.2.0 and is required in drupal:12.0.0. See https://www.drupal.org/node/3410939
```

Produced by: rendering the status report (`/admin/reports/status`) as admin.
Using the `REQUIREMENT_WARNING` constant itself raises nothing. The constant
is only marked `@deprecated` in a docblock. The notice comes from core
converting integer severities to the enum when the page renders.

**This notice is not ours alone.** The message doesn't name a module, and
it fires if *any* requirement on the page has an integer severity. On this
site, six runtime requirements do at demo-a: ours
(`jddoesdev_core_error_level`, `1`) and five from contrib modules:
`metatag_schema` (`-1`), `automatic_updates_status_check` (`0`),
`webform_libraries` (`1`), `webform_email` (`0`) and
`webform_file_private` (`1`). Converting our requirement to the enum
(demo-g) does not make the notice go away on this site. To quote it as
caused by jddoesdev_core, say that our `REQUIREMENT_WARNING` is one of the
values that triggers it.

## 4. Hooks in jddoesdev_core.tokens.inc

```
Autoloading hooks in the file (modules/custom/jddoesdev_core/jddoesdev_core.tokens.inc) is deprecated in drupal:11.2.0 and is removed from drupal:12.0.0. Move the functions in this file to either the .module file or other appropriate location. See https://www.drupal.org/node/3489765
```

Produced by: the first token replacement in a request,
`\Drupal::token()->replace('[site:jddoesdev-copyright]')`. Raised by
`ModuleHandler::getHookImplementationList()`.

## Not deprecated

Ordinary procedural hooks in `jddoesdev_core.module` (form_alter,
preprocess_node, entity_presave, cron, help, page_attachments, mail_alter,
node_access) raise **no** deprecation notice on 11.4.8.
