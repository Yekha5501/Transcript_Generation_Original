<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    // Use the people table instead of students
    protected $table = 'people';
    
    // Primary key is id, but we'll use username as the identifier
    protected $primaryKey = 'id';
    
    public $timestamps = false;
    
    protected $fillable = [
        'username', 'fullname', 'password', 'dob', 'sex', 
        'majorid', 'campusid', 'typeid', 'cell', 'address', 
        'email', 'nationalityid', 'religionid', 'startyear', 
        'sponsor', 'role', 'image_path', 'marital', 'spcell', 'spemail'
    ];
    
    // Define the relationship to registrations
    public function registrations()
    {
        return $this->hasMany(Registration::class, 'studentid', 'username');
    }
    
    // Get grades for this student
    public function grades()
    {
        return $this->hasMany(Registration::class, 'studentid', 'username')
                    ->whereNotNull('grade')
                    ->where('grade', '!=', '')
                    ->where('grade', '>', 0);
    }
    
    // Get grade for specific course
    public function getGradeForCourse($courseId)
    {
        $registration = $this->registrations()
            ->where('courseid', $courseId)
            ->first();
            
        return $registration ? $registration->grade : 'N/A';
    }
    
    // Get all grades as array keyed by course ID
    public function getGradesByCourseIdAttribute()
    {
        return $this->grades()
            ->pluck('grade', 'courseid')
            ->toArray();
    }
    
    // Scope to get only students (not staff)
    public function scopeStudents($query)
    {
        return $query->where('role', 'student')
                     ->orWhere('typeid', 1); // Adjust based on your data
    }
    
    // Accessor for student ID
    public function getRegNumberAttribute()
    {
        return $this->username;
    }
    
    // Accessor for student name
    public function getNameAttribute()
    {
        return $this->fullname;
    }
}