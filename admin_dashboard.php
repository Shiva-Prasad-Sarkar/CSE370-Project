<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['admin'])) { header("Location: admin_login.php"); exit; }

$admin    = $_SESSION['admin'];
$admin_id = (int)$admin['id'];
$msg      = '';
$msg_type = '';
$active_tab = $_GET['tab'] ?? 'overview';

// ─── Confirm order → sets status to Processing ───
if (isset($_POST['confirm_order'])) {
    $oid = (int)$_POST['order_id'];
    $loc = '';
    $s = $conn->prepare("INSERT INTO admin_confirms_orders (AdminID,OrderID,Location,IsPending) VALUES(?,?,?,0) ON DUPLICATE KEY UPDATE IsPending=0");
    $s->bind_param("iis",$admin_id,$oid,$loc); $s->execute(); $s->close();
    $s = $conn->prepare("INSERT INTO order_status (OrderID,Status,UpdatedBy) VALUES(?,\'Processing\',?) ON DUPLICATE KEY UPDATE Status=\'Processing\',UpdatedBy=?,UpdatedAt=NOW()");
    $s->bind_param("iii",$oid,$admin_id,$admin_id); $s->execute(); $s->close();
    header("Location: admin_dashboard.php?tab=orders&msg=confirmed"); exit;
}

// ─── Update order status ───
if (isset($_POST['update_order_status'])) {
    $oid = (int)$_POST['order_id'];
    $allowed = ['Placed','Processing','Shipped','Delivered'];
    $status  = in_array($_POST['new_status'] ?? '', $allowed) ? $_POST['new_status'] : 'Placed';
    $s = $conn->prepare("INSERT INTO order_status (OrderID,Status,UpdatedBy) VALUES(?,?,?) ON DUPLICATE KEY UPDATE Status=?,UpdatedBy=?,UpdatedAt=NOW()");
    $s->bind_param("isisi",$oid,$status,$admin_id,$status,$admin_id); $s->execute(); $s->close();
    if ($status !== 'Placed') {
        $loc='';
        $s = $conn->prepare("INSERT INTO admin_confirms_orders (AdminID,OrderID,Location,IsPending) VALUES(?,?,?,0) ON DUPLICATE KEY UPDATE IsPending=0");
        $s->bind_param("iis",$admin_id,$oid,$loc); $s->execute(); $s->close();
    }
    header("Location: admin_dashboard.php?tab=orders&msg=status_updated"); exit;
}

// ─── Add product ───
if (isset($_POST['add_product'])) {
    $name=$_POST['name']??''; $cat=$_POST['category']??''; $sub=$_POST['subtype']??'';
    $price=(float)($_POST['price']??0); $stock=(int)($_POST['stock']??0);
    $details=trim($_POST['details']??''); $photo=trim($_POST['photo_url']??'');
    $vc=['Accessories'=>['Soil','Glass','Wooden'],'Plants'=>['Indoor','Outdoor']];
    if (!isset($vc[$cat])||!in_array($sub,$vc[$cat])) {
        $msg="Invalid subtype '$sub' for category '$cat'."; $msg_type='error'; $active_tab='products';
    } else {
        $s=$conn->prepare("INSERT INTO products (Name,Category,SubType,Price,Stock,Details) VALUES(?,?,?,?,?,?)");
        $s->bind_param("sssdis",$name,$cat,$sub,$price,$stock,$details); $s->execute();
        if ($photo!=='') { $pid=(int)$conn->insert_id; $s2=$conn->prepare("INSERT INTO product_photos (ProductID,Photo) VALUES(?,?)"); $s2->bind_param("is",$pid,$photo); $s2->execute(); $s2->close(); }
        $s->close();
        header("Location: admin_dashboard.php?tab=products&msg=product_added"); exit;
    }
}

// ─── Edit product ───
if (isset($_POST['edit_product'])) {
    $pid=(int)$_POST['ep_id']; $name=trim($_POST['ep_name']??''); $cat=$_POST['ep_category']??'';
    $sub=$_POST['ep_subtype']??''; $price=(float)($_POST['ep_price']??0); $stock=(int)($_POST['ep_stock']??0);
    $details=trim($_POST['ep_details']??''); $photo=trim($_POST['ep_photo']??'');
    $s=$conn->prepare("UPDATE products SET Name=?,Category=?,SubType=?,Price=?,Stock=?,Details=? WHERE ID=?");
    $s->bind_param("sssdisi",$name,$cat,$sub,$price,$stock,$details,$pid); $s->execute(); $s->close();
    if ($photo!=='') {
        $d=$conn->prepare("DELETE FROM product_photos WHERE ProductID=?"); $d->bind_param("i",$pid); $d->execute(); $d->close();
        $i=$conn->prepare("INSERT INTO product_photos (ProductID,Photo) VALUES(?,?)"); $i->bind_param("is",$pid,$photo); $i->execute(); $i->close();
    }
    header("Location: admin_dashboard.php?tab=products&msg=product_updated"); exit;
}

// ─── Update stock ───
if (isset($_POST['update_stock'])) {
    $pid=(int)$_POST['product_id']; $st=max(0,(int)$_POST['new_stock']);
    $s=$conn->prepare("UPDATE products SET Stock=? WHERE ID=?"); $s->bind_param("ii",$st,$pid); $s->execute(); $s->close();
    header("Location: admin_dashboard.php?tab=products&msg=stock_updated"); exit;
}

// ─── Delete product ───
if (isset($_POST['delete_product'])) {
    $pid=(int)$_POST['product_id'];
    foreach(["DELETE FROM product_photos WHERE ProductID=?","DELETE FROM cart WHERE ProductID=?","DELETE FROM products WHERE ID=?"] as $q) {
        $s=$conn->prepare($q); $s->bind_param("i",$pid); $s->execute(); $s->close();
    }
    header("Location: admin_dashboard.php?tab=products&msg=product_deleted"); exit;
}

// ─── Create workshop ───
if (isset($_POST['create_workshop'])) {
    $topic=trim($_POST['topic']??''); $subj=trim($_POST['subject']??'');
    $date=$_POST['date']??''; $type=$_POST['type']??''; $wpts=null; $wprice=null;
    if ($type==='Paid') {
        if (empty(trim($_POST['points']??''))||empty(trim($_POST['price_workshop']??''))) {
            $msg='Paid workshops need points and price.'; $msg_type='error'; $active_tab='workshops';
        } else { $wpts=(int)$_POST['points']; $wprice=(float)$_POST['price_workshop']; }
    }
    if ($msg_type!=='error') {
        $s=$conn->prepare("INSERT INTO workshops (Topic,Subject,Date,Type,CreatedBy,Points,Price) VALUES(?,?,?,?,?,?,?)");
        $s->bind_param("ssssiid",$topic,$subj,$date,$type,$admin_id,$wpts,$wprice); $s->execute();
        $wid=(int)$conn->insert_id; $s->close();
        $br=trim($_POST['branches']??'');
        if ($br!=='') { $bids=array_filter(array_map('intval',explode(',',$br))); $s2=$conn->prepare("INSERT INTO workshops_branches (WorkshopID,BranchID) VALUES(?,?)"); foreach($bids as $bid){$s2->bind_param("ii",$wid,$bid);$s2->execute();} $s2->close(); }
        header("Location: admin_dashboard.php?tab=workshops&msg=workshop_created"); exit;
    }
}

// ─── Edit workshop ───
if (isset($_POST['edit_workshop'])) {
    $wid=(int)$_POST['ew_id']; $topic=trim($_POST['ew_topic']??''); $subj=trim($_POST['ew_subject']??'');
    $date=$_POST['ew_date']??''; $type=$_POST['ew_type']??'';
    $pts=($_POST['ew_points']!==''&&$_POST['ew_points']!==null)?(int)$_POST['ew_points']:null;
    $pr=($_POST['ew_price']!==''&&$_POST['ew_price']!==null)?(float)$_POST['ew_price']:null;
    $s=$conn->prepare("UPDATE workshops SET Topic=?,Subject=?,Date=?,Type=?,Points=?,Price=? WHERE WID=?");
    $s->bind_param("ssssiid",$topic,$subj,$date,$type,$pts,$pr,$wid); $s->execute(); $s->close();
    header("Location: admin_dashboard.php?tab=workshops&msg=workshop_updated"); exit;
}

// ─── Delete workshop ───
if (isset($_POST['delete_workshop'])) {
    $wid=(int)$_POST['workshop_id'];
    foreach(["DELETE FROM workshops_branches WHERE WorkshopID=?","DELETE FROM customers_workshops WHERE WorkshopID=?","DELETE FROM workshops WHERE WID=?"] as $q) {
        $s=$conn->prepare($q); $s->bind_param("i",$wid); $s->execute(); $s->close();
    }
    header("Location: admin_dashboard.php?tab=workshops&msg=workshop_deleted"); exit;
}

// ─── Add branch ───
if (isset($_POST['add_branch'])) {
    $name=trim($_POST['branch_name']??''); $loc=trim($_POST['branch_location']??'');
    $mgr=trim($_POST['branch_manager']??''); $rat=(float)($_POST['branch_ratings']??0);
    $det=trim($_POST['branch_details']??'');
    $s=$conn->prepare("INSERT INTO branches (Name,Location,Manager,Ratings,Details) VALUES(?,?,?,?,?)");
    $s->bind_param("sssds",$name,$loc,$mgr,$rat,$det); $s->execute(); $s->close();
    header("Location: admin_dashboard.php?tab=branches&msg=branch_added"); exit;
}

// ─── Edit branch ───
if (isset($_POST['edit_branch'])) {
    $bid=(int)$_POST['eb_id']; $name=trim($_POST['eb_name']??''); $loc=trim($_POST['eb_location']??'');
    $mgr=trim($_POST['eb_manager']??''); $rat=(float)($_POST['eb_ratings']??0); $det=trim($_POST['eb_details']??'');
    $s=$conn->prepare("UPDATE branches SET Name=?,Location=?,Manager=?,Ratings=?,Details=? WHERE ID=?");
    $s->bind_param("sssdsi",$name,$loc,$mgr,$rat,$det,$bid); $s->execute(); $s->close();
    header("Location: admin_dashboard.php?tab=branches&msg=branch_updated"); exit;
}

// ─── Delete branch ───
if (isset($_POST['delete_branch'])) {
    $bid=(int)$_POST['branch_id'];
    $s=$conn->prepare("DELETE FROM workshops_branches WHERE BranchID=?"); $s->bind_param("i",$bid); $s->execute(); $s->close();
    $s=$conn->prepare("DELETE FROM branches WHERE ID=?"); $s->bind_param("i",$bid); $s->execute(); $s->close();
    header("Location: admin_dashboard.php?tab=branches&msg=branch_deleted"); exit;
}

// ─── Create admin ───
if (isset($_POST['create_admin'])) {
    $aname=trim($_POST['admin_name']??''); $apos=trim($_POST['admin_position']??'');
    $aemail=trim($_POST['admin_email']??''); $aphone=trim($_POST['admin_phone']??'');
    $apass=trim($_POST['admin_password']??'');
    if (empty($aname)||empty($apass)) {
        $msg='Name and password are required.'; $msg_type='error'; $active_tab='admins';
    } else {
        $hashed=password_hash($apass,PASSWORD_DEFAULT);
        $s=$conn->prepare("INSERT INTO admins (Name,Position,Phone,Email,Password) VALUES(?,?,?,?,?)");
        $s->bind_param("sssss",$aname,$apos,$aphone,$aemail,$hashed); $s->execute(); $s->close();
        header("Location: admin_dashboard.php?tab=admins&msg=admin_created"); exit;
    }
}

// ─── Delete admin ───
if (isset($_POST['delete_admin'])) {
    $did=(int)$_POST['del_admin_id'];
    if ($did!==$admin_id) { $s=$conn->prepare("DELETE FROM admins WHERE ID=?"); $s->bind_param("i",$did); $s->execute(); $s->close(); }
    header("Location: admin_dashboard.php?tab=admins&msg=admin_deleted"); exit;
}

// ─── Approve review ───
if (isset($_POST['approve_review'])) {
    $rid=(int)$_POST['review_id'];
    $s=$conn->prepare("UPDATE customers_reviews SET approved=1 WHERE ID=?");
    $s->bind_param("i",$rid); $s->execute(); $s->close();
    header("Location: admin_dashboard.php?tab=reviews&msg=review_approved"); exit;
}

// ─── Delete review ───
if (isset($_POST['delete_review'])) {
    $rid=(int)$_POST['review_id'];
    $s=$conn->prepare("DELETE FROM customers_reviews WHERE ID=?");
    $s->bind_param("i",$rid); $s->execute(); $s->close();
    header("Location: admin_dashboard.php?tab=reviews&msg=review_deleted"); exit;
}

// ─── Redirect messages ───
$msg_map=[
    'confirmed'=>['success','Order confirmed — status set to Processing.'],
    'status_updated'=>['success','Order status updated.'],
    'product_added'=>['success','Product added.'],
    'product_updated'=>['success','Product updated.'],
    'product_deleted'=>['success','Product deleted.'],
    'stock_updated'=>['success','Stock updated.'],
    'workshop_created'=>['success','Workshop created.'],
    'workshop_updated'=>['success','Workshop updated.'],
    'workshop_deleted'=>['success','Workshop deleted.'],
    'branch_added'=>['success','Branch added.'],
    'branch_updated'=>['success','Branch updated.'],
    'branch_deleted'=>['success','Branch deleted.'],
    'admin_created'   =>['success','New admin account created.'],
    'admin_deleted'   =>['success','Admin removed.'],
    'review_approved' =>['success','Review approved and published.'],
    'review_deleted'  =>['success','Review deleted.'],
];
if (!$msg && isset($_GET['msg']) && isset($msg_map[$_GET['msg']])) [$msg_type,$msg]=$msg_map[$_GET['msg']];

// ─── Stats ───
$total_orders  =(int)$conn->query("SELECT COUNT(*) c FROM orders")->fetch_assoc()['c'];
$pending_count =(int)$conn->query("SELECT COUNT(*) c FROM orders WHERE ID NOT IN (SELECT OrderID FROM admin_confirms_orders WHERE IsPending=0)")->fetch_assoc()['c'];
$total_cust    =(int)$conn->query("SELECT COUNT(*) c FROM customers WHERE Type='Registered'")->fetch_assoc()['c'];
$total_prods   =(int)$conn->query("SELECT COUNT(*) c FROM products")->fetch_assoc()['c'];
$total_ws      =(int)$conn->query("SELECT COUNT(*) c FROM workshops")->fetch_assoc()['c'];
$total_br      =(int)$conn->query("SELECT COUNT(*) c FROM branches")->fetch_assoc()['c'];
$total_rev     =(float)$conn->query("SELECT COALESCE(SUM(Bill),0) r FROM orders")->fetch_assoc()['r'];

// ─── All orders (with status) ───
$all_orders=$conn->query(
    "SELECT o.ID,o.Date,o.Bill,o.Count,o.Address,
            c.Name AS CName, p.Name AS PName,
            COALESCE(os.Status,'Placed') AS OStatus,
            COALESCE(a.IsPending,1) AS IsPending
     FROM orders o
     LEFT JOIN customers c ON o.CustomerID=c.ID
     LEFT JOIN products p ON o.Product_Id=p.ID
     LEFT JOIN admin_confirms_orders a ON o.ID=a.OrderID
     LEFT JOIN order_status os ON o.ID=os.OrderID
     ORDER BY o.Date DESC"
)->fetch_all(MYSQLI_ASSOC);

// ─── Pending orders (overview) ───
$pending_orders=$conn->query(
    "SELECT o.ID,o.Date,o.Bill,o.Count,o.Address,
            c.Name AS CName, p.Name AS PName,
            COALESCE(os.Status,'Placed') AS OStatus
     FROM orders o
     LEFT JOIN customers c ON o.CustomerID=c.ID
     LEFT JOIN products p ON o.Product_Id=p.ID
     LEFT JOIN order_status os ON o.ID=os.OrderID
     WHERE o.ID NOT IN (SELECT OrderID FROM admin_confirms_orders WHERE IsPending=0)
     ORDER BY o.Date DESC"
)->fetch_all(MYSQLI_ASSOC);

// ─── Products ───
$all_products=$conn->query(
    "SELECT p.ID,p.Name,p.Category,p.SubType,p.Price,p.Stock,p.Details,pp.Photo
     FROM products p LEFT JOIN product_photos pp ON p.ID=pp.ProductID
     ORDER BY p.Category,p.Name"
)->fetch_all(MYSQLI_ASSOC);

// ─── Customers ───
$all_customers=$conn->query(
    "SELECT ID,Name,Email,Type,Points,Phone FROM customers ORDER BY Type DESC,ID ASC"
)->fetch_all(MYSQLI_ASSOC);

// ─── Workshops ───
$all_workshops=$conn->query(
    "SELECT w.WID,w.Topic,w.Subject,w.Date,w.Type,w.Price,w.Points,
            (SELECT COUNT(*) FROM customers_workshops cw WHERE cw.WorkshopID=w.WID) AS Attendees
     FROM workshops w ORDER BY w.Date DESC"
)->fetch_all(MYSQLI_ASSOC);

// ─── Branches ───
$all_branches=$conn->query("SELECT * FROM branches ORDER BY Name ASC")->fetch_all(MYSQLI_ASSOC);

// ─── Admins ───
$all_admins=$conn->query("SELECT ID,Name,Position,Email,Phone FROM admins ORDER BY ID ASC")->fetch_all(MYSQLI_ASSOC);

// ─── Reviews ───
$pending_reviews_count=(int)$conn->query("SELECT COUNT(*) c FROM customers_reviews WHERE approved=0")->fetch_assoc()['c'];
$pending_reviews=$conn->query(
    "SELECT cr.ID, cr.Comments, cr.created_at, c.Name AS CustomerName
     FROM customers_reviews cr JOIN customers c ON cr.CustomerID=c.ID
     WHERE cr.approved=0 ORDER BY cr.ID DESC"
)->fetch_all(MYSQLI_ASSOC);
$approved_reviews=$conn->query(
    "SELECT cr.ID, cr.Comments, cr.created_at, c.Name AS CustomerName
     FROM customers_reviews cr JOIN customers c ON cr.CustomerID=c.ID
     WHERE cr.approved=1 ORDER BY cr.ID DESC LIMIT 50"
)->fetch_all(MYSQLI_ASSOC);

$status_colors=['Placed'=>'bg-gray-100 text-gray-600','Processing'=>'bg-blue-100 text-blue-700','Shipped'=>'bg-amber-100 text-amber-700','Delivered'=>'bg-green-100 text-green-700'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Dashboard — EcoGrow</title>
<script src="https://cdn.tailwindcss.com"></script>
<style>
.nav-item { transition:background .15s; }
.nav-item.active { background:#166534; color:#fff; }
.nav-item:not(.active) { color:#bbf7d0; }
.nav-item:not(.active):hover { background:#14532d; color:#fff; }
</style>
</head>
<body class="bg-gray-50 min-h-screen">
<div class="flex min-h-screen">

<!-- ═══ SIDEBAR ═══ -->
<aside class="w-52 bg-green-900 text-white hidden lg:flex flex-col fixed inset-y-0 left-0 z-20">
    <div class="p-5 border-b border-green-800">
        <a href="index.php" class="text-base font-bold">🌿 EcoGrow</a>
        <p class="text-green-400 text-xs mt-0.5">Admin Panel</p>
    </div>
    <div class="p-4 border-b border-green-800 flex items-center gap-3">
        <div class="w-10 h-10 bg-green-700 rounded-full flex items-center justify-center font-bold text-sm shrink-0">
            <?= strtoupper(substr($admin['name'],0,1)) ?>
        </div>
        <div class="min-w-0">
            <p class="font-semibold text-sm leading-tight truncate"><?= htmlspecialchars($admin['name']) ?></p>
            <p class="text-green-400 text-xs truncate"><?= htmlspecialchars($admin['position']) ?></p>
        </div>
    </div>
    <nav class="p-3 flex-1 space-y-0.5 overflow-y-auto">
        <?php
        $nav=[
            'overview'  =>['📊','Overview',null],
            'orders'    =>['📦','Orders',$pending_count>0?$pending_count:null],
            'products'  =>['🌿','Products',$total_prods],
            'customers' =>['👥','Customers',$total_cust],
            'workshops' =>['🎓','Workshops',$total_ws],
            'branches'  =>['🏬','Branches',$total_br],
            'admins'    =>['🔑','Admins',null],
            'reviews'   =>['⭐','Reviews',$pending_reviews_count>0?$pending_reviews_count:null],
        ];
        foreach($nav as $key=>[$icon,$label,$badge]):
        ?>
        <button onclick="setTab('<?=$key?>')" id="nav-<?=$key?>"
            class="nav-item w-full flex items-center gap-2 px-3 py-2 rounded-lg text-xs font-medium text-left <?=$key===$active_tab?'active':''?>">
            <?=$icon?> <?=$label?>
            <?php if($badge!==null):?>
            <span class="ml-auto text-xs rounded-full px-1.5 py-0.5 leading-none <?=$key==='orders'?'bg-red-500 text-white':'bg-green-800 text-green-300'?>"><?=$badge?></span>
            <?php endif;?>
        </button>
        <?php endforeach;?>
    </nav>
    <div class="p-3 border-t border-green-800 space-y-0.5 text-xs">
        <a href="index.php" class="flex items-center gap-2 px-3 py-2 rounded-lg text-green-400 hover:bg-green-800 transition">← View Site</a>
        <a href="logout.php" class="flex items-center gap-2 px-3 py-2 rounded-lg text-red-400 hover:bg-red-900 transition">🚪 Logout</a>
    </div>
</aside>

<!-- ═══ MAIN ═══ -->
<main class="flex-1 lg:ml-52 overflow-auto">
    <!-- Mobile header -->
    <div class="lg:hidden bg-green-900 text-white px-4 py-3 flex items-center justify-between sticky top-0 z-10">
        <span class="font-bold text-sm">🌿 Admin</span>
        <div class="flex gap-1.5 flex-wrap">
            <?php foreach($nav as $key=>[$icon]):?>
            <button onclick="setTab('<?=$key?>')" class="text-green-300 text-base px-1"><?=$icon?></button>
            <?php endforeach;?>
            <a href="logout.php" class="text-red-300 text-xs ml-1 self-center">Logout</a>
        </div>
    </div>

    <div class="p-5 max-w-7xl mx-auto">
        <?php if($msg):?>
        <div class="border-l-4 p-3 mb-5 rounded-r-lg text-sm <?=$msg_type==='success'?'bg-green-50 border-green-500 text-green-800':'bg-red-50 border-red-500 text-red-800'?>">
            <?=htmlspecialchars($msg)?>
        </div>
        <?php endif;?>

        <!-- ══════ TAB: OVERVIEW ══════ -->
        <div id="tab-overview" class="tab-panel">
            <h1 class="text-xl font-bold text-gray-800 mb-5">📊 Overview</h1>
            <div class="grid grid-cols-2 sm:grid-cols-4 xl:grid-cols-7 gap-3 mb-6">
                <?php foreach([
                    ['orders','📦','Orders',$total_orders,''],
                    ['orders','⏳','Pending',$pending_count,$pending_count>0?'text-red-600':''],
                    ['customers','👥','Customers',$total_cust,''],
                    ['products','🌿','Products',$total_prods,''],
                    ['workshops','🎓','Workshops',$total_ws,''],
                    ['branches','🏬','Branches',$total_br,''],
                    ['orders','💰','Revenue','$'.number_format($total_rev,0),'text-green-600'],
                ] as [$goto,$icon,$lbl,$val,$clr]):?>
                <div onclick="setTab('<?=$goto?>')" class="bg-white rounded-xl border border-gray-100 shadow-sm p-4 cursor-pointer hover:shadow-md transition">
                    <div class="text-2xl mb-1"><?=$icon?></div>
                    <div class="text-xl font-bold text-gray-800 <?=$clr?>"><?=$val?></div>
                    <div class="text-xs text-gray-400 uppercase tracking-wide mt-0.5"><?=$lbl?></div>
                </div>
                <?php endforeach;?>
            </div>
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3 mb-6">
                <button onclick="setTab('products')"  class="bg-green-600 text-white text-sm py-2.5 rounded-xl hover:bg-green-700 transition font-medium">+ Product</button>
                <button onclick="setTab('workshops')" class="bg-blue-600 text-white text-sm py-2.5 rounded-xl hover:bg-blue-700 transition font-medium">+ Workshop</button>
                <button onclick="setTab('branches')"  class="bg-purple-600 text-white text-sm py-2.5 rounded-xl hover:bg-purple-700 transition font-medium">+ Branch</button>
                <button onclick="setTab('admins')"    class="bg-indigo-600 text-white text-sm py-2.5 rounded-xl hover:bg-indigo-700 transition font-medium">+ Admin</button>
                <button onclick="setTab('orders')"    class="bg-orange-500 text-white text-sm py-2.5 rounded-xl hover:bg-orange-600 transition font-medium">Orders</button>
                <button onclick="setTab('customers')" class="bg-teal-600 text-white text-sm py-2.5 rounded-xl hover:bg-teal-700 transition font-medium">Customers</button>
            </div>
            <!-- Pending orders quick view -->
            <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-sm font-semibold text-gray-700">⏳ Pending Orders</h2>
                    <span class="text-xs bg-red-100 text-red-600 px-2 py-0.5 rounded-full font-medium"><?=$pending_count?> pending</span>
                </div>
                <?php if($pending_orders):?>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead><tr class="text-xs text-gray-400 uppercase border-b border-gray-100 text-left">
                            <th class="pb-3 pr-3 font-medium">#</th><th class="pb-3 pr-3 font-medium">Customer</th><th class="pb-3 pr-3 font-medium">Product</th>
                            <th class="pb-3 pr-3 font-medium">Date</th><th class="pb-3 pr-3 font-medium">Total</th><th class="pb-3 font-medium">Action</th>
                        </tr></thead>
                        <tbody class="divide-y divide-gray-50">
                        <?php foreach($pending_orders as $r):?>
                        <tr class="hover:bg-gray-50">
                            <td class="py-3 pr-3 text-gray-400 text-xs">#<?=$r['ID']?></td>
                            <td class="py-3 pr-3 font-medium text-gray-800"><?=htmlspecialchars($r['CName']??'—')?></td>
                            <td class="py-3 pr-3 text-gray-500 text-xs"><?=htmlspecialchars($r['PName']??'—')?></td>
                            <td class="py-3 pr-3 text-gray-400 text-xs"><?=htmlspecialchars($r['Date'])?></td>
                            <td class="py-3 pr-3 text-green-600 font-semibold">$<?=number_format($r['Bill'],2)?></td>
                            <td class="py-3">
                                <form method="POST" class="inline">
                                    <input type="hidden" name="order_id" value="<?=(int)$r['ID']?>">
                                    <button name="confirm_order" class="bg-green-600 text-white text-xs px-3 py-1.5 rounded-lg hover:bg-green-700 transition font-medium">Confirm</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach;?>
                        </tbody>
                    </table>
                </div>
                <?php else:?>
                <div class="text-center py-10 text-gray-400"><div class="text-4xl mb-2">✅</div><p class="text-sm">All orders confirmed.</p></div>
                <?php endif;?>
            </div>
        </div>

        <!-- ══════ TAB: ORDERS ══════ -->
        <div id="tab-orders" class="tab-panel hidden">
            <h1 class="text-xl font-bold text-gray-800 mb-5">📦 All Orders</h1>
            <div class="flex gap-2 mb-4 flex-wrap">
                <?php foreach(['all'=>'All ('.count($all_orders).')','pending'=>'Pending ('.$pending_count.')','Processing'=>'Processing','Shipped'=>'Shipped','Delivered'=>'Delivered'] as $fk=>$fl):?>
                <button onclick="filterOrders('<?=$fk?>')" id="flt-<?=$fk?>" class="order-flt text-xs px-3 py-1.5 rounded-full font-medium transition bg-gray-100 text-gray-600 hover:bg-gray-200"><?=$fl?></button>
                <?php endforeach;?>
            </div>
            <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
                <?php if($all_orders):?>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead><tr class="text-xs text-gray-400 uppercase border-b border-gray-100 text-left">
                            <th class="pb-3 pr-3 font-medium">#</th><th class="pb-3 pr-3 font-medium">Customer</th>
                            <th class="pb-3 pr-3 font-medium">Product</th><th class="pb-3 pr-3 font-medium">Date</th>
                            <th class="pb-3 pr-3 font-medium">Total</th><th class="pb-3 pr-3 font-medium">Qty</th>
                            <th class="pb-3 pr-3 font-medium">Address</th><th class="pb-3 font-medium">Status</th>
                        </tr></thead>
                        <tbody class="divide-y divide-gray-50">
                        <?php foreach($all_orders as $r):
                            $st=$r['OStatus'];
                            $fltKey=$r['IsPending']?'pending':strtolower($st);
                        ?>
                        <tr class="hover:bg-gray-50 order-row" data-status="<?=$fltKey?>" data-ostatus="<?=strtolower($st)?>">
                            <td class="py-3 pr-3 text-gray-400 text-xs">#<?=$r['ID']?></td>
                            <td class="py-3 pr-3 font-medium text-gray-800"><?=htmlspecialchars($r['CName']??'—')?></td>
                            <td class="py-3 pr-3 text-gray-500 text-xs max-w-[100px] truncate"><?=htmlspecialchars($r['PName']??'—')?></td>
                            <td class="py-3 pr-3 text-gray-400 text-xs"><?=htmlspecialchars($r['Date'])?></td>
                            <td class="py-3 pr-3 text-green-600 font-semibold">$<?=number_format($r['Bill'],2)?></td>
                            <td class="py-3 pr-3 text-gray-500"><?=(int)$r['Count']?></td>
                            <td class="py-3 pr-3 text-gray-400 text-xs max-w-[100px] truncate"><?=htmlspecialchars($r['Address'])?></td>
                            <td class="py-3">
                                <form method="POST" class="flex items-center gap-1.5">
                                    <input type="hidden" name="order_id" value="<?=(int)$r['ID']?>">
                                    <select name="new_status" class="border border-gray-200 rounded-lg px-2 py-1 text-xs focus:outline-none focus:ring-1 focus:ring-green-400">
                                        <?php foreach(['Placed','Processing','Shipped','Delivered'] as $sopt):?>
                                        <option value="<?=$sopt?>" <?=$sopt===$st?'selected':''?>><?=$sopt?></option>
                                        <?php endforeach;?>
                                    </select>
                                    <button name="update_order_status" class="bg-green-600 text-white text-xs px-2 py-1 rounded-lg hover:bg-green-700 transition font-medium">✓</button>
                                </form>
                                <span class="mt-1 inline-block text-xs px-2 py-0.5 rounded-full font-medium <?=$status_colors[$st]??'bg-gray-100 text-gray-600'?>"><?=$st?></span>
                            </td>
                        </tr>
                        <?php endforeach;?>
                        </tbody>
                    </table>
                </div>
                <?php else:?>
                <div class="text-center py-10 text-gray-400"><div class="text-4xl mb-2">📦</div><p class="text-sm">No orders yet.</p></div>
                <?php endif;?>
            </div>
        </div>

        <!-- ══════ TAB: PRODUCTS ══════ -->
        <div id="tab-products" class="tab-panel hidden">
            <h1 class="text-xl font-bold text-gray-800 mb-5">🌿 Products</h1>
            <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5 mb-5">
                <h2 class="text-sm font-semibold text-gray-700 mb-4">Add New Product</h2>
                <form method="POST" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                    <input type="text" name="name" placeholder="Product Name *" required class="border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-400">
                    <select name="category" required class="border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-400">
                        <option value="">Category *</option><option>Plants</option><option>Accessories</option>
                    </select>
                    <select name="subtype" required class="border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-400">
                        <option value="">SubType *</option>
                        <optgroup label="Plants"><option>Indoor</option><option>Outdoor</option></optgroup>
                        <optgroup label="Accessories"><option>Soil</option><option>Glass</option><option>Wooden</option></optgroup>
                    </select>
                    <input type="number" step="0.01" name="price" placeholder="Price ($) *" required class="border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-400">
                    <input type="number" name="stock" placeholder="Stock qty *" required class="border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-400">
                    <input type="text" name="photo_url" placeholder="Image URL (optional)" class="border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-400">
                    <textarea name="details" rows="2" placeholder="Description" class="col-span-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-400 resize-none"></textarea>
                    <button type="submit" name="add_product" class="col-span-full bg-green-600 text-white py-2 rounded-lg text-sm font-medium hover:bg-green-700 transition">+ Add Product</button>
                </form>
            </div>
            <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
                <h2 class="text-sm font-semibold text-gray-700 mb-4">All Products (<?=count($all_products)?>)</h2>
                <?php if($all_products):?>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead><tr class="text-xs text-gray-400 uppercase border-b border-gray-100 text-left">
                            <th class="pb-3 pr-3 font-medium">Product</th><th class="pb-3 pr-3 font-medium">Category</th>
                            <th class="pb-3 pr-3 font-medium">Price</th><th class="pb-3 pr-3 font-medium">Stock</th>
                            <th class="pb-3 pr-3 font-medium">Edit</th><th class="pb-3 font-medium">Delete</th>
                        </tr></thead>
                        <tbody class="divide-y divide-gray-50">
                        <?php foreach($all_products as $p):?>
                        <tr class="hover:bg-gray-50">
                            <td class="py-3 pr-3">
                                <div class="flex items-center gap-2">
                                    <?php if(!empty($p['Photo'])):?><img src="<?=htmlspecialchars($p['Photo'])?>" alt="" class="w-9 h-9 object-cover rounded-lg shrink-0">
                                    <?php else:?><span class="text-xl w-9 h-9 flex items-center justify-center bg-green-50 rounded-lg shrink-0">🌿</span><?php endif;?>
                                    <div><p class="font-medium text-gray-800 text-sm"><?=htmlspecialchars($p['Name'])?></p><p class="text-xs text-gray-400">#<?=$p['ID']?></p></div>
                                </div>
                            </td>
                            <td class="py-3 pr-3 text-gray-500 text-xs"><?=htmlspecialchars($p['Category'])?><br><span class="text-gray-400"><?=htmlspecialchars($p['SubType'])?></span></td>
                            <td class="py-3 pr-3 text-green-600 font-semibold">$<?=number_format($p['Price'],2)?></td>
                            <td class="py-3 pr-3">
                                <form method="POST" class="flex items-center gap-1">
                                    <input type="hidden" name="product_id" value="<?=(int)$p['ID']?>">
                                    <input type="number" name="new_stock" value="<?=(int)$p['Stock']?>" min="0" class="w-16 border border-gray-200 rounded px-2 py-1 text-xs focus:outline-none <?=$p['Stock']<=0?'text-red-500 border-red-200':''?>">
                                    <button name="update_stock" class="bg-blue-100 text-blue-700 text-xs px-2 py-1 rounded hover:bg-blue-200 transition">✓</button>
                                </form>
                            </td>
                            <td class="py-3 pr-3">
                                <button onclick="openEditProduct(<?=$p['ID']?>,<?=htmlspecialchars(json_encode($p['Name']))?>,<?=htmlspecialchars(json_encode($p['Category']))?>,<?=htmlspecialchars(json_encode($p['SubType']))?>,<?=$p['Price']?>,<?=$p['Stock']?>,<?=htmlspecialchars(json_encode($p['Details']??''))?>,<?=htmlspecialchars(json_encode($p['Photo']??''))?>)"
                                    class="bg-amber-100 text-amber-700 text-xs px-2 py-1 rounded hover:bg-amber-200 transition font-medium">Edit</button>
                            </td>
                            <td class="py-3">
                                <form method="POST" onsubmit="return confirm('Delete <?=addslashes(htmlspecialchars($p['Name']))?> permanently?')">
                                    <input type="hidden" name="product_id" value="<?=(int)$p['ID']?>">
                                    <button name="delete_product" class="text-red-500 hover:text-red-700 text-xs font-medium transition">Delete</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach;?>
                        </tbody>
                    </table>
                </div>
                <?php else:?><p class="text-gray-400 text-sm text-center py-8">No products yet.</p><?php endif;?>
            </div>
        </div>

        <!-- ══════ TAB: CUSTOMERS ══════ -->
        <div id="tab-customers" class="tab-panel hidden">
            <h1 class="text-xl font-bold text-gray-800 mb-5">👥 Customers (<?=count($all_customers)?>)</h1>
            <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
                <?php if($all_customers):?>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead><tr class="text-xs text-gray-400 uppercase border-b border-gray-100 text-left">
                            <th class="pb-3 pr-4 font-medium">#</th><th class="pb-3 pr-4 font-medium">Name</th>
                            <th class="pb-3 pr-4 font-medium">Email</th><th class="pb-3 pr-4 font-medium">Phone</th>
                            <th class="pb-3 pr-4 font-medium">Type</th><th class="pb-3 font-medium">Points</th>
                        </tr></thead>
                        <tbody class="divide-y divide-gray-50">
                        <?php foreach($all_customers as $c):?>
                        <tr class="hover:bg-gray-50">
                            <td class="py-3 pr-4 text-gray-400 text-xs">#<?=$c['ID']?></td>
                            <td class="py-3 pr-4"><div class="flex items-center gap-2"><span class="w-7 h-7 inline-flex items-center justify-center bg-green-100 text-green-700 rounded-full text-xs font-bold shrink-0"><?=strtoupper(substr($c['Name']??'?',0,1))?></span><span class="font-medium text-gray-800"><?=htmlspecialchars($c['Name'])?></span></div></td>
                            <td class="py-3 pr-4 text-gray-500 text-xs"><?=htmlspecialchars($c['Email'])?></td>
                            <td class="py-3 pr-4 text-gray-500 text-xs"><?=htmlspecialchars($c['Phone']??'—')?></td>
                            <td class="py-3 pr-4"><span class="text-xs px-2 py-0.5 rounded-full font-medium <?=$c['Type']==='Registered'?'bg-green-100 text-green-700':'bg-gray-100 text-gray-500'?>"><?=$c['Type']?></span></td>
                            <td class="py-3 font-semibold text-green-600"><?=(int)$c['Points']?></td>
                        </tr>
                        <?php endforeach;?>
                        </tbody>
                    </table>
                </div>
                <?php else:?><p class="text-gray-400 text-sm text-center py-8">No customers yet.</p><?php endif;?>
            </div>
        </div>

        <!-- ══════ TAB: WORKSHOPS ══════ -->
        <div id="tab-workshops" class="tab-panel hidden">
            <h1 class="text-xl font-bold text-gray-800 mb-5">🎓 Workshops</h1>
            <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5 mb-5">
                <h2 class="text-sm font-semibold text-gray-700 mb-4">Create New Workshop</h2>
                <form method="POST" class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <input type="text" name="topic" placeholder="Topic *" required class="border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-400">
                    <input type="text" name="subject" placeholder="Subject / Description" class="border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-400">
                    <input type="date" name="date" required class="border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-400">
                    <select name="type" required class="border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-400">
                        <option value="">Type *</option><option>Free</option><option>Paid</option>
                    </select>
                    <input type="number" name="points" placeholder="Points (Paid only)" class="border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-400">
                    <input type="number" step="0.01" name="price_workshop" placeholder="Price $ (Paid only)" class="border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-400">
                    <div class="col-span-full text-xs text-gray-400">Branches: <?php foreach($all_branches as $b):?><span class="bg-gray-100 rounded px-1 mr-1 font-mono"><?=$b['ID']?>:<?=htmlspecialchars($b['Name'])?></span><?php endforeach;?></div>
                    <input type="text" name="branches" placeholder="Branch IDs e.g. 1,2" class="border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-400">
                    <button type="submit" name="create_workshop" class="bg-green-600 text-white py-2 rounded-lg text-sm font-medium hover:bg-green-700 transition">Create Workshop</button>
                </form>
            </div>
            <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
                <h2 class="text-sm font-semibold text-gray-700 mb-4">All Workshops (<?=count($all_workshops)?>)</h2>
                <?php if($all_workshops):?>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead><tr class="text-xs text-gray-400 uppercase border-b border-gray-100 text-left">
                            <th class="pb-3 pr-4 font-medium">Topic</th><th class="pb-3 pr-4 font-medium">Date</th>
                            <th class="pb-3 pr-4 font-medium">Type</th><th class="pb-3 pr-4 font-medium">Price</th>
                            <th class="pb-3 pr-4 font-medium">Attendees</th><th class="pb-3 pr-4 font-medium">Edit</th><th class="pb-3 font-medium">Del</th>
                        </tr></thead>
                        <tbody class="divide-y divide-gray-50">
                        <?php foreach($all_workshops as $w):?>
                        <tr class="hover:bg-gray-50">
                            <td class="py-3 pr-4"><p class="font-medium text-gray-800"><?=htmlspecialchars($w['Topic'])?></p><?php if($w['Subject']):?><p class="text-xs text-gray-400 mt-0.5"><?=htmlspecialchars(mb_substr($w['Subject'],0,45))?></p><?php endif;?></td>
                            <td class="py-3 pr-4 text-gray-500 text-xs"><?=htmlspecialchars($w['Date']??'—')?></td>
                            <td class="py-3 pr-4"><span class="text-xs px-2 py-0.5 rounded-full font-medium <?=$w['Type']==='Paid'?'bg-amber-100 text-amber-700':'bg-green-100 text-green-700'?>"><?=$w['Type']?></span></td>
                            <td class="py-3 pr-4 text-xs font-semibold <?=$w['Type']==='Paid'?'text-green-600':'text-gray-400'?>"><?=$w['Type']==='Paid'?'$'.number_format($w['Price'],2):'Free'?></td>
                            <td class="py-3 pr-4 text-gray-600"><?=(int)$w['Attendees']?></td>
                            <td class="py-3 pr-4">
                                <button onclick="openEditWorkshop(<?=$w['WID']?>,<?=htmlspecialchars(json_encode($w['Topic']))?>,<?=htmlspecialchars(json_encode($w['Subject']??''))?>,<?=htmlspecialchars(json_encode($w['Date']??''))?>,<?=htmlspecialchars(json_encode($w['Type']))?>,<?=(int)($w['Points']??0)?>,<?=(float)($w['Price']??0)?>)"
                                    class="bg-amber-100 text-amber-700 text-xs px-2 py-1 rounded hover:bg-amber-200 transition font-medium">Edit</button>
                            </td>
                            <td class="py-3">
                                <form method="POST" onsubmit="return confirm('Delete this workshop?')">
                                    <input type="hidden" name="workshop_id" value="<?=(int)$w['WID']?>">
                                    <button name="delete_workshop" class="text-red-500 hover:text-red-700 text-xs font-medium">Del</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach;?>
                        </tbody>
                    </table>
                </div>
                <?php else:?><p class="text-gray-400 text-sm text-center py-8">No workshops yet.</p><?php endif;?>
            </div>
        </div>

        <!-- ══════ TAB: BRANCHES ══════ -->
        <div id="tab-branches" class="tab-panel hidden">
            <h1 class="text-xl font-bold text-gray-800 mb-5">🏬 Branches</h1>
            <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5 mb-5">
                <h2 class="text-sm font-semibold text-gray-700 mb-4">Add New Branch</h2>
                <form method="POST" class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <input type="text" name="branch_name" placeholder="Branch Name *" required class="border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-400">
                    <input type="text" name="branch_location" placeholder="Location *" required class="border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-400">
                    <input type="text" name="branch_manager" placeholder="Manager Name" class="border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-400">
                    <input type="number" step="0.1" min="0" max="5" name="branch_ratings" placeholder="Ratings (0–5)" class="border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-400">
                    <textarea name="branch_details" rows="2" placeholder="Branch details" class="col-span-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-400 resize-none"></textarea>
                    <button type="submit" name="add_branch" class="col-span-full bg-green-600 text-white py-2 rounded-lg text-sm font-medium hover:bg-green-700 transition">+ Add Branch</button>
                </form>
            </div>
            <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
                <h2 class="text-sm font-semibold text-gray-700 mb-4">All Branches (<?=count($all_branches)?>)</h2>
                <?php if($all_branches):?>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    <?php foreach($all_branches as $b):?>
                    <div class="border border-gray-100 rounded-xl p-4 bg-gray-50 hover:bg-white transition">
                        <div class="flex items-start justify-between gap-2 mb-2">
                            <div><h3 class="font-semibold text-gray-800 text-sm"><?=htmlspecialchars($b['Name'])?></h3><p class="text-xs text-gray-500 mt-0.5">📍 <?=htmlspecialchars($b['Location'])?></p></div>
                            <?php if($b['Ratings']):?><span class="text-xs bg-yellow-100 text-yellow-700 px-2 py-0.5 rounded-full font-medium shrink-0">⭐ <?=number_format($b['Ratings'],1)?></span><?php endif;?>
                        </div>
                        <?php if($b['Manager']):?><p class="text-xs text-gray-500 mb-2">👤 <?=htmlspecialchars($b['Manager'])?></p><?php endif;?>
                        <?php if($b['Details']):?><p class="text-xs text-gray-400 mb-3 leading-relaxed"><?=htmlspecialchars(mb_substr($b['Details'],0,70))?></p><?php endif;?>
                        <div class="flex items-center gap-3 mt-2">
                            <button onclick="openEditBranch(<?=$b['ID']?>,<?=htmlspecialchars(json_encode($b['Name']))?>,<?=htmlspecialchars(json_encode($b['Location']))?>,<?=htmlspecialchars(json_encode($b['Manager']??''))?>,<?=(float)($b['Ratings']??0)?>,<?=htmlspecialchars(json_encode($b['Details']??''))?>)"
                                class="text-xs bg-amber-100 text-amber-700 px-2 py-1 rounded hover:bg-amber-200 transition font-medium">Edit</button>
                            <form method="POST" onsubmit="return confirm('Delete this branch?')">
                                <input type="hidden" name="branch_id" value="<?=(int)$b['ID']?>">
                                <button name="delete_branch" class="text-xs text-red-500 hover:text-red-700 transition font-medium">Delete</button>
                            </form>
                        </div>
                    </div>
                    <?php endforeach;?>
                </div>
                <?php else:?><p class="text-gray-400 text-sm text-center py-8">No branches yet.</p><?php endif;?>
            </div>
        </div>

        <!-- ══════ TAB: ADMINS ══════ -->
        <div id="tab-admins" class="tab-panel hidden">
            <h1 class="text-xl font-bold text-gray-800 mb-5">🔑 Admin Accounts</h1>
            <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5 mb-5">
                <h2 class="text-sm font-semibold text-gray-700 mb-4">Create New Admin</h2>
                <form method="POST" class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <input type="text" name="admin_name" placeholder="Full Name *" required class="border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-400">
                    <input type="text" name="admin_position" placeholder="Position / Role" class="border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-400">
                    <input type="email" name="admin_email" placeholder="Email" class="border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-400">
                    <input type="text" name="admin_phone" placeholder="Phone" class="border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-400">
                    <input type="password" name="admin_password" placeholder="Password *" required class="border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-400">
                    <p class="text-xs text-gray-400 self-center">Admin can log in at <strong>admin_login.php</strong> using their email or name.</p>
                    <button type="submit" name="create_admin" class="col-span-full bg-indigo-600 text-white py-2 rounded-lg text-sm font-medium hover:bg-indigo-700 transition">Create Admin Account</button>
                </form>
            </div>
            <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
                <h2 class="text-sm font-semibold text-gray-700 mb-4">All Admins</h2>
                <div class="space-y-3">
                    <?php foreach($all_admins as $a):?>
                    <div class="flex items-center justify-between gap-4 p-3 border border-gray-100 rounded-xl hover:bg-gray-50 transition">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-indigo-100 text-indigo-700 rounded-full flex items-center justify-center font-bold text-sm shrink-0">
                                <?=strtoupper(substr($a['Name'],0,1))?>
                            </div>
                            <div>
                                <p class="font-semibold text-gray-800 text-sm"><?=htmlspecialchars($a['Name'])?> <?=$a['ID']===$admin_id?'<span class="text-xs text-green-600">(you)</span>':''?></p>
                                <p class="text-xs text-gray-500"><?=htmlspecialchars($a['Position']??'—')?> · <?=htmlspecialchars($a['Email']??'—')?></p>
                            </div>
                        </div>
                        <?php if($a['ID']!==$admin_id):?>
                        <?php $an=addslashes(htmlspecialchars($a['Name'])); ?>
                        <form method="POST" onsubmit="return confirm('Remove admin account for <?=$an?>?')"  >
                            <input type="hidden" name="del_admin_id" value="<?=(int)$a['ID']?>">
                            <button name="delete_admin" class="text-xs text-red-500 hover:text-red-700 font-medium transition">Remove</button>
                        </form>
                        <?php else:?><span class="text-xs text-gray-300">current session</span><?php endif;?>
                    </div>
                    <?php endforeach;?>
                </div>
            </div>
        </div>

        <!-- ══════ TAB: REVIEWS ══════ -->
        <div id="tab-reviews" class="tab-panel hidden">
            <h1 class="text-xl font-bold text-gray-800 mb-5">⭐ Customer Reviews</h1>

            <!-- Pending approval -->
            <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5 mb-5">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-sm font-semibold text-gray-700">Pending Approval</h2>
                    <?php if($pending_reviews_count>0):?>
                    <span class="text-xs bg-red-100 text-red-600 px-2.5 py-1 rounded-full font-medium"><?=$pending_reviews_count?> awaiting</span>
                    <?php endif;?>
                </div>
                <?php if($pending_reviews):?>
                <div class="space-y-3">
                    <?php foreach($pending_reviews as $r):?>
                    <div class="border border-amber-200 bg-amber-50 rounded-xl p-4 flex items-start gap-4">
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 mb-2 flex-wrap">
                                <span class="w-8 h-8 bg-amber-200 text-amber-800 rounded-full flex items-center justify-center text-xs font-bold shrink-0">
                                    <?=strtoupper(substr($r['CustomerName'],0,1))?>
                                </span>
                                <span class="font-semibold text-gray-800 text-sm"><?=htmlspecialchars($r['CustomerName'])?></span>
                                <?php if(!empty($r['created_at'])):?>
                                <span class="text-xs text-gray-400"><?=date('M j, Y',strtotime($r['created_at']))?></span>
                                <?php endif;?>
                                <span class="text-xs bg-amber-200 text-amber-800 px-2 py-0.5 rounded-full font-medium">Pending</span>
                            </div>
                            <p class="text-gray-700 text-sm leading-relaxed"><?=htmlspecialchars($r['Comments'])?></p>
                        </div>
                        <div class="flex flex-col gap-2 shrink-0">
                            <form method="POST">
                                <input type="hidden" name="review_id" value="<?=(int)$r['ID']?>">
                                <button name="approve_review" class="bg-green-600 text-white text-xs px-4 py-1.5 rounded-lg hover:bg-green-700 transition font-medium w-full">✓ Approve</button>
                            </form>
                            <form method="POST" onsubmit="return confirm('Delete this review permanently?')">
                                <input type="hidden" name="review_id" value="<?=(int)$r['ID']?>">
                                <button name="delete_review" class="bg-red-100 text-red-600 text-xs px-4 py-1.5 rounded-lg hover:bg-red-200 transition font-medium w-full">✕ Delete</button>
                            </form>
                        </div>
                    </div>
                    <?php endforeach;?>
                </div>
                <?php else:?>
                <div class="text-center py-10 text-gray-400">
                    <div class="text-4xl mb-2">✅</div>
                    <p class="text-sm">No reviews pending approval.</p>
                </div>
                <?php endif;?>
            </div>

            <!-- Published reviews -->
            <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
                <h2 class="text-sm font-semibold text-gray-700 mb-4">Published Reviews (<?=count($approved_reviews)?>)</h2>
                <?php if($approved_reviews):?>
                <div class="space-y-3">
                    <?php foreach($approved_reviews as $r):?>
                    <div class="border border-green-100 rounded-xl p-4 flex items-start gap-4 hover:bg-green-50 transition">
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 mb-1.5 flex-wrap">
                                <span class="w-7 h-7 bg-green-200 text-green-800 rounded-full flex items-center justify-center text-xs font-bold shrink-0">
                                    <?=strtoupper(substr($r['CustomerName'],0,1))?>
                                </span>
                                <span class="font-semibold text-gray-800 text-sm"><?=htmlspecialchars($r['CustomerName'])?></span>
                                <?php if(!empty($r['created_at'])):?>
                                <span class="text-xs text-gray-400"><?=date('M j, Y',strtotime($r['created_at']))?></span>
                                <?php endif;?>
                                <span class="text-xs bg-green-100 text-green-700 px-2 py-0.5 rounded-full ml-auto font-medium">Published</span>
                            </div>
                            <p class="text-gray-600 text-sm leading-relaxed"><?=htmlspecialchars(mb_substr($r['Comments'],0,200))?><?=mb_strlen($r['Comments'])>200?'…':''?></p>
                        </div>
                        <form method="POST" onsubmit="return confirm('Remove this published review?')" class="shrink-0">
                            <input type="hidden" name="review_id" value="<?=(int)$r['ID']?>">
                            <button name="delete_review" class="text-xs text-red-400 hover:text-red-600 font-medium transition">Delete</button>
                        </form>
                    </div>
                    <?php endforeach;?>
                </div>
                <?php else:?>
                <p class="text-gray-400 text-sm text-center py-8">No published reviews yet.</p>
                <?php endif;?>
            </div>
        </div><!-- /tab-reviews -->

    </div><!-- /container -->
</main>
</div><!-- /flex -->

<!-- ═══ EDIT PRODUCT MODAL ═══ -->
<div id="modal-edit-product" class="fixed inset-0 bg-black/60 z-50 hidden flex items-center justify-center p-4" onclick="if(event.target===this)closeModal('modal-edit-product')">
    <div class="bg-white rounded-2xl shadow-2xl p-6 w-full max-w-lg max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between mb-5">
            <h3 class="text-lg font-bold text-gray-800">Edit Product</h3>
            <button onclick="closeModal('modal-edit-product')" class="text-gray-400 hover:text-gray-600 text-xl leading-none">✕</button>
        </div>
        <form method="POST" class="space-y-3">
            <input type="hidden" name="ep_id" id="ep_id">
            <div class="grid grid-cols-2 gap-3">
                <input type="text" name="ep_name" id="ep_name" placeholder="Name *" required class="col-span-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-400">
                <select name="ep_category" id="ep_category" class="border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-400">
                    <option>Plants</option><option>Accessories</option>
                </select>
                <select name="ep_subtype" id="ep_subtype" class="border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-400">
                    <optgroup label="Plants"><option>Indoor</option><option>Outdoor</option></optgroup>
                    <optgroup label="Accessories"><option>Soil</option><option>Glass</option><option>Wooden</option></optgroup>
                </select>
                <input type="number" step="0.01" name="ep_price" id="ep_price" placeholder="Price" required class="border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-400">
                <input type="number" name="ep_stock" id="ep_stock" placeholder="Stock" required class="border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-400">
            </div>
            <input type="text" name="ep_photo" id="ep_photo" placeholder="Image URL (leave blank to keep current)" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-400">
            <textarea name="ep_details" id="ep_details" rows="3" placeholder="Description" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-400 resize-none"></textarea>
            <div class="flex gap-3 pt-1">
                <button type="submit" name="edit_product" class="flex-1 bg-green-600 text-white py-2.5 rounded-xl hover:bg-green-700 transition font-medium text-sm">Save Changes</button>
                <button type="button" onclick="closeModal('modal-edit-product')" class="flex-1 bg-gray-100 text-gray-600 py-2.5 rounded-xl hover:bg-gray-200 transition font-medium text-sm">Cancel</button>
            </div>
        </form>
    </div>
</div>

<!-- ═══ EDIT WORKSHOP MODAL ═══ -->
<div id="modal-edit-workshop" class="fixed inset-0 bg-black/60 z-50 hidden flex items-center justify-center p-4" onclick="if(event.target===this)closeModal('modal-edit-workshop')">
    <div class="bg-white rounded-2xl shadow-2xl p-6 w-full max-w-lg">
        <div class="flex items-center justify-between mb-5">
            <h3 class="text-lg font-bold text-gray-800">Edit Workshop</h3>
            <button onclick="closeModal('modal-edit-workshop')" class="text-gray-400 hover:text-gray-600 text-xl leading-none">✕</button>
        </div>
        <form method="POST" class="space-y-3">
            <input type="hidden" name="ew_id" id="ew_id">
            <input type="text" name="ew_topic" id="ew_topic" placeholder="Topic *" required class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-400">
            <input type="text" name="ew_subject" id="ew_subject" placeholder="Subject" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-400">
            <div class="grid grid-cols-2 gap-3">
                <input type="date" name="ew_date" id="ew_date" class="border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-400">
                <select name="ew_type" id="ew_type" class="border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-400">
                    <option>Free</option><option>Paid</option>
                </select>
                <input type="number" name="ew_points" id="ew_points" placeholder="Points (Paid)" class="border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-400">
                <input type="number" step="0.01" name="ew_price" id="ew_price" placeholder="Price $ (Paid)" class="border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-400">
            </div>
            <div class="flex gap-3 pt-1">
                <button type="submit" name="edit_workshop" class="flex-1 bg-green-600 text-white py-2.5 rounded-xl hover:bg-green-700 transition font-medium text-sm">Save Changes</button>
                <button type="button" onclick="closeModal('modal-edit-workshop')" class="flex-1 bg-gray-100 text-gray-600 py-2.5 rounded-xl hover:bg-gray-200 transition font-medium text-sm">Cancel</button>
            </div>
        </form>
    </div>
</div>

<!-- ═══ EDIT BRANCH MODAL ═══ -->
<div id="modal-edit-branch" class="fixed inset-0 bg-black/60 z-50 hidden flex items-center justify-center p-4" onclick="if(event.target===this)closeModal('modal-edit-branch')">
    <div class="bg-white rounded-2xl shadow-2xl p-6 w-full max-w-lg">
        <div class="flex items-center justify-between mb-5">
            <h3 class="text-lg font-bold text-gray-800">Edit Branch</h3>
            <button onclick="closeModal('modal-edit-branch')" class="text-gray-400 hover:text-gray-600 text-xl leading-none">✕</button>
        </div>
        <form method="POST" class="space-y-3">
            <input type="hidden" name="eb_id" id="eb_id">
            <input type="text" name="eb_name" id="eb_name" placeholder="Branch Name *" required class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-400">
            <input type="text" name="eb_location" id="eb_location" placeholder="Location *" required class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-400">
            <div class="grid grid-cols-2 gap-3">
                <input type="text" name="eb_manager" id="eb_manager" placeholder="Manager" class="border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-400">
                <input type="number" step="0.1" min="0" max="5" name="eb_ratings" id="eb_ratings" placeholder="Ratings (0–5)" class="border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-400">
            </div>
            <textarea name="eb_details" id="eb_details" rows="3" placeholder="Details" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-400 resize-none"></textarea>
            <div class="flex gap-3 pt-1">
                <button type="submit" name="edit_branch" class="flex-1 bg-green-600 text-white py-2.5 rounded-xl hover:bg-green-700 transition font-medium text-sm">Save Changes</button>
                <button type="button" onclick="closeModal('modal-edit-branch')" class="flex-1 bg-gray-100 text-gray-600 py-2.5 rounded-xl hover:bg-gray-200 transition font-medium text-sm">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
const ACTIVE_TAB = <?=json_encode($active_tab)?>;

function setTab(id) {
    document.querySelectorAll('.tab-panel').forEach(p => p.classList.add('hidden'));
    document.querySelectorAll('.nav-item').forEach(n => n.classList.remove('active'));
    const panel = document.getElementById('tab-' + id);
    if (panel) panel.classList.remove('hidden');
    const nav = document.getElementById('nav-' + id);
    if (nav) nav.classList.add('active');
    history.replaceState(null, '', '?tab=' + id);
}

function openEditProduct(id, name, cat, sub, price, stock, details, photo) {
    document.getElementById('ep_id').value = id;
    document.getElementById('ep_name').value = name;
    document.getElementById('ep_category').value = cat;
    document.getElementById('ep_subtype').value = sub;
    document.getElementById('ep_price').value = price;
    document.getElementById('ep_stock').value = stock;
    document.getElementById('ep_details').value = details;
    document.getElementById('ep_photo').value = '';
    document.getElementById('modal-edit-product').classList.remove('hidden');
}

function openEditWorkshop(id, topic, subject, date, type, points, price) {
    document.getElementById('ew_id').value = id;
    document.getElementById('ew_topic').value = topic;
    document.getElementById('ew_subject').value = subject;
    document.getElementById('ew_date').value = date;
    document.getElementById('ew_type').value = type;
    document.getElementById('ew_points').value = points || '';
    document.getElementById('ew_price').value = price || '';
    document.getElementById('modal-edit-workshop').classList.remove('hidden');
}

function openEditBranch(id, name, loc, mgr, ratings, details) {
    document.getElementById('eb_id').value = id;
    document.getElementById('eb_name').value = name;
    document.getElementById('eb_location').value = loc;
    document.getElementById('eb_manager').value = mgr;
    document.getElementById('eb_ratings').value = ratings;
    document.getElementById('eb_details').value = details;
    document.getElementById('modal-edit-branch').classList.remove('hidden');
}

function closeModal(id) {
    document.getElementById(id).classList.add('hidden');
}

function filterOrders(status) {
    document.querySelectorAll('.order-row').forEach(r => {
        const s = r.dataset.status;
        const show = status === 'all' || s === status || r.dataset.ostatus === status.toLowerCase();
        r.style.display = show ? '' : 'none';
    });
    document.querySelectorAll('.order-flt').forEach(b => {
        b.classList.remove('bg-green-600','text-white');
        b.classList.add('bg-gray-100','text-gray-600');
    });
    const btn = document.getElementById('flt-' + status);
    if (btn) { btn.classList.add('bg-green-600','text-white'); btn.classList.remove('bg-gray-100','text-gray-600'); }
}

setTab(ACTIVE_TAB);
filterOrders('all');
</script>
<?php $conn->close(); ?>
</body>
</html>
