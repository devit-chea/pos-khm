<?php

namespace Modules\Ecommerce\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Contracts\Session\Session;

class DemoStorefrontProfile
{
    public function enabled(): bool
    {
        return (bool) config('ecommerce.demo_mode', false);
    }

    public function profiles(): array
    {
        return (array) config('ecommerce.demo_profiles', []);
    }

    public function selected(): ?string
    {
        $session = $this->session();
        if (!$this->enabled() || !$session) {
            return null;
        }

        $slug = $session->get($this->sessionKey());

        return is_string($slug) && array_key_exists($slug, $this->profiles()) ? $slug : null;
    }

    public function select(string $slug): bool
    {
        if (!$this->enabled() || !array_key_exists($slug, $this->profiles())) {
            return false;
        }

        if ($slug === 'default') {
            $this->session()?->forget($this->sessionKey());
        } else {
            $this->session()?->put($this->sessionKey(), $slug);
        }

        return true;
    }

    public function effectiveTheme(?string $savedTheme): string
    {
        $selected = $this->selected();

        return $selected
            ? (string) ($this->profiles()[$selected]['theme'] ?? 'default')
            : (string) ($savedTheme ?: 'default');
    }

    public function active(): ?array
    {
        $slug = $this->selected();
        if (!$slug) {
            return null;
        }

        return ['slug' => $slug] + $this->profiles()[$slug];
    }

    public function categoryIds(): array
    {
        $profile = $this->active();
        if (!$profile) {
            return [];
        }

        return $this->categoryIdsFor($profile['slug']);
    }

    public function categoryIdsFor(string $slug): array
    {
        $profile = $this->profiles()[$slug] ?? null;
        $roots = array_map(
            fn ($value) => mb_strtolower(trim((string) $value)),
            array_values(array_filter((array) ($profile['category_roots'] ?? [])))
        );
        if (!$profile || $slug === 'default' || !$roots) {
            return [];
        }

        $categories = DB::table('categories')
            ->select('id', 'parent_id', 'name', 'slug')
            ->where('is_active', 1)
            ->get();

        $matched = $categories->filter(function ($category) use ($roots) {
            $slug = mb_strtolower(trim((string) ($category->slug ?? '')));

            return in_array($slug, $roots, true);
        })->pluck('id')->map(fn ($id) => (int) $id)->all();

        $ids = array_fill_keys($matched, true);
        do {
            $changed = false;
            foreach ($categories as $category) {
                $id = (int) $category->id;
                $parentId = (int) ($category->parent_id ?? 0);
                if ($parentId && isset($ids[$parentId]) && !isset($ids[$id])) {
                    $ids[$id] = true;
                    $changed = true;
                }
            }
        } while ($changed);

        return array_map('intval', array_keys($ids));
    }

    public function scopeProducts(Builder $query): Builder
    {
        $profile = $this->active();
        if (!$profile || $profile['slug'] === 'default') {
            return $query;
        }

        $ids = $this->categoryIds();

        return $query->whereIn('category_id', $ids ?: [-1]);
    }

    public function filterCategories(Collection $categories): Collection
    {
        $profile = $this->active();
        if (!$profile || $profile['slug'] === 'default') {
            return $categories;
        }

        $allowed = array_fill_keys($this->categoryIds(), true);

        return $categories->filter(fn ($category) => isset($allowed[(int) $category->id]))->values();
    }

    public function filterSliders(Collection $sliders): Collection
    {
        $profile = $this->active();
        $keys = array_values((array) ($profile['banner_keys'] ?? []));
        if (!$profile || $profile['slug'] === 'default' || !$keys) {
            return $sliders;
        }

        $filtered = $sliders->whereIn('title', $keys)->values();

        return $filtered->isNotEmpty() ? $filtered : collect();
    }

    public function sessionKey(): string
    {
        return (string) config('ecommerce.demo_session_key', 'ecommerce_demo_profile');
    }

    private function session(): ?Session
    {
        if (app()->bound('request') && request()->hasSession()) {
            return request()->session();
        }

        return app()->bound('session.store') ? app('session.store') : null;
    }
}
