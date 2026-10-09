<?php

declare(strict_types=1);

namespace Drupal\jddoesdev_core\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Help hook implementations for jddoesdev_core.
 */
class JddoesdevCoreHelpHooks {

  use StringTranslationTrait;

  /**
   * Implements hook_help().
   */
  #[Hook('help')]
  public function help(?string $route_name, RouteMatchInterface $route_match): ?string {
    switch ($route_name) {
      case 'help.page.jddoesdev_core':
        return '<p>' . $this->t('Site-wide tweaks and glue code for the JD Does Dev site.') . '</p>';
    }
    return NULL;
  }

}
