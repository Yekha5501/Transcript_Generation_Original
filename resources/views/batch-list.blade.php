<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Batch Transcript List | Academic Suite</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .download-btn {
            transition: all 0.2s ease;
        }
        .download-btn:hover {
            transform: translateY(-1px);
        }
        .status-badge {
            transition: all 0.2s ease;
        }
        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        .animate-spin {
            animation: spin 1s linear infinite;
        }
        select option {
            padding: 8px;
        }
    </style>
</head>
<body class="bg-gradient-to-br from-slate-50 to-blue-50 min-h-screen">
    <div class="max-w-6xl mx-auto px-4 py-8">
        <div class="bg-white rounded-2xl shadow-xl overflow-hidden">
            <!-- Header -->
            <div class="bg-gradient-to-r from-indigo-700 to-blue-700 px-6 py-6">
                <div class="flex items-center justify-between flex-wrap gap-4">
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-white/20 rounded-xl backdrop-blur-sm">
                            <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                        </div>
                        <div>
                            <h1 class="text-2xl font-bold text-white">Batch Transcript Generator</h1>
                            <p class="text-indigo-100 text-sm mt-1">{{ $total }} student(s) ready for download</p>
                        </div>
                    </div>
                    <div class="flex gap-3 flex-wrap">
                        <!-- Template Type Switcher -->
                        <div class="flex items-center gap-2 bg-white/10 rounded-lg px-3 py-1">
                            <span class="text-white text-sm">Type:</span>
                            <button onclick="switchTemplate('word')" id="switch-word" class="px-3 py-1 text-sm rounded {{ $template === 'word' ? 'bg-white text-indigo-700' : 'text-white hover:bg-white/20' }} transition">
                                📄 Word
                            </button>
                            <button onclick="switchTemplate('excel')" id="switch-excel" class="px-3 py-1 text-sm rounded {{ $template === 'excel' ? 'bg-white text-indigo-700' : 'text-white hover:bg-white/20' }} transition">
                                📊 Excel
                            </button>
                        </div>
                        
                        <!-- Template File Dropdown -->
                        <div class="flex items-center gap-2 bg-white/10 rounded-lg px-3 py-1">
                            <span class="text-white text-sm">File:</span>
                            <select id="template-file-select" onchange="updateTemplateFile()" 
                                    class="bg-white/20 text-white text-sm rounded px-2 py-1 border border-white/20 focus:outline-none focus:ring-2 focus:ring-white/50">
                                @if(isset($templates) && isset($templates[$template]))
                                    @foreach($templates[$template] as $file)
                                        <option value="{{ $file }}" class="text-gray-800" 
                                            {{ (isset($template_file) && $template_file === $file) ? 'selected' : '' }}>
                                            {{ $file }}
                                        </option>
                                    @endforeach
                                @else
                                    <option value="" class="text-gray-800">No templates available</option>
                                @endif
                            </select>
                        </div>
                        
                        <button onclick="downloadAll()" class="px-4 py-2 bg-emerald-500 hover:bg-emerald-600 text-white rounded-lg transition flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                            Download All ({{ $total }})
                        </button>
                        <a href="/" class="px-4 py-2 bg-gray-500 hover:bg-gray-600 text-white rounded-lg transition flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                            Back to Home
                        </a>
                    </div>
                </div>
            </div>

            <!-- Stats Bar -->
            <div class="bg-gray-50 border-b px-6 py-3">
                <div class="flex justify-between items-center flex-wrap gap-2 text-sm">
                    <div class="flex gap-6">
                        <div>
                            <span class="text-gray-500">Total:</span>
                            <span class="font-semibold text-gray-800 ml-1">{{ $total }}</span>
                        </div>
                        <div>
                            <span class="text-gray-500">Downloaded:</span>
                            <span id="downloaded-count" class="font-semibold text-green-600 ml-1">0</span>
                        </div>
                        <div>
                            <span class="text-gray-500">Remaining:</span>
                            <span id="remaining-count" class="font-semibold text-blue-600 ml-1">{{ $total }}</span>
                        </div>
                    </div>
                    <div>
                        <span class="text-gray-500">Template:</span>
                        <span id="current-template-display" class="font-semibold text-indigo-600 ml-1">{{ ucfirst($template) }}</span>
                        <span class="text-gray-400 mx-1">|</span>
                        <span class="text-gray-500">File:</span>
                        <span id="current-template-file" class="font-semibold text-indigo-600 ml-1">{{ $template_file ?? 'Default' }}</span>
                    </div>
                </div>
            </div>

            <!-- Student List Table -->
            <div class="overflow-x-auto max-h-[500px] overflow-y-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50 sticky top-0">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">#</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Registration Number</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Student Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200" id="student-table-body">
                        @foreach($students as $index => $student)
                        <tr id="row-{{ $student['id'] }}" class="hover:bg-gray-50 transition">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $index + 1 }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-mono font-medium text-gray-900">{{ $student['registration_number'] }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700" id="name-{{ $student['id'] }}">
                                <span class="text-gray-400">Loading...</span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span id="status-{{ $student['id'] }}" class="status-badge px-2 py-1 text-xs rounded-full bg-gray-100 text-gray-600">
                                    Pending
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <button onclick="downloadTranscript('{{ $student['id'] }}', '{{ $student['registration_number'] }}')" 
                                        id="btn-{{ $student['id'] }}"
                                        class="download-btn px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm rounded-lg transition flex items-center gap-1">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                                    Download
                                </button>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Footer Actions -->
            <div class="bg-gray-50 px-6 py-4 border-t flex justify-between items-center flex-wrap gap-3">
                <div class="text-sm text-gray-500">
                    💡 Click download buttons individually or use "Download All"
                </div>
                <div class="flex gap-3 flex-wrap">
                    <button onclick="downloadPending()" class="px-4 py-2 bg-blue-500 hover:bg-blue-600 text-white rounded-lg transition flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        Download Pending
                    </button>
                    <button onclick="resetDownloads()" class="px-4 py-2 bg-yellow-500 hover:bg-yellow-600 text-white rounded-lg transition flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                        Reset All
                    </button>
                    <button onclick="refreshTemplates()" class="px-4 py-2 bg-purple-500 hover:bg-purple-600 text-white rounded-lg transition flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                        Refresh Templates
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
    // Store student data and download states
    const students = @json($students);
    const queueId = '{{ $queueId }}';
    let currentTemplate = '{{ $template }}';
    let currentTemplateFile = '{{ $template_file ?? '' }}';
    const templates = @json($templates ?? []);
    
    let downloadedCount = 0;
    let downloading = false;
    let downloadedStudents = new Set();

    // Track downloaded status from localStorage
    function loadDownloadedStatus() {
        const saved = localStorage.getItem(`batch_${queueId}_downloaded`);
        if (saved) {
            const downloadedIds = JSON.parse(saved);
            downloadedIds.forEach(id => {
                downloadedStudents.add(id);
                updateButtonToDownloaded(id);
            });
            downloadedCount = downloadedStudents.size;
            updateStats();
        }
    }

    // Save downloaded status to localStorage
    function saveDownloadedStatus() {
        localStorage.setItem(`batch_${queueId}_downloaded`, JSON.stringify([...downloadedStudents]));
    }

    // Update button to show downloaded state
    function updateButtonToDownloaded(studentId) {
        const button = document.getElementById(`btn-${studentId}`);
        const statusSpan = document.getElementById(`status-${studentId}`);
        
        if (button && !button.disabled) {
            statusSpan.innerHTML = '<span class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-600">Downloaded</span>';
            button.innerHTML = `
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                Done
            `;
            button.classList.remove('bg-indigo-600', 'hover:bg-indigo-700');
            button.classList.add('bg-green-600');
            button.disabled = true;
        }
    }

    // Sanitize filename (remove special characters, replace spaces with underscores)
    function sanitizeFilename(filename) {
        if (!filename) return 'transcript';
        let sanitized = filename.replace(/[^\w\s-]/g, '');
        sanitized = sanitized.replace(/[\s]+/g, '_');
        sanitized = sanitized.replace(/_+/g, '_');
        sanitized = sanitized.replace(/^_+|_+$/g, '');
        return sanitized;
    }

    // Update the template file dropdown
    function updateTemplateDropdown() {
        const select = document.getElementById('template-file-select');
        if (!select) return;
        
        select.innerHTML = '';
        const templateList = templates[currentTemplate] || [];
        
        if (templateList.length === 0) {
            const option = document.createElement('option');
            option.value = '';
            option.textContent = 'No templates available';
            option.disabled = true;
            select.appendChild(option);
            return;
        }
        
        templateList.forEach(file => {
            const option = document.createElement('option');
            option.value = file;
            option.textContent = file;
            if (file === currentTemplateFile) {
                option.selected = true;
            }
            select.appendChild(option);
        });
        
        // If currentTemplateFile is not in the list, select the first one
        if (!templateList.includes(currentTemplateFile) && templateList.length > 0) {
            currentTemplateFile = templateList[0];
            select.value = currentTemplateFile;
        }
        
        document.getElementById('current-template-file').innerText = currentTemplateFile || 'Default';
    }

    // Switch template type
    function switchTemplate(template) {
        if (template === currentTemplate) return;
        currentTemplate = template;
        
        // Find matching template file for this type
        const templateList = templates[template] || [];
        if (templateList.length > 0) {
            currentTemplateFile = templateList[0];
        } else {
            currentTemplateFile = '';
        }
        
        // Update UI
        document.getElementById('current-template-display').innerText = template === 'excel' ? 'Excel' : 'Word';
        document.getElementById('current-template-file').innerText = currentTemplateFile || 'No template';
        
        // Update button styles
        const wordBtn = document.getElementById('switch-word');
        const excelBtn = document.getElementById('switch-excel');
        
        if (template === 'word') {
            wordBtn.classList.add('bg-white', 'text-indigo-700');
            wordBtn.classList.remove('text-white', 'hover:bg-white/20');
            excelBtn.classList.remove('bg-white', 'text-indigo-700');
            excelBtn.classList.add('text-white', 'hover:bg-white/20');
        } else {
            excelBtn.classList.add('bg-white', 'text-indigo-700');
            excelBtn.classList.remove('text-white', 'hover:bg-white/20');
            wordBtn.classList.remove('bg-white', 'text-indigo-700');
            wordBtn.classList.add('text-white', 'hover:bg-white/20');
        }
        
        // Update dropdown
        updateTemplateDropdown();
        
        showNotification(`Switched to ${template === 'excel' ? 'Excel' : 'Word'} template.`, 'info');
    }

    // Update template file selection
    function updateTemplateFile() {
        const select = document.getElementById('template-file-select');
        if (select) {
            currentTemplateFile = select.value;
            document.getElementById('current-template-file').innerText = currentTemplateFile || 'Default';
            showNotification(`Selected template: ${currentTemplateFile}`, 'info');
        }
    }

    // Refresh templates from server
    async function refreshTemplates() {
        try {
            const response = await fetch('/api/templates');
            if (response.ok) {
                const data = await response.json();
                // Update the templates object
                Object.assign(templates, data);
                // Refresh the dropdown
                updateTemplateDropdown();
                showNotification('Templates refreshed successfully!', 'success');
            } else {
                throw new Error('Failed to refresh templates');
            }
        } catch (error) {
            console.error('Refresh error:', error);
            showNotification('Failed to refresh templates: ' + error.message, 'error');
        }
    }

    // Show notification
    function showNotification(message, type = 'info') {
        const colors = {
            info: 'bg-blue-500',
            success: 'bg-green-500',
            error: 'bg-red-500',
            warning: 'bg-yellow-500'
        };
        
        const notification = document.createElement('div');
        notification.className = `fixed bottom-4 right-4 ${colors[type]} text-white px-6 py-3 rounded-lg shadow-lg z-50 transition-opacity duration-300 max-w-md`;
        notification.innerHTML = message;
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.style.opacity = '0';
            setTimeout(() => notification.remove(), 300);
        }, 3000);
    }

    // Fetch student names and update table
    async function loadStudentNames() {
        for (const student of students) {
            try {
                const response = await fetch(`/transcript/preview/${encodeURIComponent(student.registration_number)}`);
                if (response.ok) {
                    const data = await response.json();
                    if (data.student && data.student.fullname) {
                        document.getElementById(`name-${student.id}`).innerHTML = 
                            `<span class="font-medium text-gray-800">${escapeHtml(data.student.fullname)}</span>`;
                        document.getElementById(`row-${student.id}`)?.setAttribute('data-student-name', data.student.fullname);
                    } else {
                        document.getElementById(`name-${student.id}`).innerHTML = 
                            `<span class="text-red-400">Student not found</span>`;
                        document.getElementById(`row-${student.id}`)?.setAttribute('data-student-name', 'Student_Not_Found');
                    }
                } else {
                    document.getElementById(`name-${student.id}`).innerHTML = 
                        `<span class="text-red-400">Not found</span>`;
                    document.getElementById(`row-${student.id}`)?.setAttribute('data-student-name', 'Student_Not_Found');
                }
            } catch (error) {
                console.error(`Failed to load name for ${student.registration_number}`);
                document.getElementById(`name-${student.id}`).innerHTML = 
                    `<span class="text-red-400">Error loading</span>`;
                document.getElementById(`row-${student.id}`)?.setAttribute('data-student-name', 'Student_Error');
            }
        }
    }

    // Get student name from the DOM
    function getStudentName(studentId) {
        const nameSpan = document.getElementById(`name-${studentId}`);
        if (nameSpan) {
            let name = nameSpan.innerText.trim();
            name = name.replace('Student not found', '').replace('Not found', '').replace('Error loading', '').trim();
            if (name && name !== 'Loading...' && name !== 'Student not found' && name !== 'Not found' && name !== 'Error loading') {
                return name;
            }
        }
        const row = document.getElementById(`row-${studentId}`);
        if (row && row.getAttribute('data-student-name')) {
            return row.getAttribute('data-student-name');
        }
        return 'Student';
    }

    // Download a single transcript
    async function downloadTranscript(studentId, regNumber) {
        const button = document.getElementById(`btn-${studentId}`);
        const statusSpan = document.getElementById(`status-${studentId}`);
        
        if (button.disabled && downloadedStudents.has(studentId)) {
            showNotification('Already downloaded! Use Reset All to download again.', 'warning');
            return;
        }
        
        if (button.disabled) return;
        
        const studentName = getStudentName(studentId);
        const sanitizedStudentName = sanitizeFilename(studentName);
        
        // Disable button and show loading
        button.disabled = true;
        button.innerHTML = `
            <svg class="w-4 h-4 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            Generating...
        `;
        statusSpan.innerHTML = '<span class="px-2 py-1 text-xs rounded-full bg-yellow-100 text-yellow-600">Generating...</span>';
        
        try {
            const url = `/transcript/batch/download/${queueId}/${studentId}?template=${currentTemplate}&template_file=${encodeURIComponent(currentTemplateFile)}`;
            const response = await fetch(url);
            
            if (response.ok) {
                const blob = await response.blob();
                const downloadUrl = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = downloadUrl;
                a.download = `${sanitizedStudentName}.${currentTemplate === 'excel' ? 'xlsx' : 'docx'}`;
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                window.URL.revokeObjectURL(downloadUrl);
                
                statusSpan.innerHTML = '<span class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-600">Downloaded</span>';
                button.innerHTML = `
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                    Done
                `;
                button.classList.remove('bg-indigo-600', 'hover:bg-indigo-700');
                button.classList.add('bg-green-600');
                
                downloadedStudents.add(studentId);
                downloadedCount = downloadedStudents.size;
                saveDownloadedStatus();
                updateStats();
                showNotification(`Downloaded transcript for ${studentName}`, 'success');
            } else {
                const error = await response.json();
                throw new Error(error.error || 'Download failed');
            }
        } catch (error) {
            console.error('Download failed:', error);
            statusSpan.innerHTML = '<span class="px-2 py-1 text-xs rounded-full bg-red-100 text-red-600">Failed</span>';
            button.innerHTML = `
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                Retry
            `;
            button.disabled = false;
            button.classList.remove('bg-indigo-600');
            button.classList.add('bg-red-600');
            showNotification(`Failed to download ${studentName}: ${error.message}`, 'error');
        }
    }

    // Download all transcripts in sequence
    async function downloadAll() {
        if (downloading) {
            showNotification('Download already in progress!', 'warning');
            return;
        }
        
        if (!currentTemplateFile) {
            showNotification('Please select a template file first!', 'warning');
            return;
        }
        
        downloading = true;
        showNotification('Starting batch download...', 'info');
        
        for (const student of students) {
            if (!downloadedStudents.has(student.id)) {
                await downloadTranscript(student.id, student.registration_number);
                await new Promise(resolve => setTimeout(resolve, 1500));
            }
        }
        
        downloading = false;
        if (downloadedStudents.size === students.length) {
            showNotification('All transcripts downloaded successfully!', 'success');
        }
    }

    // Download only pending transcripts
    async function downloadPending() {
        if (downloading) {
            showNotification('Download already in progress!', 'warning');
            return;
        }
        
        if (!currentTemplateFile) {
            showNotification('Please select a template file first!', 'warning');
            return;
        }
        
        downloading = true;
        const pending = students.filter(s => !downloadedStudents.has(s.id));
        
        if (pending.length === 0) {
            showNotification('No pending downloads!', 'info');
            downloading = false;
            return;
        }
        
        showNotification(`Downloading ${pending.length} pending transcript(s)...`, 'info');
        
        for (const student of pending) {
            await downloadTranscript(student.id, student.registration_number);
            await new Promise(resolve => setTimeout(resolve, 1000));
        }
        
        downloading = false;
        showNotification('Pending downloads completed!', 'success');
    }

    // Reset all download states
    function resetDownloads() {
        if (confirm('Reset all download statuses? This will allow you to download transcripts again.')) {
            localStorage.removeItem(`batch_${queueId}_downloaded`);
            downloadedStudents.clear();
            downloadedCount = 0;
            
            students.forEach(student => {
                const button = document.getElementById(`btn-${student.id}`);
                const statusSpan = document.getElementById(`status-${student.id}`);
                
                if (button) {
                    button.disabled = false;
                    button.innerHTML = `
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                        Download
                    `;
                    button.classList.remove('bg-green-600', 'bg-red-600');
                    button.classList.add('bg-indigo-600', 'hover:bg-indigo-700');
                    statusSpan.innerHTML = '<span class="px-2 py-1 text-xs rounded-full bg-gray-100 text-gray-600">Pending</span>';
                }
            });
            
            // Remove completion message if exists
            const completionMsg = document.querySelector('.completion-message');
            if (completionMsg) {
                completionMsg.remove();
            }
            
            updateStats();
            showNotification('All download statuses have been reset!', 'success');
        }
    }

    // Update statistics display
    function updateStats() {
        const remaining = students.length - downloadedCount;
        document.getElementById('downloaded-count').innerText = downloadedCount;
        document.getElementById('remaining-count').innerText = remaining;
        
        if (downloadedCount === students.length && students.length > 0) {
            if (!document.querySelector('.completion-message')) {
                const tableBody = document.getElementById('student-table-body');
                const completionMsg = document.createElement('div');
                completionMsg.className = 'completion-message bg-green-50 border-l-4 border-green-500 p-4 m-4 rounded';
                completionMsg.innerHTML = `
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-green-700">All transcripts have been downloaded successfully!</p>
                        </div>
                    </div>
                `;
                if (tableBody && tableBody.parentNode) {
                    tableBody.parentNode.insertBefore(completionMsg, tableBody);
                }
            }
        }
    }

    // Escape HTML helper
    function escapeHtml(str) {
        if (!str) return '';
        return str.replace(/[&<>]/g, function(m) {
            if (m === '&') return '&amp;';
            if (m === '<') return '&lt;';
            if (m === '>') return '&gt;';
            return m;
        });
    }

    // Load student names, saved status, and initialize dropdown on page load
    document.addEventListener('DOMContentLoaded', function() {
        loadStudentNames();
        loadDownloadedStatus();
        updateTemplateDropdown();
    });
    </script>
</body>
</html>