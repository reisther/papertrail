<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TitleSubmission extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'title1',
        'title2',
        'title3',
        'title4',
        'title5',
    ];

    /**
     * Student who submitted the titles
     */
    public function student()
    {
        return $this->belongsTo(User::class, 'student_id');
    }
}