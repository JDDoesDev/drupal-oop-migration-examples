<?php

declare(strict_types=1);

namespace Drupal\jddoesdev_core\Hook;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\Requirement\RequirementSeverity;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Requirements hook implementations for jddoesdev_core.
 */
class JddoesdevCoreRequirementsHooks {

  use StringTranslationTrait;

  public function __construct(
    protected readonly ConfigFactoryInterface $configFactory,
  ) {}

  /**
   * Implements hook_runtime_requirements().
   */
  #[Hook('runtime_requirements')]
  public function runtime(): array {
    $requirements = [];
    // Added after the 2019 incident where stack traces showed on live.
    if ($this->configFactory->get('system.logging')->get('error_level') == 'verbose') {
      $requirements['jddoesdev_core_error_level'] = [
        'title' => $this->t('Error message display'),
        'value' => $this->t('Verbose'),
        'description' => $this->t('Error messages, including backtraces, are shown to site visitors. Switch this off on production.'),
        'severity' => RequirementSeverity::Warning,
      ];
    }
    return $requirements;
  }

}
