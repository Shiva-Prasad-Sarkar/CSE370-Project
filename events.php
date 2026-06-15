<?php
session_start();
require_once 'config.php';
require_once 'includes/flash.php';

$result = $conn->query(
    "SELECT w.WID, w.Topic, w.Subject, w.Date, w.Type, w.Points, w.Price,
            GROUP_CONCAT(DISTINCT b.Location SEPARATOR ', ') AS Locations
     FROM Workshops w
     INNER JOIN Workshops_Branches wb ON w.WID = wb.WorkshopID
     LEFT JOIN Branches b ON wb.BranchID = b.ID
     GROUP BY w.WID ORDER BY w.Date ASC"
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Workshops & Events — EcoGrow</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-green-50 min-h-screen flex flex-col">
<?php require_once 'includes/navbar.php'; ?>

<div class="bg-green-700 text-white py-10 px-4">
    <div class="max-w-7xl mx-auto">
        <h1 class="text-3xl font-bold">🎓 Workshops & Events</h1>
        <p class="text-green-200 mt-1 text-sm">Learn, grow, and connect with fellow plant enthusiasts</p>
    </div>
</div>

<div class="max-w-7xl mx-auto px-4 py-8 flex-1 w-full">
    <?php render_flash(); ?>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php if ($result && $result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): ?>
            <div class="bg-white rounded-xl shadow-sm border border-green-100 p-6 hover:shadow-md transition flex flex-col">
                <div class="flex items-start justify-between gap-2 mb-3">
                    <h3 class="font-bold text-green-800 text-lg leading-snug"><?= htmlspecialchars($row['Topic']) ?></h3>
                    <span class="shrink-0 text-xs px-2 py-1 rounded-full font-medium <?= $row['Type'] === 'Paid' ? 'bg-amber-100 text-amber-700' : 'bg-green-100 text-green-700' ?>">
                        <?= $row['Type'] === 'Paid' ? '💰 Paid' : '🆓 Free' ?>
                    </span>
                </div>

                <?php if (!empty($row['Subject'])): ?>
                    <p class="text-gray-600 text-sm mb-4 leading-relaxed"><?= htmlspecialchars($row['Subject']) ?></p>
                <?php endif; ?>

                <div class="space-y-1.5 text-sm text-gray-500 mb-5">
                    <p>📅 <?= htmlspecialchars($row['Date']) ?></p>
                    <?php if (!empty($row['Locations'])): ?>
                        <p>📍 <?= htmlspecialchars($row['Locations']) ?></p>
                    <?php endif; ?>
                    <?php if ($row['Type'] === 'Paid'): ?>
                        <p>💵 $<?= number_format($row['Price'], 2) ?></p>
                        <?php if ($row['Points']): ?>
                            <p>⭐ Earn <?= (int)$row['Points'] ?> points</p>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>

                <div class="mt-auto">
                    <?php if (isset($_SESSION['customer_id'])): ?>
                        <a href="registerevents.php?event_id=<?= (int)$row['WID'] ?>"
                            class="block text-center bg-green-600 text-white text-sm py-2.5 rounded-lg hover:bg-green-700 transition font-medium">
                            Register Now
                        </a>
                    <?php else: ?>
                        <a href="login.php"
                            class="block text-center bg-gray-100 text-gray-500 text-sm py-2.5 rounded-lg hover:bg-gray-200 transition">
                            Login to Register
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="col-span-3 text-center py-20 text-gray-400">
                <div class="text-5xl mb-4">📅</div>
                <p>No upcoming events. Check back soon!</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<footer class="bg-green-800 text-green-200 text-center py-6 text-sm mt-auto">
    🌿 EcoGrow Nursery &copy; <?= date('Y') ?> — Growing Together
</footer>

<?php $conn->close(); ?>
</body>
</html>
