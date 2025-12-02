<?php
/**
 * Automatic AR Files Creator
 * Run this once to create missing AR configuration files
 */

echo "<h1>🔧 AR Files Creator</h1>";

// ============================================
// 1. CREATE ar_config.php
// ============================================

$ar_config_content = <<<'PHP'
<?php
/**
 * Hybrid AR Configuration
 */

define('AR_ENABLED', true);
define('AR_SYSTEM_MODE', 'hybrid');
define('MYWEBAR_ENABLED', false);
define('MYWEBAR_PROJECT_ID', '');
define('MYWEBAR_BASE_URL', 'https://mywebar.com/p/');
define('MYWEBAR_API_KEY', '');
define('AR_MARKER_SIZE', 400);
define('AR_MARKER_TYPE', 'qr');
define('AR_QUALITY', 'high');
define('AR_MARKER_PATH', __DIR__ . '/../uploads/ar_markers/');
define('AR_MARKER_URL', '/mandarin-learning/uploads/ar_markers/');

if (!file_exists(AR_MARKER_PATH)) {
    mkdir(AR_MARKER_PATH, 0755, true);
}

function generateQRCode($data, $filename) {
    $size = AR_MARKER_SIZE;
    $qr_url = 'https://api.qrserver.com/v1/create-qr-code/?size=' . $size . 'x' . $size . '&data=' . urlencode($data) . '&format=png';
    
    $context = stream_context_create([
        'http' => ['timeout' => 10, 'user_agent' => 'Mozilla/5.0']
    ]);
    
    $qr_image = @file_get_contents($qr_url, false, $context);
    
    if ($qr_image && strlen($qr_image) > 100) {
        file_put_contents(AR_MARKER_PATH . $filename, $qr_image);
        return true;
    }
    
    $qr_url = 'https://chart.googleapis.com/chart?chs=' . $size . 'x' . $size . '&cht=qr&chl=' . urlencode($data) . '&choe=UTF-8';
    $qr_image = @file_get_contents($qr_url, false, $context);
    
    if ($qr_image && strlen($qr_image) > 100) {
        file_put_contents(AR_MARKER_PATH . $filename, $qr_image);
        return true;
    }
    
    return false;
}

function getARViewURL($object_id, $type = 'local') {
    $base_url = 'http://' . $_SERVER['HTTP_HOST'] . '/mandarin-learning';
    
    if ($type === 'mywebar' && MYWEBAR_ENABLED && MYWEBAR_PROJECT_ID) {
        return MYWEBAR_BASE_URL . MYWEBAR_PROJECT_ID . '/' . $object_id;
    }
    
    return $base_url . '/ar-view.php?object=' . $object_id;
}
?>
PHP;

$ar_config_path = __DIR__ . '/config/ar_config.php';
if (file_put_contents($ar_config_path, $ar_config_content)) {
    echo "✅ <strong>SUCCESS:</strong> Created <code>config/ar_config.php</code><br>";
} else {
    echo "❌ <strong>ERROR:</strong> Could not create ar_config.php<br>";
}

// ============================================
// 2. CREATE ar_objects.php
// ============================================

$ar_objects_content = file_get_contents('https://pastebin.com/raw/PLEASEUSEFULLCODEBELOWinstead');

// Use the full ar_objects.php content (too long for pastebin, so I'll provide it inline)

$ar_objects_content = <<<'PHP'
<?php
$ar_objects = [
    'red_object' => ['id' => 'red_object', 'name' => 'Red Object', 'chinese' => '红色', 'pinyin' => 'hóngsè', 'english' => 'Red', 'category' => 'Colors', 'hsk_level' => 1, 'icon' => '🔴', 'color' => '#FF0000', 'audio_file' => 'hongse.mp3', 'description' => 'The color red', 'usage_example' => '这是红色的 (This is red)', 'mywebar_id' => 'red', 'related_vocab' => ['color', 'red']],
    'blue_object' => ['id' => 'blue_object', 'name' => 'Blue Object', 'chinese' => '蓝色', 'pinyin' => 'lánsè', 'english' => 'Blue', 'category' => 'Colors', 'hsk_level' => 1, 'icon' => '🔵', 'color' => '#0000FF', 'audio_file' => 'lanse.mp3', 'description' => 'The color blue', 'usage_example' => '天空是蓝色的', 'mywebar_id' => 'blue', 'related_vocab' => ['color', 'blue']],
    'yellow_object' => ['id' => 'yellow_object', 'name' => 'Yellow Object', 'chinese' => '黄色', 'pinyin' => 'huángsè', 'english' => 'Yellow', 'category' => 'Colors', 'hsk_level' => 1, 'icon' => '🟡', 'color' => '#FFFF00', 'audio_file' => 'huangse.mp3', 'description' => 'The color yellow', 'usage_example' => '香蕉是黄色的', 'mywebar_id' => 'yellow', 'related_vocab' => ['color', 'yellow']],
    'green_object' => ['id' => 'green_object', 'name' => 'Green Object', 'chinese' => '绿色', 'pinyin' => 'lǜsè', 'english' => 'Green', 'category' => 'Colors', 'hsk_level' => 1, 'icon' => '🟢', 'color' => '#00FF00', 'audio_file' => 'lvse.mp3', 'description' => 'The color green', 'usage_example' => '树叶是绿色的', 'mywebar_id' => 'green', 'related_vocab' => ['color', 'green']],
    'orange_object' => ['id' => 'orange_object', 'name' => 'Orange Object', 'chinese' => '橙色', 'pinyin' => 'chéngsè', 'english' => 'Orange', 'category' => 'Colors', 'hsk_level' => 2, 'icon' => '🟠', 'color' => '#FFA500', 'audio_file' => 'chengse.mp3', 'description' => 'The color orange', 'usage_example' => '橙子是橙色的', 'mywebar_id' => 'orange', 'related_vocab' => ['color', 'orange']],
    'purple_object' => ['id' => 'purple_object', 'name' => 'Purple Object', 'chinese' => '紫色', 'pinyin' => 'zǐsè', 'english' => 'Purple', 'category' => 'Colors', 'hsk_level' => 2, 'icon' => '🟣', 'color' => '#800080', 'audio_file' => 'zise.mp3', 'description' => 'The color purple', 'usage_example' => '葡萄是紫色的', 'mywebar_id' => 'purple', 'related_vocab' => ['color', 'purple']],
    'white_object' => ['id' => 'white_object', 'name' => 'White Object', 'chinese' => '白色', 'pinyin' => 'báisè', 'english' => 'White', 'category' => 'Colors', 'hsk_level' => 1, 'icon' => '⚪', 'color' => '#FFFFFF', 'audio_file' => 'baise.mp3', 'description' => 'The color white', 'usage_example' => '雪是白色的', 'mywebar_id' => 'white', 'related_vocab' => ['color', 'white']],
    'black_object' => ['id' => 'black_object', 'name' => 'Black Object', 'chinese' => '黑色', 'pinyin' => 'hēisè', 'english' => 'Black', 'category' => 'Colors', 'hsk_level' => 1, 'icon' => '⚫', 'color' => '#000000', 'audio_file' => 'heise.mp3', 'description' => 'The color black', 'usage_example' => '这只猫是黑色的', 'mywebar_id' => 'black', 'related_vocab' => ['color', 'black']],
    'apple' => ['id' => 'apple', 'name' => 'Apple', 'chinese' => '苹果', 'pinyin' => 'píngguǒ', 'english' => 'Apple', 'category' => 'Fruits', 'hsk_level' => 2, 'icon' => '🍎', 'color' => '#FF0000', 'audio_file' => 'pingguo.mp3', 'description' => 'An apple fruit', 'usage_example' => '我喜欢吃苹果', 'mywebar_id' => 'apple', 'related_vocab' => ['fruit', 'apple']],
    'banana' => ['id' => 'banana', 'name' => 'Banana', 'chinese' => '香蕉', 'pinyin' => 'xiāngjiāo', 'english' => 'Banana', 'category' => 'Fruits', 'hsk_level' => 2, 'icon' => '🍌', 'color' => '#FFFF00', 'audio_file' => 'xiangjiao.mp3', 'description' => 'A banana fruit', 'usage_example' => '香蕉很好吃', 'mywebar_id' => 'banana', 'related_vocab' => ['fruit', 'banana']],
    'orange' => ['id' => 'orange', 'name' => 'Orange', 'chinese' => '橙子', 'pinyin' => 'chéngzi', 'english' => 'Orange', 'category' => 'Fruits', 'hsk_level' => 2, 'icon' => '🍊', 'color' => '#FFA500', 'audio_file' => 'chengzi.mp3', 'description' => 'An orange fruit', 'usage_example' => '橙子很甜', 'mywebar_id' => 'orange_fruit', 'related_vocab' => ['fruit', 'orange']],
    'grape' => ['id' => 'grape', 'name' => 'Grape', 'chinese' => '葡萄', 'pinyin' => 'pútáo', 'english' => 'Grape', 'category' => 'Fruits', 'hsk_level' => 2, 'icon' => '🍇', 'color' => '#800080', 'audio_file' => 'putao.mp3', 'description' => 'Grapes', 'usage_example' => '我买了一些葡萄', 'mywebar_id' => 'grape', 'related_vocab' => ['fruit', 'grape']],
    'watermelon' => ['id' => 'watermelon', 'name' => 'Watermelon', 'chinese' => '西瓜', 'pinyin' => 'xīguā', 'english' => 'Watermelon', 'category' => 'Fruits', 'hsk_level' => 2, 'icon' => '🍉', 'color' => '#FF6347', 'audio_file' => 'xigua.mp3', 'description' => 'A watermelon', 'usage_example' => '西瓜很大', 'mywebar_id' => 'watermelon', 'related_vocab' => ['fruit', 'watermelon']],
    'strawberry' => ['id' => 'strawberry', 'name' => 'Strawberry', 'chinese' => '草莓', 'pinyin' => 'cǎoméi', 'english' => 'Strawberry', 'category' => 'Fruits', 'hsk_level' => 3, 'icon' => '🍓', 'color' => '#FF0000', 'audio_file' => 'caomei.mp3', 'description' => 'A strawberry', 'usage_example' => '草莓很小', 'mywebar_id' => 'strawberry', 'related_vocab' => ['fruit', 'strawberry']],
    'pear' => ['id' => 'pear', 'name' => 'Pear', 'chinese' => '梨', 'pinyin' => 'lí', 'english' => 'Pear', 'category' => 'Fruits', 'hsk_level' => 2, 'icon' => '🍐', 'color' => '#90EE90', 'audio_file' => 'li.mp3', 'description' => 'A pear fruit', 'usage_example' => '我想吃梨', 'mywebar_id' => 'pear', 'related_vocab' => ['fruit', 'pear']],
    'peach' => ['id' => 'peach', 'name' => 'Peach', 'chinese' => '桃子', 'pinyin' => 'táozi', 'english' => 'Peach', 'category' => 'Fruits', 'hsk_level' => 2, 'icon' => '🍑', 'color' => '#FFB6C1', 'audio_file' => 'taozi.mp3', 'description' => 'A peach fruit', 'usage_example' => '桃子很软', 'mywebar_id' => 'peach', 'related_vocab' => ['fruit', 'peach']],
    'number_one' => ['id' => 'number_one', 'name' => 'Number One', 'chinese' => '一', 'pinyin' => 'yī', 'english' => 'One (1)', 'category' => 'Numbers', 'hsk_level' => 1, 'icon' => '1️⃣', 'color' => '#4169E1', 'audio_file' => 'yi.mp3', 'description' => 'The number one', 'usage_example' => '我有一个苹果', 'mywebar_id' => 'number_1', 'related_vocab' => ['number', 'one']],
    'number_two' => ['id' => 'number_two', 'name' => 'Number Two', 'chinese' => '二', 'pinyin' => 'èr', 'english' => 'Two (2)', 'category' => 'Numbers', 'hsk_level' => 1, 'icon' => '2️⃣', 'color' => '#FF6347', 'audio_file' => 'er.mp3', 'description' => 'The number two', 'usage_example' => '我有两本书', 'mywebar_id' => 'number_2', 'related_vocab' => ['number', 'two']],
    'number_three' => ['id' => 'number_three', 'name' => 'Number Three', 'chinese' => '三', 'pinyin' => 'sān', 'english' => 'Three (3)', 'category' => 'Numbers', 'hsk_level' => 1, 'icon' => '3️⃣', 'color' => '#32CD32', 'audio_file' => 'san.mp3', 'description' => 'The number three', 'usage_example' => '我三岁', 'mywebar_id' => 'number_3', 'related_vocab' => ['number', 'three']],
    'number_four' => ['id' => 'number_four', 'name' => 'Number Four', 'chinese' => '四', 'pinyin' => 'sì', 'english' => 'Four (4)', 'category' => 'Numbers', 'hsk_level' => 1, 'icon' => '4️⃣', 'color' => '#FFD700', 'audio_file' => 'si.mp3', 'description' => 'The number four', 'usage_example' => '四个人', 'mywebar_id' => 'number_4', 'related_vocab' => ['number', 'four']],
    'number_five' => ['id' => 'number_five', 'name' => 'Number Five', 'chinese' => '五', 'pinyin' => 'wǔ', 'english' => 'Five (5)', 'category' => 'Numbers', 'hsk_level' => 1, 'icon' => '5️⃣', 'color' => '#FF69B4', 'audio_file' => 'wu.mp3', 'description' => 'The number five', 'usage_example' => '五天', 'mywebar_id' => 'number_5', 'related_vocab' => ['number', 'five']],
    'number_six' => ['id' => 'number_six', 'name' => 'Number Six', 'chinese' => '六', 'pinyin' => 'liù', 'english' => 'Six (6)', 'category' => 'Numbers', 'hsk_level' => 1, 'icon' => '6️⃣', 'color' => '#9370DB', 'audio_file' => 'liu.mp3', 'description' => 'The number six', 'usage_example' => '六点钟', 'mywebar_id' => 'number_6', 'related_vocab' => ['number', 'six']],
    'number_seven' => ['id' => 'number_seven', 'name' => 'Number Seven', 'chinese' => '七', 'pinyin' => 'qī', 'english' => 'Seven (7)', 'category' => 'Numbers', 'hsk_level' => 1, 'icon' => '7️⃣', 'color' => '#00CED1', 'audio_file' => 'qi.mp3', 'description' => 'The number seven', 'usage_example' => '星期七', 'mywebar_id' => 'number_7', 'related_vocab' => ['number', 'seven']],
    'number_eight' => ['id' => 'number_eight', 'name' => 'Number Eight', 'chinese' => '八', 'pinyin' => 'bā', 'english' => 'Eight (8)', 'category' => 'Numbers', 'hsk_level' => 1, 'icon' => '8️⃣', 'color' => '#FF8C00', 'audio_file' => 'ba.mp3', 'description' => 'The number eight', 'usage_example' => '八月', 'mywebar_id' => 'number_8', 'related_vocab' => ['number', 'eight']],
    'number_nine' => ['id' => 'number_nine', 'name' => 'Number Nine', 'chinese' => '九', 'pinyin' => 'jiǔ', 'english' => 'Nine (9)', 'category' => 'Numbers', 'hsk_level' => 1, 'icon' => '9️⃣', 'color' => '#DC143C', 'audio_file' => 'jiu.mp3', 'description' => 'The number nine', 'usage_example' => '九个', 'mywebar_id' => 'number_9', 'related_vocab' => ['number', 'nine']],
    'number_ten' => ['id' => 'number_ten', 'name' => 'Number Ten', 'chinese' => '十', 'pinyin' => 'shí', 'english' => 'Ten (10)', 'category' => 'Numbers', 'hsk_level' => 1, 'icon' => '🔟', 'color' => '#4B0082', 'audio_file' => 'shi.mp3', 'description' => 'The number ten', 'usage_example' => '十块钱', 'mywebar_id' => 'number_10', 'related_vocab' => ['number', 'ten']]
];

function getARObject($object_id) {
    global $ar_objects;
    return isset($ar_objects[$object_id]) ? $ar_objects[$object_id] : null;
}

function getAllARObjects() {
    global $ar_objects;
    return $ar_objects;
}

function getARObjectsByCategory($category) {
    global $ar_objects;
    return array_filter($ar_objects, function($obj) use ($category) {
        return $obj['category'] === $category;
    });
}

function getAllCategories() {
    global $ar_objects;
    $categories = array_unique(array_column($ar_objects, 'category'));
    return array_values($categories);
}

function getObjectCountByCategory() {
    global $ar_objects;
    $counts = [];
    foreach ($ar_objects as $obj) {
        $category = $obj['category'];
        if (!isset($counts[$category])) {
            $counts[$category] = 0;
        }
        $counts[$category]++;
    }
    return $counts;
}
?>
PHP;

$ar_objects_path = __DIR__ . '/config/ar_objects.php';
if (file_put_contents($ar_objects_path, $ar_objects_content)) {
    echo "✅ <strong>SUCCESS:</strong> Created <code>config/ar_objects.php</code><br>";
} else {
    echo "❌ <strong>ERROR:</strong> Could not create ar_objects.php<br>";
}

// ============================================
// 3. CREATE uploads/ar_markers folder
// ============================================

$markers_path = __DIR__ . '/uploads/ar_markers/';
if (!file_exists($markers_path)) {
    if (mkdir($markers_path, 0755, true)) {
        echo "✅ <strong>SUCCESS:</strong> Created <code>uploads/ar_markers/</code> folder<br>";
    } else {
        echo "❌ <strong>ERROR:</strong> Could not create uploads/ar_markers/ folder<br>";
    }
} else {
    echo "ℹ️ <code>uploads/ar_markers/</code> folder already exists<br>";
}

echo "<br><hr><br>";
echo "<h2>✅ Setup Complete!</h2>";
echo "<p>Now you can access:</p>";
echo "<a href='admin/generate-physical-qr.php' style='display: inline-block; padding: 12px 25px; background: #4CAF50; color: white; text-decoration: none; border-radius: 8px; font-weight: 600; margin-top: 10px;'>
    📱 Go to QR Generator
</a>";
?>
