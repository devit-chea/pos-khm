<?php

return [
    'name' => 'Ecommerce',
    'storefront_enabled_default' => true,

    /*
    | USER_VERIFIED is already the product's installation/demo boundary:
    | verified installations may mutate protected settings, while an
    | unverified installation is the public demo. Keep that meaning in one
    | config-backed place so storefront code never reads env() directly.
    */
    'demo_mode' => !filter_var(env('USER_VERIFIED', false), FILTER_VALIDATE_BOOLEAN),
    'demo_session_key' => 'ecommerce_demo_profile',

    /*
    | Demo profiles are explicit storefront compositions. Category roots are
    | exact slugs (plus their descendants) and banner titles are stable seed
    | keys, so a profile can never acquire content through fuzzy matching.
    */
    'demo_profiles' => [
        'default' => [
            'label' => 'Default Store',
            'theme' => 'default',
            'icon' => 'storefront',
            'category_roots' => [],
            'banner_keys' => [],
            'sections' => [],
            'headline' => 'SalePro Storefront',
        ],
        'electronics' => [
            'label' => 'Electronics Store',
            'theme' => 'electronics',
            'icon' => 'devices',
            'category_roots' => ['electronics'],
            'banner_keys' => [
                'hero' => 'demo:electronics:hero',
                'promo' => 'demo:electronics:promo',
            ],
            'sections' => ['hero', 'departments', 'featured', 'promo', 'new_arrivals', 'brands'],
            'catalog_limit' => 30,
            'headline' => 'Featured Electronics',
        ],
        'fashion' => [
            'label' => 'Fashion Store',
            'theme' => 'fashion',
            'icon' => 'styler',
            'category_roots' => ['fashion', 'beauty-personal-care'],
            'category_showcase' => ['bags-accessories', 'footwear', 'mens-apparel', 'skin-care'],
            'banner_keys' => [
                'hero' => 'demo:fashion:hero',
                'promo' => 'demo:fashion:promo',
            ],
            'sections' => ['hero', 'collections', 'new_arrivals', 'promo', 'shop_the_edit', 'brands'],
            'catalog_limit' => 30,
            'headline' => 'Shop the Edit',
        ],
        'grocery' => [
            'label' => 'Grocery Store',
            'theme' => 'grocery',
            'icon' => 'local_grocery_store',
            'category_roots' => ['food-grocery'],
            'banner_keys' => [
                'hero' => 'demo:grocery:hero',
                'promo' => 'demo:grocery:promo',
            ],
            'sections' => ['hero', 'services', 'categories', 'popular', 'promo', 'daily_essentials', 'brands'],
            'catalog_limit' => 35,
            'headline' => 'Fresh Groceries',
        ],
    ],
];
