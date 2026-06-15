<?php
session_start();
require_once 'config.php';
require_once 'includes/flash.php';

// Stats
$stat_products  = (int)$conn->query("SELECT COUNT(*) c FROM products")->fetch_assoc()['c'];
$stat_customers = (int)$conn->query("SELECT COUNT(*) c FROM customers WHERE Type='Registered'")->fetch_assoc()['c'];
$stat_branches  = (int)$conn->query("SELECT COUNT(*) c FROM branches")->fetch_assoc()['c'];
$stat_workshops = (int)$conn->query("SELECT COUNT(*) c FROM workshops")->fetch_assoc()['c'];

// Featured products (newest 8)
$featured = $conn->query(
    "SELECT p.ID, p.Name, p.Category, p.Price, p.Stock, pp.Photo
     FROM products p LEFT JOIN product_photos pp ON p.ID = pp.ProductID
     ORDER BY p.ID DESC LIMIT 8"
)->fetch_all(MYSQLI_ASSOC);

// Upcoming workshops (next 3)
$upcoming_ws = $conn->query(
    "SELECT w.WID, w.Topic, w.Subject, w.Date, w.Type, w.Price
     FROM workshops w
     WHERE w.Date >= CURDATE()
     ORDER BY w.Date ASC LIMIT 3"
)->fetch_all(MYSQLI_ASSOC);

// Branches
$branches = $conn->query("SELECT * FROM branches ORDER BY Ratings DESC")->fetch_all(MYSQLI_ASSOC);

// Reviews — only approved ones shown publicly
$reviews = $conn->query(
    "SELECT cr.ID, cr.Comments, c.Name FROM customers_reviews cr
     JOIN customers c ON cr.CustomerID = c.ID
     WHERE cr.approved = 1
     ORDER BY cr.ID DESC LIMIT 6"
)->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EcoGrow Nursery — Bringing Nature to Your Doorstep</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-white min-h-screen flex flex-col font-sans">
<?php require_once 'includes/navbar.php'; ?>

<!-- ═══ HERO ═══ -->
<section class="relative bg-green-900 text-white overflow-hidden">
    <div class="absolute inset-0 bg-cover bg-center opacity-25"
         style="background-image:url('https://images.unsplash.com/photo-1416879595882-3373a0480b5b?w=1400&auto=format&fit=crop')"></div>
    <div class="relative max-w-6xl mx-auto px-4 py-24 text-center">
        <span class="inline-block bg-green-700 text-green-200 text-xs font-semibold px-3 py-1 rounded-full mb-4 tracking-wide uppercase">🌿 Trusted Nursery Since 2020</span>
        <h1 class="text-5xl sm:text-6xl font-bold mb-5 leading-tight">Bringing Nature<br class="hidden sm:block"> to Your Doorstep</h1>
        <p class="text-xl text-green-200 mb-8 max-w-2xl mx-auto">Premium plants, expert accessories, and hands-on workshops — everything you need to grow a greener life.</p>
        <div class="flex flex-wrap gap-4 justify-center">
            <a href="product.php" class="bg-white text-green-800 font-bold px-8 py-3 rounded-xl hover:bg-green-50 transition shadow-lg">Shop Now</a>
            <a href="events.php" class="border-2 border-white text-white font-semibold px-8 py-3 rounded-xl hover:bg-green-800 transition">Join a Workshop</a>
        </div>
    </div>
    <!-- Stats bar -->
    <div class="relative bg-green-800/80 backdrop-blur border-t border-green-700">
        <div class="max-w-4xl mx-auto px-4 py-4 grid grid-cols-2 sm:grid-cols-4 gap-2 text-center">
            <?php foreach([
                [$stat_products,'Products'],
                [$stat_customers,'Happy Customers'],
                [$stat_branches,'Branches'],
                [$stat_workshops,'Workshops'],
            ] as [$n,$l]):?>
            <div>
                <p class="text-2xl font-bold text-white"><?=$n?>+</p>
                <p class="text-green-300 text-xs"><?=$l?></p>
            </div>
            <?php endforeach;?>
        </div>
    </div>
</section>

<?php render_flash(); ?>

<!-- ═══ CATEGORY BROWSE ═══ -->
<section class="max-w-6xl mx-auto px-4 py-14">
    <h2 class="text-3xl font-bold text-green-900 text-center mb-2">Browse by Category</h2>
    <p class="text-gray-400 text-center mb-8 text-sm">Find exactly what your garden needs</p>
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
        <?php foreach([
            ['product.php?category=Plants&amp;search=indoor&amp;sort=','🪴','Indoor Plants','Purify your air'],
            ['product.php?category=Plants&amp;sort=','🌳','Outdoor Plants','Transform your garden'],
            ['product.php?category=Accessories&amp;search=soil&amp;sort=','🪣','Soil & Pots','Quality growing media'],
            ['product.php?category=Accessories&amp;sort=','🌿','Accessories','Tools & decor'],
        ] as [$url,$icon,$title,$sub]):?>
        <a href="<?=$url?>" class="group bg-green-50 hover:bg-green-600 rounded-2xl p-6 text-center transition-all duration-200 hover:-translate-y-1 hover:shadow-xl">
            <div class="text-4xl mb-3"><?=$icon?></div>
            <h3 class="font-bold text-green-800 group-hover:text-white text-sm"><?=$title?></h3>
            <p class="text-gray-400 group-hover:text-green-200 text-xs mt-1"><?=$sub?></p>
        </a>
        <?php endforeach;?>
    </div>
</section>

<!-- ═══ FEATURED PRODUCTS ═══ -->
<?php if($featured):?>
<section class="bg-gray-50 py-14">
    <div class="max-w-6xl mx-auto px-4">
        <div class="flex items-center justify-between mb-8">
            <div>
                <h2 class="text-3xl font-bold text-green-900">New Arrivals</h2>
                <p class="text-gray-400 text-sm mt-1">Fresh additions to our collection</p>
            </div>
            <a href="product.php" class="text-green-600 text-sm font-semibold hover:underline">View all →</a>
        </div>
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-5">
            <?php foreach($featured as $p):?>
            <a href="product_detail.php?id=<?=(int)$p['ID']?>"
               class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden hover:shadow-lg hover:-translate-y-1 transition-all duration-200 flex flex-col">
                <?php if(!empty($p['Photo'])):?>
                    <img src="<?=htmlspecialchars($p['Photo'])?>" alt="<?=htmlspecialchars($p['Name'])?>" class="w-full h-44 object-cover">
                <?php else:?>
                    <div class="w-full h-44 bg-green-50 flex items-center justify-center text-5xl">🌿</div>
                <?php endif;?>
                <div class="p-4 flex flex-col flex-1">
                    <span class="text-xs text-green-600 font-semibold uppercase tracking-wide"><?=htmlspecialchars($p['Category'])?></span>
                    <h3 class="font-semibold text-gray-800 text-sm mt-1 leading-snug flex-1"><?=htmlspecialchars($p['Name'])?></h3>
                    <div class="flex items-center justify-between mt-3">
                        <span class="text-green-600 font-bold text-base">$<?=number_format($p['Price'],2)?></span>
                        <?php if($p['Stock']<=0):?>
                            <span class="text-xs text-red-400 font-medium">Out of stock</span>
                        <?php elseif($p['Stock']<=5):?>
                            <span class="text-xs text-amber-500 font-medium">Only <?=(int)$p['Stock']?> left</span>
                        <?php else:?>
                            <span class="text-xs text-green-500 font-medium">In stock</span>
                        <?php endif;?>
                    </div>
                </div>
            </a>
            <?php endforeach;?>
        </div>
    </div>
</section>
<?php endif;?>

<!-- ═══ WHY CHOOSE US ═══ -->
<section class="max-w-6xl mx-auto px-4 py-14">
    <h2 class="text-3xl font-bold text-green-900 text-center mb-2">Why EcoGrow?</h2>
    <p class="text-gray-400 text-center mb-10 text-sm">More than a nursery — a complete plant care partner</p>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
        <?php foreach([
            ['🌱','Expert Curation','Every plant is hand-selected and tested by our horticulture team before listing.'],
            ['🚚','Careful Delivery','Plants are packed with care so they arrive healthy, happy, and ready to thrive.'],
            ['🎓','Free Workshops','Regular events for beginners and experts — learn from certified plant professionals.'],
            ['♻️','Eco-Friendly','Sustainable packaging, responsible sourcing, and a love for the environment.'],
        ] as [$icon,$title,$desc]):?>
        <div class="text-center p-6 rounded-2xl bg-green-50 hover:bg-green-100 transition">
            <div class="text-4xl mb-4"><?=$icon?></div>
            <h3 class="font-bold text-green-900 text-base mb-2"><?=$title?></h3>
            <p class="text-gray-500 text-sm leading-relaxed"><?=$desc?></p>
        </div>
        <?php endforeach;?>
    </div>
</section>

<!-- ═══ UPCOMING WORKSHOPS ═══ -->
<?php if($upcoming_ws):?>
<section class="bg-green-700 py-14">
    <div class="max-w-6xl mx-auto px-4">
        <div class="flex items-center justify-between mb-8">
            <div>
                <h2 class="text-3xl font-bold text-white">Upcoming Workshops</h2>
                <p class="text-green-300 text-sm mt-1">Learn, grow, and connect with fellow plant lovers</p>
            </div>
            <a href="events.php" class="text-green-300 text-sm font-semibold hover:text-white transition">See all →</a>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
            <?php foreach($upcoming_ws as $w):?>
            <div class="bg-white/10 backdrop-blur rounded-2xl p-6 border border-white/20 hover:bg-white/20 transition">
                <div class="flex items-start justify-between gap-2 mb-3">
                    <span class="text-xs px-2 py-1 rounded-full font-semibold <?=$w['Type']==='Paid'?'bg-amber-400 text-amber-900':'bg-green-300 text-green-900'?>">
                        <?=$w['Type']==='Paid'?'💰 Paid':'🆓 Free'?>
                    </span>
                </div>
                <h3 class="font-bold text-white text-base mb-2"><?=htmlspecialchars($w['Topic'])?></h3>
                <?php if($w['Subject']):?><p class="text-green-200 text-xs mb-3 leading-relaxed"><?=htmlspecialchars(mb_substr($w['Subject'],0,80))?></p><?php endif;?>
                <p class="text-green-300 text-xs mb-4">📅 <?=htmlspecialchars($w['Date'])?></p>
                <?php if(isset($_SESSION['customer_id'])):?>
                    <a href="registerevents.php?event_id=<?=(int)$w['WID']?>" class="block text-center bg-white text-green-700 font-semibold text-sm py-2 rounded-xl hover:bg-green-50 transition">Register Free</a>
                <?php else:?>
                    <a href="login.php" class="block text-center bg-white/20 text-white text-sm py-2 rounded-xl hover:bg-white/30 transition">Login to Register</a>
                <?php endif;?>
            </div>
            <?php endforeach;?>
        </div>
    </div>
</section>
<?php endif;?>

<!-- ═══ BRANCHES ═══ -->
<?php if($branches):?>
<section class="max-w-6xl mx-auto px-4 py-14">
    <h2 class="text-3xl font-bold text-green-900 text-center mb-2">Our Locations</h2>
    <p class="text-gray-400 text-center mb-8 text-sm">Visit us at a branch near you</p>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php foreach($branches as $b):?>
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm hover:shadow-md p-6 transition">
            <div class="flex items-start justify-between gap-2 mb-3">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-green-100 rounded-xl flex items-center justify-center text-xl shrink-0">🏬</div>
                    <h3 class="font-bold text-green-900 text-base"><?=htmlspecialchars($b['Name'])?></h3>
                </div>
                <?php if($b['Ratings']):?>
                <span class="text-xs bg-yellow-50 text-yellow-700 border border-yellow-200 px-2 py-0.5 rounded-full font-semibold shrink-0">⭐ <?=number_format($b['Ratings'],1)?></span>
                <?php endif;?>
            </div>
            <p class="text-gray-500 text-sm mb-1">📍 <?=htmlspecialchars($b['Location'])?></p>
            <?php if($b['Manager']):?><p class="text-gray-400 text-xs mb-2">👤 <?=htmlspecialchars($b['Manager'])?></p><?php endif;?>
            <?php if($b['Details']):?><p class="text-gray-400 text-xs mt-3 leading-relaxed"><?=htmlspecialchars(mb_substr($b['Details'],0,100))?></p><?php endif;?>
        </div>
        <?php endforeach;?>
    </div>
</section>
<?php endif;?>

<!-- ═══ REVIEWS ═══ -->
<section class="bg-green-50 py-14">
    <div class="max-w-6xl mx-auto px-4">
        <h2 class="text-3xl font-bold text-green-900 text-center mb-2">What Our Customers Say</h2>
        <p class="text-gray-400 text-center mb-8 text-sm">Real reviews from our plant-loving community</p>

        <?php if($reviews):?>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5 mb-10">
            <?php foreach($reviews as $r):?>
            <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100">
                <div class="flex text-yellow-400 text-sm mb-3">★★★★★</div>
                <p class="text-gray-600 text-sm leading-relaxed mb-4">"<?=htmlspecialchars(mb_substr($r['Comments'],0,150))?><?=mb_strlen($r['Comments'])>150?'…':''?>"</p>
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 bg-green-200 rounded-full flex items-center justify-center text-green-800 font-bold text-xs shrink-0">
                        <?=strtoupper(substr($r['Name'],0,1))?>
                    </div>
                    <span class="text-sm font-semibold text-gray-700"><?=htmlspecialchars($r['Name'])?></span>
                    <span class="text-xs text-green-600 ml-auto">Verified Customer</span>
                </div>
            </div>
            <?php endforeach;?>
        </div>
        <?php else:?>
        <p class="text-center text-gray-400 text-sm mb-8">No reviews yet — be the first!</p>
        <?php endif;?>

        <!-- Leave a review -->
        <?php if(isset($_SESSION['customer_id'])):?>
        <div class="max-w-xl mx-auto bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
            <h3 class="font-bold text-green-900 mb-3 text-base">Share Your Experience</h3>
            <form method="POST" action="add_review.php">
                <textarea name="review" rows="3" required placeholder="Tell us what you think..."
                    class="w-full border border-gray-200 rounded-xl p-4 text-sm focus:outline-none focus:ring-2 focus:ring-green-400 resize-none mb-3"></textarea>
                <button type="submit" class="bg-green-600 text-white px-6 py-2.5 rounded-xl hover:bg-green-700 transition font-semibold text-sm">Submit Review</button>
            </form>
        </div>
        <?php else:?>
        <p class="text-center text-gray-500 text-sm">
            <a href="login.php" class="text-green-600 font-semibold hover:underline">Sign in</a> to leave a review.
        </p>
        <?php endif;?>
    </div>
</section>

<!-- ═══ CTA BANNER ═══ -->
<section class="bg-green-800 py-16 px-4 text-center text-white">
    <h2 class="text-3xl font-bold mb-3">Start Your Green Journey Today</h2>
    <p class="text-green-200 mb-7 text-base max-w-xl mx-auto">Join thousands of plant lovers who trust EcoGrow for premium plants, expert advice, and a greener lifestyle.</p>
    <div class="flex justify-center gap-4 flex-wrap">
        <a href="product.php" class="bg-white text-green-800 font-bold px-8 py-3 rounded-xl hover:bg-green-50 transition shadow">Browse Products</a>
        <?php if(!isset($_SESSION['customer_id'])):?>
        <a href="register.php" class="border-2 border-white text-white font-semibold px-8 py-3 rounded-xl hover:bg-green-700 transition">Create Account</a>
        <?php endif;?>
    </div>
</section>

<!-- ═══ FOOTER ═══ -->
<footer class="bg-green-900 text-green-300 py-10">
    <div class="max-w-6xl mx-auto px-4">
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-8 mb-8">
            <div class="col-span-2 sm:col-span-1">
                <p class="text-white font-bold text-lg mb-2">🌿 EcoGrow</p>
                <p class="text-xs leading-relaxed">Your one-stop destination for plants, accessories, and gardening expertise.</p>
            </div>
            <div>
                <p class="text-white font-semibold text-sm mb-3">Shop</p>
                <ul class="space-y-1.5 text-xs">
                    <li><a href="product.php?category=Plants" class="hover:text-white transition">Indoor Plants</a></li>
                    <li><a href="product.php?category=Plants" class="hover:text-white transition">Outdoor Plants</a></li>
                    <li><a href="product.php?category=Accessories" class="hover:text-white transition">Accessories</a></li>
                </ul>
            </div>
            <div>
                <p class="text-white font-semibold text-sm mb-3">Company</p>
                <ul class="space-y-1.5 text-xs">
                    <li><a href="about.php" class="hover:text-white transition">About Us</a></li>
                    <li><a href="events.php" class="hover:text-white transition">Workshops</a></li>
                    <li><a href="about.php#locations" class="hover:text-white transition">Locations</a></li>
                </ul>
            </div>
            <div>
                <p class="text-white font-semibold text-sm mb-3">Account</p>
                <ul class="space-y-1.5 text-xs">
                    <?php if(isset($_SESSION['customer_id'])):?>
                    <li><a href="user.php" class="hover:text-white transition">My Profile</a></li>
                    <li><a href="user.php" class="hover:text-white transition">My Orders</a></li>
                    <li><a href="logout.php" class="hover:text-white transition">Logout</a></li>
                    <?php else:?>
                    <li><a href="login.php" class="hover:text-white transition">Login</a></li>
                    <li><a href="register.php" class="hover:text-white transition">Register</a></li>
                    <?php endif;?>
                </ul>
            </div>
        </div>
        <div class="border-t border-green-800 pt-6 flex flex-col sm:flex-row items-center justify-between gap-2 text-xs">
            <p>&copy; <?=date('Y')?> EcoGrow Nursery. All rights reserved.</p>
            <p>Made with 🌿 for plant lovers everywhere</p>
        </div>
    </div>
</footer>

<?php $conn->close(); ?>
</body>
</html>
