<?php
$featuredScholarships = [
    [
        'title' => 'STEM Excellence Scholarship',
        'provider' => 'City Education Foundation',
        'amount' => '₱25,000',
        'deadline' => 'April 30, 2026',
        'tag' => 'STEM'
    ],
    [
        'title' => 'Future Teachers Grant',
        'provider' => 'National Teachers Association',
        'amount' => '₱18,000',
        'deadline' => 'May 12, 2026',
        'tag' => 'Education'
    ],
    [
        'title' => 'Community Leadership Award',
        'provider' => 'Bayanihan Youth Council',
        'amount' => '₱20,000',
        'deadline' => 'June 5, 2026',
        'tag' => 'Leadership'
    ],
    [
        'title' => 'Financial Assistance Program',
        'provider' => 'Hope for Students Foundation',
        'amount' => '₱15,000',
        'deadline' => 'May 28, 2026',
        'tag' => 'Financial Aid'
    ]
];

$categories = [
    'Academic Excellence',
    'Financial Assistance',
    'STEM Programs',
    'Leadership Grants',
    'Athletic Scholarships',
    'Special Programs'
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ScholarLink - Public Scholarship Portal</title>
    <link rel="stylesheet" href="landing.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>

<div class="page-wrap">
    <div class="main-container">

        <header class="topbar">
            <div class="logo">ScholarLink</div>

            <nav class="nav-links">
                <a href="#">Home</a>
                <a href="#featured">Scholarships</a>
                <a href="#categories">Categories</a>
                <a href="#how">How it Works</a>
                <a href="../pages/auth/login.php" class="login-link">Sign In</a>
                <a href="../pages/auth/register.php" class="join-btn">Join Now</a>
            </nav>
        </header>

        <section class="hero-section">
            <div class="hero-left">
                <div class="hero-title-box yellow-box">Find Your</div>
                <div class="hero-title-box green-box">Dream Scholarship</div>

                <p class="hero-text">
                    ScholarLink helps students discover scholarship opportunities with ease.
                    Browse scholarships, check requirements, and explore deadlines without logging in.
                    Create an account only when you are ready to apply.
                </p>

                <div class="hero-mini">
                    <div class="mini-avatars">
                        <span></span>
                        <span></span>
                        <span></span>
                    </div>
                    <div class="mini-text">
                        <strong>Find your future</strong><br>
                        scholarship with us
                    </div>
                </div>
            </div>

            <div class="hero-right">
                <div class="hero-image-card">
                    <img src="https://images.unsplash.com/photo-1523240795612-9a054b0db644?auto=format&fit=crop&w=1200&q=80" alt="Students">
                    <div class="rating-box">
                        <h3>4.9</h3>
                        <p>Trusted by students</p>
                    </div>
                </div>
            </div>
        </section>

        <section class="stats-grid">
            <div class="info-card teal-card">
                <div class="info-image">
                    <img src="https://images.unsplash.com/photo-1544717305-2782549b5136?auto=format&fit=crop&w=700&q=80" alt="Student">
                </div>
                <div class="info-content">
                    <p class="quote">
                        “Connecting students with opportunity — making scholarships easier to discover and apply for.”
                    </p>
                    <h2>20,000+</h2>
                    <p>Student Visits</p>
                </div>
            </div>

            <div class="info-card yellow-stat">
                <h2>120+</h2>
                <p>Active scholarships available for public viewing.</p>
            </div>

            <div class="info-card teal-person">
                <img src="https://images.unsplash.com/photo-1500648767791-00dcc994a43e?auto=format&fit=crop&w=700&q=80" alt="Student">
            </div>
        </section>

        <section id="categories" class="section-block">
            <div class="section-head">
                <h2 class="section-title">Scholarship Categories</h2>
                <p class="section-subtitle">Explore opportunities based on your interests and qualifications.</p>
            </div>

            <div class="categories-grid">
                <?php foreach ($categories as $category): ?>
                    <div class="category-item">
                        <div class="category-icon"><i class="bi bi-award"></i></div>
                        <h4><?= htmlspecialchars($category) ?></h4>
                        <p>Browse scholarships under this category.</p>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <section id="featured" class="section-block">
            <div class="section-head">
                <h2 class="section-title">Featured Scholarships</h2>
                <p class="section-subtitle">Public listings students can view before signing in.</p>
            </div>

            <div class="scholarship-grid">
                <?php foreach ($featuredScholarships as $item): ?>
                    <div class="scholarship-card">
                        <div class="card-top">
                            <span class="tag-badge"><?= htmlspecialchars($item['tag']) ?></span>
                            <span class="status-badge">Open</span>
                        </div>

                        <h4><?= htmlspecialchars($item['title']) ?></h4>
                        <p class="provider"><?= htmlspecialchars($item['provider']) ?></p>

                        <div class="scholarship-meta">
                            <p><i class="bi bi-cash-stack"></i> <strong>Amount:</strong> <?= htmlspecialchars($item['amount']) ?></p>
                            <p><i class="bi bi-calendar-event"></i> <strong>Deadline:</strong> <?= htmlspecialchars($item['deadline']) ?></p>
                        </div>

                        <a href="../pages/auth/login.php" class="card-btn">View Details</a>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <section id="how" class="section-block how-section">
            <div class="section-head center-text">
                <h2 class="section-title">How It Works</h2>
                <p class="section-subtitle">A simple process for students and visitors.</p>
            </div>

            <div class="how-grid">
                <div class="how-card">
                    <div class="step-number">1</div>
                    <h4>Browse Scholarships</h4>
                    <p>Explore available scholarships, providers, and deadlines publicly.</p>
                </div>

                <div class="how-card">
                    <div class="step-number">2</div>
                    <h4>Check Requirements</h4>
                    <p>Review qualifications and prepare documents before applying.</p>
                </div>

                <div class="how-card">
                    <div class="step-number">3</div>
                    <h4>Register and Apply</h4>
                    <p>Create an account only when you are ready to submit your application.</p>
                </div>
            </div>
        </section>

        <section class="cta-section">
            <div class="cta-box">
                <h2>Start browsing scholarships today</h2>
                <p>No login needed for browsing. Register only when you are ready to apply.</p>

                <div class="cta-actions">
                    <a href="../pages/auth/register.php" class="join-btn">Register</a>
                    <a href="../pages/auth/login.php" class="outline-btn">Sign In</a>
                </div>
            </div>
        </section>

        <footer class="footer">
            <div class="footer-col">
                <h4>ScholarLink</h4>
                <p>Helping students connect with scholarship opportunities through a clean and accessible platform.</p>
            </div>

            <div class="footer-col">
                <h5>Quick Links</h5>
                <a href="#">Home</a>
                <a href="#featured">Scholarships</a>
                <a href="#categories">Categories</a>
            </div>

            <div class="footer-col">
                <h5>Account</h5>
                <a href="../pages/auth/login.php">Login</a>
                <a href="../pages/auth/register.php">Register</a>
            </div>

            <div class="footer-col">
                <h5>Contact</h5>
                <p>support@scholarlink.com</p>
                <p>+63 900 000 0000</p>
            </div>
        </footer>

    </div>
</div>

</body>
</html>