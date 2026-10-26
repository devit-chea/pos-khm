<?php

namespace Modules\Ecommerce\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
class PageWidgets extends Model
{
     use Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'page_id',
        'order',
        'is_active',
        'product_category_title',
        'product_category_id',
        'product_category_type',
        'product_category_slider_loop',
        'product_category_slider_autoplay',
        'product_category_limit',
        'tab_product_collection_id',
        'tab_product_collection_type',
        'tab_product_collection_slider_loop',
        'tab_product_collection_slider_autoplay',
        'tab_product_collection_limit',
        'product_collection_title',
        'product_collection_id',
        'product_collection_type',
        'product_collection_slider_loop',
        'product_collection_slider_autoplay',
        'product_collection_limit',
        'category_slider_title',
        'category_slider_loop',
        'category_slider_autoplay',
        'category_slider_ids',
        'brand_slider_title',
        'brand_slider_loop',
        'brand_slider_autoplay',
        'brand_slider_ids',
        'three_c_banner_link1',
        'three_c_banner_image1',
        'three_c_banner_link2',
        'three_c_banner_image2',
        'three_c_banner_link3',
        'three_c_banner_image3',
        'two_c_banner_link1',
        'two_c_banner_image1',
        'two_c_banner_link2',
        'two_c_banner_image2',
        'one_c_banner_link1',
        'one_c_banner_image1',
        'text_title',
        'text_content',
        'slider_images',
        'slider_links',
        'settings',
    ];

    protected $casts = [
        'page_id' => 'integer',
        'order' => 'integer',
        'is_active' => 'boolean',
    ];

    public function getSettingsAttribute($value)
    {
        if (is_array($value)) {
            return $value;
        }

        if (is_object($value)) {
            return (array) $value;
        }

        if (is_string($value) && trim($value) !== '') {
            $decoded = json_decode($value, true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        // Fallback to old columns
        $settings = [];
        foreach ($this->fillable as $field) {
            if (!in_array($field, ['id', 'name', 'page_id', 'order', 'is_active', 'settings']) && isset($this->attributes[$field]) && $this->attributes[$field] !== null) {
                $settings[$field] = $this->attributes[$field];
            }
        }
        return $settings;
    }

    public function setSettingsAttribute($value): void
    {
        if (is_object($value)) {
            $value = (array) $value;
        } elseif (is_string($value)) {
            $decoded = json_decode($value, true);
            $value = is_array($decoded) ? $decoded : [];
        }

        $this->attributes['settings'] = json_encode(is_array($value) ? $value : []);
    }
}
