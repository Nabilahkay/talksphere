<?php
require_once '../config/session.php';
checkLogin();
require_once '../config/database.php';
$user = getCurrentUser();

// Get theme ID from URL
$theme_id = isset($_GET['theme_id']) ? intval($_GET['theme_id']) : 0;

if ($theme_id == 0) {
    header("Location: learn-vocab.php");
    exit();
}

// Get theme info
$stmt = $conn->prepare("SELECT theme_name, description FROM themes WHERE theme_id = ?");
$stmt->bind_param("i", $theme_id);
$stmt->execute();
$theme_result = $stmt->get_result();
$theme = $theme_result->fetch_assoc();

if (!$theme) {
    header("Location: learn-vocab.php");
    exit();
}

// Get vocabulary for this theme
$vocab_query = "
    SELECT 
        v.*,
        CASE WHEN up.is_learned = 1 THEN 1 ELSE 0 END as is_learned
    FROM vocabulary v
    LEFT JOIN user_progress up ON v.vocab_id = up.vocab_id AND up.user_id = ?
    WHERE v.theme_id = ?
    ORDER BY v.vocab_id";

$stmt = $conn->prepare($vocab_query);
$stmt->bind_param("ii", $user['user_id'], $theme_id);
$stmt->execute();
$vocabulary = $stmt->get_result();
$words = [];
while($word = $vocabulary->fetch_assoc()) {
    $words[] = $word;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($theme['theme_name']); ?> - Learn Vocabulary</title>
    <link rel="stylesheet" href="../css/dashboard.css">
    <link rel="stylesheet" href="../css/flashcard.css">
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <div class="logo">T</div>
            <h3>TALKSPHERE</h3>
        </div>
        <nav class="sidebar-nav">
            <a href="../dashboard.php"><span>🏠</span> Home</a>
            <a href="learn-vocab.php" class="active"><span>📚</span> Learn Vocabulary</a>
            <a href="quiz.php"><span>📝</span> Quiz</a>
            <a href="ar-marker.php"><span>📱</span> AR Marker</a>
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
                    <a href="ar-marker.php">AR Marker</a>
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

        <!-- Intro Screen -->
        <div class="intro-screen" id="introScreen">
            <button class="back-btn" onclick="location.href='learn-vocab.php'">
                ← 
            </button>
            
            <div class="intro-content">
                <h1>Learn <?php echo htmlspecialchars($theme['theme_name']); ?></h1>
                
                <div class="theme-mascot">
                    <?php if($theme['theme_name'] == 'Colors'): ?>
                        <div class="mascot-colors">🎨</div>
                    <?php elseif($theme['theme_name'] == 'Numbers'): ?>
                        <div class="mascot-numbers">🔢</div>
                    <?php else: ?>
                        <div class="mascot-fruits">🍎</div>
                    <?php endif; ?>
                </div>
                
                <button class="learn-now-btn" onclick="startLearning()">
                    Learn Now →
                </button>
                   
            </div>
        </div>

        <!-- Flashcard Screen -->
        <div class="flashcard-screen" id="flashcardScreen" style="display: none;">
            <button class="back-btn" onclick="backToIntro()">
                ←
            </button>
            
            <!-- Progress Bar -->
            <div class="progress-tracker">
                <div class="progress-segments" id="progressSegments"></div>
            </div>

            <!-- Flashcard Container -->
            <div class="flashcard-container">
                <div class="flashcard" id="flashcard">
                    <div class="flashcard-content">
                        <div id="cardContent"></div>
                        <button class="audio-btn" id="audioBtn" onclick="playAudio()">
                            🔊
                        </button>
                    </div>
                </div>
            </div>

            <!-- Navigation Buttons -->
            <div class="flashcard-nav">
                <button class="nav-btn back-card-btn" id="backBtn" onclick="previousCard()" style="display: none;">
                    ← Back
                </button>
                <button class="nav-btn continue-btn" id="continueBtn" onclick="nextCard()">
                    Continue →
                </button>
            </div>
        </div>
    </div>
    <script>
// Store vocabulary data
const vocabulary = <?php echo json_encode($words); ?>;
const themeName = "<?php echo htmlspecialchars($theme['theme_name']); ?>";
let currentCardIndex = 0;
let showingFront = true;

// Toggle sidebar
const hamburger = document.querySelector('.hamburger');
const sidebar = document.querySelector('.sidebar');
const mainContent = document.querySelector('.main-content');

hamburger.addEventListener('click', function() {
    sidebar.classList.toggle('closed');
    mainContent.classList.toggle('expanded');
});

// Toggle dropdown
const userDropdown = document.getElementById('userDropdown');
const dropdownMenu = document.getElementById('dropdownMenu');

userDropdown.addEventListener('click', function(e) {
    e.stopPropagation();
    dropdownMenu.classList.toggle('show');
});

document.addEventListener('click', function() {
    dropdownMenu.classList.remove('show'); 
});

document.addEventListener('click', function() {
    dropdownMenu.classList.remove('show');
});

//start learning
function startLearning() {
    console.log('Starting learning with', vocabulary.length, 'words');
    document.getElementById('introScreen').style.display = 'none';
    document.getElementById('flashcardScreen').style.display = 'block';
    initializeProgress();
    showCard(0, 'english');
}

// Back to intro
function backToIntro() {
    document.getElementById('flashcardScreen').style.display = 'none';
    document.getElementById('introScreen').style.display = 'flex';
    currentCardIndex = 0;
    showingFront = true;
}

// Initialize progress tracker
function initializeProgress() {
    const progressSegments = document.getElementById('progressSegments');
    progressSegments.innerHTML = '';
    
    for (let i = 0; i < vocabulary.length * 2; i++) {
        const segment = document.createElement('div');
        segment.className = 'progress-segment';
        if (i === 0) segment.classList.add('active');
        progressSegments.appendChild(segment);
    }
}

// Update progress
function updateProgress() {
    const segments = document.querySelectorAll('.progress-segment');
    const currentProgress = currentCardIndex * 2 + (showingFront ? 0 : 1);
    
    segments.forEach((segment, index) => {
        segment.classList.remove('active', 'completed');
        if (index < currentProgress) {
            segment.classList.add('completed');
        } else if (index === currentProgress) {
            segment.classList.add('active');
        }
    });
}

// Show card
function showCard(index, side) {
    const word = vocabulary[index];
    const cardContent = document.getElementById('cardContent');
    const flashcard = document.getElementById('flashcard');
    const audioBtn = document.getElementById('audioBtn');
    
    flashcard.style.backgroundColor = getThemeColor();
    
    if (side === 'english') {
        cardContent.innerHTML = `
            <div class="card-english">
                <h2>${word.english_meaning}</h2>
            </div>
        `;
        showingFront = true;
        audioBtn.style.display = 'none';
    } else {
        cardContent.innerHTML = `
            <div class="card-chinese">
                <h2>${word.pinyin}</h2>
                <h1>${word.chinese_character}</h1>
            </div>
        `;
        showingFront = false;
        audioBtn.style.display = 'block';
    }
    
    updateProgress();
    updateButtons();
}

// Get theme color
function getThemeColor() {
    const word = vocabulary[currentCardIndex];
    
    if (themeName === 'Colors' && word.english_meaning) {
        const colorMap = {
            'Red': '#e53935', 'Yellow': '#fdd835', 'Blue': '#1e88e5',
            'Green': '#43a047', 'White': '#f5f5f5', 'Black': '#212121',
            'Purple': '#8e24aa', 'Pink': '#ec407a', 'Orange': '#fb8c00',
            'Gray': '#757575', 'Grey': '#757575'
        };
        
        const colorName = word.english_meaning.trim();
        const cardColor = colorMap[colorName];
        
        const flashcardContent = document.querySelector('.flashcard-content');
        if (flashcardContent) {
            if (colorName === 'White' || colorName === 'Yellow') {
                flashcardContent.style.color = '#333';
            } else {
                flashcardContent.style.color = '#fff';
            }
        }
        
        return cardColor || '#4caf50';
    }
    
    if (themeName === 'Numbers') return '#ff9800';
    if (themeName === 'Fruits') return '#f44336';
    return '#4caf50';
}

// Next card
function nextCard() {
    if (showingFront) {
        showCard(currentCardIndex, 'chinese');
        const word = vocabulary[currentCardIndex];
        trackCardViewed(word.vocab_id);
    } else {
        currentCardIndex++;
        if (currentCardIndex >= vocabulary.length) {
            showCompletionScreen();
        } else {
            showCard(currentCardIndex, 'english');
        }
    }
}

// Previous card
function previousCard() {
    if (!showingFront) {
        showCard(currentCardIndex, 'english');
    } else if (currentCardIndex > 0) {
        currentCardIndex--;
        showCard(currentCardIndex, 'chinese');
    }
}

// Update button visibility
function updateButtons() {
    const backBtn = document.getElementById('backBtn');
    if (currentCardIndex === 0 && showingFront) {
        backBtn.style.display = 'none';
    } else {
        backBtn.style.display = 'inline-block';
    }
}

// Show completion screen
function showCompletionScreen() {
    console.log('Showing completion screen');
    const cardContent = document.getElementById('cardContent');
    const flashcard = document.getElementById('flashcard');
    
    flashcard.style.backgroundColor = '#e53935';
    
    markThemeAsCompleted();
    
    cardContent.innerHTML = `
        <div class="completion-screen">
            <h1>🎉 Congratulations!</h1>
            <p>You've completed all ${vocabulary.length} words!</p>
            <p class="completion-message">Your progress has been saved!</p>
            <button onclick="location.href='learn-vocab.php'" class="finish-btn">
                Back to Themes
            </button>
        </div>
    `;
    document.getElementById('backBtn').style.display = 'none';
    document.getElementById('continueBtn').style.display = 'none';
    document.getElementById('audioBtn').style.display = 'none';
}

// Mark theme as completed
function markThemeAsCompleted() {
    const vocabIds = vocabulary.map(word => word.vocab_id);
    
    fetch('../api/mark-theme-completed.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ vocab_ids: vocabIds })
    })
    .then(response => response.json())
    .then(data => {
        console.log('Progress saved:', data);
        if (data.success) {
            console.log('Theme completed! Streak updated.');
        }
    })
    .catch(error => console.error('Error saving progress:', error));
}

// Track card viewed
function trackCardViewed(vocabId) {
    fetch('../api/track-card.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ vocab_id: vocabId })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            console.log('Card tracked:', vocabId);
        }
    })
    .catch(error => console.error('Error tracking card:', error));
}

// Audio player
let voices = [];

function loadVoices() {
    voices = window.speechSynthesis.getVoices();
}

if (speechSynthesis.onvoiceschanged !== undefined) {
    speechSynthesis.onvoiceschanged = loadVoices;
}
loadVoices();

function playAudio() {
    const word = vocabulary[currentCardIndex];
    
    if (window.speechSynthesis.speaking) {
        window.speechSynthesis.cancel();
    }

    const audioBtn = document.getElementById('audioBtn');
    audioBtn.textContent = '🔄';
    audioBtn.disabled = true;

    setTimeout(() => {
        const availableVoices = window.speechSynthesis.getVoices();
        let chineseVoice = availableVoices.find(voice => 
            voice.lang === 'zh-CN' || voice.lang === 'zh-TW' || voice.lang.startsWith('zh')
        );
        
        const utterance = new SpeechSynthesisUtterance(word.chinese_character);
        
        if (chineseVoice) {
            utterance.voice = chineseVoice;
            utterance.lang = chineseVoice.lang;
        } else {
            utterance.lang = 'zh-CN';
        }
        
        utterance.rate = 0.7;
        utterance.pitch = 1;
        utterance.volume = 1;
        
        utterance.onstart = () => {
            audioBtn.textContent = '🔊';
        };
        
        utterance.onend = () => {
            audioBtn.textContent = '🔊';
            audioBtn.disabled = false;
        };
        
        utterance.onerror = (event) => {
            console.error('Speech error:', event);
            audioBtn.textContent = '🔊';
            audioBtn.disabled = false;
            alert('Audio not available. Try Chrome or Edge browser.');
        };

        try {
            window.speechSynthesis.speak(utterance);
        } catch (error) {
            console.error('Error speaking:', error);
            audioBtn.textContent = '🔊';
            audioBtn.disabled = false;
        }
    }, 100);
}
    </script>
</body>
</html>