<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class DocumentCategory extends Model
{
    protected $fillable = ['name', 'slug', 'description', 'icon', 'color', 'documents_count'];

    protected static function booted(): void
    {
        static::creating(function (DocumentCategory $cat) {
            if (empty($cat->slug)) {
                $cat->slug = Str::slug($cat->name);
            }
        });
    }

    public function documents()
    {
        return $this->hasMany(Document::class, 'category_id');
    }

    public static function bootUpdating(): void
    {
        static::updating(function (DocumentCategory $cat) {
            if ($cat->isDirty('name') && !$cat->isDirty('slug')) {
                $cat->slug = Str::slug($cat->name);
            }
        });
    }
}
