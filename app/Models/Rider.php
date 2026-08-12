<?php

namespace App\Models;

use App\Support\MediaUrl;
use Illuminate\Database\Eloquent\Model;

class Rider extends Model
{
    protected $guarded = [];

    protected $appends = [
        'profile_photo_url',
        'agreement_pdf_url',
        'vehicle_type_bn',
        'kyc_status_bn',
        'account_status_bn',
        'availability_status_bn',
    ];

    protected $casts = [
        'agreement_accepted' => 'boolean',
        'kyc_submitted_at' => 'datetime',
        'kyc_reviewed_at' => 'datetime',
        'agreement_accepted_at' => 'datetime',
        'last_location_at' => 'datetime',
        'commission_value' => 'decimal:2',
        'rating' => 'decimal:2',
        'wallet_balance' => 'decimal:2',
        'pending_payout' => 'decimal:2',
        'cash_in_hand' => 'decimal:2',
        'last_lat' => 'decimal:7',
        'last_lng' => 'decimal:7',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function documents()
    {
        return $this->hasMany(RiderDocument::class);
    }

    public function walletEntries()
    {
        return $this->hasMany(RiderWalletEntry::class);
    }

    public function orders()
    {
        return $this->hasMany(FoodOrder::class);
    }

    public function tickets()
    {
        return $this->hasMany(RiderSupportTicket::class);
    }

    public function ratings()
    {
        return $this->hasMany(RiderRating::class);
    }

    public function getProfilePhotoUrlAttribute(): ?string
    {
        return MediaUrl::toUrl($this->profile_photo);
    }

    public function getAgreementPdfUrlAttribute(): ?string
    {
        return MediaUrl::toUrl($this->agreement_pdf);
    }

    public function getVehicleTypeBnAttribute(): string
    {
        if (! $this->vehicle_type) {
            return '';
        }

        return [
            'cycle' => 'সাইকেল',
            'bike' => 'মোটরসাইকেল',
            'car' => 'গাড়ি',
        ][$this->vehicle_type] ?? $this->vehicle_type;
    }

    public function getKycStatusBnAttribute(): string
    {
        if (! $this->kyc_status) {
            return '';
        }

        return [
            'draft' => 'খসড়া',
            'pending' => 'পর্যালোচনায় আছে',
            'approved' => 'অনুমোদিত',
            'rejected' => 'বাতিল',
        ][$this->kyc_status] ?? $this->kyc_status;
    }

    public function getAccountStatusBnAttribute(): string
    {
        if (! $this->account_status) {
            return '';
        }

        return [
            'pending' => 'অপেক্ষমাণ',
            'active' => 'সক্রিয়',
            'suspended' => 'সাময়িক বন্ধ',
            'blocked' => 'ব্লক',
        ][$this->account_status] ?? $this->account_status;
    }

    public function getAvailabilityStatusBnAttribute(): string
    {
        if (! $this->availability_status) {
            return '';
        }

        return [
            'offline' => 'অফলাইন',
            'online' => 'অনলাইন',
            'busy' => 'ব্যস্ত',
        ][$this->availability_status] ?? $this->availability_status;
    }
}
