<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Course extends Model
{
    protected $table = 'courses';
    
    public $timestamps = false;
    
    protected $fillable = ['id', 'code', 'subject', 'credits'];
    
    // Relationship to registrations
    public function registrations()
    {
        return $this->hasMany(Registration::class, 'courseid', 'id');
    }
    
    // Get full course name
    public function getFullNameAttribute()
    {
        return $this->code . ' - ' . $this->subject;
    }
}