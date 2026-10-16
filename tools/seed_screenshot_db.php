<?php
/**
 * Builds `mebel_crm_shots`, the throwaway database the site's app screenshots are taken from.
 *
 *   D:\xampp\php\php.exe tools\seed_screenshot_db.php
 *
 * The workshop in it is invented — every client, worker and phone number below is made up —
 * because a screenshot on a public page must never show a real customer. That is also why it
 * is a database of its own rather than more rows in `mebel_crm_design`: that one is a working
 * preview for UI changes, and this one is staged to look like a busy, healthy month.
 *
 * The schema is copied from `mebel_crm_design` (structure only, never data), so it tracks the
 * app without this file restating any DDL. Re-running drops and rebuilds the whole thing.
 *
 * Sign-in: username `direktor`, with the password this script writes to
 * tools/screenshot-db.local (git-ignored). Serve the app against it with
 *   set USTAERP_DB_NAME=mebel_crm_shots&& php -S localhost:8770 -t ..\UstaCRM\prototype
 */

date_default_timezone_set('Asia/Tashkent');

const DB     = 'mebel_crm_shots';
const SOURCE = 'mebel_crm_design';
const MYSQL  = 'D:/xampp/mysql/bin/';

// The one guard that matters: nothing in here may ever touch the live database.
if (DB === 'mebel_crm' || SOURCE === 'mebel_crm') exit("refusing to touch the live database\n");

$pdo = new PDO('mysql:host=127.0.0.1;charset=utf8mb4', 'root', '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$pdo->exec('DROP DATABASE IF EXISTS `' . DB . '`');
$pdo->exec('CREATE DATABASE `' . DB . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');

$schema = shell_exec('"' . MYSQL . 'mysqldump.exe" -u root --no-data --skip-comments ' . SOURCE);
if (!$schema || strpos($schema, 'CREATE TABLE') === false) exit("could not read the schema of " . SOURCE . "\n");
$pdo->exec('USE `' . DB . '`');
$pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
$pdo->exec($schema);

// Configuration rows the app expects to find — roles, which role works which stage, the
// default piece rates, product templates. None of it is about a customer.
foreach (['roles', 'stage_roles', 'stage_rates', 'product_templates', 'template_stages', 'template_materials', 'currency_rates'] as $t) {
    $pdo->exec("INSERT INTO `$t` SELECT * FROM `" . SOURCE . "`.`$t`");
}

$T = 1;
$today = new DateTimeImmutable('today');
$d  = fn(int $days) => $today->modify(($days >= 0 ? '+' : '') . $days . ' days')->format('Y-m-d');
$dt = fn(int $days, string $time = '10:00:00') => $d($days) . ' ' . $time;
$ins = function (string $table, array $row) use ($pdo): int {
    $cols = array_keys($row);
    $pdo->prepare("INSERT INTO `$table` (`" . implode('`,`', $cols) . '`) VALUES (' . rtrim(str_repeat('?,', count($cols)), ',') . ')')
        ->execute(array_values($row));
    return (int) $pdo->lastInsertId();
};

$ins('tenants', ['id' => $T, 'name' => 'Usta Mebel', 'slug' => 'default', 'is_active' => 1, 'city' => 'Toshkent']);
$ins('license', ['id' => 1, 'installed_at' => $dt(-90), 'trial_ends_at' => $d(-76), 'paid_until' => $d(200),
                 'worker_seat_limit' => 25, 'device_limit' => 10, 'is_suspended' => 0]);

// ---- people -------------------------------------------------------------------------------
$password = rtrim(strtr(base64_encode(random_bytes(12)), '+/', 'xy'), '=');
file_put_contents(__DIR__ . '/screenshot-db.local', "mebel_crm_shots sign-in\nusername: direktor\npassword: $password\n");
$hash = password_hash($password, PASSWORD_DEFAULT);

$people = [
    ['direktor',  'Bobur Karimov',      'director'],
    ['menejer',   'Dilnoza Yusupova',   'rukovoditel'],
    ['sex',       'Akmal Rahimov',      'nachalnik-tsekha'],
    ['zamer',     'Sardor Tursunov',    'zamershchik'],
    ['kto',       'Madina Aliyeva',     'kto'],
    ['ombor',     'Jasur Ergashev',     'warehouse'],
    ['taminot',   'Shoxrux Qodirov',    'procurement'],
    ['kesish',    'Otabek Nazarov',     'cutting'],
    ['kromka',    'Rustam Sobirov',     'edging'],
    ['parma',     'Ulugbek Hamidov',    'drilling'],
    ['cnc',       'Javohir Mirzayev',   'cnc-milling'],
    ['yigish',    'Farrux Islomov',     'joinery'],
    ['bezak',     'Nodir Xolmatov',     'finishing'],
    ['qoplama',   'Gulnora Saidova',    'upholstery'],
    ['sifat',     'Sherzod Abdullayev', 'quality-control'],
    ['qadoq',     'Bekzod Usmonov',     'packaging'],
    ['yetkazish', 'Elyor Raximov',      'delivery'],
    ['yuklash',   'Doston Kamolov',     'loader'],
    ['ornatish',  'Anvar Jurayev',      'installer'],
];
$byRole = [];
foreach ($people as [$u, $name, $role]) {
    $id = $ins('users', ['tenant_id' => $T, 'username' => $u, 'password_hash' => $hash, 'full_name' => $name,
                         'role_key' => $role, 'is_active' => 1, 'created_at' => $dt(-90)]);
    $byRole[$role] = ['id' => $id, 'name' => $name];
}
$mgr = $byRole['rukovoditel'];

// ---- materials ----------------------------------------------------------------------------
$materials = [
    // name, category, unit, stock, reorder at, cost, supplier
    ['LDSP 18 mm, oq',            'Plita',      'list', 64,  20,  420000, 'Kronospan Toshkent'],
    ['LDSP 18 mm, dub sonoma',    'Plita',      'list', 38,  15,  455000, 'Kronospan Toshkent'],
    ['LDSP 16 mm, antrasit',      'Plita',      'list', 22,  12,  410000, 'Egger Markaz'],
    ['MDF 18 mm, bo\'yoq ostiga', 'Plita',      'list', 17,  10,  610000, 'Egger Markaz'],
    ['DVP 3 mm',                  'Plita',      'list', 45,  15,   95000, 'Kronospan Toshkent'],
    ['Dub shpon',                 'Plita',      'list',  9,   8,  980000, 'Shpon Servis'],
    ['Kromka PVX 2 mm, oq',       'Kromka',     'm',   850, 300,    3200, 'Rehau Uz'],
    ['Kromka PVX 0.4 mm, oq',     'Kromka',     'm',   190, 300,    1100, 'Rehau Uz'],
    ['Kromka 2 mm, dub sonoma',   'Kromka',     'm',   420, 200,    3600, 'Rehau Uz'],
    ['Petlya Blum, yumshoq',      'Furnitura',  'dona',  0, 100,   38000, 'Blum Distribyutor'],
    ['Yo\'naltiruvchi Tandembox', 'Furnitura',  'dona',  0,  40,  265000, 'Blum Distribyutor'],
    ['Tutqich, qora 160 mm',      'Furnitura',  'dona', 140, 60,   18000, 'Furnitura Bozori'],
    ['Konfirmat 7×50',            'Mahkamlash', 'dona', 3200, 1000,  250, 'Furnitura Bozori'],
    ['Evrovint 6.3×13',           'Mahkamlash', 'dona', 5400, 2000,  120, 'Furnitura Bozori'],
    ['Akril bo\'yoq, mat oq',     'Bo\'yoq',    'l',    26,  10,   145000, 'Tikkurila Uz'],
    ['Lak, poliuretan',           'Bo\'yoq',    'l',    14,   8,   165000, 'Tikkurila Uz'],
    ['Kupe profili, kumush',      'Profil',     'm',    36,  30,    85000, 'Alu Profil'],
    ['Oyna 4 mm',                 'Shisha',     'm²',  4.5,   6,   190000, 'Shisha Uy'],
];
$matIds = [];
foreach ($materials as [$name, $cat, $unit, $stock, $th, $cost, $sup]) {
    $matIds[] = $ins('materials', ['tenant_id' => $T, 'name' => $name, 'category' => $cat, 'unit' => $unit,
        'stock_qty' => $stock, 'reorder_threshold' => $th, 'cost_per_unit' => $cost, 'supplier_name' => $sup,
        'supplier_contact' => '+998 71 2' . sprintf('%02d %02d %02d', rand(0, 99), rand(0, 99), rand(0, 99)),
        'is_active' => 1, 'created_at' => $dt(-90), 'updated_at' => $dt(-2)]);
}

// ---- clients and orders -------------------------------------------------------------------
$stages = ['Measurement', 'Drawing / Design', 'Workshop chief', 'Warehouse', 'Procurement', 'Cutting', 'Edging',
           'Drilling', 'CNC milling', 'Joinery', 'Finishing', 'Upholstery', 'Quality control', 'Packaging',
           'Delivery', 'Loader', 'Installer'];
$stageRole = ['Measurement' => 'zamershchik', 'Drawing / Design' => 'kto', 'Workshop chief' => 'nachalnik-tsekha',
    'Warehouse' => 'warehouse', 'Procurement' => 'procurement', 'Cutting' => 'cutting', 'Edging' => 'edging',
    'Drilling' => 'drilling', 'CNC milling' => 'cnc-milling', 'Joinery' => 'joinery', 'Finishing' => 'finishing',
    'Upholstery' => 'upholstery', 'Quality control' => 'quality-control', 'Packaging' => 'packaging',
    'Delivery' => 'delivery', 'Loader' => 'loader', 'Installer' => 'installer'];
// Which stages a kind of job skips: a plain cabinet needs neither CNC nor upholstery.
$skip = [
    'kitchen'  => ['Upholstery'],
    'wardrobe' => ['Upholstery', 'CNC milling'],
    'office'   => ['Upholstery', 'Finishing'],
    'kids'     => [],
    'hall'     => ['Upholstery', 'CNC milling'],
    'tv'       => ['Upholstery'],
    'bath'     => ['Upholstery', 'CNC milling'],
    'bed'      => ['CNC milling'],
];

$orders = [
    // client, phone, district, kind, product, cost, ordered (days ago), deadline (days from now), done stages, status
    ['Aziza Karimova',     '90 123 45 67', 'Yunusobod',   'kitchen',  'Oq oshxona',       42000000, 34,  -3, 99, 'ready'],
    ['Rustam Toshmatov',   '93 210 77 08', 'Chilonzor',   'wardrobe', 'Kiyim xonasi',        33900000, 21,   6, 10, 'in_progress'],
    ['Nodira Xasanova',    '97 455 12 90', 'Mirzo Ulug\'bek', 'hall', 'Dahliz mebeli',                    9800000,  9,  12,  2, 'in_progress'],
    ['Timur Abdurahmonov', '99 870 33 21', 'Shayxontohur', 'office',  'Ofis stollari ×6',                27300000, 26,   2, 12, 'in_progress'],
    ['Malika Rasulova',    '90 777 14 52', 'Yakkasaroy',  'kids',     'Bolalar xonasi to\'plami',        15600000, 18,   9,  7, 'in_progress'],
    ['Jamshid Ortiqov',    '94 301 66 09', 'Sergeli',     'bath',     'Oynali vanna tumbasi',            6200000, 12,   4,  9, 'in_progress'],
    ['Sevara Mahmudova',   '91 512 40 73', 'Olmazor',     'tv',       'Mehmonxona TV devori',            12400000,  3,  21,  0, 'not_started'],
    ['Bahodir Ismoilov',   '93 645 28 11', 'Yunusobod',   'kitchen',  'Antrasit oshxona',    51800000, 30,   1, 14, 'in_progress'],
    ['Zarina Qosimova',    '97 118 92 35', 'Mirobod',     'wardrobe', 'Oynali kupe shkaf',        18500000, 15,   8,  6, 'in_progress'],
    ['Ilhom Saidov',       '90 340 51 27', 'Uchtepa',     'bed',      'Yotoqxona to\'plami',             24700000, 22,   5, 11, 'in_progress'],
    ['Feruza Nematova',    '99 204 63 18', 'Bektemir',    'kitchen',  'Katta oshxona',              38600000, 41, -12, 99, 'ready'],
    ['Sanjar Yo\'ldoshev', '93 777 05 44', 'Chilonzor',   'office',   'Rahbar kabineti',                 21900000, 38,  -8, 99, 'ready'],
    ['Kamola Ahmedova',    '94 980 17 62', 'Yashnobod',   'kids',     'O\'smir xonasi',                  13200000,  6,  16,  1, 'in_progress'],
    ['Oybek Murodov',      '91 266 38 90', 'Mirzo Ulug\'bek', 'hall', 'Dahliz + poyabzal javoni',         7400000, 11,   3,  8, 'in_progress'],
    ['Lola Xudoyberdiyeva','90 619 72 03', 'Yakkasaroy',  'tv',       'Shpon TV devori',                16900000, 20,  -1, 12, 'in_progress'],
    ['Davron Pulatov',     '97 352 84 46', 'Sergeli',     'wardrobe', 'Uch eshikli shkaf',          11300000,  2,  25,  0, 'not_started'],
    ['Gulchehra Valiyeva', '99 448 21 75', 'Olmazor',     'kitchen',  'Yashil oshxona',             36400000, 16,  11,  6, 'in_progress'],
    ['Murod Hakimov',      '93 801 59 32', 'Yunusobod',   'bath',     'Vanna mebeli ×2',                  8900000, 47, -20, 99, 'ready'],
];
$orderIds = [];
// A year of finished work before this month's, so Analytics draws a workshop with a history
// rather than one that opened in September. Inserted first so they take the lower order
// numbers, and the Director's screen, newest first, still opens on the jobs under way.
mt_srand(7);
$past = [
    ['kitchen', 'Oshxona to\'plami', 28, 52], ['wardrobe', 'Kupe shkaf', 11, 24], ['office', 'Ofis mebeli', 14, 30],
    ['kids', 'Bolalar xonasi', 10, 19], ['hall', 'Dahliz mebeli', 6, 12], ['tv', 'TV devori', 9, 18],
    ['bath', 'Vanna tumbasi', 4, 9], ['bed', 'Yotoqxona to\'plami', 17, 29],
];
$first = ['Aziz', 'Barno', 'Dilshod', 'Gavhar', 'Hamid', 'Iroda', 'Komil', 'Laylo', 'Mansur', 'Nargiza', 'Odil', 'Parvina',
          'Rahim', 'Shahnoza', 'Temur', 'Umida', 'Vohid', 'Xurshida', 'Yusuf', 'Zebo'];
$last = ['Aminov', 'Boboyev', 'Davletov', 'Fayziyev', 'G\'aniyev', 'Haydarov', 'Jo\'rayev', 'Latipov', 'Normatov', 'Qurbonov',
         'Rajabov', 'Sultonov', 'To\'xtayev', 'Umarov', 'Xoliqov', 'Yoqubov'];
$districts = ['Yunusobod', 'Chilonzor', 'Mirzo Ulug\'bek', 'Yakkasaroy', 'Sergeli', 'Olmazor', 'Mirobod', 'Uchtepa', 'Yashnobod', 'Bektemir'];
for ($month = 11; $month >= 2; $month--) {
    // Slow winter, a busy spring and late summer — the shape a furniture order book has.
    $count = [11 => 4, 10 => 3, 9 => 4, 8 => 5, 7 => 7, 6 => 8, 5 => 7, 4 => 6, 3 => 6, 2 => 8][$month];
    for ($j = 0; $j < $count; $j++) {
        [$kind, $product, $lo, $hi] = $past[mt_rand(0, count($past) - 1)];
        $ago = $month * 30 + mt_rand(-12, 12);
        $took = mt_rand(18, 27);
        $cost = mt_rand($lo, $hi) * 1000000 + mt_rand(0, 9) * 100000;
        $name = $first[mt_rand(0, count($first) - 1)] . ' ' . $last[mt_rand(0, count($last) - 1)];
        $phone = sprintf('9%d %03d %02d %02d', mt_rand(0, 9), mt_rand(100, 999), mt_rand(10, 99), mt_rand(10, 99));
        $district = $districts[mt_rand(0, count($districts) - 1)];
        $clientId = $ins('clients', ['tenant_id' => $T, 'name' => $name, 'contact' => '+998 ' . $phone,
            'address' => "Toshkent, $district tumani", 'is_active' => 1, 'created_by' => $mgr['id'], 'created_at' => $dt(-$ago, '09:20:00')]);
        $orderId = $ins('orders', ['tenant_id' => $T, 'created_by' => $mgr['id'], 'accepted_by' => $byRole['nachalnik-tsekha']['id'],
            'accepted_at' => $dt(-$ago + 1, '11:00:00'), 'order_date' => $d(-$ago), 'deadline' => $d(-$ago + $took + 2), 'status' => 'ready',
            'client_id' => $clientId, 'client_name' => $name, 'client_contact' => '+998 ' . $phone,
            'delivery_address' => "Toshkent, $district tumani", 'furniture_type' => $product, 'cost' => $cost,
            'quote_approved_cost' => $cost, 'quote_approved_at' => $dt(-$ago + 1, '15:30:00'), 'quote_approved_by' => $mgr['id'],
            'workshop_chief' => $byRole['nachalnik-tsekha']['name'], 'created_at' => $dt(-$ago, '09:30:00'), 'updated_at' => $dt(-$ago + $took)]);
        $own = array_values(array_diff($stages, $skip[$kind]));
        foreach ($own as $k => $stage) {
            $who = $byRole[$stageRole[$stage]];
            $at = -$ago + 1 + (int) floor($took * ($k + 1) / count($own));
            $ins('order_stages', ['tenant_id' => $T, 'order_id' => $orderId, 'stage_name' => $stage, 'sort_order' => array_search($stage, $stages),
                'status' => 'completed', 'responsible' => $who['name'], 'user_id' => $who['id'],
                'started_at' => $dt($at - 1, '09:10:00'), 'finished_at' => $dt($at, '16:40:00')]);
        }
        $deposit = round($cost * 0.5, -5);
        $ins('order_payments', ['tenant_id' => $T, 'order_id' => $orderId, 'amount' => $deposit, 'method' => ['cash', 'card', 'transfer'][$j % 3],
            'paid_on' => $d(-$ago + 1), 'note' => 'Oldindan to\'lov', 'created_by' => $mgr['id'], 'created_at' => $dt(-$ago + 1, '15:40:00')]);
        $ins('order_payments', ['tenant_id' => $T, 'order_id' => $orderId, 'amount' => $cost - $deposit, 'method' => 'cash',
            'paid_on' => $d(-$ago + $took), 'note' => 'Yakuniy to\'lov', 'created_by' => $mgr['id'], 'created_at' => $dt(-$ago + $took, '17:10:00')]);
    }
}

// The app counts a stage late once the days since acceptance pass the running total of
// STAGE_SLA_DAYS (api/overdue.php) up to it. So an order under way is dated back by exactly the
// SLA of the stages it has finished: on schedule. The two with a deadline at most a day out are
// pushed three days further back, so the board shows a little red, as a real one would.
$sla = ['Measurement' => 1, 'Drawing / Design' => 3, 'Workshop chief' => 1, 'Warehouse' => 1, 'Procurement' => 2,
        'Cutting' => 1, 'Edging' => 1, 'Drilling' => 1, 'CNC milling' => 2, 'Joinery' => 2, 'Finishing' => 2,
        'Upholstery' => 2, 'Quality control' => 1, 'Packaging' => 1, 'Delivery' => 2, 'Loader' => 1, 'Installer' => 1];
foreach ($orders as $i => [$client, $phone, $district, $kind, $product, $cost, $ago, $due, $doneCount, $status]) {
    if ($status !== 'ready') {
        $passed = 0;
        foreach (array_slice($stages, 0, array_search(array_values(array_diff($stages, $skip[$kind]))[min($doneCount, count(array_diff($stages, $skip[$kind])) - 1)], $stages)) as $s) {
            $passed += $sla[$s];
        }
        $ago = $doneCount === 0 ? 1 : $passed + 1 + ($due <= 1 ? 3 : 0);
        $due = $due <= 1 ? $due : 28 - $ago + ($i % 4) * 2;
    }
    $clientId = $ins('clients', ['tenant_id' => $T, 'name' => $client, 'contact' => '+998 ' . $phone,
        'address' => "Toshkent, $district tumani", 'is_active' => 1, 'created_by' => $mgr['id'], 'created_at' => $dt(-$ago, '09:20:00')]);
    $mat = $kind === 'kitchen' ? [0, 6, 9] : ($kind === 'wardrobe' ? [1, 8, 16] : [2, 6, 11]);
    $orderId = $ins('orders', ['tenant_id' => $T, 'created_by' => $mgr['id'], 'accepted_by' => $byRole['nachalnik-tsekha']['id'],
        'accepted_at' => $dt(-$ago + 1, '11:00:00'), 'order_date' => $d(-$ago), 'deadline' => $d($due), 'status' => $status,
        'client_id' => $clientId, 'client_name' => $client, 'client_contact' => '+998 ' . $phone,
        'delivery_address' => "Toshkent, $district tumani", 'furniture_type' => $product, 'cost' => $cost,
        'quote_approved_cost' => $cost, 'quote_approved_at' => $dt(-$ago + 1, '15:30:00'), 'quote_approved_by' => $mgr['id'],
        'body_material' => $materials[$mat[0]][0], 'body_material_id' => $matIds[$mat[0]],
        'facade_material' => $materials[$mat[0]][0], 'facade_material_id' => $matIds[$mat[0]],
        'hardware' => $materials[$mat[2]][0], 'hardware_id' => $matIds[$mat[2]],
        'workshop_chief' => $byRole['nachalnik-tsekha']['name'],
        'created_at' => $dt(-$ago, '09:30:00'), 'updated_at' => $dt(-1)]);
    $orderIds[] = $orderId;

    $ins('order_events', ['tenant_id' => $T, 'order_id' => $orderId, 'event_type' => 'order_created', 'actor_id' => $mgr['id'],
        'actor_name' => $mgr['name'], 'subject' => $product, 'created_at' => $dt(-$ago, '09:30:00')]);

    // Stages: the first $doneCount are finished, spread evenly between order date and today;
    // the next one is under way (unless the order is waiting to start), the rest wait.
    $own = array_values(array_diff($stages, $skip[$kind]));
    $n = count($own);
    foreach ($own as $k => $name) {
        $who = $byRole[$stageRole[$name]];
        $row = ['tenant_id' => $T, 'order_id' => $orderId, 'stage_name' => $name, 'sort_order' => array_search($name, $stages),
                'status' => 'not_started', 'pay_rate' => null];
        if ($k < min($doneCount, $n)) {
            $startAgo = (int) round($ago - ($ago * $k / max(1, min($doneCount, $n))));
            $endAgo = max(0, $startAgo - 1);
            $row += ['responsible' => $who['name'], 'user_id' => $who['id'],
                     'started_at' => $dt(-$startAgo, '09:10:00'), 'finished_at' => $dt(-$endAgo, '16:40:00')];
            $row['status'] = 'completed';
            $ins('order_stages', $row);
            $ins('order_events', ['tenant_id' => $T, 'order_id' => $orderId, 'event_type' => 'stage_completed',
                'actor_id' => $who['id'], 'actor_name' => $who['name'], 'subject' => $name, 'created_at' => $dt(-$endAgo, '16:40:00')]);
        } elseif ($k === $doneCount && $status === 'in_progress') {
            $row += ['responsible' => $who['name'], 'user_id' => $who['id'], 'started_at' => $dt(0, '08:45:00')];
            $row['status'] = 'in_progress';
            $ins('order_stages', $row);
        } else {
            $ins('order_stages', $row);
        }
    }

    // Money: a deposit when the quote is approved, the rest on hand-over for finished jobs and
    // a part-payment for some jobs in the middle — the realistic shape of a workshop's book.
    $deposit = round($cost * 0.5, -5);
    $ins('order_payments', ['tenant_id' => $T, 'order_id' => $orderId, 'amount' => $deposit, 'method' => ['cash', 'card', 'transfer'][$i % 3],
        'paid_on' => $d(-$ago + 1), 'note' => 'Oldindan to\'lov', 'created_by' => $mgr['id'], 'created_at' => $dt(-$ago + 1, '15:40:00')]);
    if ($status === 'ready') {
        $ins('order_payments', ['tenant_id' => $T, 'order_id' => $orderId, 'amount' => $cost - $deposit, 'method' => 'cash',
            'paid_on' => $d($due), 'note' => 'Yakuniy to\'lov', 'created_by' => $mgr['id'], 'created_at' => $dt($due, '17:10:00')]);
    } elseif ($doneCount >= 10) {
        $ins('order_payments', ['tenant_id' => $T, 'order_id' => $orderId, 'amount' => round($cost * 0.3, -5), 'method' => 'transfer',
            'paid_on' => $d(-3), 'note' => 'Ikkinchi qism', 'created_by' => $mgr['id'], 'created_at' => $dt(-3, '12:00:00')]);
    }

    foreach ($mat as $m) {
        $qty = $materials[$m][2] === 'list' ? rand(6, 18) : ($materials[$m][2] === 'm' ? rand(40, 120) : rand(8, 30));
        $ins('order_materials', ['tenant_id' => $T, 'order_id' => $orderId, 'material_id' => $matIds[$m],
            'stage_name' => $materials[$m][1] === 'Plita' ? 'Cutting' : ($materials[$m][1] === 'Kromka' ? 'Edging' : 'Joinery'),
            'quantity_required' => $qty, 'quantity_consumed' => $doneCount > 6 ? $qty : 0, 'created_at' => $dt(-$ago + 1)]);
    }
}

// ---- expenses over the last two months ----------------------------------------------------
$expenses = [
    [-58, 'rent', 9000000, 'transfer', 'Sex ijarasi, avgust'], [-28, 'rent', 9000000, 'transfer', 'Sex ijarasi, sentyabr'],
    [-55, 'materials', 18400000, 'transfer', 'Kronospan — LDSP'], [-33, 'materials', 12600000, 'transfer', 'Blum furnitura'],
    [-14, 'materials', 21300000, 'transfer', 'Kronospan + Egger plitalar'], [-5, 'materials', 4800000, 'cash', 'Kromka, Rehau'],
    [-50, 'utilities', 2350000, 'card', 'Elektr energiya'], [-20, 'utilities', 2610000, 'card', 'Elektr energiya'],
    [-45, 'transport', 1200000, 'cash', 'Yetkazib berish, yoqilg\'i'], [-17, 'transport', 1450000, 'cash', 'Yetkazib berish, yoqilg\'i'],
    [-40, 'tools', 3100000, 'card', 'Arra disklari, frezalar'], [-8, 'tools', 950000, 'cash', 'Parma uchlari'],
    [-30, 'wages', 38500000, 'cash', 'Sentyabr ish haqi'], [-25, 'tax', 4700000, 'transfer', 'Soliq, III chorak'],
];
foreach ($expenses as [$ago, $cat, $amount, $method, $note]) {
    $ins('expenses', ['tenant_id' => $T, 'spent_on' => $d($ago), 'amount' => $amount, 'method' => $method, 'category' => $cat,
        'note' => $note, 'created_by' => $byRole['director']['id'], 'created_at' => $dt($ago, '18:00:00')]);
}

// Everyone has read their notifications: a "99+" badge in a product shot reads as a backlog, while a few new ones read as a working day.
$pdo->exec('UPDATE users SET notifications_seen_id = (SELECT GREATEST(COALESCE(MAX(id), 0) - 3, 0) FROM order_events)');

$pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
printf("%s rebuilt: %d orders, %d people, %d materials. Sign-in details in tools/screenshot-db.local\n",
    DB, count($orderIds), count($people), count($matIds));
