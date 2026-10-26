<?php

namespace Modules\Ecommerce\Services;

class EffectiveStorefrontTheme
{
    public const REQUEST_ATTRIBUTE = 'ecommerce.storefront_theme_context';

    public function __construct(
        private DemoStorefrontProfile $demoProfile,
        private ThemeRegistry $themes
    ) {
    }

    public function resolve(?string $savedTheme): array
    {
        $savedTheme = $this->themes->resolve($savedTheme ?: 'default');
        $theme = $this->themes->resolve($this->demoProfile->effectiveTheme($savedTheme));

        return [
            'saved_theme' => $savedTheme,
            'demo_profile' => $this->demoProfile->selected(),
            'theme' => $theme,
            'home_view' => $this->themes->view($theme, 'home'),
            'header_view' => $this->themes->headerView($theme),
            'stylesheet' => $this->themes->stylesheet($theme),
        ];
    }

    public function current(): ?array
    {
        if (!app()->bound('request')) {
            return null;
        }

        $context = request()->attributes->get(self::REQUEST_ATTRIBUTE);

        return is_array($context) ? $context : null;
    }

    public function remember(array $context): void
    {
        if (app()->bound('request')) {
            request()->attributes->set(self::REQUEST_ATTRIBUTE, $context);
        }
    }
}
