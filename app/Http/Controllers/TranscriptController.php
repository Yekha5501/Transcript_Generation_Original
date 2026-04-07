<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\Registration;
use PhpOffice\PhpWord\TemplateProcessor;
use Illuminate\Http\Request;
use ZipArchive;
use Illuminate\Support\Facades\Log;

class TranscriptController extends Controller
{
    /**
     * Generate transcript for a single student
     */
    public function generate($regNumber)
    {
        try {
            // Find student using username from people table
            $student = Student::where('username', $regNumber)->firstOrFail();
            
            // Get all grades for this student - GROUP BY courseid and take MAX grade
            $grades = Registration::where('studentid', $regNumber)
                ->whereNotNull('grade')
                ->where('grade', '!=', '')
                ->where('grade', '>', 0)
                ->select('courseid', Registration::raw('MAX(grade) as grade'))
                ->groupBy('courseid')
                ->get();
            
            if ($grades->isEmpty()) {
                return back()->with('error', "No grades found for student: $regNumber");
            }
            
            // Create lookup array: courseid => grade (now with highest grade)
            $gradeLookup = [];
            foreach ($grades as $grade) {
                $gradeLookup[$grade->courseid] = $grade->grade;
            }
            
            // Load Word template
            $templatePath = storage_path('app/templates/transcript_template_DCM2.docx');
            
            if (!file_exists($templatePath)) {
                throw new \Exception("Transcript template not found at: $templatePath");
            }
            
            $templateProcessor = new TemplateProcessor($templatePath);
            
            // Set student information
            $templateProcessor->setValue('student_name', $student->fullname);
            $templateProcessor->setValue('reg_number', $student->username);
            $templateProcessor->setValue('program', $this->getProgramName($student->majorid));
            $templateProcessor->setValue('generation_date', now()->format('F d, Y'));
            
            // Get course mapping from config
            $courseMapping = config('course_mapping', []);
            
            // Fill grades using the mapping
            $totalGrades = 0;
            $sumGrades = 0;
            
            foreach ($courseMapping as $placeholder => $courseId) {
                $grade = isset($gradeLookup[$courseId]) ? $gradeLookup[$courseId] : 'N/A';
                $templateProcessor->setValue($placeholder, $grade);
                
                if ($grade !== 'N/A' && is_numeric($grade)) {
                    $totalGrades++;
                    $sumGrades += (float) $grade;
                }
            }
            
            // Calculate statistics
            $averageGrade = $totalGrades > 0 ? round($sumGrades / $totalGrades, 2) : 'N/A';
            $classification = $this->getClassification($averageGrade);
            
            $templateProcessor->setValue('total_courses', $totalGrades);
            $templateProcessor->setValue('average_grade', $averageGrade);
            $templateProcessor->setValue('classification', $classification);
            
            // Save the generated transcript
            $fileName = 'Transcript_' . str_replace('/', '_', $student->username) . '.docx';
            $filePath = storage_path("app/transcripts/{$fileName}");
            
            // Ensure directory exists
            $directory = dirname($filePath);
            if (!is_dir($directory)) {
                mkdir($directory, 0755, true);
            }
            
            $templateProcessor->saveAs($filePath);
            
            // Return the file for download
            return response()->download($filePath)->deleteFileAfterSend(true);
            
        } catch (\Exception $e) {
            Log::error("Transcript generation failed for {$regNumber}: " . $e->getMessage());
            return back()->with('error', 'Failed to generate transcript: ' . $e->getMessage());
        }
    }
    
    /**
     * Generate transcripts for multiple students from uploaded file
     */
    public function generateBatch(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt,xlsx',
        ]);
        
        set_time_limit(300); // 5 minutes timeout for large batches
        
        try {
            // Parse student IDs from uploaded file
            $studentIds = $this->parseStudentIds($request->file('file'));
            
            if (empty($studentIds)) {
                return back()->with('error', 'No valid registration numbers found in file');
            }
            
            // Create temp directory for individual transcripts
            $tempDir = storage_path('app/temp/bulk_transcripts_' . time());
            mkdir($tempDir, 0755, true);
            
            // Create ZIP file
            $zipFileName = 'All_Transcripts_' . date('Y-m-d_H-i-s') . '.zip';
            $zipPath = storage_path("app/{$zipFileName}");
            $zip = new ZipArchive();
            
            if ($zip->open($zipPath, ZipArchive::CREATE) !== true) {
                throw new \Exception("Could not create zip file");
            }
            
            $templatePath = storage_path('app/templates/transcript_template.docx');
            $courseMapping = config('course_mapping', []);
            $successCount = 0;
            $failedStudents = [];
            
            foreach ($studentIds as $studentId) {
                try {
                    // Find student
                    $student = Student::where('username', $studentId)->first();
                    if (!$student) {
                        $failedStudents[] = "$studentId (Student not found)";
                        continue;
                    }
                    
                    // Get grades - GROUP BY courseid and take MAX grade
                    $grades = Registration::where('studentid', $studentId)
                        ->whereNotNull('grade')
                        ->where('grade', '!=', '')
                        ->where('grade', '>', 0)
                        ->select('courseid', Registration::raw('MAX(grade) as grade'))
                        ->groupBy('courseid')
                        ->get();
                    
                    if ($grades->isEmpty()) {
                        $failedStudents[] = "$studentId (No grades found)";
                        continue;
                    }
                    
                    // Create grade lookup with highest grades
                    $gradeLookup = [];
                    foreach ($grades as $grade) {
                        $gradeLookup[$grade->courseid] = $grade->grade;
                    }
                    
                    // Process template
                    $templateProcessor = new TemplateProcessor($templatePath);
                    
                    // Set student info
                    $templateProcessor->setValue('student_name', $student->fullname);
                    $templateProcessor->setValue('reg_number', $student->username);
                    $templateProcessor->setValue('program', $this->getProgramName($student->majorid));
                    $templateProcessor->setValue('generation_date', now()->format('F d, Y'));
                    
                    // Fill grades
                    $totalGrades = 0;
                    $sumGrades = 0;
                    
                    foreach ($courseMapping as $placeholder => $courseId) {
                        $grade = isset($gradeLookup[$courseId]) ? $gradeLookup[$courseId] : 'N/A';
                        $templateProcessor->setValue($placeholder, $grade);
                        
                        if ($grade !== 'N/A' && is_numeric($grade)) {
                            $totalGrades++;
                            $sumGrades += (float) $grade;
                        }
                    }
                    
                    // Statistics
                    $averageGrade = $totalGrades > 0 ? round($sumGrades / $totalGrades, 2) : 'N/A';
                    $templateProcessor->setValue('total_courses', $totalGrades);
                    $templateProcessor->setValue('average_grade', $averageGrade);
                    $templateProcessor->setValue('classification', $this->getClassification($averageGrade));
                    
                    // Save individual transcript
                    $fileName = 'Transcript_' . str_replace('/', '_', $student->username) . '.docx';
                    $filePath = $tempDir . '/' . $fileName;
                    $templateProcessor->saveAs($filePath);
                    
                    // Add to zip
                    $zip->addFile($filePath, $fileName);
                    $successCount++;
                    
                } catch (\Exception $e) {
                    $failedStudents[] = "$studentId (" . $e->getMessage() . ")";
                    Log::error("Failed to generate transcript for $studentId: " . $e->getMessage());
                    continue;
                }
            }
            
            $zip->close();
            
            // Clean up temp directory
            if (is_dir($tempDir)) {
                $files = glob($tempDir . '/*');
                foreach ($files as $file) {
                    unlink($file);
                }
                rmdir($tempDir);
            }
            
            if ($successCount === 0) {
                unlink($zipPath);
                return back()->with('error', 'No transcripts were generated. Failed students: ' . implode(', ', $failedStudents));
            }
            
            $message = "Generated $successCount transcripts successfully.";
            if (!empty($failedStudents)) {
                $message .= " Failed: " . implode(', ', array_slice($failedStudents, 0, 5));
                if (count($failedStudents) > 5) {
                    $message .= " and " . (count($failedStudents) - 5) . " more...";
                }
            }
            
            return response()->download($zipPath)->deleteFileAfterSend(true);
            
        } catch (\Exception $e) {
            Log::error("Batch transcript generation failed: " . $e->getMessage());
            return back()->with('error', 'Batch generation failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Parse student IDs from uploaded file
     */
    private function parseStudentIds($file)
    {
        $studentIds = [];
        $extension = $file->getClientOriginalExtension();
        
        if ($extension === 'csv') {
            $handle = fopen($file->getPathname(), 'r');
            while (($data = fgetcsv($handle)) !== false) {
                $id = trim($data[0]);
                if (!empty($id)) {
                    $studentIds[] = $id;
                }
            }
            fclose($handle);
        } elseif ($extension === 'txt') {
            $content = file_get_contents($file->getPathname());
            $lines = explode("\n", $content);
            foreach ($lines as $line) {
                $id = trim($line);
                if (!empty($id)) {
                    $studentIds[] = $id;
                }
            }
        } elseif ($extension === 'xlsx') {
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file->getPathname());
            $sheet = $spreadsheet->getActiveSheet();
            $rows = $sheet->toArray();
            foreach ($rows as $row) {
                $id = trim($row[0]);
                if (!empty($id)) {
                    $studentIds[] = $id;
                }
            }
        }
        
        return array_unique($studentIds);
    }
    
    /**
     * Get program name from major ID
     */
    private function getProgramName($majorId)
    {
        $programs = [
            5 => 'Certificate in Clinical Medicine',
            6 => 'Bachelor of Public Health',
            7 => 'Bachelor of Medical Laboratory Sciences',
            // Add more programs as needed
        ];
        
        return $programs[$majorId] ?? 'Unknown Program';
    }
    
    /**
     * Get classification based on average grade
     */
    private function getClassification($averageGrade)
    {
        if ($averageGrade === 'N/A') return 'Incomplete';
        if ($averageGrade >= 75) return 'Distinction';
        if ($averageGrade >= 65) return 'Credit';
        if ($averageGrade >= 50) return 'Pass';
        return 'Fail';
    }
    
    /**
     * Preview student grades (for debugging) - FIXED to show highest grades only
     */
    public function preview($regNumber)
    {
        $student = Student::where('username', $regNumber)->first();
        
        if (!$student) {
            return response()->json(['error' => 'Student not found'], 404);
        }
        
        // Get grades with highest grade per course
        $grades = Registration::where('studentid', $regNumber)
            ->whereNotNull('grade')
            ->where('grade', '!=', '')
            ->where('grade', '>', 0)
            ->select('courseid', Registration::raw('MAX(grade) as grade'))
            ->groupBy('courseid')
            ->with('course')
            ->get();
        
        // Calculate statistics
        $totalGrades = 0;
        $sumGrades = 0;
        $gradeList = [];
        
        foreach ($grades as $grade) {
            $numericGrade = (float) $grade->grade;
            $totalGrades++;
            $sumGrades += $numericGrade;
            
            $gradeList[] = [
                'course_code' => $grade->course->code ?? 'N/A',
                'course_name' => $grade->course->subject ?? 'N/A',
                'grade' => $grade->grade,
            ];
        }
        
        $averageGrade = $totalGrades > 0 ? round($sumGrades / $totalGrades, 2) : 0;
        
        return response()->json([
            'student' => [
                'username' => $student->username,
                'fullname' => $student->fullname,
                'program' => $this->getProgramName($student->majorid),
            ],
            'grades' => $gradeList,
            'total_grades' => $totalGrades,
            'average_grade' => $averageGrade,
            'classification' => $this->getClassification($averageGrade),
        ]);
    }
}