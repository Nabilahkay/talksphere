<?php
require_once 'config/session.php';
checkLogin();
require_once 'config/database.php';
$user = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to TalkSphere - Learn Mandarin with AR</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #fff;
            overflow-x: hidden;
        }

        /* Hero Section */
        .landing-hero {
            background: linear-gradient(135deg, #c92a2a 0%, #a61e1e 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
            color: white;
            padding: 80px 40px 40px;
        }

        .landing-hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg width="100" height="100" xmlns="http://www.w3.org/2000/svg"><circle cx="50" cy="50" r="2" fill="rgba(255,255,255,0.1)"/></svg>');
            opacity: 0.3;
        }

        .hero-content {
            max-width: 1200px;
            text-align: center;
            position: relative;
            z-index: 2;
            animation: fadeInUp 1s ease;
        }

        .hero-logo {
            width: 120px;
            height: 120px;
            background: white;
            border-radius: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 60px;
            font-weight: bold;
            color: #c92a2a;
            margin: 0 auto 30px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            animation: bounce 2s ease infinite;
        }

        .hero-title {
            font-size: 72px;
            font-weight: 800;
            margin-bottom: 20px;
            text-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
            letter-spacing: -2px;
        }

        .hero-subtitle {
            font-size: 28px;
            margin-bottom: 40px;
            opacity: 0.95;
            font-weight: 300;
            line-height: 1.6;
        }

        .hero-cta {
            display: flex;
            gap: 20px;
            justify-content: center;
            margin-top: 50px;
        }

        .cta-btn {
            padding: 20px 50px;
            font-size: 20px;
            font-weight: 700;
            border-radius: 50px;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 10px;
        }

        .cta-primary {
            background: white;
            color: #c92a2a;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        }

        .cta-primary:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.3);
        }

        .cta-secondary {
            background: transparent;
            color: white;
            border: 3px solid white;
        }

        .cta-secondary:hover {
            background: rgba(255, 255, 255, 0.1);
            transform: translateY(-5px);
        }

        .scroll-indicator {
            position: absolute;
            bottom: 40px;
            left: 50%;
            transform: translateX(-50%);
            animation: bounce 2s ease infinite;
            cursor: pointer;
        }

        .scroll-indicator span {
            font-size: 40px;
            opacity: 0.8;
        }

        /* Features Section */
        .features-section {
            padding: 100px 40px;
            background: #f8f9fa;
        }

        .section-header {
            text-align: center;
            margin-bottom: 80px;
        }

        .section-title {
            font-size: 48px;
            color: #333;
            margin-bottom: 20px;
            font-weight: 700;
        }

        .section-subtitle {
            font-size: 20px;
            color: #666;
            max-width: 600px;
            margin: 0 auto;
        }

        .features-grid {
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 40px;
        }

        .feature-card {
            background: white;
            border-radius: 20px;
            padding: 40px;
            text-align: center;
            transition: all 0.3s ease;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        }

        .feature-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.15);
        }

        .feature-icon {
            font-size: 80px;
            margin-bottom: 25px;
            display: inline-block;
            animation: float 3s ease-in-out infinite;
        }

        .feature-card:nth-child(2) .feature-icon {
            animation-delay: 0.5s;
        }

        .feature-card:nth-child(3) .feature-icon {
            animation-delay: 1s;
        }

        .feature-title {
            font-size: 24px;
            color: #333;
            margin-bottom: 15px;
            font-weight: 700;
        }

        .feature-description {
            font-size: 16px;
            color: #666;
            line-height: 1.8;
        }

        /* How It Works Section */
        .how-it-works {
            padding: 100px 40px;
            background: white;
        }

        .steps-container {
            max-width: 1000px;
            margin: 0 auto;
            display: grid;
            gap: 60px;
        }

        .step {
            display: grid;
            grid-template-columns: 100px 1fr;
            gap: 30px;
            align-items: center;
        }

        .step:nth-child(even) {
            direction: rtl;
        }

        .step:nth-child(even) > * {
            direction: ltr;
        }

        .step-number {
            width: 100px;
            height: 100px;
            background: linear-gradient(135deg, #c92a2a 0%, #a61e1e 100%);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 42px;
            font-weight: 800;
            box-shadow: 0 10px 30px rgba(201, 42, 42, 0.3);
        }

        .step-content h3 {
            font-size: 28px;
            color: #333;
            margin-bottom: 15px;
            font-weight: 700;
        }

        .step-content p {
            font-size: 18px;
            color: #666;
            line-height: 1.8;
        }

        /* Stats Section */
        .stats-section {
            padding: 80px 40px;
            background: linear-gradient(135deg, #c92a2a 0%, #a61e1e 100%);
            color: white;
        }

        .stats-grid {
            max-width: 1000px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 40px;
            text-align: center;
        }

        .stat-box {
            padding: 30px;
        }

        .stat-number {
            font-size: 56px;
            font-weight: 800;
            margin-bottom: 10px;
        }

        .stat-label {
            font-size: 18px;
            opacity: 0.9;
        }

        /* CTA Section */
        .final-cta {
            padding: 100px 40px;
            background: #f8f9fa;
            text-align: center;
        }

        .final-cta h2 {
            font-size: 48px;
            color: #333;
            margin-bottom: 30px;
            font-weight: 700;
        }

        .final-cta p {
            font-size: 20px;
            color: #666;
            margin-bottom: 50px;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
        }

        /* Animations */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(50px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes bounce {
            0%, 100% {
                transform: translateY(0);
            }
            50% {
                transform: translateY(-20px);
            }
        }

        @keyframes float {
            0%, 100% {
                transform: translateY(0);
            }
            50% {
                transform: translateY(-15px);
            }
        }

        /* Responsive */
        @media (max-width: 768px) {
            .hero-title {
                font-size: 48px;
            }

            .hero-subtitle {
                font-size: 20px;
            }

            .hero-cta {
                flex-direction: column;
                align-items: center;
            }

            .cta-btn {
                width: 100%;
                max-width: 300px;
            }

            .section-title {
                font-size: 36px;
            }

            .step {
                grid-template-columns: 1fr;
                text-align: center;
            }

            .step:nth-child(even) {
                direction: ltr;
            }

            .step-number {
                margin: 0 auto;
            }
        }
    </style>
</head>
<body>
    <!-- Hero Section -->
    <div class="landing-hero">
        <div class="hero-content">
            <div class="hero-logo">T</div>
            <h1 class="hero-title">Welcome to TalkSphere</h1>
            <p class="hero-subtitle">
                Master Mandarin Chinese with cutting-edge AR technology.<br>
                Learn, practice, and immerse yourself in interactive experiences.
            </p>
            
            <div class="hero-cta">
                <a href="dashboard.php" class="cta-btn cta-primary">
                    <span>Start Learning</span>
                    <span>→</span>
                </a>
                <a href="#features" class="cta-btn cta-secondary">
                    <span>Learn More</span>
                </a>
            </div>
        </div>
        
        <div class="scroll-indicator" onclick="document.getElementById('features').scrollIntoView({behavior: 'smooth'})">
            <span>↓</span>
        </div>
    </div>

    <!-- Features Section -->
    <div class="features-section" id="features">
        <div class="section-header">
            <h2 class="section-title">Why Choose TalkSphere?</h2>
            <p class="section-subtitle">
                Experience the future of language learning with innovative tools designed for success
            </p>
        </div>

        <div class="features-grid">
            <div class="feature-card">
                <div class="feature-icon">📚</div>
                <h3 class="feature-title">Interactive Vocabulary</h3>
                <p class="feature-description">
                    Learn Chinese characters, pinyin, and meanings through engaging flashcards and visual aids.
                </p>
            </div>

            <div class="feature-card">
                <div class="feature-icon">🎯</div>
                <h3 class="feature-title">Smart Quizzes</h3>
                <p class="feature-description">
                    Test your knowledge with adaptive quizzes that adjust to your skill level and track progress.
                </p>
            </div>

            <div class="feature-card">
                <div class="feature-icon">📱</div>
                <h3 class="feature-title">AR Experience</h3>
                <p class="feature-description">
                    Scan QR codes to unlock 3D models and audio pronunciations in augmented reality.
                </p>
            </div>

            <div class="feature-card">
                <div class="feature-icon">🔥</div>
                <h3 class="feature-title">Streak Tracking</h3>
                <p class="feature-description">
                    Build consistent study habits with daily streaks and earn points as you progress.
                </p>
            </div>

            <div class="feature-card">
                <div class="feature-icon">🎨</div>
                <h3 class="feature-title">Themed Learning</h3>
                <p class="feature-description">
                    Master vocabulary through organized themes like Colors, Numbers, Fruits, and more.
                </p>
            </div>

            <div class="feature-card">
                <div class="feature-icon">📊</div>
                <h3 class="feature-title">Progress Analytics</h3>
                <p class="feature-description">
                    Monitor your learning journey with detailed statistics and performance insights.
                </p>
            </div>
        </div>
    </div>

    <!-- How It Works Section -->
    <div class="how-it-works">
        <div class="section-header">
            <h2 class="section-title">How It Works</h2>
            <p class="section-subtitle">
                Get started in just a few simple steps
            </p>
        </div>

        <div class="steps-container">
            <div class="step">
                <div class="step-number">1</div>
                <div class="step-content">
                    <h3>Choose Your Theme</h3>
                    <p>
                        Select from various themes like Colors, Numbers, or Fruits to begin your learning journey. Each theme contains carefully curated vocabulary.
                    </p>
                </div>
            </div>

            <div class="step">
                <div class="step-number">2</div>
                <div class="step-content">
                    <h3>Learn Vocabulary</h3>
                    <p>
                        Study Chinese characters with pinyin pronunciation guides, English meanings, and audio support. Flip cards to reveal information.
                    </p>
                </div>
            </div>

            <div class="step">
                <div class="step-number">3</div>
                <div class="step-content">
                    <h3>Take Interactive Quizzes</h3>
                    <p>
                        Challenge yourself with multiple-choice questions, character recognition, and sentence building exercises across three difficulty levels.
                    </p>
                </div>
            </div>

            <div class="step">
                <div class="step-number">4</div>
                <div class="step-content">
                    <h3>Explore AR Learning</h3>
                    <p>
                        Scan QR codes with your device to experience vocabulary in augmented reality with 3D models and native audio pronunciation.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Stats Section -->
    <div class="stats-section">
        <div class="stats-grid">
            <div class="stat-box">
                <div class="stat-number">50+</div>
                <div class="stat-label">Vocabulary Words</div>
            </div>

            <div class="stat-box">
                <div class="stat-number">3</div>
                <div class="stat-label">Difficulty Levels</div>
            </div>

            <div class="stat-box">
                <div class="stat-number">AR</div>
                <div class="stat-label">Technology</div>
            </div>

            <div class="stat-box">
                <div class="stat-number">24/7</div>
                <div class="stat-label">Learn Anytime</div>
            </div>
        </div>
    </div>

    <!-- Final CTA -->
    <div class="final-cta">
        <h2>Ready to Start Your Journey?</h2>
        <p>
            Join TalkSphere today and discover a revolutionary way to learn Mandarin Chinese. Your path to fluency begins here!
        </p>
        <a href="dashboard.php" class="cta-btn cta-primary">
            <span>Go to Dashboard</span>
            <span>→</span>
        </a>
    </div>

    <script>
        // Smooth scroll animation
        document.addEventListener('DOMContentLoaded', () => {
            // Add scroll reveal animations
            const observerOptions = {
                threshold: 0.1,
                rootMargin: '0px 0px -50px 0px'
            };

            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.style.opacity = '1';
                        entry.target.style.transform = 'translateY(0)';
                    }
                });
            }, observerOptions);

            // Observe all feature cards and steps
            document.querySelectorAll('.feature-card, .step').forEach(el => {
                el.style.opacity = '0';
                el.style.transform = 'translateY(30px)';
                el.style.transition = 'all 0.6s ease';
                observer.observe(el);
            });
        });
    </script>
</body>
</html>