<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Transcript Generator | Academic Suite</title>
    <!-- Tailwind CSS + Flowbite CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .card-hover {
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .card-hover:hover {
            transform: translateY(-2px);
            box-shadow: 0 20px 25px -12px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.02);
        }
        .preview-table tbody tr:hover {
            background-color: #f9fafb;
        }
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        .animate-slide-in {
            animation: slideIn 0.3s ease-out;
        }
    </style>
</head>
<body class="bg-gradient-to-br from-slate-50 to-blue-50 font-sans antialiased">

    <div class="min-h-screen py-8 px-4 sm:px-6 lg:py-12">
        <div class="max-w-4xl mx-auto">
            <div class="bg-white/90 backdrop-blur-sm rounded-2xl shadow-2xl border border-white/30 overflow-hidden transition-all duration-300">
                <!-- header -->
                <div class="bg-gradient-to-r from-indigo-700 to-blue-700 px-6 py-6 sm:px-8">
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-white/20 rounded-xl backdrop-blur-sm">
                            <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                        </div>
                        <div>
                            <h1 class="text-2xl sm:text-3xl font-extrabold text-white tracking-tight">Transcript Generator</h1>
                            <p class="text-indigo-100 text-sm mt-1">Academic records · Instant transcripts · Word & Excel export</p>
                        </div>
                    </div>
                </div>
                
                <div class="p-6 sm:p-8 space-y-8">
                    <!-- Flash Messages -->
                    @if(session('error'))
                        <div class="flex items-center p-4 mb-4 text-red-800 rounded-xl bg-red-50 border-l-8 border-red-500 shadow-sm">
                            <svg class="w-5 h-5 mr-3 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path></svg>
                            <span class="font-medium">{{ session('error') }}</span>
                        </div>
                    @endif
                    
                    @if(session('success'))
                        <div class="flex items-center p-4 mb-4 text-green-800 rounded-xl bg-green-50 border-l-8 border-green-500 shadow-sm">
                            <svg class="w-5 h-5 mr-3 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
                            <span class="font-medium">{{ session('success') }}</span>
                        </div>
                    @endif

                    @if(session('info'))
                        <div class="flex items-center p-4 mb-4 text-blue-800 rounded-xl bg-blue-50 border-l-8 border-blue-500 shadow-sm">
                            <svg class="w-5 h-5 mr-3 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path></svg>
                            <span class="font-medium">{{ session('info') }}</span>
                        </div>
                    @endif

                    <!-- Template Selection Toggle -->
                    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
                        <div class="flex flex-col sm:flex-row gap-4 items-center justify-between">
                            <div class="flex gap-3">
                                <button onclick="setTemplate('word')" id="btn-word" class="template-btn px-5 py-2.5 rounded-lg font-medium transition-all duration-200 bg-indigo-600 text-white shadow-md">
                                    📄 Word Document
                                </button>
                                <button onclick="setTemplate('excel')" id="btn-excel" class="template-btn px-5 py-2.5 rounded-lg font-medium transition-all duration-200 bg-gray-200 text-gray-700 hover:bg-gray-300">
                                    📊 Excel Spreadsheet
                                </button>
                            </div>
                            <div class="text-sm text-gray-500">
                                Current template: <span id="selected-template" class="font-semibold text-indigo-600">Word Document</span>
                            </div>
                        </div>
                    </div>

                    <!-- ========== 1. SINGLE STUDENT TRANSCRIPT SECTION ========== -->
                    <div class="bg-white rounded-xl border border-gray-200 shadow-sm card-hover overflow-hidden">
                        <div class="border-b border-gray-100 bg-gray-50/70 px-5 py-4">
                            <div class="flex items-center gap-2">
                                <div class="p-1.5 bg-indigo-100 rounded-lg">
                                    <svg class="w-5 h-5 text-indigo-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                    </svg>
                                </div>
                                <h2 class="text-lg font-semibold text-gray-800">Single Student Transcript</h2>
                            </div>
                        </div>
                        <div class="p-5">
                            <form id="single-form" action="#" method="GET" onsubmit="event.preventDefault(); generateSingle();">
                                <label for="reg_number" class="block text-sm font-medium text-gray-700 mb-1.5">Registration Number <span class="text-red-500">*</span></label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2"></path></svg>
                                    </div>
                                    <input type="text" id="reg_number" name="reg_number" placeholder="e.g., BPH26354, CS20123" 
                                           class="block w-full pl-10 pr-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 bg-gray-50/30 transition-all" required>
                                </div>
                                <input type="hidden" id="template-type" name="template" value="word">
                                <button type="submit" class="mt-5 w-full sm:w-auto inline-flex justify-center items-center gap-2 px-5 py-2.5 bg-gradient-to-r from-indigo-600 to-blue-600 hover:from-indigo-700 hover:to-blue-700 text-white font-medium rounded-lg shadow-md hover:shadow-lg transition-all duration-200">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                    Generate Transcript
                                </button>
                            </form>
                        </div>
                    </div>

                    <!-- ========== 2. BATCH TRANSCRIPTS SECTION ========== -->
                    <div class="bg-white rounded-xl border border-gray-200 shadow-sm card-hover overflow-hidden">
                        <div class="border-b border-gray-100 bg-gray-50/70 px-5 py-4">
                            <div class="flex items-center gap-2">
                                <div class="p-1.5 bg-emerald-100 rounded-lg">
                                    <svg class="w-5 h-5 text-emerald-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path>
                                    </svg>
                                </div>
                                <h2 class="text-lg font-semibold text-gray-800">Batch Transcripts</h2>
                                <span class="ml-2 text-xs bg-emerald-100 text-emerald-800 px-2 py-0.5 rounded-full">Manual Control</span>
                            </div>
                        </div>
                        <div class="p-5">
                            <form action="{{ route('transcript.batch.queue') }}" method="POST" enctype="multipart/form-data" id="batch-form">
                                @csrf
                                <input type="hidden" id="batch-template-type" name="template" value="word">
                                <input type="hidden" name="mode" value="sequential">
                                
                                <label for="file" class="block text-sm font-medium text-gray-700 mb-1.5">Upload Student List <span class="text-xs text-gray-500 font-normal">(CSV, Excel, or TXT)</span></label>
                                
                                <div class="mb-4 p-3 bg-blue-50 rounded-lg border border-blue-200">
                                    <div class="flex items-center gap-2">
                                        <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                        </svg>
                                        <p class="text-sm text-blue-800">
                                            <span class="font-semibold">Batch Mode:</span> Upload CSV to see all students. Download individually or use batch controls.
                                        </p>
                                    </div>
                                </div>
                                
                                <div class="flex flex-col sm:flex-row gap-3 items-start sm:items-center">
                                    <div class="relative w-full">
                                        <input type="file" id="file" name="file" accept=".csv,.txt,.xlsx,.xls" 
                                               class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 transition-all cursor-pointer border border-gray-300 rounded-lg shadow-sm" required>
                                    </div>
                                    <button type="submit" id="batch-submit-btn" class="inline-flex justify-center items-center gap-2 px-5 py-2.5 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 text-white font-medium rounded-lg shadow-md transition-all duration-200 whitespace-nowrap">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4-4m0 0L8 8m4-4v12"></path></svg>
                                        Prepare Batch Downloads
                                    </button>
                                </div>
                                <p class="mt-3 text-xs text-gray-500 flex items-center gap-1">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                    Upload CSV file with registration numbers. You'll be able to download each transcript individually.
                                </p>
                            </form>
                        </div>
                    </div>

                    <!-- ========== 3. PREVIEW SECTION ========== -->
                    <div class="bg-white rounded-xl border border-gray-200 shadow-sm card-hover overflow-hidden">
                        <div class="border-b border-gray-100 bg-gray-50/70 px-5 py-4">
                            <div class="flex items-center gap-2">
                                <div class="p-1.5 bg-amber-100 rounded-lg">
                                    <svg class="w-5 h-5 text-amber-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                    </svg>
                                </div>
                                <h2 class="text-lg font-semibold text-gray-800">Preview Student Grades</h2>
                                <span class="ml-2 text-xs bg-amber-100 text-amber-800 px-2 py-0.5 rounded-full">live preview</span>
                            </div>
                        </div>
                        <div class="p-5">
                            <div class="flex flex-col sm:flex-row gap-4 items-end">
                                <div class="flex-1 w-full">
                                    <label for="preview_reg" class="block text-sm font-medium text-gray-700 mb-1.5">Registration Number</label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                            <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                                        </div>
                                        <input type="text" id="preview_reg" placeholder="e.g., BPH26354" 
                                               class="block w-full pl-10 pr-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500 bg-gray-50/30">
                                    </div>
                                </div>
                                <button type="button" onclick="previewGrades()" 
                                        class="inline-flex justify-center items-center gap-2 px-6 py-2.5 bg-amber-500 hover:bg-amber-600 text-white font-medium rounded-lg shadow-md transition-all duration-200">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                    Preview Grades
                                </button>
                            </div>
                            <div id="preview-result" class="mt-6 transition-all duration-300"></div>
                        </div>
                    </div>
                    
                    <div class="text-center text-xs text-gray-400 pt-2 border-t border-gray-100 mt-2">
                        <span>🔒 Secure academic portal • Transcripts generated in real-time • Supports Word & Excel formats</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/flowbite@4.0.1/dist/flowbite.min.js"></script>
    
    <script>
        let currentTemplate = 'word';
        
        function setTemplate(type) {
            currentTemplate = type;
            const wordBtn = document.getElementById('btn-word');
            const excelBtn = document.getElementById('btn-excel');
            const selectedSpan = document.getElementById('selected-template');
            const templateInput = document.getElementById('template-type');
            const batchTemplateInput = document.getElementById('batch-template-type');
            
            if (type === 'word') {
                wordBtn.classList.remove('bg-gray-200', 'text-gray-700');
                wordBtn.classList.add('bg-indigo-600', 'text-white', 'shadow-md');
                excelBtn.classList.remove('bg-indigo-600', 'text-white', 'shadow-md');
                excelBtn.classList.add('bg-gray-200', 'text-gray-700');
                selectedSpan.textContent = 'Word Document';
            } else {
                excelBtn.classList.remove('bg-gray-200', 'text-gray-700');
                excelBtn.classList.add('bg-indigo-600', 'text-white', 'shadow-md');
                wordBtn.classList.remove('bg-indigo-600', 'text-white', 'shadow-md');
                wordBtn.classList.add('bg-gray-200', 'text-gray-700');
                selectedSpan.textContent = 'Excel Spreadsheet';
            }
            
            if (templateInput) templateInput.value = type;
            if (batchTemplateInput) batchTemplateInput.value = type;
        }
        
        function generateSingle() {
            const regNumber = document.getElementById('reg_number').value.trim();
            if (!regNumber) {
                alert('Please enter a registration number');
                return;
            }
            window.location.href = '/transcript/' + encodeURIComponent(regNumber) + '?template=' + currentTemplate;
        }
        
        async function previewGrades() {
            const regNumber = document.getElementById('preview_reg').value.trim();
            const resultDiv = document.getElementById('preview-result');
            
            if (!regNumber) {
                resultDiv.innerHTML = `
                    <div class="flex items-center p-4 rounded-xl bg-amber-50 border-l-8 border-amber-400 text-amber-800 shadow-sm">
                        <svg class="w-5 h-5 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                        <span class="font-medium">Please enter a registration number</span>
                    </div>
                `;
                return;
            }
            
            resultDiv.innerHTML = `
                <div class="animate-pulse bg-gray-100 rounded-xl p-5 border border-gray-200">
                    <div class="flex items-center space-x-3">
                        <div class="rounded-full bg-gray-300 h-8 w-8"></div>
                        <div class="flex-1 h-4 bg-gray-300 rounded"></div>
                    </div>
                    <div class="mt-3 space-y-2">
                        <div class="h-3 bg-gray-300 rounded w-3/4"></div>
                        <div class="h-3 bg-gray-300 rounded w-1/2"></div>
                    </div>
                </div>
            `;
            
            try {
                const response = await fetch(`/transcript/preview/${encodeURIComponent(regNumber)}`);
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}`);
                }
                const data = await response.json();
                
                if (data.error) {
                    resultDiv.innerHTML = `
                        <div class="flex items-center p-4 rounded-xl bg-red-50 border-l-8 border-red-500 text-red-800 shadow-sm">
                            <svg class="w-5 h-5 mr-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path></svg>
                            <span class="font-medium">${escapeHtml(data.error)}</span>
                        </div>
                    `;
                    return;
                }
                
                let gradesHtml = `
                    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden shadow-md animate-slide-in">
                        <div class="bg-gradient-to-r from-indigo-50 to-blue-50 px-5 py-4 border-b border-gray-200">
                            <div class="flex flex-wrap justify-between items-start gap-2">
                                <div>
                                    <h3 class="text-xl font-bold text-gray-800">${escapeHtml(data.student.fullname)}</h3>
                                    <p class="text-sm text-gray-600 mt-0.5">${escapeHtml(data.student.username)} • ${escapeHtml(data.student.program)}</p>
                                </div>
                                <div class="bg-white rounded-full px-3 py-1 shadow-sm border">
                                    <span class="text-xs font-semibold text-gray-700">📊 Total Grades: ${data.total_grades || 0}</span>
                                </div>
                            </div>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="preview-table min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Course Code</th>
                                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Course Name</th>
                                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Grade</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-100">
                `;
                
                if (data.grades && data.grades.length > 0) {
                    data.grades.forEach(grade => {
                        let gradeBadgeClass = "bg-gray-100 text-gray-800";
                        const gradeVal = (grade.grade || '').toString();
                        if (gradeVal.startsWith('A')) gradeBadgeClass = "bg-green-100 text-green-800";
                        else if (gradeVal.startsWith('B')) gradeBadgeClass = "bg-blue-100 text-blue-800";
                        else if (gradeVal.startsWith('C')) gradeBadgeClass = "bg-yellow-100 text-yellow-800";
                        else if (gradeVal === 'D' || gradeVal === 'E' || gradeVal === 'F') gradeBadgeClass = "bg-red-100 text-red-800";
                        
                        gradesHtml += `
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-5 py-3 whitespace-nowrap text-sm font-medium text-gray-900">${escapeHtml(grade.course_code)}</td>
                                <td class="px-5 py-3 text-sm text-gray-700">${escapeHtml(grade.course_name)}</td>
                                <td class="px-5 py-3 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${gradeBadgeClass}">${escapeHtml(grade.grade)}</span>
                                </td>
                            </tr>
                        `;
                    });
                } else {
                    gradesHtml += `
                        <tr>
                            <td colspan="3" class="px-5 py-6 text-center text-sm text-gray-500">📭 No grade records found for this student.</td>
                        </tr>
                    `;
                }
                
                gradesHtml += `
                                </tbody>
                            </table>
                        </div>
                        <div class="bg-gray-50 px-5 py-3 text-right text-xs text-gray-400 border-t">
                            Academic transcript preview • real-time data
                        </div>
                    </div>
                `;
                
                resultDiv.innerHTML = gradesHtml;
                
            } catch (error) {
                console.error("Preview error:", error);
                resultDiv.innerHTML = `
                    <div class="flex items-center p-4 rounded-xl bg-red-50 border-l-8 border-red-500 text-red-800 shadow-sm">
                        <svg class="w-5 h-5 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        <span class="font-medium">Network error: ${escapeHtml(error.message)}</span>
                    </div>
                `;
            }
        }
        
        function escapeHtml(str) {
            if (!str) return '';
            return str.replace(/[&<>]/g, function(m) {
                if (m === '&') return '&amp;';
                if (m === '<') return '&lt;';
                if (m === '>') return '&gt;';
                return m;
            });
        }
        
        document.getElementById('preview_reg')?.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                previewGrades();
            }
        });
    </script>
</body>
</html>