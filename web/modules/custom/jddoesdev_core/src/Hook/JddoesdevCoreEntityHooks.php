<?php

declare(strict_types=1);

namespace Drupal\jddoesdev_core\Hook;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\node\NodeInterface;

/**
 * Entity hook implementations for jddoesdev_core.
 */
class JddoesdevCoreEntityHooks {

  public function __construct(
    protected readonly AccountInterface $currentUser,
    protected readonly LoggerChannelFactoryInterface $loggerFactory,
    protected readonly ModuleHandlerInterface $moduleHandler,
  ) {}

  /**
   * Implements hook_entity_presave().
   *
   * Tidies whitespace in node titles.
   */
  #[Hook('entity_presave')]
  public function trimNodeTitle(EntityInterface $entity): void {
    if (!$entity instanceof NodeInterface) {
      return;
    }
    $entity->setTitle($this->cleanTitle((string) $entity->getTitle()));
  }

  /**
   * Implements hook_entity_presave().
   *
   * Fills an empty article description from the article content.
   */
  #[Hook('entity_presave')]
  public function fillArticleDescription(EntityInterface $entity): void {
    if (!$entity instanceof NodeInterface || $entity->bundle() != 'article') {
      return;
    }
    if ($entity->get('field_description')->isEmpty() && !$entity->get('field_content')->isEmpty()) {
      $text = strip_tags($entity->get('field_content')->value);
      $entity->set('field_description', Unicode::truncate(trim($text), 200, TRUE, TRUE));
    }
  }

  /**
   * Implements hook_entity_presave().
   *
   * Logs which editor saved a node.
   */
  #[Hook('entity_presave')]
  public function logNodeEditor(EntityInterface $entity): void {
    if (!$entity instanceof NodeInterface) {
      return;
    }
    $this->loggerFactory->get('jddoesdev_core')->notice('%title saved by %user.', [
      '%title' => $entity->label(),
      '%user' => $this->currentUser->getAccountName(),
    ]);
  }

  /**
   * Implements hook_node_access().
   */
  #[Hook('node_access')]
  public function nodeAccess(NodeInterface $node, string $op, AccountInterface $account): AccessResultInterface {
    // Sites that still have the old jddoesdev_access module enabled get their
    // project access rules from there. See #2954.
    if ($this->moduleHandler->moduleExists('jddoesdev_access')) {
      return AccessResult::neutral();
    }
    if ($op == 'delete' && $node->bundle() == 'project' && !$account->hasPermission('administer nodes')) {
      return AccessResult::forbidden()->cachePerPermissions();
    }
    return AccessResult::neutral();
  }

  /**
   * Tidies whitespace in a node title.
   */
  private function cleanTitle(string $title): string {
    return preg_replace('/\s+/', ' ', trim($title));
  }

}
