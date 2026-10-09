<?php

declare(strict_types=1);

namespace Drupal\jddoesdev_core\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\State\StateInterface;

/**
 * Mail hook implementations for jddoesdev_core.
 */
class JddoesdevCoreMailHooks {

  public function __construct(
    protected readonly StateInterface $state,
  ) {}

  /**
   * Implements hook_mail_alter().
   */
  #[Hook('mail_alter')]
  public function mailAlter(array &$message): void {
    $footer = $this->state->get('jddoesdev_core.mail_footer');
    if (!$footer) {
      return;
    }
    foreach ($message['body'] as $line) {
      if (str_contains((string) $line, $footer)) {
        return;
      }
    }
    $message['body'][] = $footer;
  }

}
