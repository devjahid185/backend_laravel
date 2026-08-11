<?php

namespace App\Models;

use App\Support\MediaUrl;
use Illuminate\Database\Eloquent\Model;

class RiderDocument extends Model
{
    protected $guarded = [];

    protected $appends = ['file_url', 'type_bn', 'status_bn'];

    public function rider()
    {
        return $this->belongsTo(Rider::class);
    }

    public function getFileUrlAttribute(): ?string
    {
        return MediaUrl::toUrl($this->file_path);
    }

    public function getTypeBnAttribute(): string
    {
        return [
            'nid_front' => 'এনআইডি সামনে',
            'nid_back' => 'এনআইডি পিছনে',
            'selfie' => 'সেলফি যাচাই',
            'driving_license' => 'ড্রাইভিং লাইসেন্স',
            'vehicle_paper' => 'গাড়ির কাগজ',
            'bank_mfs' => 'ব্যাংক/মোবাইল ব্যাংকিং',
        ][$this->type] ?? $this->type;
    }

    public function getStatusBnAttribute(): string
    {
        return [
            'pending' => 'পর্যালোচনায়',
            'approved' => 'অনুমোদিত',
            'rejected' => 'বাতিল',
        ][$this->status] ?? $this->status;
    }
}
