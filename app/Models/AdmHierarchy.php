<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdmHierarchy extends Model
{
    use HasFactory;

    protected $table = "adm_h";

    protected $primaryKey = 'OBJECTID';

    public $incrementing = false;

    public $timestamps = false;

    public $fillable = [
        "OBJECTID",
        "PARENTOBJID"
    ];
}
