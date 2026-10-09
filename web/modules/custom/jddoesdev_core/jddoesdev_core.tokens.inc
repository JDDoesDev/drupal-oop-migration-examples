<?php

/**
 * @file
 * Token hooks for jddoesdev_core.
 */

use Drupal\Core\Render\BubbleableMetadata;

/**
 * Implements hook_token_info().
 */
function jddoesdev_core_token_info() {
  $info['tokens']['site']['jddoesdev-copyright'] = array(
    'name' => t('Copyright line'),
    'description' => t('Copyright notice with the site name and current year.'),
  );
  return $info;
}

/**
 * Implements hook_tokens().
 */
function jddoesdev_core_tokens($type, $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata) {
  $replacements = [];
  if ($type == 'site' && isset($tokens['jddoesdev-copyright'])) {
    $site_name = \Drupal::config('system.site')->get('name');
    $year = date('Y', \Drupal::time()->getRequestTime());
    $replacements[$tokens['jddoesdev-copyright']] = '© 2016–' . $year . ' ' . $site_name;
    $bubbleable_metadata->addCacheTags(['config:system.site']);
  }
  return $replacements;
}
