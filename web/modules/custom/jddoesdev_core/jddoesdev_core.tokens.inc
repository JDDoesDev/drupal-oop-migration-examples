<?php

/**
 * @file
 * Token hooks for jddoesdev_core.
 */
use Drupal\Core\Hook\Attribute\LegacyHook;
use Drupal\jddoesdev_core\Hook\JddoesdevCoreTokensHooks;
use Drupal\Core\Render\BubbleableMetadata;

/**
 * Implements hook_token_info().
 */
#[LegacyHook]
function jddoesdev_core_token_info()
{
    return \Drupal::service(JddoesdevCoreTokensHooks::class)->tokenInfo();
}

/**
 * Implements hook_tokens().
 */
#[LegacyHook]
function jddoesdev_core_tokens($type, $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata)
{
    return \Drupal::service(JddoesdevCoreTokensHooks::class)->tokens($type, $tokens, $data, $options, $bubbleable_metadata);
}
