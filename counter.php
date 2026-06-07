<?php
// steam_login.php
session_start();

// Настройки Steam API
$apiKey = 'YOUR_STEAM_API_KEY'; // Получите ключ здесь: https://steamcommunity.com/dev/apikey
$returnUrl = 'http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['SCRIPT_NAME']) . '/index.html';

// Получаем параметры от Steam
if (isset($_GET['openid_mode']) && $_GET['openid_mode'] == 'id_res') {
    // Проверяем OpenID ответ
    $params = array(
        'openid.assoc_handle' => $_GET['openid_assoc_handle'],
        'openid.signed' => $_GET['openid_signed'],
        'openid.sig' => $_GET['openid_sig'],
        'openid.ns' => 'http://specs.openid.net/auth/2.0',
    );
    
    $signed = explode(',', $_GET['openid_signed']);
    foreach ($signed as $item) {
        $value = $_GET['openid_' . str_replace('.', '_', $item)];
        $params['openid.' . $item] = $value;
    }
    $params['openid.mode'] = 'check_authentication';
    
    // Отправляем запрос на проверку
    $data = http_build_query($params);
    $context = stream_context_create(array(
        'http' => array(
            'method' => 'POST',
            'header' => "Content-type: application/x-www-form-urlencoded\r\nContent-Length: " . strlen($data) . "\r\n",
            'content' => $data
        )
    ));
    
    $result = file_get_contents('https://steamcommunity.com/openid/login', false, $context);
    
    if (preg_match('/is_valid:true/', $result)) {
        // Получаем SteamID
        preg_match('/https:\/\/steamcommunity\.com\/openid\/id\/(\d+)/', $_GET['openid_claimed_id'], $matches);
        $steamId = $matches[1];
        
        // Получаем данные пользователя через Steam API
        $apiUrl = "https://api.steampowered.com/ISteamUser/GetPlayerSummaries/v0002/?key={$apiKey}&steamids={$steamId}";
        $userData = file_get_contents($apiUrl);
        $userData = json_decode($userData, true);
        
        if (isset($userData['response']['players'][0])) {
            $player = $userData['response']['players'][0];
            $playerName = urlencode($player['personaname']);
            $avatar = urlencode($player['avatar']);
            $token = md5($steamId . time() . 'skinsynth_secret');
            
            // Сохраняем сессию
            $_SESSION['steam_id'] = $steamId;
            $_SESSION['steam_name'] = $player['personaname'];
            $_SESSION['steam_avatar'] = $player['avatar'];
            
            // Перенаправляем на главную страницу с параметрами
            header("Location: index.html?steam_id={$steamId}&player={$playerName}&avatar={$avatar}&token={$token}");
            exit;
        }
    }
    header("Location: index.html?error=1");
    exit;
}

// Начало авторизации
if (!isset($_GET['openid_mode'])) {
    // Формируем OpenID запрос
    $openId = 'https://steamcommunity.com/openid/login';
    $params = array(
        'openid.ns' => 'http://specs.openid.net/auth/2.0',
        'openid.mode' => 'checkid_setup',
        'openid.return_to' => $returnUrl,
        'openid.realm' => 'http://' . $_SERVER['HTTP_HOST'],
        'openid.identity' => 'http://specs.openid.net/auth/2.0/identifier_select',
        'openid.claimed_id' => 'http://specs.openid.net/auth/2.0/identifier_select',
    );
    
    $redirectUrl = $openId . '?' . http_build_query($params);
    header("Location: " . $redirectUrl);
    exit;
}
?>
