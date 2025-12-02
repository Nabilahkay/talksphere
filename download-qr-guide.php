<?php
session_start();

// Optional login
$isLoggedIn = isset($_SESSION['user_id']);

require_once 'config/database.php';
require_once 'config/ar_config.php';
require_once 'config/ar_objects.php';

$all_objects = getAllARObjects();
$categories = getAllCategories();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QR Codes Guide - AR Learning</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .header {
            background: white;
            padding: 40px;
            border-radius: 20px;
            text-align: center;
            margin-bottom: 30px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
        }

        .header h1 {
            color: #333;
            font-size: 36px;
            margin-bottom: 15px;
        }

        .header p {
            color: #666;
            font-size: 18px;
        }

        .guide-section {
            background: white;
            padding: 40px;
            border-radius: 20px;
            margin-bottom: 30px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
        }

        .guide-section h2 {
            color: #333;
            font-size: 28px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .steps {
            display: grid;
            gap: 25px;
            margin-top: 30px;
        }

        .step {
            background: #f8f9fa;
            padding: 25px;
            border-radius: 15px;
            border-left: 5px solid #667eea;
        }

        .step-number {
            display: inline-block;
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 50%;
            text-align: center;
            line-height: 40px;
            font-weight: 700;
            font-size: 20px;
            margin-right: 15px;
        }

        .step-title {
            font-size: 20px;
            font-weight: 700;
            color: #333;
            margin-bottom: 10px;
        }

        .step-description {
            color: #666;
            line-height: 1.6;
            font-size: 16px;
        }

        .category-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 25px;
            margin-top: 30px;
        }

        .category-card {
            background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%);
            padding: 30px;
            border-radius: 15px;
            text-align: center;
        }

        .category-card h3 {
            font-size: 24px;
            color: #1565c0;
            margin-bottom: 15px;
        }

        .category-icon {
            font-size: 48px;
            margin-bottom: 15px;
        }

        .object-count {
            font-size: 36px;
            font-weight: 700;
            color: #1976d2;
            margin-bottom: 5px;
        }

        .object-label {
            font-size: 14px;
            color: #1565c0;
        }

        .action-buttons {
            display: flex;
            gap: 20px;
            justify-content: center;
            margin-top: 30px;
            flex-wrap: wrap;
        }

        .btn {
            padding: 15px 40px;
            border-radius: 12px;
            text-decoration: none;
            font-size: 16px;
            font-weight: 600;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 10px;
        }

        .btn-primary {
            background: linear-gradient(135deg, #4CAF50 0%, #45a049 100%);
            color: white;
            box-shadow: 0 4px 15px rgba(76, 175, 80, 0.3);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(76, 175, 80, 0.4);
        }

        .btn-secondary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }

        .btn-secondary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
        }

        .example-section {
            background: linear-gradient(135deg, #fff3e0 0%, #ffe0b2 100%);
            padding: 30px;
            border-radius: 15px;
            margin-top: 30px;
        }

        .example-section h3 {
            color: #e65100;
            font-size: 22px;
            margin-bottom: 20px;
        }

        .example-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .example-item {
            background: white;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
        }

        .example-icon {
            font-size: 40px;
            margin-bottom: 10px;
        }

        .example-text {
            font-size: 14px;
            color: #666;
            font-weight: 600;
        }

        @media (max-width: 768px) {
            .action-buttons {
                flex-direction: column;
            }

            .btn {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1>📱 AR Learning System Guide</h1>
            <p>Learn Mandarin Chinese using physical objects and QR codes!</p>
        </div>

        <!-- How It Works -->
        <div class="guide-section">
            <h2>🎯 How It Works</h2>
            
            <div class="steps">
                <div class="step">
                    <span class="step-number">1</span>
                    <div style="display: inline-block; vertical-align: top; width: calc(100% - 60px);">
                        <div class="step-title">Get QR Codes</div>
                        <div class="step-description">
                            Your teacher will provide printed QR codes, or download and print them yourself. 
                            Each QR code represents a Chinese word (color, fruit, or number).
                        </div>
                    </div>
                </div>

                <div class="step">
                    <span class="step-number">2</span>
                    <div style="display: inline-block; vertical-align: top; width: calc(100% - 60px);">
                        <div class="step-title">Attach to Objects</div>
                        <div class="step-description">
                            Stick QR codes onto physical objects around you. For example:
                            <ul style="margin-top: 10px; padding-left: 20px;">
                                <li>Red QR → Red apple, red book, red marker</li>
                                <li>Apple QR → Real apple or toy apple</li>
                                <li>Number 5 QR → Group of 5 items</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <div class="step">
                    <span class="step-number">3</span>
                    <div style="display: inline-block; vertical-align: top; width: calc(100% - 60px);">
                        <div class="step-title">Scan with AR Scanner</div>
                        <div class="step-description">
                            Open the Hybrid AR Scanner on your laptop. Click "Start Scanning" and point your 
                            webcam at the QR code. The system will instantly show you the Chinese character, 
                            pinyin pronunciation, and English translation!
                        </div>
                    </div>
                </div>

                <div class="step">
                    <span class="step-number">4</span>
                    <div style="display: inline-block; vertical-align: top; width: calc(100% - 60px);">
                        <div class="step-title">Learn & Practice</div>
                        <div class="step-description">
                            Practice with different objects throughout your day. The more you scan and use the 
                            vocabulary, the better you'll remember it! Your progress is automatically tracked.
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Available Objects -->
        <div class="guide-section">
            <h2>📦 Available AR Objects</h2>
            
            <div class="category-grid">
                <?php 
                $category_icons = [
                    'Colors' => '🎨',
                    'Fruits' => '🍎',
                    'Numbers' => '🔢'
                ];
                
                foreach ($categories as $category): 
                    $objects = getARObjectsByCategory($category);
                    $count = count($objects);
                ?>
                <div class="category-card">
                    <div class="category-icon"><?php echo $category_icons[$category] ?? '📦'; ?></div>
                    <h3><?php echo $category; ?></h3>
                    <div class="object-count"><?php echo $count; ?></div>
                    <div class="object-label">Objects Available</div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Examples -->
        <div class="guide-section">
            <h2>💡 Usage Examples</h2>
            
            <div class="example-section">
                <h3>Colors (颜色 - yánsè)</h3>
                <div class="example-grid">
                    <div class="example-item">
                        <div class="example-icon">🔴</div>
                        <div class="example-text">Attach to red objects</div>
                    </div>
                    <div class="example-item">
                        <div class="example-icon">🔵</div>
                        <div class="example-text">Attach to blue objects</div>
                    </div>
                    <div class="example-item">
                        <div class="example-icon">🟡</div>
                        <div class="example-text">Attach to yellow objects</div>
                    </div>
                    <div class="example-item">
                        <div class="example-icon">🟢</div>
                        <div class="example-text">Attach to green objects</div>
                    </div>
                </div>
            </div>

            <div class="example-section">
                <h3>Fruits (水果 - shuǐguǒ)</h3>
                <div class="example-grid">
                    <div class="example-item">
                        <div class="example-icon">🍎</div>
                        <div class="example-text">Real apple or picture</div>
                    </div>
                    <div class="example-item">
                        <div class="example-icon">🍌</div>
                        <div class="example-text">Banana or toy</div>
                    </div>
                    <div class="example-item">
                        <div class="example-icon">🍊</div>
                        <div class="example-text">Orange fruit</div>
                    </div>
                    <div class="example-item">
                        <div class="example-icon">🍇</div>
                        <div class="example-text">Bunch of grapes</div>
                    </div>
                </div>
            </div>

            <div class="example-section">
                <h3>Numbers (数字 - shùzì)</h3>
                <div class="example-grid">
                    <div class="example-item">
                        <div class="example-icon">1️⃣</div>
                        <div class="example-text">One item</div>
                    </div>
                    <div class="example-item">
                        <div class="example-icon">2️⃣</div>
                        <div class="example-text">Two items</div>
                    </div>
                    <div class="example-item">
                        <div class="example-icon">5️⃣</div>
                        <div class="example-text">Five items</div>
                    </div>
                    <div class="example-item">
                        <div class="example-icon">🔟</div>
                        <div class="example-text">Ten items</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="guide-section">
            <h2>🚀 Get Started</h2>
            
            <div class="action-buttons">
                <a href="hybrid-scanner.php" class="btn btn-primary">
                    📱 Open AR Scanner
                </a>
                
                <?php if ($isLoggedIn): ?>
                <a href="dashboard.php" class="btn btn-secondary">
                    ← Back to Dashboard
                </a>
                <?php else: ?>
                <a href="login.php" class="btn btn-secondary">
                    🔑 Login to Save Progress
                </a>
                <?php endif; ?>
            </div>

            <div style="text-align: center; margin-top: 30px; color: #666; font-size: 14px;">
                <p><strong>Need QR codes?</strong> Ask your teacher or admin to print them for you!</p>
            </div>
        </div>
    </div>
</body>
</html>