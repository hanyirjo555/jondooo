<?php

// Function to clear screen based on OS
function clearScreen() {
    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
        system('cls');
    } else {
        system('clear');
    }
}

// Function to generate random user agent
function generateUserAgent() {
    $os = ['Windows', 'Linux', 'iOS', 'Android'];
    $versions = ['8', '9', '10', '11', '12', '13', '14'];
    $devices = ['Samsung', 'Motorola', 'Xiaomi', 'Huawei', 'OnePlus'];

    $selectedOs = $os[array_rand($os)];

    if ($selectedOs === 'Android') {
        $version = $versions[array_rand($versions)];
        $device = $devices[array_rand($devices)];
        return "Mozilla/5.0 (Linux; Android $version; $device) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/89.0.4389.90 Mobile Safari/537.36";
    } else {
        return "Mozilla/5.0 ($selectedOs NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/89.0.4389.90 Safari/537.36";
    }
}

// Function to print colored text
function printColored($text, $color) {
    return "\033[" . $color . "m" . $text . "\033[0m";
}

// Color codes
$green = "32";
$red = "31";
$yellow = "33";
$blue = "34";

// Load users data
$usersFile = 'users.json';
if (!file_exists($usersFile)) {
    echo printColored("Error: No users found! Please add users using the adduser.php script.\n", $red);
    exit;
}

$users = json_decode(file_get_contents($usersFile), true);
if (!$users || !is_array($users)) {
    echo printColored("Error: Invalid users.json format!\n", $red);
    exit;
}

// Initialize points
$userPoints = array_fill_keys(array_keys($users), 0);

// Function to generate random chat instance
function generateChatInstance() {
    return strval(rand(10000000000000, 99999999999999));
}

// Function to make API request
function makeApiRequest($tgId) {
    $url = "https://api.adsgram.ai/adv?blockId=4853&tg_id=$tgId&tg_platform=android&platform=Linux%20aarch64&language=en&chat_type=sender&chat_instance=" . generateChatInstance() . "&top_domain=app.notpx.app";

    $headers = [
        'Host: api.adsgram.ai',
        'Connection: keep-alive',
        'Cache-Control: max-age=0',
        'sec-ch-ua-platform: "Android"',
        "User-Agent: " . generateUserAgent(),
        'sec-ch-ua: "Android WebView";v="131", "Chromium";v="131", "Not_A Brand";v="24"',
        'sec-ch-ua-mobile: ?1',
        'Accept: */*',
        'Origin: https://app.notpx.app',
        'X-Requested-With: org.telegram.messenger',
        'Sec-Fetch-Site: cross-site',
        'Sec-Fetch-Mode: cors',
        'Sec-Fetch-Dest: empty',
        'Referer: https://app.notpx.app/',
        'Accept-Encoding: gzip, deflate, br, zstd',
        'Accept-Language: en,en-US;q=0.9',
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return [$response, $httpCode];
}

// Function to extract reward value
function extractReward($response) {
    $data = json_decode($response, true);
    if ($data && isset($data['banner']['trackings'])) {
        foreach ($data['banner']['trackings'] as $tracking) {
            if ($tracking['name'] === 'reward') {
                return $tracking['value'];
            }
        }
    }
    return null;
}

$totalPoints = 0;
$firstRun = true;

while (true) {
    clearScreen();

    if (!$firstRun) {
        foreach ($users as $userId => $userData) {
            echo printColored("---> $userId +{$userPoints[$userId]} PX\n", $green);
        }
        echo printColored("Total PX Earned: +$totalPoints\n", $green);
    }

    foreach ($users as $userId => $userData) {
        $tgId = $userData['tg_id'];
        echo printColored("[ INFO ] Processing TG ID: $userId\n", $yellow);

        list($response, $httpCode) = makeApiRequest($tgId);

        if ($httpCode === 200) {
            $reward = extractReward($response);
            if ($reward) {
                $totalPoints += 16;
                $userPoints[$userId] += 16;
                echo printColored("[ SUCCESS ] Earned 16 PX for $userId.\n", $green);
            } else {
                echo printColored("[ ERROR ] No rewards available.\n", $red);
            }
        } else {
            echo printColored("[ ERROR ] HTTP Error: $httpCode\n", $red);
        }
    }

    $firstRun = false;

    // Cooldown
    for ($i = 20; $i > 0; $i--) {
        echo "\rCooldown: $i seconds remaining...";
        sleep(1);
    }
    echo "\n";
}
?>