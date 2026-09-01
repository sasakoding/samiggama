<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Donation extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_number',
        'donation_program_id',
        'donor_name',
        'phone',
        'amount',
        'total_amount',
        'payment_method',
        'donor_message',
        'status',
    ];

    protected $casts = [
        'amount' => 'integer',
        'total_amount' => 'integer',
    ];

    public function donationProgram()
    {
        return $this->belongsTo(DonationProgram::class);
    }

    public function issuedCertificate()
    {
        return $this->hasOne(IssuedCertificate::class);
    }
}
