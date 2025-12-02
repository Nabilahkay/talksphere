<?php
session_start();
require_once '../config/database.php';
require_once '../config/session.php';
$user = getCurrentUser();

//Get current user
$user = getCurrentUser();
if (!$user) {
    header('Location: ../auth/login.php');
    exit;
}
$user_id = $user['user_id'];

// Get theme and difficulty from URL
$theme_id = isset($_GET['theme']) ? intval($_GET['theme']) : 0;
$difficulty = isset($_GET['difficulty']) ? $_GET['difficulty'] : 'beginner';

//validate difficulties
$valid_difficulties = ['beginner', 'intermediate', 'advanced'];
if (!in_array($difficulty, $valid_difficulties)) {
    die("Invalid difficulty level");
}

if ($theme_id == 0) {
    header("Location: quiz.php");
    exit();
}

// Get theme info
$stmt = $conn->prepare("SELECT theme_name FROM themes WHERE theme_id = ?");
$stmt->bind_param("i", $theme_id);
$stmt->execute();
$theme_result = $stmt->get_result();
$theme = $theme_result->fetch_assoc();

if (!$theme) {
    header("Location: quiz.php");
    exit();
}

// Get vocabulary for this theme and difficulty
$vocab_query = "
    SELECT vocab_id, chinese_character, pinyin, english_meaning 
    FROM vocabulary 
    WHERE theme_id = ? AND difficulty_level = ?
    ORDER BY RAND()
    LIMIT 10";

$stmt = $conn->prepare($vocab_query);
$stmt->bind_param("is", $theme_id, $difficulty);
$stmt->execute();
$vocab_result = $stmt->get_result();
$questions = [];

while($word = $vocab_result->fetch_assoc()) {
    $questions[] = $word;
}

// If less than 10 questions, get more without difficulty filter
if (count($questions) < 10) {
    $vocab_query = "
        SELECT vocab_id, chinese_character, pinyin, english_meaning 
        FROM vocabulary 
        WHERE theme_id = ?
        ORDER BY RAND()
        LIMIT 10";
    
    $stmt = $conn->prepare($vocab_query);
    $stmt->bind_param("i", $theme_id);
    $stmt->execute();
    $vocab_result = $stmt->get_result();
    $questions = [];
    
    while($word = $vocab_result->fetch_assoc()) {
        $questions[] = $word;
    }
}

// Generate wrong options for multiple choice
function getWrongOptions($conn, $theme_id, $correct_answer, $count = 2) {
    $stmt = $conn->prepare("
        SELECT english_meaning 
        FROM vocabulary 
        WHERE theme_id = ? AND english_meaning != ?
        ORDER BY RAND()
        LIMIT ?
    ");
    $stmt->bind_param("isi", $theme_id, $correct_answer, $count);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $options = [];
    while($row = $result->fetch_assoc()) {
        $options[] = $row['english_meaning'];
    }
    
    // Fallback: If not enough options, get from any theme
    if (count($options) < $count) {
        $remaining = $count - count($options);
        $stmt = $conn->prepare("
            SELECT english_meaning 
            FROM vocabulary 
            WHERE english_meaning != ?
            ORDER BY RAND()
            LIMIT ?
        ");
        $stmt->bind_param("si", $correct_answer, $remaining);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while($row = $result->fetch_assoc()) {
            $options[] = $row['english_meaning'];
        }
    }
    
    return $options;
}

// Get wrong words with icons for "Which one is..." questions
function getWrongWords($conn, $theme_id, $correct_word, $count = 2) {
    $stmt = $conn->prepare("
        SELECT chinese_character, pinyin, english_meaning 
        FROM vocabulary 
        WHERE theme_id = ? AND chinese_character != ?
        ORDER BY RAND()
        LIMIT ?
    ");
    $stmt->bind_param("isi", $theme_id, $correct_word, $count);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $words = [];
    while($row = $result->fetch_assoc()) {
        $words[] = $row;
    }
    
    // Fallback
    if (count($words) < $count) {
        $remaining = $count - count($words);
        $stmt = $conn->prepare("
            SELECT chinese_character, pinyin, english_meaning 
            FROM vocabulary 
            WHERE chinese_character != ?
            ORDER BY RAND()
            LIMIT ?
        ");
        $stmt->bind_param("si", $correct_word, $remaining);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while($row = $result->fetch_assoc()) {
            $words[] = $row;
        }
    }
    
    return $words;
}

// Assign question types based on difficulty level
foreach($questions as $index => &$question) {
    $questionNumber = $index + 1;
    
    if ($difficulty === 'beginner') {
        $question['type'] = 'select_meaning';
        $wrong_options = getWrongOptions($conn, $theme_id, $question['english_meaning'], 2);
        $all_options = array_merge([$question['english_meaning']], $wrong_options);
        shuffle($all_options);
        $question['options'] = $all_options;
        
    } elseif ($difficulty === 'intermediate') {
        if ($questionNumber <= 3) {
            $question['type'] = 'select_meaning';
            $wrong_options = getWrongOptions($conn, $theme_id, $question['english_meaning'], 2);
            $all_options = array_merge([$question['english_meaning']], $wrong_options);
            shuffle($all_options);
            $question['options'] = $all_options;
        } else {
            $question['type'] = 'select_word';
            $wrong_words = getWrongWords($conn, $theme_id, $question['chinese_character'], 2);
            $all_words = array_merge([
                [
                    'chinese_character' => $question['chinese_character'],
                    'pinyin' => $question['pinyin'],
                    'english_meaning' => $question['english_meaning']
                ]
            ], $wrong_words);
            shuffle($all_words);
            $question['word_options'] = $all_words;
        }
        
    } elseif ($difficulty === 'advanced') {
        if ($questionNumber <= 3) {
            $question['type'] = 'select_meaning';
            $wrong_options = getWrongOptions($conn, $theme_id, $question['english_meaning'], 2);
            $all_options = array_merge([$question['english_meaning']], $wrong_options);
            shuffle($all_options);
            $question['options'] = $all_options;
        } elseif ($questionNumber <= 6) {
            $question['type'] = 'select_word';
            $wrong_words = getWrongWords($conn, $theme_id, $question['chinese_character'], 2);
            $all_words = array_merge([
                [
                    'chinese_character' => $question['chinese_character'],
                    'pinyin' => $question['pinyin'],
                    'english_meaning' => $question['english_meaning']
                ]
            ], $wrong_words);
            shuffle($all_words);
            $question['word_options'] = $all_words;
        } else {
            $question['type'] = 'rearrange';
            $words = explode(' ', $question['english_meaning']);
            $distractors = ['and', 'the', 'is', 'in', 'of'];
            $available_distractors = array_diff($distractors, $words);
            if (count($available_distractors) > 0) {
                $words[] = array_values($available_distractors)[0];
            }
            shuffle($words);
            $question['word_bank'] = $words;
        }
    }
    
    $question['correct_answer'] = $question['english_meaning'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quiz - <?php echo htmlspecialchars($theme['theme_name']); ?></title>
       <link rel="stylesheet" href="../css/quiz.css">
    <link rel="stylesheet" href="../css/dashboard.css">

</head>
<body>
    <div class="quiz-container">
        <div class="quiz-header">
            <button class="close-btn" onclick="confirmExit()">✕</button>
            <div class="progress-bar-container">
                <div class="progress-bar" id="progressBar"></div>
            </div>
        </div>

        <div class="question-container" id="questionContainer">
            <!-- Questions loaded dynamically -->
        </div>

        <div class="answer-buttons" id="answerButtons">
            <button class="skip-btn" onclick="skipQuestion()">SKIP</button>
            <button class="check-btn" id="checkBtn" onclick="checkAnswer()" disabled>CHECK</button>
        </div>
    </div>

    <!-- Instant Feedback HTML -->

<div class="feedback-overlay" id="feedbackOverlay"></div>

<div class="instant-feedback-popup" id="instantFeedbackPopup">
    <div class="instant-feedback-icon" id="instantFeedbackIcon">✓</div>
    <div class="instant-feedback-title" id="instantFeedbackTitle">Awesome!</div>
    <div class="instant-feedback-message" id="instantFeedbackMessage">Keep going!</div>
    <div class="correct-answer-display" id="correctAnswerDisplay"></div>
    <button class="instant-feedback-continue" onclick="closeInstantFeedback()">
        CONTINUE →
    </button>
</div>
    

    <script>
        document.addEventListener('DOMContentLoaded', () => {
    console.log('Feedback overlay exists:', document.getElementById('feedbackOverlay') !== null);
    console.log('Feedback popup exists:', document.getElementById('instantFeedbackPopup') !== null);
    console.log('Continue button exists:', document.querySelector('.instant-feedback-continue') !== null);
});
        const questions = <?php echo json_encode($questions, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
        const themeName = <?php echo json_encode($theme['theme_name']); ?>;
        const themeId = <?php echo $theme_id; ?>;
        const difficulty = <?php echo json_encode($difficulty); ?>;
        
        let currentQuestionIndex = 0;
        let score = 0;
        let userAnswers = [];
        let selectedAnswer = null;
        let selectedWords = [];
        let correctStreak = 0;

        document.addEventListener('DOMContentLoaded', () => {
            if (questions.length === 0) {
                alert('No questions available!');
                window.location.href = 'quiz.php';
                return;
            }
            showQuestion(0);
        });

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        function showQuestion(index) {
            const question = questions[index];
            const container = document.getElementById('questionContainer');
            const progress = ((index) / questions.length) * 100;
            document.getElementById('progressBar').style.width = progress + '%';
            
            let questionHTML = '';
            
            if (question.type === 'select_meaning') {
                questionHTML = `
                    <div class="question-header">
                        <span class="question-label">Select the correct meaning</span>
                    </div>
                    <div class="question-word">
                        <button class="audio-btn-quiz" data-text="${escapeHtml(question.chinese_character)}" onclick="playQuizAudio(this.dataset.text)">🔊</button>
                        <span class="chinese-word">${escapeHtml(question.chinese_character)}</span>
                    </div>
                    <div class="options-container">
                        ${question.options.map((option, i) => `
                            <button class="option-btn" data-answer="${escapeHtml(option)}" onclick="selectOption(this)">
                                <span class="option-number">${i + 1}</span>
                                <span class="option-text">${escapeHtml(option)}</span>
                            </button>
                        `).join('')}
                    </div>
                `;
            } else if (question.type === 'select_word') {
                questionHTML = `
                    <div class="question-header">
                        <span class="question-label">Which one of these is "${escapeHtml(question.english_meaning)}"?</span>
                    </div>
                    <div class="word-options-grid">
                        ${question.word_options.map((word, i) => `
                            <button class="word-option-card" data-answer="${escapeHtml(word.chinese_character)}" onclick="selectWordOption(this)">
                                <div class="word-card-chinese">${escapeHtml(word.chinese_character)}</div>
                                <div class="word-card-pinyin">${escapeHtml(word.pinyin)}</div>
                                <div class="word-card-number">${i + 1}</div>
                            </button>
                        `).join('')}
                    </div>
                `;
            } else if (question.type === 'rearrange') {
                questionHTML = `
                    <div class="question-header">
                        <span class="question-label">Write this in English</span>
                    </div>
                    <div class="question-word">
                        <button class="audio-btn-quiz" data-text="${escapeHtml(question.chinese_character)}" onclick="playQuizAudio(this.dataset.text)">🔊</button>
                        <span class="chinese-word">${escapeHtml(question.chinese_character)}</span>
                    </div>
                    <div class="rearrange-area">
                        <div class="answer-area" id="answerArea">
                            <span class="answer-placeholder">Tap the words below</span>
                        </div>
                        <div class="word-bank" id="wordBank">
                            ${question.word_bank.map((word, i) => `
                                <button class="word-chip" data-word="${escapeHtml(word)}" data-index="${i}" onclick="addWordToAnswer(this)">
                                    ${escapeHtml(word)}
                                </button>
                            `).join('')}
                        </div>
                    </div>
                `;
            }
            
            container.innerHTML = questionHTML;
            selectedAnswer = null;
            selectedWords = [];
            document.getElementById('checkBtn').disabled = true;
        }

        function selectOption(btn) {
           document.querySelectorAll('.option-btn').forEach(b => {
            b.classList.remove('selected');
           });
           btn.classList.add('selected');

            selectedAnswer = btn.getAttribute('data-answer');
            document.getElementById('checkBtn').disabled = false;
        console.log('Answer selected:', selectedAnswer);
        }

        function selectWordOption(btn) {
            document.querySelectorAll('.word-option-card').forEach(b => b.classList.remove('selected'));
            btn.classList.add('selected');
            selectedAnswer = btn.getAttribute('data-answer');
            document.getElementById('checkBtn').disabled = false;
        }

        function addWordToAnswer(chip) {
            const word = chip.getAttribute('data-word');
            const index = chip.getAttribute('data-index');
            selectedWords.push({ word, index });
            chip.style.display = 'none';
            updateAnswerArea();
        }

        function updateAnswerArea() {
            const answerArea = document.getElementById('answerArea');
            if (selectedWords.length === 0) {
                answerArea.innerHTML = '<span class="answer-placeholder">Tap the words below</span>';
                selectedAnswer = null;
                document.getElementById('checkBtn').disabled = true;
            } else {
                answerArea.innerHTML = selectedWords.map((item, i) => `
                    <span class="selected-word" onclick="removeWordFromAnswer(${i})">
                        ${escapeHtml(item.word)}
                        <span class="remove-word">×</span>
                    </span>
                `).join('');
                selectedAnswer = selectedWords.map(item => item.word).join(' ');
                document.getElementById('checkBtn').disabled = false;
            }
        }

        function removeWordFromAnswer(position) {
            const removedWord = selectedWords[position];
            selectedWords.splice(position, 1);
            const chip = document.querySelector(`.word-chip[data-index="${removedWord.index}"]`);
            if (chip) chip.style.display = 'inline-block';
            updateAnswerArea();
        }

        function checkAnswer() {
            if (!selectedAnswer) return;

            const question = questions[currentQuestionIndex];
            let isCorrect = false;
            
            if (question.type === 'select_meaning') {
                isCorrect = selectedAnswer === question.correct_answer;
            } else if (question.type === 'select_word') {
                isCorrect = selectedAnswer === question.chinese_character;
            } else if (question.type === 'rearrange') {
                isCorrect = selectedAnswer.toLowerCase().trim() === question.english_meaning.toLowerCase().trim();
            }
            
            userAnswers.push({
                question: question,
                userAnswer: selectedAnswer,
                isCorrect: isCorrect
            });
            
            if (isCorrect) {
                score++;
                correctStreak++;
            } else {
                correctStreak = 0;
            }
            
            disableOptions();
            highlightAnswers(isCorrect);
            
            setTimeout(() => {
                showInstantFeedback(isCorrect);
            }, 600);
        }

        function disableOptions() {
            document.querySelectorAll('.option-btn, .word-option-card, .word-chip').forEach(el => {
                el.style.pointerEvents = 'none';
            });
            document.getElementById('checkBtn').disabled = true;
        }

        function highlightAnswers(isCorrect) {
            const question = questions[currentQuestionIndex];
            
            if (question.type === 'select_meaning') {
                document.querySelectorAll('.option-btn').forEach(btn => {
                    const optionText = btn.getAttribute('data-answer');
                    if (optionText === question.correct_answer) {
                        btn.classList.add('correct');
                    } else if (optionText === selectedAnswer && !isCorrect) {
                        btn.classList.add('wrong');
                    }
                });
            } else if (question.type === 'select_word') {
                document.querySelectorAll('.word-option-card').forEach(btn => {
                    const wordChar = btn.getAttribute('data-answer');
                    if (wordChar === question.chinese_character) {
                        btn.classList.add('correct');
                    } else if (wordChar === selectedAnswer && !isCorrect) {
                        btn.classList.add('wrong');
                    }
                });
            } else if (question.type === 'rearrange') {
                const answerArea = document.getElementById('answerArea');
                if (answerArea) {
                    answerArea.style.borderColor = isCorrect ? '#4caf50' : '#f44336';
                    answerArea.style.background = isCorrect ? '#e8f5e9' : '#ffebee';
                }
            }
        }

        function showInstantFeedback(isCorrect) {
    const overlay = document.getElementById('feedbackOverlay');
    const popup = document.getElementById('instantFeedbackPopup');
    const icon = document.getElementById('instantFeedbackIcon');
    const title = document.getElementById('instantFeedbackTitle');
    const message = document.getElementById('instantFeedbackMessage');
    const correctAnswerDiv = document.getElementById('correctAnswerDisplay');
    
    // Show overlay and popup
    overlay.classList.add('show');
    popup.classList.add('show');
    
    if (isCorrect) {
        // Correct answer
        popup.classList.remove('wrong');
        popup.classList.add('correct');
        
        icon.textContent = '✓';
        
        const correctMessages = [
            'Awesome!', 'Perfect!', 'Excellent!', 
            'Amazing!', 'Brilliant!', 'Outstanding!', 'Fantastic!'
        ];
        title.textContent = correctMessages[Math.floor(Math.random() * correctMessages.length)];
        
        message.textContent = "Keep going! You're doing great!";
        correctAnswerDiv.style.display = 'none';
        
        createConfetti();
    } else {
        // Wrong answer
        popup.classList.remove('correct');
        popup.classList.add('wrong');
        
        icon.textContent = '✕';
        title.textContent = 'Not quite!';
        message.textContent = "Don't worry, you'll get the next one!";
        
        const question = questions[currentQuestionIndex];
        correctAnswerDiv.textContent = `✓ Correct answer: ${question.correct_answer}`;
        correctAnswerDiv.style.display = 'block';
    }
}

        function closeInstantFeedback() {
    const popup = document.getElementById('instantFeedbackPopup');
    const overlay = document.getElementById('feedbackOverlay');
    
    popup.classList.remove('show');
    overlay.classList.remove('show');
    
    document.querySelectorAll('.confetti-particle').forEach(c => c.remove());
    
    currentQuestionIndex++;
    selectedWords = [];
    
    if (currentQuestionIndex >= questions.length) {
        // FIXED: Added themeName property
        sessionStorage.setItem('quizResults', JSON.stringify({
            theme: themeName,
            themeName: themeName,
            themeId: themeId,
            difficulty: difficulty,
            score: score,
            total: questions.length,
            answers: userAnswers,
            finalStreak: correctStreak
        }));
        window.location.href = 'quiz-results.php';
    } else {
        setTimeout(() => {
            showQuestion(currentQuestionIndex);
        }, 300);
    }
}

        function createConfetti() {
            const colors = ['#4caf50', '#2196f3', '#ff9800', '#e91e63', '#9c27b0', '#ffeb3b'];
            for (let i = 0; i < 60; i++) {
                setTimeout(() => {
                    const confetti = document.createElement('div');
                    confetti.className = 'confetti-particle';
                    confetti.style.left = Math.random() * 100 + '%';
                    confetti.style.top = '-20px';
                    confetti.style.background = colors[Math.floor(Math.random() * colors.length)];
                    const size = Math.random() * 10 + 5;
                    confetti.style.width = size + 'px';
                    confetti.style.height = size + 'px';
                    document.body.appendChild(confetti);
                    setTimeout(() => confetti.remove(), 3000);
                }, i * 20);
            }
        }

        function nextQuestion() {
            // This function is now only used by skipQuestion
            currentQuestionIndex++;
            selectedWords = [];
            
            if (currentQuestionIndex >= questions.length) {
                // FIXED: Added themeName property
                sessionStorage.setItem('quizResults', JSON.stringify({
                    theme: themeName,
                    themeName: themeName,
                    themeId: themeId,
                    difficulty: difficulty,
                    score: score,
                    total: questions.length,
                    answers: userAnswers,
                    finalStreak: correctStreak
                }));
                window.location.href = 'quiz-results.php';
            } else {
                document.getElementById('answerButtons').style.display = 'flex';
                showQuestion(currentQuestionIndex);
            }
        }

        function skipQuestion() {
            userAnswers.push({
                question: questions[currentQuestionIndex],
                userAnswer: null,
                isCorrect: false
            });
            nextQuestion();
        }

        function playQuizAudio(text) {
            if (window.speechSynthesis.speaking) {
                window.speechSynthesis.cancel();
            }
            const utterance = new SpeechSynthesisUtterance(text);
            const voices = window.speechSynthesis.getVoices();
            const chineseVoice = voices.find(voice => voice.lang.startsWith('zh'));
            if (chineseVoice) {
                utterance.voice = chineseVoice;
                utterance.lang = chineseVoice.lang;
            } else {
                utterance.lang = 'zh-CN';
            }
            utterance.rate = 0.7;
            window.speechSynthesis.speak(utterance);
        }

        function confirmExit() {
            if (confirm('Are you sure you want to quit? Your progress will be lost.')) {
                window.location.href = 'quiz.php';
            }
        }

        

    </script>
</body>
</html>