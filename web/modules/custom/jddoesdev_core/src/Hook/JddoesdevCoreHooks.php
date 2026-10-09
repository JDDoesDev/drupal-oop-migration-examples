<?php

namespace Drupal\jddoesdev_core\Hook;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\StringTranslation\StringTranslationTrait;
/**
 * Hook implementations for jddoesdev_core.
 */
class JddoesdevCoreHooks
{
    use StringTranslationTrait;
    /**
     * Implements hook_help().
     */
    #[Hook('help')]
    public function help($route_name, \Drupal\Core\Routing\RouteMatchInterface $route_match)
    {
        switch ($route_name) {
            case 'help.page.jddoesdev_core':
                return '<p>' . $this->t('Site-wide tweaks and glue code for the JD Does Dev site.') . '</p>';
        }
    }
    /**
     * Implements hook_form_alter().
     */
    #[Hook('form_alter')]
    public function formAlter(&$form, \Drupal\Core\Form\FormStateInterface $form_state, $form_id)
    {
        if ($form_id == 'node_article_form' || $form_id == 'node_article_edit_form') {
            $name = \Drupal::currentUser()->getDisplayName();
            $form['title']['widget'][0]['value']['#description'] = $this->t('@name, keep article titles under 70 characters so they fit in search results.', [
                '@name' => $name,
            ]);
        }
    }
    /**
     * Implements hook_preprocess_node().
     */
    #[Hook('preprocess_node')]
    public function preprocessNode(array &$variables)
    {
        $node = $variables['node'];
        if ($node->bundle() != 'blog_post' || $variables['view_mode'] != 'full') {
            return;
        }
        $text = strip_tags((string) $node->get('field_content')->value);
        $minutes = max(1, (int) ceil(str_word_count($text) / 200));
        $variables['content']['jddoesdev_reading_time'] = [
            '#markup' => '<p class="reading-time">' . $this->t('@count min read', [
                '@count' => $minutes,
            ]) . '</p>',
            '#weight' => -100,
        ];
    }
    /**
     * Implements hook_cron().
     */
    #[Hook('cron')]
    public function cron()
    {
        // Temporary fix: purge the old import log until the table is gone everywhere.
        // We stopped creating it in 2018 but some environments still have it.
        $database = \Drupal::database();
        if (!$database->schema()->tableExists('jddoesdev_import_log')) {
            return;
        }
        $database->delete('jddoesdev_import_log')->condition('created', \Drupal::time()->getRequestTime() - 30 * 86400, '<')->execute();
    }
    /**
     * Implements hook_page_attachments().
     */
    #[Hook('page_attachments')]
    public function pageAttachments(array &$attachments)
    {
        $attachments['#attached']['html_head'][] = [
            [
                '#tag' => 'meta',
                '#attributes' => [
                    'name' => 'built-by',
                    'content' => 'jddoesdev',
                ],
            ],
            'jddoesdev_core_built_by',
        ];
    }
    /**
     * Implements hook_node_access().
     */
    #[Hook('node_access')]
    public function nodeAccess($node, $op, $account)
    {
        if ($op == 'delete' && $node->bundle() == 'project' && !$account->hasPermission('administer nodes')) {
            return \Drupal\Core\Access\AccessResult::forbidden()->cachePerPermissions();
        }
        return \Drupal\Core\Access\AccessResult::neutral();
    }
}
