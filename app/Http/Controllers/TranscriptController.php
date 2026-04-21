<?php
namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\Registration;
use PhpOffice\PhpWord\TemplateProcessor;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Illuminate\Http\Request;
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
            
            // Prepare student data
            $studentData = [
                'student_name' => $student->fullname,
                'reg_number' => $student->username,
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
                $filePath = $this->generateExcelTranscript($templateData, $student->username);
                return response()->download($filePath)->deleteFileAfterSend(true);
            } else {
                $filePath = $this->generateWordTranscript($templateData, $student->username);
                return response()->download($filePath)->deleteFileAfterSend(true);
            }
            
        } catch (\Exception $e) {
            Log::error("Transcript generation failed for {$regNumber}: " . $e->getMessage());
            return back()->with('error', 'Failed to generate transcript: ' . $e->getMessage());
        }
    }
    
    /**
     * Generate Word transcript
     */
    private function generateWordTranscript($templateData, $username)
    {
        $templatePath = storage_path('app/templates/BMS_TRANCRIPT_TEMPLATE.docx');
        
        if (!file_exists($templatePath)) {
            throw new \Exception("Word template not found at: $templatePath");
        }
        
        $templateProcessor = new TemplateProcessor($templatePath);
        
        // Set all values in the template
        foreach ($templateData as $placeholder => $value) {
            $templateProcessor->setValue($placeholder, $value);
        }
        
        // Save the generated transcript
        $fileName = 'Transcript_' . str_replace('/', '_', $username) . '.docx';
        $filePath = storage_path("app/transcripts/{$fileName}");
        
        $directory = dirname($filePath);
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }
        
        $templateProcessor->saveAs($filePath);
        
        return $filePath;
    }
    
    /**
     * Generate Excel transcript
     */
    private function generateExcelTranscript($templateData, $username)
    {
        $templatePath = storage_path('app/templates/transcript_template.xlsx');
        
        // If Excel template exists, use it; otherwise create from scratch
        if (file_exists($templatePath)) {
            $spreadsheet = IOFactory::load($templatePath);
        } else {
            $spreadsheet = new Spreadsheet();
            $this->createDefaultExcelTemplate($spreadsheet);
        }
        
        $sheet = $spreadsheet->getActiveSheet();
        
        // Replace placeholders in Excel
        foreach ($templateData as $placeholder => $value) {
            $this->replaceExcelPlaceholder($sheet, $placeholder, $value);
        }
        
        // Save the generated transcript
        $fileName = 'Transcript_' . str_replace('/', '_', $username) . '.xlsx';
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
     * Create default Excel template structure
     */
    private function createDefaultExcelTemplate($spreadsheet)
    {
        $sheet = $spreadsheet->getActiveSheet();
        
        // Set title
        $sheet->setCellValue('A1', 'ACADEMIC TRANSCRIPT');
        $sheet->mergeCells('A1:C1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
        
        // Student info section
        $sheet->setCellValue('A3', 'Student Name:');
        $sheet->setCellValue('B3', '${student_name}');
        $sheet->setCellValue('A4', 'Registration Number:');
        $sheet->setCellValue('B4', '${reg_number}');
        $sheet->setCellValue('A5', 'Program:');
        $sheet->setCellValue('B5', '${program}');
        $sheet->setCellValue('A6', 'Date Generated:');
        $sheet->setCellValue('B6', '${generation_date}');
        
        // Course headers
        $sheet->setCellValue('A8', 'Course Code');
        $sheet->setCellValue('B8', 'Course Name');
        $sheet->setCellValue('C8', 'Grade');
        $sheet->getStyle('A8:C8')->getFont()->setBold(true);
        
        // Course rows will be filled dynamically
        $row = 9;
        $courseMapping = config('course_mapping', []);
        
        foreach ($courseMapping as $placeholder => $courseId) {
            $sheet->setCellValue('A' . $row, '');
            $sheet->setCellValue('B' . $row, str_replace('_', ' ', $placeholder));
            $sheet->setCellValue('C' . $row, '${' . $placeholder . '}');
            $row++;
        }
        
        // Summary section
        $row++;
        $sheet->setCellValue('A' . $row, 'Total Courses Completed:');
        $sheet->setCellValue('C' . $row, '${total_courses}');
        $row++;
        $sheet->setCellValue('A' . $row, 'Average Grade:');
        $sheet->setCellValue('C' . $row, '${average_grade}');
        $row++;
        $sheet->setCellValue('A' . $row, 'Classification:');
        $sheet->setCellValue('C' . $row, '${classification}');
        
        // Auto-size columns
        foreach (range('A', 'C') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
    }
    
    /**
     * Replace placeholders in Excel sheet
     */
    private function replaceExcelPlaceholder($sheet, $placeholder, $value)
    {
        $search = '${' . $placeholder . '}';
        
        // Search all cells in used range
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
     * Generate transcripts for multiple students from uploaded file
     */
    
    
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