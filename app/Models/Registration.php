<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Registration extends Model
{
    protected $table = 'registration';
    
    public $timestamps = false;
    
    protected $fillable = [
        'studentid', 'semesterid', 'courseid', 'campusid', 
        'grade', 'assign1', 'midterm', 'points', 'res', 
        'adviserapp', 'fnceapp', 'credits'
    ];
    
    // Relationship to Student (using people table)
    public function student()
    {
        return $this->belongsTo(Student::class, 'studentid', 'username');
    }
    
    // Relationship to Course
    public function course()
    {
        return $this->belongsTo(Course::class, 'courseid', 'id');
    }
    
    // Get only valid grades
    public function scopeHasGrade($query)
    {
        return $query->whereNotNull('grade')
                     ->where('grade', '!=', '')
                     ->where('grade', '>', 0);
    }
}