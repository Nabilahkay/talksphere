<?php
require_once '../config/session.php';
checkLogin();
require_once '../config/database.php';
$user = getCurrentUser();

// Get available themes
$themes_query = "SELECT theme_id, theme_name FROM themes WHERE theme_name IN ('Colors', 'Numbers', 'Fruits')";
$themes_result = $conn->query($themes_query);
$themes = [];
while($theme = $themes_result->fetch_assoc()) {
    $themes[] = $theme;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quiz - Mandarin Learning Platform</title>
    <link rel="stylesheet" href="../css/dashboard.css">
   <link rel="stylesheet" href="../css/quiz.css">
</head>
<body>
    <div class="sidebar">
        <div class="sidebar-header">
            <img src="../img/talksphere_logo.png" alt="TalkSphere Logo" class="sidebar-logo">
            <h3>TALKSPHERE</h3>
        </div>
        <nav class="sidebar-nav">
            <a href="../dashboard.php"><span>🏠</span> Home</a>
            <a href="learn-vocab.php"><span>📚</span> Learn Vocabulary</a>
            <a href="quiz.php" class="active"><span>📝</span> Quiz</a>
             <a href="laptop-ar-scanner.php"><span>💻</span> AR Marker </a>
            <a href="profile.php"><span>👤</span> Profile</a>
        </nav>
    </div>

    <div class="main-content">
        <header class="header">
            <div class="header-top">
                <div class="hamburger">☰</div>
                <nav class="top-nav">
                    <a href="../dashboard.php">Home</a>
                    <a href="learn-vocab.php">Learn Vocab</a>
                    <a href="quiz.php" class="active">Quiz</a>
                    <a href="pages/laptop-ar-scanner.php">AR Marker </a>
                    <a href="profile.php">Profile</a>
                </nav>
                <div class="user-info">
                    <span><?php echo htmlspecialchars($user['full_name']); ?></span>
                    <div class="user-avatar" id="userDropdown">
                        <span>👤</span>
                        <div class="dropdown-menu" id="dropdownMenu">
                            <a href="profile.php"><span>👤</span> My Profile</a>
                            <a href="../logout.php"><span>🔓</span> Logout</a>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <div class="quiz-selection-container">
            <h1>Choose Your Quiz</h1>
            <p class="subtitle">Select a theme and difficulty level to start</p>

            <div class="themes-grid">
                <?php 
                $theme_colors = [
                    'Colors' => '#4caf50',
                    'Numbers' => '#ff9800',
                    'Fruits' => '#f44336'
                ];
                
                $theme_icons = [
                    'Colors' => '🎨',
                    'Numbers' => '🔢',
                    'Fruits' => '🍎'
                ];
                
                foreach($themes as $theme): 
                    $color = $theme_colors[$theme['theme_name']];
                    $icon = $theme_icons[$theme['theme_name']];
                ?>
                <div class="theme-box">
                    <div class="theme-icon-box" style="background: <?php echo $color; ?>;">
                        <span class="theme-icon"><?php echo $icon; ?></span>
                    </div>
                    <h3 class="theme-title"><?php echo $theme['theme_name']; ?></h3>
                    <p class="theme-label">Select Level</p>
                    
                    <div class="difficulty-buttons">
                        <button class="difficulty-btn" 
                                data-theme="<?php echo $theme['theme_id']; ?>" 
                                data-difficulty="beginner">
                            <span class="stars">⭐</span>
                            <span class="diff-text">Beginner</span>
                        </button>
                        <button class="difficulty-btn" 
                                data-theme="<?php echo $theme['theme_id']; ?>" 
                                data-difficulty="intermediate">
                            <span class="stars">⭐⭐</span>
                            <span class="diff-text">Intermediate</span>
                        </button>
                        <button class="difficulty-btn" 
                                data-theme="<?php echo $theme['theme_id']; ?>" 
                                data-difficulty="advanced">
                            <span class="stars">⭐⭐⭐</span>
                            <span class="diff-text">Advanced</span>
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Start Button -->
            <div class="start-quiz-section">
                <button class="start-quiz-btn" id="startQuizBtn" disabled>
                    Start Quiz →
                </button>
                <p class="selection-status" id="selectionStatus">Please select a difficulty level from any theme</p>
            </div>
        </div>
    </div>

    <script>
        let selectedTheme = null;
        let selectedDifficulty = null;

        // Toggle sidebar
        const hamburger = document.querySelector('.hamburger');
        const sidebar = document.querySelector('.sidebar');
        const mainContent = document.querySelector('.main-content');
        
        hamburger.addEventListener('click', () => {
            sidebar.classList.toggle('closed');
            mainContent.classList.toggle('expanded');
        });

        // Toggle dropdown
        const userDropdown = document.getElementById('userDropdown');
        const dropdownMenu = document.getElementById('dropdownMenu');
        
        userDropdown.addEventListener('click', (e) => {
            e.stopPropagation();
            dropdownMenu.classList.toggle('show');
        });
        
        document.addEventListener('click', () => {
            dropdownMenu.classList.remove('show');
        });

        // Difficulty button selection
        document.querySelectorAll('.difficulty-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                // Remove selection from all buttons
                document.querySelectorAll('.difficulty-btn').forEach(b => b.classList.remove('selected'));
                
                // Add selection to clicked button
                this.classList.add('selected');
                
                selectedTheme = this.dataset.theme;
                selectedDifficulty = this.dataset.difficulty;
                
                updateStartButton();
            });
        });

        // Update start button state
        function updateStartButton() {
            const btn = document.getElementById('startQuizBtn');
            const status = document.getElementById('selectionStatus');
            
            if (selectedTheme && selectedDifficulty) {
                btn.disabled = false;
                status.textContent = 'Ready to start! Click the button below';
                status.style.color = '#4caf50';
            } else {
                btn.disabled = true;
                status.textContent = 'Please select a difficulty level from any theme';
                status.style.color = '#999';
            }
        }

        // Start quiz
        document.getElementById('startQuizBtn').addEventListener('click', () => {
            if (selectedTheme && selectedDifficulty) {
                window.location.href = `take-quiz.php?theme=${selectedTheme}&difficulty=${selectedDifficulty}`;
            }
        });
    </script>
</body>
</html>