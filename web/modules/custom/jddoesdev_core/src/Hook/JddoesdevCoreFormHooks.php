<?php

declare(strict_types=1);

namespace Drupal\jddoesdev_core\Hook;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Form hook implementations for jddoesdev_core.
 */
class JddoesdevCoreFormHooks {

  use StringTranslationTrait;

  public function __construct(
    protected readonly AccountInterface $currentUser,
  ) {}

  /**
   * Implements hook_form_alter().
   */
  #[Hook('form_alter')]
  public function formAlter(array &$form, FormStateInterface $form_state, string $form_id): void {
    if ($form_id == 'node_article_form' || $form_id == 'node_article_edit_form') {
      $name = $this->currentUser->getDisplayName();
      $form['title']['widget'][0]['value']['#description'] = $this->t('@name, keep article titles under 70 characters so they fit in search results.', ['@name' => $name]);
    }
  }

}
