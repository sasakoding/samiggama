<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IssuedCertificate extends Model
{
    use HasFactory;

    protected $fillable = [
        'certificate_number',
        'donation_id',
        'certificate_template_id',
        'recipient_name',
        'amount',
        'issued_date',
        'file_path',
    ];

    protected $casts = [
        'amount' => 'integer',
        'issued_date' => 'date',
    ];

    public function donation()
    {
        return $this->belongsTo(Donation::class);
    }

    public function certificateTemplate()
    {
        return $this->belongsTo(CertificateTemplate::class);
    }
}
