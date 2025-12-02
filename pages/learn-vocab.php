<?php
require_once '../config/session.php';
checkLogin();
require_once '../config/database.php';
require_once '../config/streak_functions.php';
$user = getCurrentUser();

// Log activity when user visits vocabulary page
logUserActivity($user['user_id'], 'vocab_page_visit', 5);

// Get themes with vocabulary count and user progress
$user_id = $user['user_id'];
$themes_query = "
    SELECT 
        t.theme_id,
        t.theme_name,
        COUNT(DISTINCT v.vocab_id) as total_words,
        COUNT(DISTINCT CASE WHEN up.is_learned = 1 THEN up.vocab_id END) as learned_words
    FROM themes t
    LEFT JOIN vocabulary v ON t.theme_id = v.theme_id
    LEFT JOIN user_progress up ON v.vocab_id = up.vocab_id AND up.user_id = ?
    WHERE t.theme_name IN ('Colors', 'Numbers', 'Fruits')
    GROUP BY t.theme_id, t.theme_name
    ORDER BY t.theme_id";

$stmt = $conn->prepare($themes_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$themes = $stmt->get_result();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Learn Vocabulary - Mandarin Learning Platform</title>
    <link rel="stylesheet" href="../css/dashboard.css">
    <link rel="stylesheet" href="../css/vocabulary.css">
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <img src="../img/talksphere_logo.png" alt="TalkSphere Logo" class="sidebar-logo">
            <h3>TALKSPHERE</h3>
        </div>
        <nav class="sidebar-nav">
            <a href="dashboard.php" class="active"><span>🏠</span> Home</a>
            <a href="pages/learn-vocab.php"><span>📚</span> Learn Vocabulary</a>
            <a href="quiz.php"><span>📝</span> Quiz</a>
            <a href="laptop-ar-scanner.php"><span>💻</span> AR Marker</a>
            <a href="profile.php"><span>👤</span> Profile</a>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Header -->
        <header class="header">
            <div class="header-top">
                <div class="hamburger">☰</div>
                <nav class="top-nav">
                    <a href="../dashboard.php">Home</a>
                    <a href="learn-vocab.php" class="active">Learn Vocab</a>
                    <a href="quiz.php">Quiz</a>
                    <a href="pages/laptop-ar-scanner.php">Laptop Scanner</a>
                    <a href="profile.php">Profile</a>
                </nav>
                <div class="user-info">
                    <span><?php echo htmlspecialchars($user['full_name']); ?></span>
                    <div class="user-avatar" id="userDropdown">
                        <span>👤</span>
                        <div class="dropdown-menu" id="dropdownMenu">
                            <a href="profile.php">
                                <span>👤</span> My Profile
                            </a>
                            <a href="../logout.php">
                                <span>🔓</span> Logout
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <!-- Vocabulary Themes -->
        <div class="vocab-container">
            <h1>Learn Vocabulary</h1>
            
            <div class="themes-list">
                <?php 
                $theme_colors = [
                    'Colors' => ['bg' => '#4caf50', 'icon' => '📖', 'level' => '2'],
                    'Numbers' => ['bg' => '#ff9800', 'icon' => '📖', 'level' => '1'],
                    'Fruits' => ['bg' => '#f44336', 'icon' => '🔥', 'level' => '3']
                ];
                
                while($theme = $themes->fetch_assoc()): 
                    $theme_name = $theme['theme_name'];
                    $color_info = $theme_colors[$theme_name];
                    $learned = $theme['learned_words'];
                    $total = $theme['total_words'];
                    $percentage = $total > 0 ? round(($learned / $total) * 100) : 0;
                ?>
                <div class="theme-card" onclick="location.href='vocabulary-detail.php?theme_id=<?php echo $theme['theme_id']; ?>'">
                    <div class="theme-icon" style="background: <?php echo $color_info['bg']; ?>;">
                        <span><?php echo $color_info['icon']; ?></span>
                        <div class="theme-level">LEVEL <?php echo $color_info['level']; ?></div>
                    </div>
                    <div class="theme-info">
                        <h3><?php echo $theme_name; ?></h3>
                        <p class="theme-time">1 Hour 40 Minute</p>
                        <div class="progress-container">
                            <div class="progress-bar-vocab">
                                <div class="progress-fill-vocab" style="width: <?php echo $percentage; ?>%; background: <?php echo $color_info['bg']; ?>;"></div>
                            </div>
                            <span class="progress-text"><?php echo $learned; ?>/<?php echo $total; ?></span>
                        </div>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
        </div>
    </div>
    
    <script>
        const hamburger = document.querySelector('.hamburger');
        const sidebar = document.querySelector('.sidebar');
        const mainContent = document.querySelector('.main-content');
        
        hamburger.addEventListener('click', function() {
            sidebar.classList.toggle('closed');
            mainContent.classList.toggle('expanded');
        });
        
        const userDropdown = document.getElementById('userDropdown');
        const dropdownMenu = document.getElementById('dropdownMenu');
        
        userDropdown.addEventListener('click', function(e) {
            e.stopPropagation();
            dropdownMenu.classList.toggle('show');
        });
        
        document.addEventListener('click', function() {
            dropdownMenu.classList.remove('show');
        });

        
    </script>
</body>
</html>