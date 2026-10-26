<?php

namespace Modules\Ecommerce\Services;

use Illuminate\Support\Facades\View;

class ThemeRegistry
{
    public function sanitize(?string $slug): string
    {
        return preg_replace('/[^a-z0-9_-]/i', '', (string) $slug) ?: 'default';
    }

    public function exists(string $slug): bool
    {
        return is_file($this->themePath($this->sanitize($slug), 'theme.json'));
    }

    public function resolve(?string $slug): string
    {
        $slug = $this->sanitize($slug);

        return $this->exists($slug) ? $slug : 'default';
    }

    public function view(string $slug, string $viewName): string
    {
        $slug = $this->resolve($slug);
        $themed = "ecommerce::frontend.themes.{$slug}.{$viewName}";

        return View::exists($themed) ? $themed : "ecommerce::frontend.{$viewName}";
    }

    public function headerView(string $slug): string
    {
        $themed = 'ecommerce::frontend.layout.header.header-' . $this->resolve($slug);

        return View::exists($themed)
            ? $themed
            : 'ecommerce::frontend.layout.header.header';
    }

    public function stylesheet(string $slug): ?string
    {
        $slug = $this->resolve($slug);
        if ($slug === 'default') {
            return null;
        }

        $manifest = $this->manifest($slug);
        $filename = $manifest['stylesheet'] ?? "style-{$slug}.css";
        $filename = basename((string) $filename);

        return is_file(module_path('Ecommerce', 'assets/css/' . $filename)) ? $filename : null;
    }

    public function manifest(string $slug): array
    {
        $path = $this->themePath($this->resolve($slug), 'theme.json');
        $decoded = json_decode((string) @file_get_contents($path), true);

        return is_array($decoded) ? $decoded : [];
    }

    private function themePath(string $slug, string $file): string
    {
        return module_path('Ecommerce', "Resources/views/frontend/themes/{$slug}/{$file}");
    }
}
