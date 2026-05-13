<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Batch Transcript Processor - Auto Download</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .progress-bar { transition: width 0.3s ease; }
    </style>
</head>
<body class="bg-gradient-to-br from-slate-50 to-blue-50 min-h-screen">
    <div class="max-w-2xl mx-auto px-4 py-12">
        <div class="bg-white rounded-2xl shadow-xl overflow-hidden">
            <div class="bg-gradient-to-r from-indigo-700 to-blue-700 px-6 py-6">
                <h1 class="text-2xl font-bold text-white">Auto Download in Progress</h1>
                <p class="text-indigo-100 mt-1">Transcripts are downloading automatically</p>
            </div>
            
            <div class="p-6">
                <div class="mb-6">
                    <div class="flex justify-between text-sm text-gray-600 mb-2">
                        <span>Progress</span>
                        <span id="progress-text">0 / 0</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-3 overflow-hidden">
                        <div id="progress-bar" class="progress-bar bg-indigo-600 h-3 rounded-full" style="width: 0%"></div>
                    </div>
                </div>
                
                <div class="bg-gray-50 rounded-xl p-4 mb-6">
                    <div class="flex items-center gap-2 mb-3">
                        <div id="status-icon" class="w-3 h-3 bg-green-500 rounded-full animate-pulse"></div>
                        <span id="status-message" class="text-gray-700 font-medium">Starting downloads...</span>
                    </div>
                    <div class="text-sm text-gray-500 space-y-1">
                        <p>✅ Completed: <span id="completed-count">0</span></p>
                        <p>❌ Failed: <span id="failed-count">0</span></p>
                        <p>⏳ Remaining: <span id="remaining-count">0</span></p>
                    </div>
                </div>
                
                <button onclick="window.location.href='/'"
                        class="w-full py-3 bg-gray-500 hover:bg-gray-600 text-white font-medium rounded-lg transition">
                    Return to Home
                </button>
            </div>
        </div>
    </div>
    
    <script>
        let queueId = '{{ $queueId }}';
        let currentIndex = {{ $currentIndex }};
        let total = {{ $total }};
        
        function downloadNext() {
            if (currentIndex >= total) return;
            
            const downloadUrl = `/transcript/batch/process/${queueId}/${currentIndex}`;
            const iframe = document.createElement('iframe');
            iframe.style.display = 'none';
            iframe.src = downloadUrl;
            document.body.appendChild(iframe);
            
            document.getElementById('status-message').innerHTML = '📥 Downloading transcript ' + (currentIndex + 1) + ' of ' + total + '...';
        }
        
        function updateProgress() {
            fetch(`/transcript/batch/status/${queueId}`)
                .then(res => res.json())
                .then(data => {
                    const processed = data.completed + data.failed;
                    const percentage = (processed / total) * 100;
                    
                    document.getElementById('progress-bar').style.width = `${percentage}%`;
                    document.getElementById('progress-text').innerText = `${processed} / ${total}`;
                    document.getElementById('completed-count').innerText = data.completed;
                    document.getElementById('failed-count').innerText = data.failed;
                    document.getElementById('remaining-count').innerText = total - processed;
                    
                    if (data.current_index > currentIndex) {
                        currentIndex = data.current_index;
                        if (currentIndex < total) {
                            setTimeout(downloadNext, 1500);
                        }
                    }
                    
                    if (processed >= total) {
                        document.getElementById('status-message').innerHTML = '✅ Complete! All transcripts downloaded.';
                        document.getElementById('status-icon').className = 'w-3 h-3 bg-green-500 rounded-full';
                    }
                })
                .catch(err => console.error('Status check failed:', err));
        }
        
        downloadNext();
        setInterval(updateProgress, 2000);
        updateProgress();
    </script>
</body>
</html>