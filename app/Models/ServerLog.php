<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServerLog extends Model
{
    use HasFactory;

    protected $table = 'server_logs'; // Nama tabel

    protected $fillable = ['filename', 'log_data'];

    protected $casts = [
        'log_data' => 'string',
    ];
}
