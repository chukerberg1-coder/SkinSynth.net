<?php
// Файл counter.php - положите в ту же папку что и index.html
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

$dataFile = dirname(__FILE__) . '/online_data.json';
$timeout = 60; // секунд бездействия

// Создаем файл если не существует
if (!file_exists($dataFile)) {
    $defaultData = ['visitors' => [], 'online' => 0];
    file_put_contents($dataFile, json_encode($defaultData));
    chmod($dataFile, 0666);
}

// Загружаем данные
$data = json_decode(file_get_contents($dataFile), true);
if (!$data) {
    $data = ['visitors' => [], 'online' => 0];
}

$action = isset($_POST['action']) ? $_POST['action'] : '';
$visitorId = isset($_POST['visitor_id']) ? $_POST['visitor_id'] : '';

$currentTime = time();

// Очистка неактивных
$cleaned = false;
foreach ($data['visitors'] as $id => $lastSeen) {
    if ($currentTime - $lastSeen > $timeout) {
        unset($data['visitors'][$id]);
        $cleaned = true;
    }
}
if ($cleaned) {
    $data['online'] = count($data['visitors']);
}

if ($action === 'register') {
    if ($visitorId && !isset($data['visitors'][$visitorId])) {
        $data['visitors'][$visitorId] = $currentTime;
        $data['online'] = count($data['visitors']);
    }
} elseif ($action === 'heartbeat') {
    if ($visitorId) {
        $data['visitors'][$visitorId] = $currentTime;
        $data['online'] = count($data['visitors']);
    }
} elseif ($action === 'unregister') {
    if ($visitorId && isset($data['visitors'][$visitorId])) {
        unset($data['visitors'][$visitorId]);
        $data['online'] = count($data['visitors']);
    }
}

// Сохраняем данные
file_put_contents($dataFile, json_encode($data));
if (file_exists($dataFile)) {
    chmod($dataFile, 0666);
}

echo json_encode(['online' => $data['online'], 'success' => true]);
?>
