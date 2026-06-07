<?php
// Файл counter.php - положите в ту же папку что и index.html

$dataFile = 'online_data.json';
$timeout = 60; // секунд бездействия (60 секунд)

// Загружаем текущие данные
if (file_exists($dataFile)) {
    $data = json_decode(file_get_contents($dataFile), true);
} else {
    $data = ['visitors' => [], 'online' => 0];
}

$action = isset($_POST['action']) ? $_POST['action'] : '';
$visitorId = isset($_POST['visitor_id']) ? $_POST['visitor_id'] : '';

if ($action === 'register') {
    // Регистрация нового посетителя
    if ($visitorId && !isset($data['visitors'][$visitorId])) {
        $data['visitors'][$visitorId] = time();
        $data['online']++;
    }
    
} elseif ($action === 'heartbeat') {
    // Обновление времени активности
    if ($visitorId && isset($data['visitors'][$visitorId])) {
        $data['visitors'][$visitorId] = time();
    }
    
} elseif ($action === 'unregister') {
    // Удаление посетителя
    if ($visitorId && isset($data['visitors'][$visitorId])) {
        unset($data['visitors'][$visitorId]);
        $data['online']--;
    }
}

// Удаляем неактивных посетителей (таймаут)
$currentTime = time();
$changed = false;
foreach ($data['visitors'] as $id => $lastSeen) {
    if ($currentTime - $lastSeen > $timeout) {
        unset($data['visitors'][$id]);
        $data['online']--;
        $changed = true;
    }
}

if ($changed) {
    $data['online'] = max(0, $data['online']);
}

// Сохраняем данные
file_put_contents($dataFile, json_encode($data));

// Возвращаем количество онлайн
header('Content-Type: application/json');
echo json_encode(['online' => $data['online']]);
?>
