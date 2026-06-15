<?php
session_start();
require_once 'config.php';
require_once 'includes/flash.php';

$filter = $_GET['type'] ?? 'all';

$sql = "SELECT w.WID, w.Topic, w.Subject, w.Date, w.Type, w.Points, w.Price,
               GROUP_CONCAT(DISTINCT b.Name SEPARATOR ', ') AS BranchNames,
               (SELECT COUNT(*) FROM customers_workshops cw WHERE cw.WorkshopID = w.WID) AS Attendees
        FROM workshops w
        INNER JOIN workshops_branches wb ON w.WID = wb.WorkshopID
        LEFT JOIN branches b ON wb.BranchID = b.ID";

if ($filter === 'free') $sql .= " WHERE w.Type = 'Free'";
elseif ($filter === 'paid') $sql .= " WHERE w.Type = 'Paid'";

$sql .= " GROUP BY w.WID ORDER BY w.Date ASC";
$result = $conn->query($sql);
$workshops = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];

$today = date('Y-m-d');
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

<!-- Header -->
<div class="bg-green-700 text-white py-12 px-4">
    <div class="max-w-6xl mx-auto">
        <h1 class="text-4xl font-bold mb-2">🎓 Workshops & Events</h1>
        <p class="text-green-200 text-sm">Learn, grow, and connect with fellow plant enthusiasts</p>
    </div>
</div>

<div class="max-w-6xl mx-auto px-4 py-8 flex-1 w-full">
    <?php render_flash(); ?>

    <!-- Filter tabs -->
    <div class="flex gap-2 mb-8 bg-white rounded-xl p-2 shadow-sm border border-green-100 w-fit">
        <?php foreach(['all'=>'All Events','free'=>'🆓 Free','paid'=>'💰 Paid'] as $key=>$label):?>
        <a href="events.php?type=<?=$key?>"
           class="px-4 py-2 rounded-lg text-sm font-medium transition <?=$filter===$key?'bg-green-600 text-white shadow':'text-gray-500 hover:text-green-700 hover:bg-green-50'?>">
            <?=$label?>
        </a>
        <?php endforeach;?>
    </div>

    <?php if($workshops):?>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php foreach($workshops as $row):
            $isPast    = $row['Date'] < $today;
            $isToday   = $row['Date'] === $today;
        ?>
        <div class="bg-white rounded-2xl shadow-sm border border-green-100 overflow-hidden hover:shadow-md transition flex flex-col <?=$isPast?'opacity-70':''?>">
            <!-- Color band -->
            <div class="h-1.5 <?=$row['Type']==='Paid'?'bg-amber-400':'bg-green-500'?>"></div>
            <div class="p-6 flex flex-col flex-1">
                <div class="flex items-start justify-between gap-2 mb-3">
                    <h3 class="font-bold text-green-900 text-lg leading-snug"><?=htmlspecialchars($row['Topic'])?></h3>
                    <span class="shrink-0 text-xs px-2 py-1 rounded-full font-semibold <?=$row['Type']==='Paid'?'bg-amber-100 text-amber-700':'bg-green-100 text-green-700'?>">
                        <?=$row['Type']==='Paid'?'💰 Paid':'🆓 Free'?>
                    </span>
                </div>

                <?php if(!empty($row['Subject'])):?>
                <p class="text-gray-500 text-sm mb-4 leading-relaxed"><?=htmlspecialchars($row['Subject'])?></p>
                <?php endif;?>

                <div class="space-y-2 text-sm text-gray-500 mb-5 flex-1">
                    <p class="flex items-center gap-2">
                        📅
                        <span class="<?=$isToday?'text-green-600 font-semibold':($isPast?'line-through text-gray-300':'')?>">
                            <?=htmlspecialchars($row['Date'])?>
                        </span>
                        <?php if($isToday):?><span class="text-xs bg-green-100 text-green-700 px-2 py-0.5 rounded-full font-medium">Today!</span><?php endif;?>
                        <?php if($isPast):?><span class="text-xs text-gray-400">(ended)</span><?php endif;?>
                    </p>
                    <?php if(!empty($row['BranchNames'])):?>
                    <p>📍 <?=htmlspecialchars($row['BranchNames'])?></p>
                    <?php endif;?>
                    <?php if($row['Type']==='Paid'):?>
                    <p class="font-semibold text-green-600">💵 $<?=number_format($row['Price'],2)?></p>
                    <?php if($row['Points']):?><p class="text-xs text-amber-600">⭐ Earn <?=(int)$row['Points']?> reward points</p><?php endif;?>
                    <?php endif;?>
                    <p class="text-xs text-gray-400">👥 <?=(int)$row['Attendees']?> registered</p>
                </div>

                <div class="mt-auto">
                    <?php if($isPast):?>
                        <span class="block text-center bg-gray-100 text-gray-400 text-sm py-2.5 rounded-xl font-medium">Event Ended</span>
                    <?php elseif(isset($_SESSION['customer_id'])):?>
                        <a href="registerevents.php?event_id=<?=(int)$row['WID']?>"
                            class="block text-center bg-green-600 text-white text-sm py-2.5 rounded-xl hover:bg-green-700 transition font-semibold shadow-sm">
                            Register Now →
                        </a>
                    <?php else:?>
                        <a href="login.php"
                            class="block text-center bg-gray-100 text-gray-500 text-sm py-2.5 rounded-xl hover:bg-gray-200 transition font-medium">
                            Login to Register
                        </a>
                    <?php endif;?>
                </div>
            </div>
        </div>
        <?php endforeach;?>
    </div>
    <?php else:?>
    <div class="text-center py-20 text-gray-400">
        <div class="text-6xl mb-4">📅</div>
        <p class="text-lg">No <?=$filter!=='all'?$filter:''?> events found.</p>
        <?php if($filter!=='all'):?><a href="events.php" class="text-green-600 text-sm mt-2 inline-block hover:underline">View all events</a><?php endif;?>
    </div>
    <?php endif;?>
</div>

<footer class="bg-green-900 text-green-300 text-center py-6 text-sm mt-auto">
    <p class="mb-2 text-white font-semibold">🌿 EcoGrow Nursery</p>
    <div class="flex justify-center gap-6 text-xs">
        <a href="index.php" class="hover:text-white transition">Home</a>
        <a href="product.php" class="hover:text-white transition">Products</a>
        <a href="events.php" class="hover:text-white transition">Events</a>
        <a href="about.php" class="hover:text-white transition">About</a>
    </div>
    <p class="mt-3 text-xs text-green-600">&copy; <?=date('Y')?> EcoGrow Nursery. All rights reserved.</p>
</footer>

<?php $conn->close(); ?>
</body>
</html>
