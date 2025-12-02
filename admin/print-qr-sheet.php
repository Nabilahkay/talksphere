<?php
session_start();
require_once '../config/database.php';
require_once '../config/ar_config.php';
require_once '../config/ar_objects.php';
require_once 'config/admin_auth.php';

checkAdminLogin();

// Get all objects with their QR codes
$all_objects = getAllARObjects();

// Organize by category
$categories = getAllCategories();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Print QR Codes - AR Learning</title>
    <style>
        @media print {
            .no-print { display: none !important; }
            .page-break { page-break-after: always; }
            body { background: white; }
        }
        
        body {
            font-family: Arial, sans-serif;
            padding: 20px;
            background: #f5f5f5;
        }
        
        .no-print {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .print-controls {
            display: flex;
            gap: 15px;
            align-items: center;
            flex-wrap: wrap;
        }
        
        .print-btn {
            padding: 15px 40px;
            background: linear-gradient(135deg, #4CAF50 0%, #45a049 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .print-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(76, 175, 80, 0.4);
        }

        .back-link {
            padding: 12px 25px;
            background: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
        }
        
        .print-header {
            text-align: center;
            margin-bottom: 40px;
            padding-bottom: 20px;
            border-bottom: 3px solid #8B0000;
            background: white;
            padding: 30px;
            border-radius: 10px;
        }

        .print-header h1 {
            color: #8B0000;
            font-size: 36px;
            margin-bottom: 10px;
        }

        .print-header p {
            color: #666;
            font-size: 16px;
        }

        .category-section {
            background: white;
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .category-title {
            font-size: 28px;
            font-weight: 700;
            color: #333;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #e0e0e0;
        }
        
        .qr-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 25px;
            margin: 20px 0;
        }
        
        .qr-item {
            border: 3px solid #333;
            padding: 20px;
            text-align: center;
            border-radius: 15px;
            background: white;
            page-break-inside: avoid;
        }
        
        .qr-item img {
            width: 200px;
            height: 200px;
            border: 3px dashed #ccc;
            padding: 10px;
            margin-bottom: 15px;
        }

        .qr-icon {
            font-size: 48px;
            margin-bottom: 10px;
        }
        
        .qr-label {
            font-size: 32px;
            font-weight: 700;
            color: #8B0000;
            margin-bottom: 5px;
        }

        .qr-pinyin {
            font-size: 18px;
            color: #666;
            font-style: italic;
            margin-bottom: 5px;
        }
        
        .qr-english {
            font-size: 16px;
            color: #333;
            font-weight: 600;
        }

        .cutting-guide {
            margin-top: 10px;
            padding-top: 10px;
            border-top: 1px dashed #ccc;
            font-size: 11px;
            color: #999;
        }

        .print-instructions {
            background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%);
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }

        .print-instructions h3 {
            color: #1565c0;
            margin-bottom: 10px;
        }

        .print-instructions ol {
            padding-left: 20px;
            color: #1976d2;
        }

        .print-instructions li {
            margin-bottom: 8px;
        }
    </style>
</head>
<body>
    <!-- Controls (Hidden when printing) -->
    <div class="no-print">
        <div class="print-instructions">
            <h3>📖 Printing Instructions:</h3>
            <ol>
                <li><strong>Print Settings:</strong> Use "Portrait" orientation, normal quality</li>
                <li><strong>Size:</strong> Each QR code is 200x200 pixels (recommended: 3x3 inches when printed)</li>
                <li><strong>Paper:</strong> Use white paper for best scanning results</li>
                <li><strong>Cutting:</strong> Cut along the borders, leaving some margin</li>
                <li><strong>Attaching:</strong> Use glue or tape to attach QR codes to physical objects</li>
            </ol>
        </div>

        <div class="print-controls">
            <button class="print-btn" onclick="window.print()">
                🖨️ Print All QR Codes
            </button>
            <a href="generate-physical-qr.php" class="back-link">← Back to Generator</a>
            <div style="flex: 1;"></div>
            <span style="font-weight: 600; color: #666;">
                Total Objects: <?php echo count($all_objects); ?>
            </span>
        </div>
    </div>

    <!-- Print Header -->
    <div class="print-header">
        <h1>🎯 AR Learning QR Codes</h1>
        <p>Mandarin Chinese - Colors, Fruits & Numbers</p>
        <p style="font-size: 14px; margin-top: 10px;">Cut out and attach to physical objects for AR learning</p>
    </div>

    <!-- QR Codes by Category -->
    <?php foreach ($categories as $category): ?>
        <?php $objects = getARObjectsByCategory($category); ?>
        
        <div class="category-section">
            <h2 class="category-title">
                <?php 
                $icons = ['Colors' => '🎨', 'Fruits' => '🍎', 'Numbers' => '🔢'];
                echo $icons[$category] ?? '📦';
                ?> 
                <?php echo $category; ?> (<?php echo count($objects); ?>)
            </h2>
            
            <div class="qr-grid">
                <?php foreach ($objects as $obj): ?>
                    <?php
                    // Generate QR filename
                    $qr_filename = 'qr_' . $obj['id'] . '_*.png';
                    $qr_files = glob(AR_MARKER_PATH . 'qr_' . $obj['id'] . '_*.png');
                    $qr_file = !empty($qr_files) ? basename($qr_files[0]) : '';
                    ?>
                    
                    <?php if ($qr_file): ?>
                    <div class="qr-item">
                        <div class="qr-icon"><?php echo $obj['icon']; ?></div>
                        <img src="<?php echo AR_MARKER_URL . $qr_file; ?>" alt="QR Code">
                        <div class="qr-label"><?php echo htmlspecialchars($obj['chinese']); ?></div>
                        <div class="qr-pinyin"><?php echo htmlspecialchars($obj['pinyin']); ?></div>
                        <div class="qr-english"><?php echo htmlspecialchars($obj['english']); ?></div>
                        <div class="cutting-guide">✂️ Cut along border</div>
                    </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        </div>

        <?php 
        // Add page break after each category (except last)
        if ($category !== end($categories)): 
        ?>
        <div class="page-break"></div>
        <?php endif; ?>
        
    <?php endforeach; ?>

    <!-- Footer (on last page) -->
    <div style="text-align: center; margin-top: 50px; padding: 20px; background: white; border-radius: 10px;">
        <p style="color: #666; font-size: 14px;">
            TalkSphere Mandarin Learning Platform<br>
            Generated on <?php echo date('F d, Y'); ?>
        </p>
    </div>

    <script>
        // Auto-print on load (optional - uncomment if you want)
        // window.onload = () => window.print();
    </script>
</body>
</html>