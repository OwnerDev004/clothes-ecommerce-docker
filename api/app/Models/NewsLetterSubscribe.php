<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NewsLetterSubscribe extends Model
{
    protected $table = 'newsletter_subscribe';

    protected $fillable = [
        'email',
        'mail_sent_date'
    ];

    protected $casts = [
        'mail_sent_date' => 'datetime'
    ];



}
