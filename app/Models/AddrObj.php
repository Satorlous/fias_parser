<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AddrObj extends Model
{
    use HasFactory;

    protected $table = "addr_obj";

    protected $primaryKey = 'OBJECTID';

    public $incrementing = false;

    public $timestamps = false;

    public $fillable = [
        "OBJECTID",
        "OBJECTGUID",
        "NAME",
        "TYPENAME",
        "FULL_TYPENAME",
        "LEVEL",
        "FULL_LEVEL",
        "PARENTOBJID",
        "REGION"
    ];
}
