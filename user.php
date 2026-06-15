<?php
session_start();
if (!isset($_SESSION['customer_id'])) { header("Location: login.php"); exit; }

require_once 'config.php';
require_once 'includes/flash.php';

$user_id  = $_SESSION['customer_id'];
$msg      = '';
$msg_type = '';

// Handle password change
if (isset($_POST['change_password'])) {
    $current = trim($_POST['current_password'] ?? '');
    $new     = trim($_POST['new_password'] ?? '');
    $confirm = trim($_POST['confirm_new_password'] ?? '');

    if (empty($current) || empty($new) || empty($confirm)) {
        $msg = 'Please fill all password fields.';
        $msg_type = 'error';
    } elseif ($new !== $confirm) {
        $msg = 'New password and confirmation do not match.';
        $msg_type = 'error';
    } elseif (strlen($new) < 6) {
        $msg = 'New password must be at least 6 characters.';
        $msg_type = 'error';
    } else {
        $stmt = $conn->prepare("SELECT Password, Type FROM customers WHERE ID = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($row['Type'] !== 'Registered') {
            $msg = 'Guest accounts cannot change their password.';
            $msg_type = 'error';
        } elseif (password_verify($current, $row['Password']) || $current === $row['Password']) {
            $hashed = password_hash($new, PASSWORD_DEFAULT);
            $upd = $conn->prepare("UPDATE customers SET Password = ? WHERE ID = ?");
            $upd->bind_param("si", $hashed, $user_id);
            $upd->execute();
            $upd->close();
            $msg = 'Password updated successfully.';
            $msg_type = 'success';
        } else {
            $msg = 'Current password is incorrect.';
            $msg_type = 'error';
        }
    }
}

// Fetch user
$stmt = $conn->prepare("SELECT ID, Name, Email, Type, Points FROM customers WHERE ID = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user) { session_destroy(); header("Location: login.php"); exit; }

// Cart
$stmt = $conn->prepare(
    "SELECT c.ProductID, c.AddedOn, p.Name, p.Price, p.Stock
     FROM cart c JOIN products p ON c.ProductID = p.ID
     WHERE c.CustomerID = ?"
);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$cart_items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Orders (with status timeline)
$stmt = $conn->prepare(
    "SELECT o.ID AS order_id, o.Date, o.Bill, o.Count, o.Address,
            COALESCE(a.IsPending, 1) AS IsPending,
            COALESCE(os.Status, 'Placed') AS OStatus,
            GROUP_CONCAT(p.Name SEPARATOR ', ') AS ProductNames
     FROM orders o
     LEFT JOIN admin_confirms_orders a ON o.ID = a.OrderID
     LEFT JOIN order_status os ON o.ID = os.OrderID
     JOIN products p ON o.Product_Id = p.ID
     WHERE o.CustomerID = ?
     GROUP BY o.ID ORDER BY o.Date DESC"
);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$orders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$status_steps = ['Placed', 'Processing', 'Shipped', 'Delivered'];
$status_icons = ['Placed'=>'📋','Processing'=>'⚙️','Shipped'=>'🚚','Delivered'=>'✅'];

// Events
$stmt = $conn->prepare(
    "SELECT w.WID, w.Topic, w.Subject, w.Date, w.Type, w.Price, cw.Date AS RegistrationDate
     FROM workshops w JOIN customers_workshops cw ON w.WID = cw.WorkshopID
     WHERE cw.CustomerID = ? ORDER BY w.Date ASC"
);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$events = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$default_tab = $msg ? 'security' : (count($cart_items) > 0 ? 'cart' : 'orders');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile — EcoGrow</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-green-50 min-h-screen flex flex-col">
<?php require_once 'includes/navbar.php'; ?>

<div class="max-w-5xl mx-auto px-4 py-8 flex-1 w-full">
    <!-- Profile header -->
    <div class="bg-white rounded-xl shadow-sm border border-green-100 p-6 mb-6 flex items-center gap-5">
        <div class="w-14 h-14 bg-green-200 rounded-full flex items-center justify-center text-xl font-bold text-green-800 shrink-0">
            <?= strtoupper(substr($user['Name'], 0, 1)) ?>
        </div>
        <div class="flex-1 min-w-0">
            <h1 class="text-xl font-bold text-green-800 truncate"><?= htmlspecialchars($user['Name']) ?></h1>
            <p class="text-gray-400 text-sm truncate"><?= htmlspecialchars($user['Email']) ?></p>
            <span class="inline-block mt-1 text-xs bg-green-100 text-green-700 px-2 py-0.5 rounded-full font-medium">
                <?= $user['Type'] === 'Registered' ? '✅ Registered' : '👤 Guest' ?>
            </span>
        </div>
        <div class="text-right shrink-0">
            <div class="text-3xl font-bold text-green-600"><?= (int)($user['Points'] ?? 0) ?></div>
            <div class="text-xs text-gray-400">Reward Points</div>
        </div>
    </div>

    <!-- Tabs -->
    <div class="bg-white rounded-xl shadow-sm border border-green-100 overflow-hidden">
        <div class="flex border-b border-green-100 overflow-x-auto" role="tablist">
            <?php
            $tabs = [
                'cart'     => '🛒 Cart (' . count($cart_items) . ')',
                'orders'   => '📦 Orders (' . count($orders) . ')',
                'events'   => '🎓 Events (' . count($events) . ')',
                'security' => '🔒 Security',
            ];
            foreach ($tabs as $id => $label): ?>
                <button onclick="showTab('<?= $id ?>')" id="tab-<?= $id ?>" role="tab"
                    class="tab-btn px-5 py-4 text-sm font-medium whitespace-nowrap border-b-2 transition
                           <?= $id === $default_tab
                               ? 'border-green-600 text-green-700'
                               : 'border-transparent text-gray-500 hover:text-green-700' ?>">
                    <?= $label ?>
                </button>
            <?php endforeach; ?>
        </div>

        <!-- Cart -->
        <div id="panel-cart" class="tab-panel p-6 <?= $default_tab !== 'cart' ? 'hidden' : '' ?>">
            <h2 class="text-base font-semibold text-green-800 mb-4">Cart</h2>
            <?php if ($cart_items): ?>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-xs text-gray-400 uppercase tracking-wide border-b border-gray-100 text-left">
                                <th class="pb-3 pr-4 font-medium">Product</th>
                                <th class="pb-3 pr-4 font-medium">Price</th>
                                <th class="pb-3 pr-4 font-medium">Stock</th>
                                <th class="pb-3 pr-4 font-medium">Added</th>
                                <th class="pb-3 font-medium">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <?php foreach ($cart_items as $item): ?>
                            <tr class="hover:bg-gray-50 transition">
                                <td class="py-3 pr-4 font-medium text-gray-800"><?= htmlspecialchars($item['Name']) ?></td>
                                <td class="py-3 pr-4 text-green-600 font-semibold">$<?= number_format($item['Price'], 2) ?></td>
                                <td class="py-3 pr-4 text-gray-500"><?= (int)$item['Stock'] ?></td>
                                <td class="py-3 pr-4 text-gray-400 text-xs"><?= htmlspecialchars($item['AddedOn']) ?></td>
                                <td class="py-3">
                                    <div class="flex items-center gap-1.5">
                                        <a href="order.php?productid=<?= urlencode($item['ProductID']) ?>"
                                            class="bg-green-600 text-white text-xs px-3 py-1.5 rounded-lg hover:bg-green-700 transition font-medium">
                                            Order
                                        </a>
                                        <form method="POST" action="remove_from_cart.php" onsubmit="return confirm('Remove this item from cart?')">
                                            <input type="hidden" name="product_id" value="<?= (int)$item['ProductID'] ?>">
                                            <button type="submit"
                                                class="bg-red-100 text-red-600 text-xs px-3 py-1.5 rounded-lg hover:bg-red-200 transition font-medium">
                                                Remove
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="text-center py-14 text-gray-400">
                    <div class="text-4xl mb-3">🛒</div>
                    <p>Your cart is empty.</p>
                    <a href="product.php" class="text-green-600 text-sm mt-2 inline-block hover:underline">Browse Products</a>
                </div>
            <?php endif; ?>
        </div>

        <!-- Orders -->
        <div id="panel-orders" class="tab-panel p-6 <?= $default_tab !== 'orders' ? 'hidden' : '' ?>">
            <h2 class="text-base font-semibold text-green-800 mb-4">Your Orders</h2>
            <?php if ($orders): ?>
                <div class="space-y-4">
                    <?php foreach ($orders as $o):
                        $cur_step = array_search($o['OStatus'], $status_steps);
                        if ($cur_step === false) $cur_step = 0;
                    ?>
                    <div class="border border-gray-100 rounded-xl p-5 hover:bg-gray-50 transition">
                        <!-- Order header -->
                        <div class="flex flex-wrap items-start justify-between gap-3 mb-4">
                            <div>
                                <p class="font-semibold text-gray-800 text-sm"><?= htmlspecialchars($o['ProductNames']) ?></p>
                                <p class="text-xs text-gray-400 mt-0.5">
                                    Order #<?= $o['order_id'] ?> &nbsp;·&nbsp; <?= htmlspecialchars($o['Date']) ?>
                                    &nbsp;·&nbsp; Qty: <?= (int)$o['Count'] ?>
                                    &nbsp;·&nbsp; <?= htmlspecialchars(mb_substr($o['Address'], 0, 30)) ?>…
                                </p>
                            </div>
                            <div class="text-right">
                                <p class="text-lg font-bold text-green-600">$<?= number_format($o['Bill'], 2) ?></p>
                                <?php if ($o['IsPending'] && $o['OStatus'] === 'Placed'): ?>
                                    <form method="POST" action="cancel_order.php" class="inline"
                                          onsubmit="return confirm('Cancel order #<?= $o['order_id'] ?>?')">
                                        <input type="hidden" name="order_id" value="<?= (int)$o['order_id'] ?>">
                                        <button type="submit" class="text-xs text-red-500 hover:text-red-700 font-medium mt-1">Cancel order</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Status timeline -->
                        <div class="flex items-center gap-0">
                            <?php foreach ($status_steps as $i => $step):
                                $done    = $i <= $cur_step;
                                $current = $i === $cur_step;
                                $last    = $i === count($status_steps) - 1;
                            ?>
                            <div class="flex items-center flex-1 <?= $last ? '' : '' ?>">
                                <div class="flex flex-col items-center shrink-0">
                                    <div class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-bold border-2 transition
                                        <?= $done
                                            ? ($current ? 'bg-green-600 border-green-600 text-white' : 'bg-green-500 border-green-500 text-white')
                                            : 'bg-white border-gray-200 text-gray-300' ?>">
                                        <?= $done ? ($current ? $status_icons[$step] : '✓') : ($i + 1) ?>
                                    </div>
                                    <p class="text-xs mt-1 font-medium whitespace-nowrap
                                        <?= $current ? 'text-green-700' : ($done ? 'text-gray-500' : 'text-gray-300') ?>">
                                        <?= $step ?>
                                    </p>
                                </div>
                                <?php if (!$last): ?>
                                <div class="flex-1 h-0.5 mx-1 mb-5 <?= $i < $cur_step ? 'bg-green-500' : 'bg-gray-200' ?>"></div>
                                <?php endif; ?>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="text-center py-14 text-gray-400">
                    <div class="text-4xl mb-3">📦</div>
                    <p>No orders yet.</p>
                    <a href="product.php" class="text-green-600 text-sm mt-2 inline-block hover:underline">Browse Products</a>
                </div>
            <?php endif; ?>
        </div>

        <!-- Events -->
        <div id="panel-events" class="tab-panel p-6 <?= $default_tab !== 'events' ? 'hidden' : '' ?>">
            <h2 class="text-base font-semibold text-green-800 mb-4">Registered Events</h2>
            <?php if ($events): ?>
                <div class="space-y-3">
                    <?php foreach ($events as $ev): ?>
                    <div class="border border-green-100 rounded-lg p-4 flex flex-col sm:flex-row justify-between gap-3">
                        <div>
                            <h3 class="font-semibold text-green-800 text-sm"><?= htmlspecialchars($ev['Topic']) ?></h3>
                            <p class="text-xs text-gray-500 mt-0.5"><?= htmlspecialchars($ev['Subject']) ?></p>
                            <p class="text-xs text-gray-400 mt-1">📅 <?= htmlspecialchars($ev['Date']) ?></p>
                        </div>
                        <div class="text-right shrink-0">
                            <span class="text-xs px-2 py-1 rounded-full <?= $ev['Type'] === 'Paid' ? 'bg-amber-100 text-amber-700' : 'bg-green-100 text-green-700' ?>">
                                <?= $ev['Type'] === 'Paid' ? '$' . number_format($ev['Price'], 2) : 'Free' ?>
                            </span>
                            <p class="text-xs text-gray-400 mt-1">Registered: <?= htmlspecialchars($ev['RegistrationDate']) ?></p>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="text-center py-14 text-gray-400">
                    <div class="text-4xl mb-3">🎓</div>
                    <p>No events registered yet.</p>
                    <a href="events.php" class="text-green-600 text-sm mt-2 inline-block hover:underline">Browse Events</a>
                </div>
            <?php endif; ?>
        </div>

        <!-- Security -->
        <div id="panel-security" class="tab-panel p-6 <?= $default_tab !== 'security' ? 'hidden' : '' ?>">
            <h2 class="text-base font-semibold text-green-800 mb-4">Change Password</h2>
            <?php if ($msg): ?>
                <div class="border-l-4 p-4 mb-5 rounded-r-lg text-sm
                    <?= $msg_type === 'success' ? 'bg-green-50 border-green-500 text-green-800' : 'bg-red-50 border-red-500 text-red-800' ?>">
                    <?= htmlspecialchars($msg) ?>
                </div>
            <?php endif; ?>
            <?php if ($user['Type'] === 'Registered'): ?>
                <form method="POST" class="max-w-sm space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Current Password</label>
                        <input type="password" name="current_password" required
                            class="w-full border border-gray-300 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-green-400">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">New Password</label>
                        <input type="password" name="new_password" required
                            class="w-full border border-gray-300 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-green-400">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Confirm New Password</label>
                        <input type="password" name="confirm_new_password" required
                            class="w-full border border-gray-300 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-green-400">
                    </div>
                    <button type="submit" name="change_password"
                        class="bg-green-600 text-white px-6 py-2.5 rounded-xl hover:bg-green-700 transition text-sm font-medium">
                        Update Password
                    </button>
                </form>
            <?php else: ?>
                <p class="text-gray-500 text-sm">Guest accounts cannot change their password.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<footer class="bg-green-800 text-green-200 text-center py-6 text-sm mt-auto">
    🌿 EcoGrow Nursery &copy; <?= date('Y') ?>
</footer>

<script>
function showTab(id) {
    document.querySelectorAll('.tab-panel').forEach(p => p.classList.add('hidden'));
    document.querySelectorAll('.tab-btn').forEach(b => {
        b.classList.remove('border-green-600', 'text-green-700');
        b.classList.add('border-transparent', 'text-gray-500');
    });
    document.getElementById('panel-' + id).classList.remove('hidden');
    const btn = document.getElementById('tab-' + id);
    btn.classList.add('border-green-600', 'text-green-700');
    btn.classList.remove('border-transparent', 'text-gray-500');
}
</script>

<?php $conn->close(); ?>
</body>
</html>
