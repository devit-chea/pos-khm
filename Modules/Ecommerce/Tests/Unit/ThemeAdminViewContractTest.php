<?php

namespace Modules\Ecommerce\Tests\Unit;

use PHPUnit\Framework\TestCase;

class ThemeAdminViewContractTest extends TestCase
{
    private function modulePath(string $path): string
    {
        return dirname(__DIR__, 2) . '/' . ltrim($path, '/');
    }

    public function test_theme_admin_views_do_not_shadow_shared_backend_theme_variable(): void
    {
        $themeSelection = (string) file_get_contents($this->modulePath('Resources/views/backend/settings/theme.blade.php'));
        $generalSettings = (string) file_get_contents($this->modulePath('Resources/views/backend/settings/index.blade.php'));

        $this->assertStringNotContainsString('@foreach($themes as $theme)', $themeSelection);
        $this->assertStringNotContainsString('@foreach($themes as $theme)', $generalSettings);
        $this->assertStringContainsString('@foreach($themes as $themeOption)', $themeSelection);
        $this->assertStringContainsString('@foreach($themes as $themeOption)', $generalSettings);
    }
}
