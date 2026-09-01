<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Document extends Model
{
    protected $fillable = [
        'title', 'description', 'file_path', 'file_name', 'file_type',
        'file_size', 'category_id', 'access_level', 'uploaded_by', 'user_id',
    ];

    public function category()
    {
        return $this->belongsTo(DocumentCategory::class, 'category_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getFileSizeFormattedAttribute(): string
    {
        $bytes = $this->file_size;
        if ($bytes >= 1048576) return round($bytes / 1048576, 1) . ' MB';
        if ($bytes >= 1024) return round($bytes / 1024, 0) . ' KB';
        return $bytes . ' B';
    }

    public function getFileIconColor(): string
    {
        $ext = strtolower(pathinfo($this->file_name, PATHINFO_EXTENSION));
        return match ($ext) {
            'pdf' => 'var(--danger)',
            'doc', 'docx' => 'var(--blue-accent)',
            'xls', 'xlsx' => 'var(--green-accent)',
            'jpg', 'jpeg', 'png' => 'var(--purple)',
            default => 'var(--text-secondary)',
        };
    }

    public function getFileIconBg(): string
    {
        $ext = strtolower(pathinfo($this->file_name, PATHINFO_EXTENSION));
        return match ($ext) {
            'pdf' => 'var(--danger-bg)',
            'doc', 'docx' => 'var(--info-bg)',
            'xls', 'xlsx' => 'var(--success-bg)',
            'jpg', 'jpeg', 'png' => 'var(--purple-bg)',
            default => 'rgba(15,23,42,.06)',
        };
    }
}
