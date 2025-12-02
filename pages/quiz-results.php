<?php
require_once '../config/session.php';
checkLogin();
require_once '../config/database.php';
require_once '../config/streak_functions.php';
$user = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quiz Results</title>
    <link rel="stylesheet" href="../css/dashboard.css">
    <link rel="stylesheet" href="../css/quiz.css">
    <style>
        /* Force full width and height */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body, html {
            width: 100%;
            height: 100%;
            overflow-x: hidden;
        }

        .results-container {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            width: 100vw;
            height: 100vh;
            background: #f5f5f5;
            padding: 40px 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow-y: auto;
        }

        .results-card {
            background: white;
            border-radius: 30px;
            padding: 30px 25px;
            max-width: 500px;
            width: 100%;
            box-shadow: 0 20px 60px rgba(0,0,0,0.15);
            animation: slideUp 0.5s ease;
            margin: 20px auto;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .results-header {
            text-align: center;
            margin-bottom: 20px;
        }

        .results-emoji {
            font-size: 50px;
            margin-bottom: 10px;
            animation: bounce 1s ease infinite;
        }

        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }

        .results-title {
            font-size: 24px;
            font-weight: 800;
            color: #333;
            margin-bottom: 6px;
        }

        .results-subtitle {
            font-size: 14px;
            color: #666;
        }

        /* Score Circle */
        .score-circle-container {
            display: flex;
            justify-content: center;
            margin: 20px 0;
        }

        .score-circle {
            position: relative;
            width: 140px;
            height: 140px;
        }

        .score-circle svg {
            transform: rotate(-90deg);
        }

        .score-circle-bg {
            fill: none;
            stroke: #e0e0e0;
            stroke-width: 10;
        }

        .score-circle-progress {
            fill: none;
            stroke: #4caf50;
            stroke-width: 10;
            stroke-linecap: round;
            transition: stroke-dashoffset 1s ease;
        }

        .score-text {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            text-align: center;
        }

        .score-number {
            font-size: 36px;
            font-weight: 800;
            color: #4caf50;
        }

        .score-label {
            font-size: 11px;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 12px;
            margin: 20px 0;
        }

        .stat-card {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 10px;
            text-align: center;
            border: 2px solid #e0e0e0;
        }

        .stat-icon {
            font-size: 24px;
            margin-bottom: 6px;
        }

        .stat-value {
            font-size: 22px;
            font-weight: 700;
            color: #333;
            margin-bottom: 3px;
        }

        .stat-label {
            font-size: 11px;
            color: #666;
            text-transform: uppercase;
        }

        /* Performance Bar Chart */
        .performance-section {
            margin: 20px 0;
        }

        .section-title {
            font-size: 18px;
            font-weight: 700;
            color: #333;
            margin-bottom: 12px;
        }

        .performance-bar {
            background: #f8f9fa;
            height: 30px;
            border-radius: 15px;
            overflow: hidden;
            position: relative;
            margin-bottom: 8px;
        }

        .performance-bar-fill {
            height: 100%;
            background: linear-gradient(90deg, #4caf50, #8bc34a);
            display: flex;
            align-items: center;
            justify-content: flex-end;
            padding-right: 10px;
            color: white;
            font-weight: 700;
            transition: width 1s ease;
            font-size: 13px;
        }

        .performance-bar-label {
            position: absolute;
            left: 10px;
            top: 50%;
            transform: translateY(-50%);
            font-weight: 600;
            color: #333;
            z-index: 1;
            font-size: 13px;
        }

        /* Answer Review */
        .review-section {
            margin: 20px 0;
        }

        .review-toggle {
            background: #2196f3;
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
            transition: 0.3s;
            width: 100%;
        }

        .review-toggle:hover {
            background: #1976d2;
        }

        .review-list {
            margin-top: 20px;
            display: none;
        }

        .review-list.show {
            display: block;
        }

        .review-item {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 12px;
            margin-bottom: 10px;
            border-left: 4px solid #e0e0e0;
        }

        .review-item.correct {
            border-left-color: #4caf50;
            background: #e8f5e9;
        }

        .review-item.wrong {
            border-left-color: #f44336;
            background: #ffebee;
        }

        .review-question {
            font-size: 18px;
            font-weight: 700;
            margin-bottom: 6px;
            color: #333;
        }

        .review-answers {
            font-size: 13px;
            color: #666;
        }

        .review-answers strong {
            color: #333;
        }

        /* Action Buttons */
        .action-buttons {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }

        .action-btn {
            flex: 1;
            padding: 12px;
            border: none;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            text-transform: uppercase;
            position: relative;
            overflow: hidden;
        }

        .action-btn::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.3);
            transform: translate(-50%, -50%);
            transition: width 0.6s, height 0.6s;
        }

        .action-btn:active::before {
            width: 300px;
            height: 300px;
        }

        .retry-btn {
            background: #2196f3;
            color: white;
            box-shadow: 0 4px 15px rgba(33, 150, 243, 0.3);
        }

        .retry-btn:hover {
            background: #1976d2;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(33, 150, 243, 0.4);
        }

        .home-btn {
            background: #4caf50;
            color: white;
            box-shadow: 0 4px 15px rgba(76, 175, 80, 0.3);
        }

        .home-btn:hover {
            background: #45a049;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(76, 175, 80, 0.4);
        }

        /* Mobile Responsive */
        @media (max-width: 768px) {
            .results-card {
                padding: 30px 20px;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .action-buttons {
                flex-direction: column;
            }

            .score-circle {
                width: 140px;
                height: 140px;
            }

            .score-number {
                font-size: 32px;
            }
        }
    </style>
</head>
<body>
    <div class="results-container">
        <div class="results-card">
            <div class="results-header">
                <div class="results-emoji" id="resultsEmoji">🎉</div>
                <h1 class="results-title" id="resultsTitle">Quiz Complete!</h1>
                <p class="results-subtitle" id="resultsSubtitle">Here's how you did</p>
            </div>

            <!-- Score Circle -->
            <div class="score-circle-container">
                <div class="score-circle">
                    <svg width="140" height="140">
                        <circle class="score-circle-bg" cx="70" cy="70" r="60"></circle>
                        <circle class="score-circle-progress" id="scoreCircle" cx="70" cy="70" r="60"
                                stroke-dasharray="377" stroke-dashoffset="377"></circle>
                    </svg>
                    <div class="score-text">
                        <div class="score-number" id="scorePercentage">0%</div>
                        <div class="score-label">Score</div>
                    </div>
                </div>
            </div>

            <!-- Stats Grid -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">✅</div>
                    <div class="stat-value" id="correctCount">0</div>
                    <div class="stat-label">Correct</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">❌</div>
                    <div class="stat-value" id="wrongCount">0</div>
                    <div class="stat-label">Wrong</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">🔥</div>
                    <div class="stat-value" id="streakCount">0</div>
                    <div class="stat-label">Best Streak</div>
                </div>
            </div>

            <!-- Performance Chart -->
            <div class="performance-section">
                <h2 class="section-title">Performance Breakdown</h2>
                <div class="performance-bar">
                    <div class="performance-bar-label">Correct Answers</div>
                    <div class="performance-bar-fill" id="correctBar" style="width: 0%">
                        <span id="correctBarText">0%</span>
                    </div>
                </div>
                <div class="performance-bar">
                    <div class="performance-bar-label">Wrong Answers</div>
                    <div class="performance-bar-fill" id="wrongBar" style="width: 0%; background: linear-gradient(90deg, #f44336, #e57373);">
                        <span id="wrongBarText">0%</span>
                    </div>
                </div>
            </div>

            <!-- Answer Review -->
            <div class="review-section">
                <button class="review-toggle" onclick="toggleReview()">📝 View Answer Review</button>
                <div class="review-list" id="reviewList">
                    <!-- Reviews will be inserted here -->
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="action-buttons">
                <button class="action-btn retry-btn" onclick="retryQuiz()">🔄 Try Again</button>
                <button class="action-btn home-btn" onclick="goHome()">🏠 Back to Quizzes</button>
            </div>
        </div>
    </div>

    <script>
       
// Get results from sessionStorage
const resultsData = sessionStorage.getItem('quizResults');

if (!resultsData) {
    alert('No quiz results found. Please take a quiz first.');
    window.location.href = 'quiz.php';
} else {
    const results = JSON.parse(resultsData);
    displayResults(results);
    // Save score after displaying results
    saveQuizScore(results);
}

// Auto-save quiz score to database
async function saveQuizScore(results) {
    try {
        // Prepare data for saving
        const saveData = {
            theme_id: results.themeId,
            theme_name: results.themeName,  // FIXED: Corrected syntax error
            difficulty: results.difficulty,
            score: results.score,
            total_questions: results.total,
            percentage: Math.round((results.score / results.total) * 100),
            time_taken: results.timeTaken || 0,
            best_streak: results.finalStreak || 0,
            answers: results.answers ? results.answers.map(answer => ({
                question_id: answer.question.vocab_id,
                user_answer: answer.userAnswer || '',
                correct_answer: answer.question.english_meaning,
                is_correct: answer.isCorrect
            })) : []
        };

        const response = await fetch('save-quiz-score.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(saveData)
        });
        
        const result = await response.json();
        if (result.success) {
            console.log('✅ Score saved successfully! Score ID:', result.score_id);
        } else {
            console.error('❌ Failed to save score:', result.message);
        }
    } catch (error) {
        console.error('❌ Error saving score:', error);
    }
}

function displayResults(results) {
    const { theme, difficulty, score, total, answers, finalStreak } = results;
    
    const percentage = Math.round((score / total) * 100);
    const wrong = total - score;
    const wrongPercentage = Math.round((wrong / total) * 100);
    
    // Update emoji and messages based on score
    const emoji = document.getElementById('resultsEmoji');
    const title = document.getElementById('resultsTitle');
    const subtitle = document.getElementById('resultsSubtitle');
    
    if (percentage === 100) {
        emoji.textContent = '🏆';
        title.textContent = 'Perfect Score!';
        subtitle.textContent = 'Outstanding! You\'re a master!';
    } else if (percentage >= 80) {
        emoji.textContent = '🎉';
        title.textContent = 'Excellent Work!';
        subtitle.textContent = 'Great job! Keep it up!';
    } else if (percentage >= 60) {
        emoji.textContent = '👍';
        title.textContent = 'Good Effort!';
        subtitle.textContent = 'Nice work! Practice makes perfect!';
    } else {
        emoji.textContent = '💪';
        title.textContent = 'Keep Practicing!';
        subtitle.textContent = 'You\'ll get better with practice!';
    }
    
    // Animate score circle
    setTimeout(() => {
        const circle = document.getElementById('scoreCircle');
        const circumference = 377;
        const offset = circumference - (percentage / 100) * circumference;
        circle.style.strokeDashoffset = offset;
        
        // Animate percentage number
        animateNumber('scorePercentage', 0, percentage, 1000, '%');
    }, 300);
    
    // Update stats
    animateNumber('correctCount', 0, score, 1000);
    animateNumber('wrongCount', 0, wrong, 1000);
    animateNumber('streakCount', 0, finalStreak || 0, 1000);
    
    // Update performance bars
    setTimeout(() => {
        document.getElementById('correctBar').style.width = percentage + '%';
        document.getElementById('correctBarText').textContent = percentage + '%';
        
        document.getElementById('wrongBar').style.width = wrongPercentage + '%';
        document.getElementById('wrongBarText').textContent = wrongPercentage + '%';
    }, 500);
    
    // Generate answer review
    if (answers && answers.length > 0) {
        const reviewList = document.getElementById('reviewList');
        reviewList.innerHTML = answers.map((answer, index) => {
            const isCorrect = answer.isCorrect;
            const itemClass = isCorrect ? 'correct' : 'wrong';
            const icon = isCorrect ? '✅' : '❌';
            
            return `
                <div class="review-item ${itemClass}">
                    <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 10px;">
                        <span style="font-size: 24px;">${icon}</span>
                        <span style="font-size: 18px; font-weight: 600;">Question ${index + 1}</span>
                    </div>
                    <div class="review-question">${escapeHtml(answer.question.chinese_character)}</div>
                    <div class="review-answers">
                        ${answer.userAnswer ? `
                            <div><strong>Your answer:</strong> ${escapeHtml(answer.userAnswer)}</div>
                        ` : `
                            <div><strong>You skipped this question</strong></div>
                        `}
                        ${!isCorrect ? `
                            <div style="color: #4caf50; margin-top: 5px;">
                                <strong>Correct answer:</strong> ${escapeHtml(answer.question.correct_answer)}
                            </div>
                        ` : ''}
                    </div>
                </div>
            `;
        }).join('');
    }
}

function animateNumber(elementId, start, end, duration, suffix = '') {
    const element = document.getElementById(elementId);
    const range = end - start;
    const increment = range / (duration / 16);
    let current = start;
    
    const timer = setInterval(() => {
        current += increment;
        if ((increment > 0 && current >= end) || (increment < 0 && current <= end)) {
            current = end;
            clearInterval(timer);
        }
        element.textContent = Math.round(current) + suffix;
    }, 16);
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function toggleReview() {
    const reviewList = document.getElementById('reviewList');
    reviewList.classList.toggle('show');
}

function retryQuiz() {
    const resultsData = sessionStorage.getItem('quizResults');
    if (resultsData) {
        const results = JSON.parse(resultsData);
        
        // Show confirmation with animation
        if (confirm('🔄 Ready to try again? Your previous score will be replaced.')) {
            // Clear old results
            sessionStorage.removeItem('quizResults');
            
            // Redirect to quiz with same settings
            window.location.href = `take-quiz.php?theme=${results.themeId}&difficulty=${results.difficulty}`;
        }
    } else {
        alert('⚠️ No quiz data found. Redirecting to quiz selection...');
        window.location.href = 'quiz.php';
    }
}

function goHome() {
    // Clear results
    sessionStorage.removeItem('quizResults');
    
    // Redirect to quiz selection
    window.location.href = 'quiz.php';
}
    </script>
</body>
</html>