<?php
session_start();

// Database configuration
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "bakerym";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Function to get or create a valid user
function getValidUserId($conn) {
    if (isset($_SESSION['user_id'])) {
        $user_id = $_SESSION['user_id'];
        
        $check_stmt = $conn->prepare("SELECT id FROM users WHERE id = ?");
        $check_stmt->bind_param("i", $user_id);
        $check_stmt->execute();
        $result = $check_stmt->get_result();
        
        if ($result->num_rows > 0) {
            return $user_id;
        }
    }
    
    $result = $conn->query("SELECT id FROM users ORDER BY id LIMIT 1");
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        return $row['id'];
    }
    
    return null;
}

$user_id = getValidUserId($conn);

if (!$user_id) {
    echo "Please log in to view your cart.";
    exit;
}

// Handle quantity updates
if (isset($_POST['update_quantity'])) {
    $product_id = intval($_POST['product_id']);
    $quantity = intval($_POST['quantity']);
    $item_id = intval($_POST['item_id']);
    
    try {
        if ($quantity > 0) {
            // Check available stock before updating
            $stock_check = $conn->prepare("
                SELECT 
                    p.quantity as total_stock,
                    COALESCE(SUM(CASE WHEN oi.id != ? THEN oi.quantity ELSE 0 END), 0) as other_reserved
                FROM products p
                LEFT JOIN order_items oi ON oi.products_id = p.id AND oi.orders_id = 0
                WHERE p.id = ?
                GROUP BY p.id, p.quantity
            ");
            $stock_check->bind_param("ii", $item_id, $product_id);
            $stock_check->execute();
            $stock_result = $stock_check->get_result();
            
            if ($stock_result->num_rows > 0) {
                $stock_row = $stock_result->fetch_assoc();
                $available_stock = $stock_row['total_stock'] - $stock_row['other_reserved'];
                
                if ($quantity <= $available_stock) {
                    $stmt = $conn->prepare("UPDATE order_items SET quantity = ? WHERE id = ? AND user_id = ?");
                    $stmt->bind_param("iii", $quantity, $item_id, $user_id);
                    $stmt->execute();
                    $_SESSION['cart_success'] = "Cart updated successfully!";
                } else {
                    $_SESSION['cart_error'] = "Only $available_stock items available in stock.";
                }
            }
        } else {
            // Delete the item if quantity is 0
            $stmt = $conn->prepare("DELETE FROM order_items WHERE id = ? AND user_id = ?");
            $stmt->bind_param("ii", $item_id, $user_id);
            $stmt->execute();
            $_SESSION['cart_success'] = "Item removed from cart.";
        }
    } catch (Exception $e) {
        $_SESSION['cart_error'] = "Error updating cart: " . $e->getMessage();
    }
    
    header("Location: CART.php");
    exit();
}

// Handle item removal
if (isset($_POST['remove_item'])) {
    $item_id = intval($_POST['item_id']);
    
    try {
        $stmt = $conn->prepare("DELETE FROM order_items WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $item_id, $user_id);
        $stmt->execute();
        $_SESSION['cart_success'] = "Item removed from cart.";
    } catch (Exception $e) {
        $_SESSION['cart_error'] = "Error removing item: " . $e->getMessage();
    }
    
    header("Location: CART.php");
    exit();
}

// Get cart items with enhanced error handling
$cart_query = "
    SELECT 
        oi.id as item_id,
        oi.quantity,
        oi.price,
        oi.products_id,
        p.id as product_id,
        p.name,
        p.description,
        p.images_path,
        p.quantity as stock_quantity,
        (oi.quantity * oi.price) as item_total
    FROM order_items oi
    JOIN products p ON oi.products_id = p.id
    WHERE oi.orders_id = 0 AND oi.user_id = ?
    ORDER BY oi.created_at DESC
";

$cart_stmt = $conn->prepare($cart_query);
if (!$cart_stmt) {
    die("Prepare failed: " . $conn->error);
}

$cart_stmt->bind_param("i", $user_id);
$cart_stmt->execute();
$cart_result = $cart_stmt->get_result();

$cart_items = [];
$cart_count = 0;
$subtotal = 0;

if ($cart_result->num_rows > 0) {
    while ($item = $cart_result->fetch_assoc()) {
        // Check if item is properly fetched
        if ($item === null || !is_array($item)) {
            continue;
        }
        
        // Check essential fields and set defaults
        $item = array_merge([
            'item_id' => 0,
            'name' => 'Unknown Product',
            'price' => 0.00,
            'quantity' => 0,
            'item_total' => 0.00,
            'products_id' => 0,
            'stock_quantity' => 0,
            'images_path' => null,
            'description' => ''
        ], $item);
        
        $cart_items[] = $item;
        $subtotal += floatval($item['item_total']);
        $cart_count++;
    }
}

$tax = $subtotal * 0.06;
$delivery_fee = ($subtotal > 0) ? 5.00 : 0;
$total = $subtotal + $tax + $delivery_fee;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cart - Daily Bread Bakery</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        :root {
            --primary-color: #8B4513;
            --secondary-color: #DEB887;
            --accent-color: #A0522D;
            --light-bg: #FFF8DC;
            --text-color: #444;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(rgba(0, 0, 0, 0.3), rgba(0, 0, 0, 0.3)),
                        url('./Background/background0.png') center/cover fixed;
            background-repeat: no-repeat;
            background-size: cover;
            color: var(--text-color);
            line-height: 1.6;
        }

        /* NAVBAR */
        .navbar {
            background-color: rgba(255, 255, 255, 0.98);
            padding: 1rem 5%;
            position: fixed;
            width: 100%;
            top: 0;
            z-index: 1000;
            box-shadow: 0 2px 15px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(10px);
        }

        .nav-content {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
        }

        .logo-container {
            display: flex;
            align-items: center;
            gap: 1rem;
            transition: transform 0.3s ease;
        }

        .logo-container:hover {
            transform: scale(1.02);
        }

        .logo {
            font-family: 'Playfair Display', serif;
            font-size: 2rem;
            color: var(--primary-color);
            letter-spacing: 1px;
        }

        .logo-img {
            height: 50px;
            width: auto;
            transition: transform 0.3s ease;
        }

        .nav-links {
            display: flex;
            gap: 2.5rem;
            margin: 0 20px;
        }

        .nav-links a {
            text-decoration: none;
            color: #555;
            font-weight: 450;
            position: relative;
            padding: 5px 0;
            transition: color 0.3s ease;
        }

        .nav-links a::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 0;
            height: 2px;
            background: var(--accent-color);
            transition: width 0.3s ease;
        }

        .nav-links a:hover {
            color: var(--accent-color);
        }

        .nav-links a:hover::after {
            width: 100%;
        }

        .search-container {
            position: relative;
            margin-right: 15px;
        }

        .search-input {
            width: 200px;
            padding: 10px 15px 10px 40px;
            border-radius: 30px;
            border: 1px solid #ddd;
            font-size: 14px;
            transition: all 0.3s ease;
            outline: none;
        }

        .search-input:focus {
            border-color: #4361ee;
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.2);
            width: 250px;
        }

        .search-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #888;
        }

        .action-buttons {
            display: flex;
            gap: 10px;
        }

        .action-btn,
        .cart-btn {
            background: none;
            border: none;
            color: #555;
            font-size: 1.2rem;
            position: relative;
            cursor: pointer;
            padding: 5px 10px;
        }

        .cart-btn span {
            background: none;
            color: darkgrey;
            border-radius: 50%;
            width: 15px;
            height: 15px;
            font-size: 9px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            position: absolute;
            right: -5px;
            top: -5px;
        }

        .badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background-color: var(--accent-color);
            color: white;
            border-radius: 50%;
            width: 18px;
            height: 18px;
            font-size: 0.7rem;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .message {
            max-width: 1200px;
            margin: 1rem auto;
            padding: 1rem 2rem;
            border-radius: 8px;
            font-weight: 500;
        }

        .message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .cart-section {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 2rem;
        }

        .cart-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 6rem;
            margin-bottom: 2rem;
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }

        .cart-header h2 {
            font-family: 'Playfair Display', serif;
            color: var(--primary-color);
            font-size: 2rem;
            margin: 0;
        }

        .continue-shopping {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: color 0.3s ease;
        }

        .continue-shopping:hover {
            color: var(--accent-color);
        }

        .cart-container {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 2rem;
        }

        .cart-items {
            background: white;
            border-radius: 12px;
            padding: 2rem;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }

        .cart-item {
            display: flex;
            gap: 1rem;
            padding: 1.5rem 0;
            border-bottom: 1px solid #eee;
            align-items: center;
        }

        .cart-item:last-child {
            border-bottom: none;
        }

        .cart-item-img {
            width: 100px;
            height: 100px;
            border-radius: 8px;
            object-fit: cover;
            background: #f0f0f0;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #999;
            flex-direction: column;
        }

        .cart-item-details {
            flex: 1;
        }

        .cart-item-title {
            font-size: 1.2rem;
            font-weight: 600;
            color: var(--primary-color);
            margin-bottom: 0.5rem;
        }

        .cart-item-price {
            color: var(--accent-color);
            font-weight: 500;
            margin-bottom: 1rem;
        }

        .quantity-control {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin: 1rem 0;
        }

        .quantity-btn {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            border: 2px solid var(--secondary-color);
            background: white;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
            font-weight: bold;
            color: var(--primary-color);
        }

        .quantity-btn:hover {
            background: var(--secondary-color);
            color: white;
        }

        .quantity-input {
            width: 60px;
            text-align: center;
            border: 2px solid #e0e0e0;
            border-radius: 5px;
            padding: 0.5rem;
            font-size: 1rem;
        }

        .quantity-input:focus {
            outline: none;
            border-color: var(--primary-color);
        }

        .remove-btn {
            background: none;
            border: none;
            color: #dc3545;
            cursor: pointer;
            font-size: 1rem;
            transition: color 0.3s;
            padding: 0.5rem 1rem;
            border-radius: 5px;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .remove-btn:hover {
            background: #ffe6e6;
            color: #c82333;
        }

        .action-form {
            display: flex;
            gap: 1rem;
            align-items: center;
            flex-wrap: wrap;
        }

        .cart-summary {
            background: white;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            height: fit-content;
            position: sticky;
            top: 120px;
        }

        .summary-title {
            font-family: 'Playfair Display', serif;
            font-size: 1.5rem;
            color: var(--primary-color);
            margin-bottom: 1.5rem;
            text-align: center;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 1rem;
            padding: 0.5rem 0;
        }

        .summary-row.total {
            border-top: 2px solid var(--primary-color);
            font-weight: bold;
            font-size: 1.2rem;
            color: var(--primary-color);
            margin-top: 1rem;
            padding-top: 1rem;
        }

        .checkout-btn {
            width: 100%;
            background: linear-gradient(135deg, var(--primary-color), var(--accent-color));
            color: white;
            border: none;
            padding: 1rem 2rem;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1.1rem;
            font-weight: 600;
            margin-top: 1.5rem;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .checkout-btn:hover {
            background: linear-gradient(135deg, var(--accent-color), var(--primary-color));
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(139, 69, 19, 0.3);
        }

        .empty-cart {
            text-align: center;
            padding: 4rem 2rem;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            grid-column: 1 / -1;
        }

        .empty-cart i {
            font-size: 4rem;
            color: var(--secondary-color);
            margin-bottom: 1rem;
        }

        .empty-cart h3 {
            font-family: 'Playfair Display', serif;
            font-size: 1.8rem;
            color: var(--primary-color);
            margin-bottom: 1rem;
        }

        .empty-cart p {
            color: #666;
            margin-bottom: 2rem;
        }

        .empty-cart .continue-shopping {
            background: linear-gradient(135deg, var(--primary-color), var(--accent-color));
            color: white;
            padding: 1rem 2rem;
            border-radius: 25px;
            text-decoration: none;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: transform 0.3s ease;
        }

        .empty-cart .continue-shopping:hover {
            transform: translateY(-2px);
        }

        footer {
            background: var(--primary-color);
            color: white;
            padding: 3rem 2rem 2rem;
            margin-top: 4rem;
            text-align: center;
        }

        .footer-content {
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
            text-align: left;
        }

        .footer-section h3 {
            font-family: 'Playfair Display', serif;
            font-size: 1.3rem;
            margin-bottom: 1rem;
            color: var(--secondary-color);
        }

        .footer-section p {
            margin-bottom: 0.5rem;
            line-height: 1.6;
        }

        .social-links {
            display: flex;
            gap: 1rem;
            margin-top: 1rem;
        }

        .social-links a {
            color: white;
            font-size: 1.5rem;
            transition: color 0.3s ease;
        }

        .social-links a:hover {
            color: var(--secondary-color);
        }

        .footer-section input[type="email"] {
            padding: 0.8rem 1rem;
            margin-top: 1rem;
            width: 100%;
            border-radius: 25px;
            border: none;
            font-size: 0.9rem;
        }

        @media (max-width: 768px) {
            .cart-container {
                grid-template-columns: 1fr;
            }
            
            .cart-summary {
                position: static;
            }
            
            .cart-header {
                flex-direction: column;
                gap: 1rem;
                text-align: center;
            }
            
            .cart-item {
                flex-direction: column;
                text-align: center;
            }
            
            .cart-item-img {
                align-self: center;
            }
            
            .nav-content {
                flex-wrap: wrap;
                gap: 1rem;
            }
            
            .search-input {
                width: 250px;
            }
        }
    </style>
</head>
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
            <button onclick="window.location.href='CART.php'" class="cart-btn">
                <i class="fas fa-shopping-cart"></i>
            </button>
            <button onclick="window.location.href='PROFILE.php'" class="action-btn">
                <i class="far fa-user"></i>
            </button>
        </div>
    </div>
</nav>

<?php if (isset($_SESSION['cart_success'])): ?>
    <div class="message success">
        <i class="fas fa-check-circle"></i>
        <?php echo $_SESSION['cart_success']; unset($_SESSION['cart_success']); ?>
    </div>
<?php endif; ?>

<?php if (isset($_SESSION['cart_error'])): ?>
    <div class="message error">
        <i class="fas fa-exclamation-circle"></i>
        <?php echo $_SESSION['cart_error']; unset($_SESSION['cart_error']); ?>
    </div>
<?php endif; ?>

<section class="cart-section">
    <div class="cart-header">
        <h2>Your Cart (<?php echo $cart_count; ?> items)</h2>
        <a href="MENU.php" class="continue-shopping">
            <i class="fas fa-arrow-left"></i> Continue Shopping
        </a>
    </div>

    <div class="cart-container">
        <?php if ($cart_count > 0): ?>
            <div class="cart-items">
                <?php foreach ($cart_items as $index => $item): ?>
                    <?php 
                    // Additional safety check for each item
                    if (!is_array($item) || empty($item)) {
                        continue;
                    }
                    ?>
                    
                    <div class="cart-item">
                        <?php
                        // Enhanced image handling with null checks
                        $image_src = '';
                        $show_image = false;
                        
                        if (!empty($item['images_path']) && trim($item['images_path']) !== '') {
                            $clean_path = trim($item['images_path']);
                            
                            if (strpos($clean_path, 'http') === 0) {
                                $image_src = $clean_path;
                                $show_image = true;
                            } else {
                                $possible_paths = [
                                    $clean_path,
                                    'assets/images/' . basename($clean_path),
                                    'images/' . basename($clean_path),
                                    'uploads/' . basename($clean_path),
                                    './images/' . basename($clean_path),
                                    '../images/' . basename($clean_path),
                                    'admin/assets/images/' . basename($clean_path),
                                    '../admin/assets/images/' . basename($clean_path),
                                ];
                                
                                foreach ($possible_paths as $path) {
                                    if (file_exists($path)) {
                                        $image_src = $path;
                                        $show_image = true;
                                        break;
                                    }
                                }
                            }
                        }
                        ?>
                        
                        <?php if ($show_image): ?>
                            <img src="<?php echo htmlspecialchars($image_src); ?>" 
                                 class="cart-item-img" 
                                 alt="<?php echo htmlspecialchars($item['name']); ?>"
                                 onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                            <div class="cart-item-img" style="display: none;">
                                <i class="fas fa-image" style="font-size: 2rem; color: #999;"></i>
                                <div style="font-size: 0.8rem; color: #999; margin-top: 0.5rem;">No Image</div>
                            </div>
                        <?php else: ?>
                            <div class="cart-item-img">
                                <i class="fas fa-image" style="font-size: 2rem; color: #999;"></i>
                                <div style="font-size: 0.8rem; color: #999; margin-top: 0.5rem;">No Image</div>
                            </div>
                        <?php endif; ?>
                        
                        <div class="cart-item-details">
                            <h3 class="cart-item-title"><?php echo htmlspecialchars($item['name']); ?></h3>
                            <div class="cart-item-price">RM <?php echo number_format(floatval($item['price']), 2); ?> each</div>
                            <div style="color: #666; margin: 0.5rem 0;">Total: RM <?php echo number_format(floatval($item['item_total']), 2); ?></div>
                            
                            <div class="action-form">
                                <form method="POST" class="quantity-control">
                                    <input type="hidden" name="item_id" value="<?php echo intval($item['item_id']); ?>">
                                    <input type="hidden" name="product_id" value="<?php echo intval($item['products_id']); ?>">
                                    <button type="button" class="quantity-btn minus" onclick="updateQuantity(this, -1)">-</button>
                                    <input type="number" name="quantity" class="quantity-input" 
                                           value="<?php echo intval($item['quantity']); ?>" 
                                           min="1" 
                                           max="<?php echo intval($item['stock_quantity']); ?>">
                                    <button type="button" class="quantity-btn plus" onclick="updateQuantity(this, 1)">+</button>
                                    <button type="submit" name="update_quantity" style="display:none;"></button>
                                </form>
                                
                                <form method="POST">
                                    <input type="hidden" name="item_id" value="<?php echo intval($item['item_id']); ?>">
                                    <button type="submit" name="remove_item" class="remove-btn" 
                                            onclick="return confirm('Remove this item from cart?')">
                                        <i class="fas fa-trash"></i> Remove
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="cart-summary">
                <h3 class="summary-title">Order Summary</h3>
                
                <div class="summary-row">
                    <span>Subtotal:</span>
                    <span>RM <?php echo number_format($subtotal, 2); ?></span>
                </div>
                <div class="summary-row">
                    <span>Tax (6%):</span>
                    <span>RM <?php echo number_format($tax, 2); ?></span>
                </div>
                <div class="summary-row">
                    <span>Delivery Fee:</span>
                    <span>RM <?php echo number_format($delivery_fee, 2); ?></span>
                </div>
                <div class="summary-row total">
                    <span>Total:</span>
                    <span>RM <?php echo number_format($total, 2); ?></span>
                </div>
                
                <button class="checkout-btn" onclick="window.location.href='checkout.php'">
                    <i class="fas fa-lock"></i> Proceed to Checkout - RM <?php echo number_format($total, 2); ?>
                </button>
            </div>
        <?php else: ?>
            <div class="empty-cart">
                <i class="fas fa-shopping-cart"></i>
                <h3>Your cart is empty!</h3>
                <p>Looks like you haven't added anything to your cart yet.</p>
                <a href="MENU.php" class="continue-shopping">
                    <i class="fas fa-utensils"></i> Browse Menu
                </a>
            </div>
        <?php endif; ?>
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
            <div class="social-links">
                <a href="#"><i class="fab fa-instagram"></i></a>
                <a href="#"><i class="fab fa-facebook"></i></a>
                <a href="#"><i class="fab fa-whatsapp"></i></a>
            </div>
        </div>
        <div class="footer-section">
            <h3>Fresh Updates</h3>
            <p>Subscribe to our newsletter for seasonal specials and baking tips!</p>
            <input type="email" placeholder="Enter your email">
        </div>
    </div>
    <p style="margin-top: 3rem; color: #DEB887;">© <?php echo date('Y'); ?> Daily Bread Bakery - Crafted with ❤️</p>
</footer>

<script>
function updateQuantity(button, change) {
    const form = button.closest('form');
    const input = form.querySelector('.quantity-input');
    const currentValue = parseInt(input.value);
    const max = parseInt(input.getAttribute('max'));
    let newValue = currentValue + change;
    
    // Ensure quantity doesn't go below 1 or above max
    if (newValue < 1) newValue = 1;
    if (newValue > max) {
        alert(`Maximum available quantity is ${max}`);
        newValue = max;
    }
    
    // Update the input value
    input.value = newValue;
    
    // Submit the form automatically
    form.querySelector('button[type="submit"]').click();
}

// Auto-submit quantity changes after user stops typing
document.querySelectorAll('.quantity-input').forEach(input => {
    let timeout;
    input.addEventListener('input', function() {
        clearTimeout(timeout);
        const form = this.closest('form');
        timeout = setTimeout(() => {
            form.querySelector('button[type="submit"]').click();
        }, 1000); // Wait 1 second after user stops typing
    });
    
    // Validate quantity on change
    input.addEventListener('change', function() {
        const max = parseInt(this.getAttribute('max'));
        const value = parseInt(this.value);
        
        if (value > max) {
            this.value = max;
            alert(`Maximum available quantity is ${max}`);
        }
        if (value < 1) {
            this.value = 1;
        }
    });
});

// Auto-hide messages after 5 seconds
document.addEventListener('DOMContentLoaded', function() {
    const messages = document.querySelectorAll('.message');
    messages.forEach(message => {
        setTimeout(() => {
            message.style.opacity = '0';
            message.style.transform = 'translateY(-20px)';
            setTimeout(() => {
                message.remove();
            }, 300);
        }, 5000);
    });
});

// Confirm before removing items
document.querySelectorAll('.remove-btn').forEach(button => {
    button.addEventListener('click', function(e) {
        if (!confirm('Are you sure you want to remove this item from your cart?')) {
            e.preventDefault();
        }
    });
});
</script>

</body>
</html>