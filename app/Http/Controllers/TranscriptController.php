<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\Registration;
use PhpOffice\PhpWord\TemplateProcessor;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Log;

class TranscriptController extends Controller
{
    /**
     * Generate transcript for a single student
     */
    public function generate(Request $request, $regNumber)
    {
        try {
            // Get template type from request (word or excel)
            $templateType = $request->get('template', 'word');
            
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
            
            // Get course mapping from config
            $courseMapping = config('course_mapping', []);
            
            // Calculate statistics
            $totalGrades = 0;
            $sumGrades = 0;
            $gradeData = [];
            
            foreach ($courseMapping as $placeholder => $courseId) {
                $grade = isset($gradeLookup[$courseId]) ? $gradeLookup[$courseId] : 'N/A';
                $gradeData[$placeholder] = $grade;
                
                if ($grade !== 'N/A' && is_numeric($grade)) {
                    $totalGrades++;
                    $sumGrades += (float) $grade;
                }
            }
            
            $averageGrade = $totalGrades > 0 ? round($sumGrades / $totalGrades, 2) : 'N/A';
            $classification = $this->getClassification($averageGrade);
            
            // Prepare student data with gender/sex
            $studentData = [
                'student_name' => $student->fullname,
                'reg_number' => $student->username,
                'gender' => $this->formatGender($student->sex),
                'sex' => $this->formatGender($student->sex), // Added both for flexibility
                'program' => $this->getProgramName($student->majorid),
                'generation_date' => now()->format('F d, Y'),
                'total_courses' => $totalGrades,
                'average_grade' => $averageGrade,
                'classification' => $classification,
            ];
            
            // Merge grade data with student data
            $templateData = array_merge($studentData, $gradeData);
            
            // Generate based on template type
            if ($templateType === 'excel') {
                $filePath = $this->generateExcelTranscript($templateData, $student->fullname);
                $fileName = basename($filePath);
                return response()->download($filePath, $fileName)->deleteFileAfterSend(true);
            } else {
                $filePath = $this->generateWordTranscript($templateData, $student->fullname);
                $fileName = basename($filePath);
                return response()->download($filePath, $fileName)->deleteFileAfterSend(true);
            }
            
        } catch (\Exception $e) {
            Log::error("Transcript generation failed for {$regNumber}: " . $e->getMessage());
            return back()->with('error', 'Failed to generate transcript: ' . $e->getMessage());
        }
    }
    
    /**
     * Format gender/sex value
     */
    private function formatGender($sex)
    {
        if (empty($sex)) {
            return 'Not Specified';
        }
        
        $sex = strtolower(trim($sex));
        
        // Handle various possible values
        if ($sex === 'm' || $sex === 'male' || $sex === 'M' || $sex === 'Male') {
            return 'Male';
        } elseif ($sex === 'f' || $sex === 'female' || $sex === 'F' || $sex === 'Female') {
            return 'Female';
        } else {
            return ucfirst($sex); // Return as-is if other value
        }
    }
    
    /**
     * Generate Word transcript
     */
    private function generateWordTranscript($templateData, $studentName)
    {
        $templatePath = storage_path('app/templates/DCM.docx');
        
        if (!file_exists($templatePath)) {
            throw new \Exception("Word template not found at: $templatePath");
        }
        
        $templateProcessor = new TemplateProcessor($templatePath);
        
        // Set all values in the template
        foreach ($templateData as $placeholder => $value) {
            $templateProcessor->setValue($placeholder, $value);
        }
        
        // Sanitize filename (remove special characters) - use only student name
        $sanitizedName = $this->sanitizeFilename($studentName);
        
        // Save the generated transcript with student name only
        $fileName = $sanitizedName . '.docx';
        $filePath = storage_path("app/transcripts/{$fileName}");
        
        $directory = dirname($filePath);
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }
        
        $templateProcessor->saveAs($filePath);
        
        return $filePath;
    }
    
    /**
     * Generate Excel transcript using the template file
     */
    private function generateExcelTranscript($templateData, $studentName)
    {
        $templatePath = storage_path('app/templates/BSNM 2025.xlsx');
        
        // Check if Excel template exists
        if (!file_exists($templatePath)) {
            throw new \Exception("Excel template not found at: $templatePath");
        }
        
        // Load the template
        $spreadsheet = IOFactory::load($templatePath);
        $sheet = $spreadsheet->getActiveSheet();
        
        // Replace placeholders in Excel - iterate through all template data
        foreach ($templateData as $placeholder => $value) {
            $this->replaceExcelPlaceholderRecursive($sheet, $placeholder, $value);
        }
        
        // Also try direct cell replacement for common placeholders
        $this->replaceExcelPlaceholderDirect($sheet, $templateData);
        
        // Sanitize filename (remove special characters) - use only student name
        $sanitizedName = $this->sanitizeFilename($studentName);
        
        // Save the generated transcript with student name only
        $fileName = $sanitizedName . '.xlsx';
        $filePath = storage_path("app/transcripts/{$fileName}");
        
        $directory = dirname($filePath);
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }
        
        $writer = new Xlsx($spreadsheet);
        $writer->save($filePath);
        
        return $filePath;
    }
    
    /**
     * Replace placeholders in Excel sheet recursively through all cells
     */
    private function replaceExcelPlaceholderRecursive($sheet, $placeholder, $value)
    {
        $search = '${' . $placeholder . '}';
        
        // Get all used cells
        $highestRow = $sheet->getHighestRow();
        $highestColumn = $sheet->getHighestColumn();
        
        for ($row = 1; $row <= $highestRow; $row++) {
            for ($col = 'A'; $col <= $highestColumn; $col++) {
                $cell = $sheet->getCell($col . $row);
                $cellValue = $cell->getValue();
                
                if (is_string($cellValue) && strpos($cellValue, $search) !== false) {
                    $newValue = str_replace($search, $value, $cellValue);
                    $cell->setValue($newValue);
                }
            }
        }
    }
    
    /**
     * Direct replacement for specific cells (faster for known placeholders)
     */
    private function replaceExcelPlaceholderDirect($sheet, $templateData)
    {
        foreach ($templateData as $placeholder => $value) {
            $search = '${' . $placeholder . '}';
            
            // Search entire sheet for this specific placeholder
            $highestRow = $sheet->getHighestRow();
            $highestColumn = $sheet->getHighestColumn();
            
            for ($row = 1; $row <= $highestRow; $row++) {
                for ($col = 'A'; $col <= $highestColumn; $col++) {
                    $cell = $sheet->getCell($col . $row);
                    $cellValue = $cell->getValue();
                    
                    if (is_string($cellValue) && $cellValue === $search) {
                        $cell->setValue($value);
                    } elseif (is_string($cellValue) && strpos($cellValue, $search) !== false) {
                        $newValue = str_replace($search, $value, $cellValue);
                        $cell->setValue($newValue);
                    }
                }
            }
        }
    }
    
    /**
     * Find row number containing specific text
     */
    private function findRowWithText($sheet, $searchText)
    {
        $highestRow = $sheet->getHighestRow();
        $highestColumn = $sheet->getHighestColumn();
        
        for ($row = 1; $row <= $highestRow; $row++) {
            for ($col = 'A'; $col <= $highestColumn; $col++) {
                $cellValue = $sheet->getCell($col . $row)->getValue();
                if (is_string($cellValue) && stripos($cellValue, $searchText) !== false) {
                    return $row;
                }
            }
        }
        return 0;
    }
    
    /**
     * Sanitize filename by removing special characters
     */
    private function sanitizeFilename($filename)
    {
        // Remove any characters that might cause issues in filenames
        $filename = preg_replace('/[^\w\s-]/u', '', $filename);
        // Replace spaces with underscores
        $filename = preg_replace('/[\s]+/', '_', $filename);
        // Remove multiple underscores
        $filename = preg_replace('/_+/', '_', $filename);
        // Trim underscores from beginning and end
        $filename = trim($filename, '_');
        
        return $filename;
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
            $spreadsheet = IOFactory::load($file->getPathname());
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
     * Preview student grades (for debugging)
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
                'gender' => $this->formatGender($student->sex),
                'program' => $this->getProgramName($student->majorid),
            ],
            'grades' => $gradeList,
            'total_grades' => $totalGrades,
            'average_grade' => $averageGrade,
            'classification' => $this->getClassification($averageGrade),
        ]);
    }

    /**
     * Queue batch transcripts for sequential download
     */
    public function queueBatch(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt,xlsx,xls'
        ]);
        
        // Parse student IDs from uploaded file
        $studentIds = $this->parseStudentIds($request->file('file'));
        
        // Clean all IDs - Remove BOM, invisible chars, trim
        $studentIds = array_map(function($id) {
            $id = preg_replace('/[\x00-\x1F\x7F-\x9F]/u', '', $id);
            $id = preg_replace('/^[\pZ\pC]+|[\pZ\pC]+$/u', '', $id);
            return trim($id);
        }, $studentIds);
        
        // Remove empty values
        $studentIds = array_filter($studentIds, function($id) {
            return !empty($id);
        });
        
        // Re-index array
        $studentIds = array_values($studentIds);
        
        if (empty($studentIds)) {
            return back()->with('error', 'No valid student IDs found in the file.');
        }
        
        // Get template type from request
        $templateType = $request->input('template', 'word');
        
        // Create a unique queue ID
        $queueId = uniqid('batch_', true);
        
        // Store the queue in session with student info
        $studentsData = [];
        foreach ($studentIds as $index => $regNumber) {
            $student = Student::where('username', $regNumber)->first();
            $studentsData[] = [
                'id' => $index,
                'registration_number' => $regNumber,
                'student_name' => $student ? $student->fullname : 'Unknown Student',
                'gender' => $student ? $this->formatGender($student->sex) : 'Not Specified',
                'exists' => $student ? true : false
            ];
        }
        
        $queue = [
            'students' => $studentsData,
            'total' => count($studentsData),
            'template' => $templateType,
            'timestamp' => now()
        ];
        
        Session::put("transcript_queue_{$queueId}", $queue);
        
        return redirect()->route('transcript.batch.list', ['queueId' => $queueId])
            ->with('info', "{$queue['total']} student(s) loaded. Click download buttons to generate transcripts.");
    }

    /**
     * Show batch list with all student IDs and names
     */
    public function showBatchList($queueId, Request $request)
    {
        $queue = Session::get("transcript_queue_{$queueId}");
        
        if (!$queue) {
            return redirect()->route('transcript.index')
                ->with('error', 'Batch session expired. Please upload the file again.');
        }
        
        // Get template from URL parameter or from queue
        $template = $request->get('template', $queue['template']);
        
        // Update queue template if changed
        if ($queue['template'] !== $template) {
            $queue['template'] = $template;
            Session::put("transcript_queue_{$queueId}", $queue);
        }
        
        // Prepare student list for the view with names pre-loaded
        $students = [];
        foreach ($queue['students'] as $studentData) {
            $students[] = [
                'id' => $studentData['id'],
                'registration_number' => $studentData['registration_number'],
                'student_name' => $studentData['student_name'],
                'gender' => $studentData['gender'] ?? 'Not Specified',
                'exists' => $studentData['exists']
            ];
        }
        
        return view('batch-list', [
            'students' => $students,
            'total' => count($students),
            'queueId' => $queueId,
            'template' => $template
        ]);
    }

    /**
     * Download a single transcript from batch
     * This saves the file with student name only
     */
    public function downloadBatchTranscript($queueId, $studentId, Request $request)
    {
        $queue = Session::get("transcript_queue_{$queueId}");
        
        if (!$queue) {
            return response()->json(['error' => 'Batch session expired'], 404);
        }
        
        // Get template from URL parameter or from queue
        $templateType = $request->get('template', $queue['template']);
        
        // Find the student data in the queue
        $studentData = null;
        foreach ($queue['students'] as $student) {
            if ($student['id'] == $studentId) {
                $studentData = $student;
                break;
            }
        }
        
        if (!$studentData) {
            return response()->json(['error' => 'Student not found in batch'], 404);
        }
        
        $regNumber = $studentData['registration_number'];
        
        try {
            // Clean the registration number
            $regNumber = trim($regNumber);
            $regNumber = preg_replace('/[\x00-\x1F\x7F-\x9F]/u', '', $regNumber);
            $regNumber = trim($regNumber);
            
            // Find student
            $student = Student::where('username', $regNumber)->first();
            
            if (!$student) {
                throw new \Exception("Student not found: {$regNumber}");
            }
            
            // Get grades
            $grades = Registration::where('studentid', $regNumber)
                ->whereNotNull('grade')
                ->where('grade', '!=', '')
                ->where('grade', '>', 0)
                ->select('courseid', Registration::raw('MAX(grade) as grade'))
                ->groupBy('courseid')
                ->get();
            
            if ($grades->isEmpty()) {
                throw new \Exception("No grades found");
            }
            
            $gradeLookup = [];
            foreach ($grades as $grade) {
                $gradeLookup[$grade->courseid] = $grade->grade;
            }
            
            $courseMapping = config('course_mapping', []);
            $totalGrades = 0;
            $sumGrades = 0;
            $gradeData = [];
            
            foreach ($courseMapping as $placeholder => $courseId) {
                $grade = isset($gradeLookup[$courseId]) ? $gradeLookup[$courseId] : 'N/A';
                $gradeData[$placeholder] = $grade;
                if ($grade !== 'N/A' && is_numeric($grade)) {
                    $totalGrades++;
                    $sumGrades += (float) $grade;
                }
            }
            
            $averageGrade = $totalGrades > 0 ? round($sumGrades / $totalGrades, 2) : 'N/A';
            $classification = $this->getClassification($averageGrade);
            
            $studentDataArray = [
                'student_name' => $student->fullname,
                'reg_number' => $student->username,
                'gender' => $this->formatGender($student->sex),
                'sex' => $this->formatGender($student->sex), // Added both for flexibility
                'program' => $this->getProgramName($student->majorid),
                'generation_date' => now()->format('F d, Y'),
                'total_courses' => $totalGrades,
                'average_grade' => $averageGrade,
                'classification' => $classification,
            ];
            
            $templateData = array_merge($studentDataArray, $gradeData);
            
            // Generate file based on template type (saved with student name only)
            if ($templateType === 'excel') {
                $filePath = $this->generateExcelTranscript($templateData, $student->fullname);
                $fileExtension = 'xlsx';
            } else {
                $filePath = $this->generateWordTranscript($templateData, $student->fullname);
                $fileExtension = 'docx';
            }
            
            // Create filename with student name only (no registration number)
            $sanitizedName = $this->sanitizeFilename($student->fullname);
            $fileName = $sanitizedName . '.' . $fileExtension;
            
            // Return file download with proper filename
            return response()->download($filePath, $fileName)->deleteFileAfterSend(true);
            
        } catch (\Exception $e) {
            Log::error("Batch download failed for {$regNumber}: " . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Get queue status (for AJAX polling)
     */
    public function getQueueStatus($queueId)
    {
        $queue = Session::get("transcript_queue_{$queueId}");
        
        if (!$queue) {
            return response()->json(['error' => 'Queue not found'], 404);
        }
        
        return response()->json([
            'total' => $queue['total'],
            'template' => $queue['template'],
            'timestamp' => $queue['timestamp']
        ]);
    }
}