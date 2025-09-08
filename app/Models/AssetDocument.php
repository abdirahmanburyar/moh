<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssetDocument extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'asset_id',
        'meta_data',
        'document_type',
        'file_path',
        'file_name',
        'mime_type',
        'file_size',
        'description',
    ];

    protected $casts = [
        'meta_data' => 'array',
        'file_size' => 'integer',
    ];

    // Relationships
    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    // Helper methods
    public function getFileSizeInMB(): float
    {
        return $this->file_size ? round($this->file_size / 1024 / 1024, 2) : 0;
    }

    public function getFileSizeInKB(): float
    {
        return $this->file_size ? round($this->file_size / 1024, 2) : 0;
    }

    public function getFormattedFileSize(): string
    {
        if ($this->file_size >= 1024 * 1024) {
            return $this->getFileSizeInMB() . ' MB';
        } elseif ($this->file_size >= 1024) {
            return $this->getFileSizeInKB() . ' KB';
        } else {
            return $this->file_size . ' bytes';
        }
    }

    public function isImage(): bool
    {
        return str_starts_with($this->mime_type ?? '', 'image/');
    }

    public function isPDF(): bool
    {
        return $this->mime_type === 'application/pdf';
    }

    public function isDocument(): bool
    {
        $documentTypes = [
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/vnd.ms-powerpoint',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        ];
        
        return in_array($this->mime_type, $documentTypes);
    }

    public function getDownloadUrl(): string
    {
        return route('asset.documents.download', $this->id);
    }

    public function getPreviewUrl(): string
    {
        if ($this->isImage()) {
            return asset('storage/' . $this->file_path);
        }
        
        return $this->getDownloadUrl();
    }

    // Scopes
    public function scopeByType($query, $type)
    {
        return $query->where('document_type', $type);
    }

    public function scopeByMimeType($query, $mimeType)
    {
        return $query->where('mime_type', $mimeType);
    }

    public function scopeImages($query)
    {
        return $query->where('mime_type', 'like', 'image/%');
    }

    public function scopePDFs($query)
    {
        return $query->where('mime_type', 'application/pdf');
    }

    public function scopeDocuments($query)
    {
        $documentTypes = [
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/vnd.ms-powerpoint',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        ];
        
        return $query->whereIn('mime_type', $documentTypes);
    }

    public function scopeByAsset($query, $assetId)
    {
        return $query->where('asset_id', $assetId);
    }
}
