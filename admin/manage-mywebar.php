<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in (you can add admin check here)
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

$success = '';
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $vocab_id = intval($_POST['vocab_id']);
    $mywebar_url = trim($_POST['mywebar_url']);
    $mywebar_qr_image = trim($_POST['mywebar_qr_image']);
    
    $stmt = $conn->prepare("UPDATE vocabulary SET mywebar_url = ?, mywebar_qr_image = ? WHERE vocab_id = ?");
    $stmt->bind_param("ssi", $mywebar_url, $mywebar_qr_image, $vocab_id);
    
    if ($stmt->execute()) {
        $success = "MyWebAR URL updated successfully!";
    } else {
        $error = "Failed to update. Please try again.";
    }
}

// Get all vocabulary with themes
$vocab_query = "
    SELECT v.*, t.theme_name 
    FROM vocabulary v
    LEFT JOIN themes t ON v.theme_id = t.theme_id
    ORDER BY t.theme_name, v.vocab_id
";
$vocabulary = $conn->query($vocab_query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MyWebAR URL Manager</title>
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
            padding: 40px 20px;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            background: white;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }

        h1 {
            color: #333;
            margin-bottom: 10px;
            font-size: 36px;
        }

        .subtitle {
            color: #666;
            margin-bottom: 30px;
            font-size: 16px;
        }

        .alert {
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 25px;
            font-weight: 500;
        }

        .success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .instructions {
            background: #e3f2fd;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 30px;
            border-left: 4px solid #2196f3;
        }

        .instructions h3 {
            color: #1976d2;
            margin-bottom: 15px;
            font-size: 20px;
        }

        .instructions ol {
            margin-left: 20px;
            color: #555;
            line-height: 1.8;
        }

        .instructions li {
            margin: 8px 0;
        }

        .instructions a {
            color: #2196f3;
            font-weight: 600;
            text-decoration: none;
        }

        .instructions a:hover {
            text-decoration: underline;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        thead {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        th {
            padding: 15px;
            text-align: left;
            font-weight: 600;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        td {
            padding: 15px;
            border-bottom: 1px solid #e0e0e0;
        }

        tr:hover {
            background: #f8f9fa;
        }

        .char-display {
            font-size: 32px;
            font-weight: bold;
            color: #c92a2a;
        }

        .theme-badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            background: #e0e0e0;
            color: #666;
        }

        .status {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .status.active {
            background: #d4edda;
            color: #155724;
        }

        .status.pending {
            background: #fff3cd;
            color: #856404;
        }

        .url-input {
            width: 100%;
            padding: 10px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
            transition: border-color 0.3s;
        }

        .url-input:focus {
            outline: none;
            border-color: #667eea;
        }

        .save-btn {
            background: #4caf50;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.3s;
        }

        .save-btn:hover {
            background: #45a049;
            transform: translateY(-2px);
        }

        .test-btn {
            background: #2196f3;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 13px;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s;
        }

        .test-btn:hover {
            background: #1976d2;
            transform: translateY(-2px);
        }

        .back-btn {
            display: inline-block;
            padding: 12px 24px;
            background: #e0e0e0;
            color: #333;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            margin-bottom: 20px;
            transition: all 0.3s;
        }

        .back-btn:hover {
            background: #d0d0d0;
            transform: translateY(-2px);
        }

        @media (max-width: 768px) {
            .container {
                padding: 20px;
            }

            h1 {
                font-size: 28px;
            }

            table {
                font-size: 14px;
            }

            th, td {
                padding: 10px 8px;
            }

            .char-display {
                font-size: 24px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="../dashboard.php" class="back-btn">← Back to Dashboard</a>

        <h1>🎯 MyWebAR URL Manager</h1>
        <p class="subtitle">Add and manage AR experience URLs for vocabulary words</p>

        <?php if ($success): ?>
        <div class="alert success">✓ <?= $success ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
        <div class="alert error">✗ <?= $error ?></div>
        <?php endif; ?>

        <div class="instructions">
            <h3>📝 How to Add AR Experience:</h3>
            <ol>
                <li>Login to <a href="https://mywebar.com" target="_blank">MyWebAR.com</a></li>
                <li>Create a new AR project → Select <strong>"AR on QR Code"</strong></li>
                <li>Upload your 3D model (.glb) and audio file (.mp3)</li>
                <li>Add text overlays (Chinese character, pinyin, meaning)</li>
                <li>Publish and copy the AR experience URL</li>
                <li>Paste the URL below and save</li>
            </ol>
        </div>

        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Character</th>
                    <th>Pinyin</th>
                    <th>Meaning</th>
                    <th>Theme</th>
                    <th>MyWebAR URL</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($vocab = $vocabulary->fetch_assoc()): ?>
                <tr>
                    <td><strong>#<?= $vocab['vocab_id'] ?></strong></td>
                    <td class="char-display"><?= htmlspecialchars($vocab['chinese_character']) ?></td>
                    <td><?= htmlspecialchars($vocab['pinyin']) ?></td>
                    <td><?= htmlspecialchars($vocab['english_meaning']) ?></td>
                    <td>
                        <span class="theme-badge">
                            <?= htmlspecialchars($vocab['theme_name'] ?? 'N/A') ?>
                        </span>
                    </td>
                    <td>
                        <form method="POST" style="display: flex; gap: 10px; align-items: center;">
                            <input type="hidden" name="vocab_id" value="<?= $vocab['vocab_id'] ?>">
                            <input 
                                type="url" 
                                name="mywebar_url" 
                                class="url-input"
                                placeholder="https://app.mywebar.com/ar/..."
                                value="<?= htmlspecialchars($vocab['mywebar_url'] ?? '') ?>"
                            >
                            <input 
                                type="hidden" 
                                name="mywebar_qr_image" 
                                value="<?= htmlspecialchars($vocab['mywebar_qr_image'] ?? '') ?>"
                            >
                            <button type="submit" class="save-btn">💾 Save</button>
                        </form>
                    </td>
                    <td>
                        <?php if (!empty($vocab['mywebar_url'])): ?>
                            <span class="status active">✓ Active</span>
                        <?php else: ?>
                            <span class="status pending">⏳ Pending</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if (!empty($vocab['mywebar_url'])): ?>
                            <a href="<?= htmlspecialchars($vocab['mywebar_url']) ?>" 
                               target="_blank" 
                               class="test-btn">
                                🔗 Test AR
                            </a>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</body>
</html>