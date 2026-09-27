<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Login extends Model
{
  use HasFactory;
  protected $connection = 'db2';
  protected $table = 'tbluser';

  protected $fillable = [
    'username',
    'fk_cabang_user',
  ];
}
