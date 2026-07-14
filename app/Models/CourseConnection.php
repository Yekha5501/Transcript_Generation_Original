<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CourseConnection extends Model
{
    protected $table = 'course_connections';
    
    protected $fillable = [
        'canonical_course_id',
        'alias_course_id',
        'connection_type',
        'notes',
        'is_active'
    ];
    
    protected $casts = [
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
    
    // Relationship to canonical course
    public function canonicalCourse()
    {
        return $this->belongsTo(Course::class, 'canonical_course_id');
    }
    
    // Relationship to alias course
    public function aliasCourse()
    {
        return $this->belongsTo(Course::class, 'alias_course_id');
    }
}