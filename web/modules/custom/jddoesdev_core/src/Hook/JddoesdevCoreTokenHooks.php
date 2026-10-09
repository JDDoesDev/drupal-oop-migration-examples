<?php

declare(strict_types=1);

namespace Drupal\jddoesdev_core\Hook;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Token hook implementations for jddoesdev_core.
 */
class JddoesdevCoreTokenHooks {

  use StringTranslationTrait;

  public function __construct(
    protected readonly ConfigFactoryInterface $configFactory,
    protected readonly TimeInterface $time,
  ) {}

  /**
   * Implements hook_token_info().
   */
  #[Hook('token_info')]
  public function tokenInfo(): array {
    $info = [];
    $info['tokens']['site']['jddoesdev-copyright'] = [
      'name' => $this->t('Copyright line'),
      'description' => $this->t('Copyright notice with the site name and current year.'),
    ];
    return $info;
  }

  /**
   * Implements hook_tokens().
   */
  #[Hook('tokens')]
  public function tokens(string $type, array $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata): array {
    $replacements = [];
    if ($type == 'site' && isset($tokens['jddoesdev-copyright'])) {
      $site_name = $this->configFactory->get('system.site')->get('name');
      $year = date('Y', $this->time->getRequestTime());
      $replacements[$tokens['jddoesdev-copyright']] = '© 2016–' . $year . ' ' . $site_name;
      $bubbleable_metadata->addCacheTags(['config:system.site']);
    }
    return $replacements;
  }

}
