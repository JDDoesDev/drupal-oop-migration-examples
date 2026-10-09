<?php

declare(strict_types=1);

namespace Drupal\jddoesdev_core\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Theme hook implementations for jddoesdev_core.
 */
class JddoesdevCoreThemeHooks {

  use StringTranslationTrait;

  /**
   * Implements hook_preprocess_node().
   */
  #[Hook('preprocess_node')]
  public function preprocessNode(array &$variables): void {
    $node = $variables['node'];
    if ($node->bundle() != 'blog_post' || $variables['view_mode'] != 'full') {
      return;
    }
    $text = strip_tags((string) $node->get('field_content')->value);
    $minutes = max(1, (int) ceil(str_word_count($text) / 200));
    $variables['content']['jddoesdev_reading_time'] = [
      '#markup' => '<p class="reading-time">' . $this->t('@count min read', ['@count' => $minutes]) . '</p>',
      '#weight' => -100,
    ];
  }

  /**
   * Implements hook_page_attachments().
   */
  #[Hook('page_attachments')]
  public function pageAttachments(array &$attachments): void {
    $attachments['#attached']['html_head'][] = [
      [
        '#tag' => 'meta',
        '#attributes' => ['name' => 'built-by', 'content' => 'jddoesdev'],
      ],
      'jddoesdev_core_built_by',
    ];
  }

}
