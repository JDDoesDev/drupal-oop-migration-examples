<?php

namespace Drupal\jddoesdev_core\Hook;

use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\StringTranslation\StringTranslationTrait;
/**
 * Hook implementations for jddoesdev_core.
 */
class JddoesdevCoreTokensHooks
{
    use StringTranslationTrait;
    /**
     * Implements hook_token_info().
     */
    #[Hook('token_info')]
    public function tokenInfo()
    {
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
    public function tokens($type, $tokens, array $data, array $options, \Drupal\Core\Render\BubbleableMetadata $bubbleable_metadata)
    {
        $replacements = [
        ];
        if ($type == 'site' && isset($tokens['jddoesdev-copyright'])) {
            $site_name = \Drupal::config('system.site')->get('name');
            $year = date('Y', \Drupal::time()->getRequestTime());
            $replacements[$tokens['jddoesdev-copyright']] = '© 2016–' . $year . ' ' . $site_name;
            $bubbleable_metadata->addCacheTags([
                'config:system.site',
            ]);
        }
        return $replacements;
    }
}
