<?php


namespace App\Models\h2ddd;


use Illuminate\Database\Eloquent\Model;

class UserModel extends Model
{
    protected $fillable = [
        'phone',
        'password',
        'token'
    ];
    protected $table = "h2ddd_user";
    protected $dateFormat = "U";
}
