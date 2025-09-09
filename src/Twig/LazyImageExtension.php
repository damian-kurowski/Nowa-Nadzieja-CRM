<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Twig extension for lazy loading images
 */
class LazyImageExtension extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction('lazy_img', [$this, 'lazyImage'], ['is_safe' => ['html']]),
        ];
    }

    /**
     * Generate lazy loading image HTML
     */
    public function lazyImage(
        string $src, 
        string $alt = '', 
        array $attributes = [], 
        ?string $placeholder = null
    ): string {
        // Default attributes
        $defaultAttributes = [
            'loading' => 'lazy',
            'data-src' => $src,
            'alt' => $alt,
            'class' => 'lazy-image'
        ];
        
        // Add placeholder or transparent pixel
        if ($placeholder) {
            $defaultAttributes['src'] = $placeholder;
        } else {
            // 1x1 transparent pixel
            $defaultAttributes['src'] = 'data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1 1"%3E%3C/svg%3E';
        }
        
        // Merge with custom attributes
        $allAttributes = array_merge($defaultAttributes, $attributes);
        
        // Add existing classes to lazy-image class
        if (isset($attributes['class'])) {
            $allAttributes['class'] = 'lazy-image ' . $attributes['class'];
        }
        
        // Build HTML attributes
        $htmlAttributes = [];
        foreach ($allAttributes as $key => $value) {
            if ($value !== null && $value !== false) {
                $htmlAttributes[] = sprintf('%s="%s"', htmlspecialchars($key), htmlspecialchars($value));
            }
        }
        
        return sprintf('<img %s>', implode(' ', $htmlAttributes));
    }
}