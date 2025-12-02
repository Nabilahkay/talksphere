<?php
require_once '../config/session.php';
checkLogin();
require_once '../config/database.php';
$user = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ar Marker</title>
    
    <!-- QR Scanner Library -->
    <script src="https://unpkg.com/html5-qrcode"></script>
    
    <!-- Three.js for 3D Display -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #c92a2a 0%, #8B0000 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .container {
            width: 90%;
            max-width: 1200px;
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            overflow: hidden;
        }

        /* Header */
        .header {
            background: linear-gradient(135deg, #c92a2a 0%, #a61e1e 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }

        .header h1 {
            font-size: 36px;
            margin-bottom: 10px;
        }

        .header p {
            font-size: 16px;
            opacity: 0.9;
        }

        .back-to-dashboard-btn {
            position: absolute;
            top: 20px;
            left: 20px;
            background: rgba(255, 255, 255, 0.2);
            color: white;
            text-decoration: none;
            padding: 10px 20px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .back-to-dashboard-btn:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: translateX(-3px);
        }

        /* Two Column Layout */
        .content {
            display: grid;
            grid-template-columns: 1fr 1.5fr;
            min-height: 600px;
        }

        /* Scanner Section */
        .scanner-section {
            padding: 30px;
            background: #f8f9fa;
            border-right: 1px solid #e0e0e0;
        }

        .scanner-title {
            font-size: 20px;
            color: #333;
            margin-bottom: 20px;
            font-weight: 600;
        }

        #reader {
            border-radius: 15px;
            overflow: hidden;
            border: 3px solid #667eea;
        }

        .scan-status {
            margin-top: 20px;
            padding: 15px;
            background: white;
            border-radius: 10px;
            text-align: center;
            font-size: 14px;
            color: #666;
        }

        .scan-status.success {
            background: #d4edda;
            color: #155724;
            font-weight: 600;
        }

        /* Viewer Section */
        .viewer-section {
            padding: 30px;
            display: flex;
            flex-direction: column;
            position: relative;
        }

        .empty-state {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100%;
            color: #999;
            text-align: center;
        }

        .empty-icon {
            font-size: 80px;
            margin-bottom: 20px;
            opacity: 0.5;
        }

        .empty-text {
            font-size: 18px;
        }

        /* Vocabulary Display */
        .vocab-display {
            display: none;
            height: 100%;
        }

        .vocab-display.active {
            display: flex;
            flex-direction: column;
        }

        .vocab-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .chinese-large {
            font-size: 80px;
            font-weight: bold;
            color: #c92a2a;
            margin-bottom: 15px;
        }

        .pinyin-large {
            font-size: 32px;
            color: #667eea;
            margin-bottom: 10px;
        }

        .meaning-large {
            font-size: 24px;
            color: #666;
        }

        /* 3D Canvas */
        #three-canvas {
            flex: 1;
            border-radius: 15px;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            margin: 20px 0;
        }

        /* Controls */
        .controls {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 20px;
        }

        .control-btn {
            padding: 15px 30px;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .control-btn.primary {
            background: #4CAF50;
            color: white;
        }

        .control-btn.primary:hover {
            background: #45a049;
            transform: translateY(-2px);
        }

        .control-btn.secondary {
            background: #2196F3;
            color: white;
        }

        .control-btn.secondary:hover {
            background: #1976d2;
            transform: translateY(-2px);
        }

        .control-btn.danger {
            background: #f44336;
            color: white;
        }

        .control-btn.danger:hover {
            background: #da190b;
            transform: translateY(-2px);
        }

        /* Back Button */
        .back-btn {
            position: absolute;
            top: 20px;
            right: 20px;
            background: rgba(0, 0, 0, 0.1);
            border: none;
            color: #333;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            cursor: pointer;
            font-size: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s;
        }

        .back-btn:hover {
            background: rgba(0, 0, 0, 0.2);
            transform: rotate(90deg);
        }

        /* Responsive */
        @media (max-width: 768px) {
            .content {
                grid-template-columns: 1fr;
            }

            .scanner-section {
                border-right: none;
                border-bottom: 1px solid #e0e0e0;
            }

            .chinese-large {
                font-size: 60px;
            }

            .pinyin-large {
                font-size: 24px;
            }

            .controls {
                flex-wrap: wrap;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header" style="position: relative;">
            <a href="../dashboard.php" class="back-to-dashboard-btn">
                ← Back to Dashboard
            </a>
            <h1>📱 AR Marker</h1>
            <p>Show a QR code to your camera to view vocabulary in 3D</p>
        </div>

        <!-- Content -->
        <div class="content">
            <!-- Scanner Section -->
            <div class="scanner-section">
                <h2 class="scanner-title">📷 Camera Scanner</h2>
                <div id="reader"></div>
                <div class="scan-status" id="scanStatus">
                    Waiting for QR code...
                </div>
            </div>

            <!-- Viewer Section -->
            <div class="viewer-section">
                <button class="back-btn" onclick="resetScanner()">×</button>

                <!-- Empty State -->
                <div class="empty-state" id="emptyState">
                    <div class="empty-icon">📱</div>
                    <div class="empty-text">
                        Show a QR code to your camera<br>to view vocabulary
                    </div>
                </div>

                <!-- Vocabulary Display -->
                <div class="vocab-display" id="vocabDisplay">
                    <div class="vocab-header">
                        <div class="chinese-large" id="chineseChar">你</div>
                        <div class="pinyin-large" id="pinyinText">nǐ</div>
                        <div class="meaning-large" id="meaningText">you (singular)</div>
                    </div>

                    <!-- 3D Canvas -->
                    <canvas id="three-canvas"></canvas>

                    <!-- Controls -->
                    <div class="controls">
                        <button class="control-btn primary" onclick="playAudio()">
                            <span>🔊</span>
                            <span>Play Audio</span>
                        </button>
                        <button class="control-btn secondary" onclick="resetView()">
                            <span>🔄</span>
                            <span>Reset View</span>
                        </button>
                        <button class="control-btn danger" onclick="resetScanner()">
                            <span>✕</span>
                            <span>Scan Again</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <audio id="audioPlayer"></audio>

    <script>
        let html5QrCode;
        let scene, camera, renderer, textMesh;
        let currentVocab = null;

        // Initialize QR Scanner
        function initScanner() {
            html5QrCode = new Html5Qrcode("reader");
            
            html5QrCode.start(
                { facingMode: "environment" },
                { fps: 10, qrbox: { width: 250, height: 250 } },
                onScanSuccess,
                onScanError
            ).catch(err => {
                console.error("Scanner error:", err);
                document.getElementById('scanStatus').textContent = 'Camera access denied. Please allow camera access.';
            });
        }

        // When QR is scanned
        function onScanSuccess(decodedText, decodedResult) {
            console.log(`QR Scanned: ${decodedText}`);
            
            document.getElementById('scanStatus').textContent = 'QR Code detected! Loading...';
            document.getElementById('scanStatus').classList.add('success');

            // Try to parse as vocab_id or URL
            let vocabId = null;
            
            try {
                // Try JSON format first
                const data = JSON.parse(decodedText);
                vocabId = data.vocab_id || data.id;
            } catch (e) {
                // Not JSON, try other formats
                
                // Check if it's a MyWebAR URL
                if (decodedText.includes('mywebar.com') || decodedText.includes('http')) {
                    // Extract vocab_id from URL or database lookup by URL
                    loadVocabularyByUrl(decodedText);
                    return;
                }
                
                // Try as plain number
                vocabId = parseInt(decodedText);
            }

            if (vocabId && !isNaN(vocabId)) {
                loadVocabulary(vocabId);
            } else {
                document.getElementById('scanStatus').textContent = 'Invalid QR code format: ' + decodedText.substring(0, 50);
                document.getElementById('scanStatus').classList.remove('success');
            }
        }
        
        // Load vocabulary by MyWebAR URL
        async function loadVocabularyByUrl(url) {
            try {

                //what URL is being searched
                const response = await fetch(`../api/get-vocabulary-by-url.php?url=${encodeURIComponent(url)}`);
                const vocab = await response.json();

                //see the response
                console.log('API Response:', vocab);

                if (vocab.error) {
                    alert('No vocabulary found for this MyWebAR URL');
                    document.getElementById('scanStatus').textContent = 'Vocabulary not linked to this QR code';
                    document.getElementById('scanStatus').classList.remove('success');
                    return;
                }

                currentVocab = vocab;
                displayVocabulary(vocab);

            } catch (error) {
                console.error('Error loading vocabulary:', error);
                alert('Failed to load vocabulary');
            }
        }

        function onScanError(error) {
            // Ignore continuous scan errors
        }

        // Load vocabulary from database
        async function loadVocabulary(vocabId) {
            try {
                const response = await fetch(`../api/get-vocabulary.php?vocab_id=${vocabId}`);
                const vocab = await response.json();

                if (vocab.error) {
                    alert('Vocabulary not found');
                    return;
                }

                currentVocab = vocab;
                displayVocabulary(vocab);

            } catch (error) {
                console.error('Error loading vocabulary:', error);
                alert('Failed to load vocabulary');
            }
        }

        // Display vocabulary
function displayVocabulary(vocab) {
    // Stop scanner
    html5QrCode.stop();

    // Check if MyWebAR URL exists
    if (vocab.mywebar_url && vocab.mywebar_url.trim() !== '') {
        // Show loading message
        document.getElementById('scanStatus').textContent = `✓ Opening AR for ${vocab.chinese_character}...`;
        document.getElementById('scanStatus').classList.add('success');
        
        // Direct redirect to MyWebAR (no popup blocker!)
        setTimeout(() => {
            window.location.href = vocab.mywebar_url;
        }, 1000);
        
        return; // Exit function
    }
    
    // Fallback: If no MyWebAR URL, show error
    alert('This vocabulary does not have an AR experience yet. Please add MyWebAR URL in admin panel.');
    resetScanner();
}

        // Initialize Three.js 3D Viewer
        function init3DViewer(character) {
            const canvas = document.getElementById('three-canvas');
            const width = canvas.clientWidth;
            const height = canvas.clientHeight;

            // Scene
            scene = new THREE.Scene();
            scene.background = new THREE.Color(0xf8f9fa);

            // Camera
            camera = new THREE.PerspectiveCamera(75, width / height, 0.1, 1000);
            camera.position.z = 5;

            // Renderer
            renderer = new THREE.WebGLRenderer({ canvas, antialias: true });
            renderer.setSize(width, height);

            // Lighting
            const ambientLight = new THREE.AmbientLight(0xffffff, 0.6);
            scene.add(ambientLight);

            const directionalLight = new THREE.DirectionalLight(0xffffff, 0.8);
            directionalLight.position.set(5, 5, 5);
            scene.add(directionalLight);

            // Create 3D Text
            const loader = new THREE.FontLoader();
            
            // Using built-in geometry as placeholder
            const geometry = new THREE.TorusGeometry(1, 0.4, 16, 100);
            const material = new THREE.MeshStandardMaterial({ 
                color: 0xc92a2a,
                metalness: 0.5,
                roughness: 0.5
            });
            textMesh = new THREE.Mesh(geometry, material);
            scene.add(textMesh);

            // Animation loop
            animate();

            // Handle window resize
            window.addEventListener('resize', onWindowResize);
        }

        function animate() {
            requestAnimationFrame(animate);

            // Rotate the object
            if (textMesh) {
                textMesh.rotation.x += 0.01;
                textMesh.rotation.y += 0.01;
            }

            renderer.render(scene, camera);
        }

        function onWindowResize() {
            const canvas = document.getElementById('three-canvas');
            camera.aspect = canvas.clientWidth / canvas.clientHeight;
            camera.updateProjectionMatrix();
            renderer.setSize(canvas.clientWidth, canvas.clientHeight);
        }

        // Play audio
        function playAudio() {
            const audio = document.getElementById('audioPlayer');
            if (audio.src) {
                audio.currentTime = 0;
                audio.play();
            } else {
                // Fallback: Use speech synthesis
                const utterance = new SpeechSynthesisUtterance(currentVocab.chinese_character);
                utterance.lang = 'zh-CN';
                utterance.rate = 0.7;
                window.speechSynthesis.speak(utterance);
            }
        }

        // Reset view
        function resetView() {
            if (camera) {
                camera.position.z = 5;
                camera.rotation.set(0, 0, 0);
            }
            if (textMesh) {
                textMesh.rotation.set(0, 0, 0);
            }
        }

        // Reset scanner
        function resetScanner() {
            document.getElementById('vocabDisplay').classList.remove('active');
            document.getElementById('emptyState').style.display = 'flex';
            document.getElementById('scanStatus').textContent = 'Waiting for QR code...';
            document.getElementById('scanStatus').classList.remove('success');
            
            // Restart scanner
            initScanner();
        }

        // Initialize on load
        window.addEventListener('load', () => {
            initScanner();
        });
    </script>
</body>
</html>