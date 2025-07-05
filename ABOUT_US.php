<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us - Daily Bread Bakery</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="ABOUT_US.css">
</head>
<style>
    body {
      margin: 0;
      background: linear-gradient(rgba(0, 0, 0, 0.3), rgba(0, 0, 0, 0.3)),
                  url('./Background/background0.png') center/cover fixed;
      background-repeat: no-repeat;
      background-size: cover;
    }
</style>
<body>
    <nav class="navbar">
        <div class="nav-content">
            <div class="logo-container">
                <img src="logo.png" alt="Bakery Logo" class="logo-img">
                <div class="logo">Daily Bread Bakery</div>
            </div>

            <div class="nav-links">
                <a href="INDEX.php">Home</a>
                <a href="MENU.php">Menu</a>
                <a href="ABOUT_US.php">About Us</a>
                <a href="CONTACT_US.php">Contact</a>
            </div>

            <div class="search-container">
                <i class="fas fa-search search-icon"></i>
        <form method="GET">
            <input type="text" class="search-input" name="search" placeholder="search for products..." 
                   value="<?= isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '' ?>">
        </form>
    </div>

            <div class="action-buttons">
                <button onclick="window.location.href='user/CART.php'" class="cart-btn">
                    <i class="fas fa-shopping-cart"></i>
                </button>
                <button onclick="window.location.href='user/PROFILE.php'" class="action-btn">
                    <i class="far fa-user"></i>
                </button>
            </div>
        </div>
    </nav>

    <section class="about-hero">
        <div class="about-hero-content">
            <h1>Our Story</h1>
            <p>Discover the passion and tradition behind every loaf we bake</p>
        </div>
    </section>

    <section class="story-section">
        <div class="section-title">
            <h2>Our Journey</h2>
        </div>
        <div class="story-content">
            <div class="story-image">
                <img src="./Background/About Us.png" alt="About Us">
            </div>
            <div class="story-text">
                <h3>From Humble Beginnings</h3>
                <p>Founded in 2023, Daily Bread Bakery began as a small family operation in Cyberjaya. Our founder, Aw Zhing Yee, Chen Yun Jia, and Tan Zi Xian, combined Yun Jia's grandmother's traditional recipes with modern baking techniques to create the delicious breads we're known for today.</p>
                <p>What started with just two ovens and a dream has grown into a beloved local institution. Sarah's vision was simple: create exceptional bread using time-honored techniques and the finest ingredients.</p>
                <p>Today, we still bake everything by hand in small batches, just as we did on day one. Our commitment to quality and craftsmanship remains unchanged.</p>
                
                <div class="milestones">
                    <div class="milestone">
                        <h4>2023</h4>
                        <p>Founded</p>
                    </div>
                    <div class="milestone">
                        <h4>10+</h4>
                        <p>Expert Bakers</p>
                    </div>
                    <div class="milestone">
                        <h4>36k+</h4>
                        <p>Happy Customers</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="values-section">
        <div class="section-title">
            <h2>Our Values</h2>
        </div>
        <div class="values-grid">
            <div class="value-card">
                <div class="value-icon">
                    <i class="fas fa-bread-slice"></i>
                </div>
                <h3>Quality Ingredients</h3>
                <p>We source only the finest organic flour, locally sourced dairy, and natural sweeteners. Every ingredient is carefully selected for its quality and flavor.</p>
            </div>
            <div class="value-card">
                <div class="value-icon">
                    <i class="fas fa-hand-holding-heart"></i>
                </div>
                <h3>Handcrafted with Love</h3>
                <p>Every loaf is shaped by hand, every pastry carefully crafted. We believe the human touch makes all the difference in creating exceptional baked goods.</p>
            </div>
            <div class="value-card">
                <div class="value-icon">
                    <i class="fas fa-leaf"></i>
                </div>
                <h3>Sustainable Practices</h3>
                <p>From compostable packaging to energy-efficient ovens, we're committed to reducing our environmental impact while creating delicious products.</p>
            </div>
        </div>
    </section>

    <section class="team-section">
        <div class="section-title">
            <h2>Meet Our Bakers</h2>
        </div>
        <div class="team-grid">
            <div class="team-member">
                <img src="./Background/CHEN.png" alt="CHEN" class="member-photo">
                <h3>Chen</h3>
                <p>Founder & Head Baker</p>
                <p>Granddaughter of a master baker, Chen brings generations of baking wisdom to every creation.</p>
                <div class="social-links">
                    <a href="https://www.instagram.com/y.jiaaa_?utm_source=ig_web_button_share_sheet&igsh=ZDNlZDc0MzIxNw=="><i class="fab fa-instagram"></i></a>
                </div>
            </div>
            <div class="team-member">
                <img src="./Background/EMILY.png" alt="EMILY" class="member-photo">
                <h3>Emily </h3>
                <p>Pastry Chef</p>
                <p>Trained in Paris, Emily creates our exquisite pastries and desserts with French precision.</p>
                <div class="social-links">
                    <a href="https://www.instagram.com/"><i class="fab fa-instagram"></i></a>
                </div>
            </div>
            <div class="team-member">
                <img src="./Background/LINDA.png" alt="LINDA" class="member-photo">
                <h3>Linda</h3>
                <p>Head of Bread</p>
                <p>Linda's sourdough starters are legendary, with some dating back over a decade.</p>
                <div class="social-links">
                    <a href="https://www.instagram.com/"><i class="fab fa-instagram"></i></a>
                </div>
            </div>
            <div class="team-member">
                <img src="./Background/DAVID.png" alt="DAVID LEE" class="member-photo">
                <h3>David Lee</h3>
                <p>Cake Artist</p>
                <p>David transforms simple ingredients into edible works of art that taste as good as they look.</p>
                <div class="social-links">
                    <a href="https://www.instagram.com/"><i class="fab fa-instagram"></i></a>
                </div>
            </div>
        </div>
    </section>

    <section class="gallery-section">
        <div class="section-title">
            <h2>Behind the Scenes</h2>
        </div>
        <div class="gallery-grid">
            <div class="gallery-item">
                <img src="./Background/Bakery Kitchen.png" alt="Bakery Kitchen">
            </div>
            <div class="gallery-item">
                <img src="./Background/Ingredients Store.png" alt="Ingredients Store">
            </div>
            <div class="gallery-item">
                <img src="./Background/Baking Process.png" alt="Baking Process">
            </div>
            <div class="gallery-item">
                <img src="./Background/Products.png" alt="Finished Products">
            </div>
        </div>
    </section>

    <section class="cta-section">
        <div class="cta-content">
            <h2>Experience Our Bread</h2>
            <p>Visit us today to taste the difference that passion, tradition, and quality ingredients make in every bite.</p>
            <a href="MENU.php" class="cta-button">View Our Menu</a>
        </div>
    </section>

    <footer id="contact">
        <div class="footer-content">
            <div class="footer-section">
                <h3>Visit Us</h3>
                <p>423 Artisan Lane<br>Cyberjaya, Selangor</p>
                <p>Open Daily: 9AM - 7PM</p>
            </div>
            <div class="footer-section">
                <h3>Contact</h3>
                <p>Phone: (888) 123-4567<br>Email: hello@dailybread.com</p>
                <div class="social-links-footer">
                    <a href="#"><i class="fab fa-instagram"></i></a>
                    <a href="#"><i class="fab fa-facebook"></i></a>
                    <a href="#"><i class="fab fa-whatsapp"></i></a>
                </div>
            </div>
            <div class="footer-section">
                <h3>Fresh Updates</h3>
                <p>Subscribe to our newsletter for seasonal specials and baking tips!</p>
                <input type="email" placeholder="Enter your email" style="padding: 0.5rem; margin-top: 1rem; width: 100%; border-radius: 30px; border: none; padding: 12px 20px;">
            </div>
        </div>
        <p style="margin-top: 3rem; color: #DEB887;">© <?php echo date("Y"); ?> Daily Bread Bakery - Crafted with ❤️</p>
    </footer>
</body>
</html>