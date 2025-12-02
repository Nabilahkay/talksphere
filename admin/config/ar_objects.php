<?php
/**
 * Physical AR Objects Database - Colors, Fruits, Numbers
 * Define all physical objects that will have QR codes
 */

$ar_objects = [
    // ============================================
    // COLORS (颜色 - yánsè)
    // ============================================
    
    'red_object' => [
        'id' => 'red_object',
        'name' => 'Red Object',
        'chinese' => '红色',
        'pinyin' => 'hóngsè',
        'english' => 'Red',
        'category' => 'Colors',
        'hsk_level' => 1,
        'icon' => '🔴',
        'color' => '#FF0000',
        'audio_file' => 'hongse.mp3',
        'description' => 'The color red - attach this QR to any red object',
        'usage_example' => '这是红色的 (This is red)',
        'mywebar_id' => 'red',
        'related_vocab' => ['color', 'red']
    ],
    
    'blue_object' => [
        'id' => 'blue_object',
        'name' => 'Blue Object',
        'chinese' => '蓝色',
        'pinyin' => 'lánsè',
        'english' => 'Blue',
        'category' => 'Colors',
        'hsk_level' => 1,
        'icon' => '🔵',
        'color' => '#0000FF',
        'audio_file' => 'lanse.mp3',
        'description' => 'The color blue - attach this QR to any blue object',
        'usage_example' => '天空是蓝色的 (The sky is blue)',
        'mywebar_id' => 'blue',
        'related_vocab' => ['color', 'blue', 'sky']
    ],
    
    'yellow_object' => [
        'id' => 'yellow_object',
        'name' => 'Yellow Object',
        'chinese' => '黄色',
        'pinyin' => 'huángsè',
        'english' => 'Yellow',
        'category' => 'Colors',
        'hsk_level' => 1,
        'icon' => '🟡',
        'color' => '#FFFF00',
        'audio_file' => 'huangse.mp3',
        'description' => 'The color yellow - attach this QR to any yellow object',
        'usage_example' => '香蕉是黄色的 (Bananas are yellow)',
        'mywebar_id' => 'yellow',
        'related_vocab' => ['color', 'yellow', 'banana']
    ],
    
    'green_object' => [
        'id' => 'green_object',
        'name' => 'Green Object',
        'chinese' => '绿色',
        'pinyin' => 'lǜsè',
        'english' => 'Green',
        'category' => 'Colors',
        'hsk_level' => 1,
        'icon' => '🟢',
        'color' => '#00FF00',
        'audio_file' => 'lvse.mp3',
        'description' => 'The color green - attach this QR to any green object',
        'usage_example' => '树叶是绿色的 (Leaves are green)',
        'mywebar_id' => 'green',
        'related_vocab' => ['color', 'green', 'leaf']
    ],
    
    'orange_object' => [
        'id' => 'orange_object',
        'name' => 'Orange Object',
        'chinese' => '橙色',
        'pinyin' => 'chéngsè',
        'english' => 'Orange',
        'category' => 'Colors',
        'hsk_level' => 2,
        'icon' => '🟠',
        'color' => '#FFA500',
        'audio_file' => 'chengse.mp3',
        'description' => 'The color orange - attach this QR to any orange object',
        'usage_example' => '橙子是橙色的 (Oranges are orange)',
        'mywebar_id' => 'orange',
        'related_vocab' => ['color', 'orange']
    ],
    
    'purple_object' => [
        'id' => 'purple_object',
        'name' => 'Purple Object',
        'chinese' => '紫色',
        'pinyin' => 'zǐsè',
        'english' => 'Purple',
        'category' => 'Colors',
        'hsk_level' => 2,
        'icon' => '🟣',
        'color' => '#800080',
        'audio_file' => 'zise.mp3',
        'description' => 'The color purple - attach this QR to any purple object',
        'usage_example' => '葡萄是紫色的 (Grapes are purple)',
        'mywebar_id' => 'purple',
        'related_vocab' => ['color', 'purple', 'grape']
    ],
    
    'white_object' => [
        'id' => 'white_object',
        'name' => 'White Object',
        'chinese' => '白色',
        'pinyin' => 'báisè',
        'english' => 'White',
        'category' => 'Colors',
        'hsk_level' => 1,
        'icon' => '⚪',
        'color' => '#FFFFFF',
        'audio_file' => 'baise.mp3',
        'description' => 'The color white - attach this QR to any white object',
        'usage_example' => '雪是白色的 (Snow is white)',
        'mywebar_id' => 'white',
        'related_vocab' => ['color', 'white', 'snow']
    ],
    
    'black_object' => [
        'id' => 'black_object',
        'name' => 'Black Object',
        'chinese' => '黑色',
        'pinyin' => 'hēisè',
        'english' => 'Black',
        'category' => 'Colors',
        'hsk_level' => 1,
        'icon' => '⚫',
        'color' => '#000000',
        'audio_file' => 'heise.mp3',
        'description' => 'The color black - attach this QR to any black object',
        'usage_example' => '这只猫是黑色的 (This cat is black)',
        'mywebar_id' => 'black',
        'related_vocab' => ['color', 'black']
    ],
    
    // ============================================
    // FRUITS (水果 - shuǐguǒ)
    // ============================================
    
    'apple' => [
        'id' => 'apple',
        'name' => 'Apple',
        'chinese' => '苹果',
        'pinyin' => 'píngguǒ',
        'english' => 'Apple',
        'category' => 'Fruits',
        'hsk_level' => 2,
        'icon' => '🍎',
        'color' => '#FF0000',
        'audio_file' => 'pingguo.mp3',
        'description' => 'An apple fruit - attach this QR to a real apple',
        'usage_example' => '我喜欢吃苹果 (I like to eat apples)',
        'mywebar_id' => 'apple',
        'related_vocab' => ['fruit', 'apple', 'eat']
    ],
    
    'banana' => [
        'id' => 'banana',
        'name' => 'Banana',
        'chinese' => '香蕉',
        'pinyin' => 'xiāngjiāo',
        'english' => 'Banana',
        'category' => 'Fruits',
        'hsk_level' => 2,
        'icon' => '🍌',
        'color' => '#FFFF00',
        'audio_file' => 'xiangjiao.mp3',
        'description' => 'A banana fruit - attach this QR to a real banana',
        'usage_example' => '香蕉很好吃 (Bananas are delicious)',
        'mywebar_id' => 'banana',
        'related_vocab' => ['fruit', 'banana', 'yellow']
    ],
    
    'orange' => [
        'id' => 'orange',
        'name' => 'Orange',
        'chinese' => '橙子',
        'pinyin' => 'chéngzi',
        'english' => 'Orange',
        'category' => 'Fruits',
        'hsk_level' => 2,
        'icon' => '🍊',
        'color' => '#FFA500',
        'audio_file' => 'chengzi.mp3',
        'description' => 'An orange fruit - attach this QR to a real orange',
        'usage_example' => '橙子很甜 (Oranges are sweet)',
        'mywebar_id' => 'orange_fruit',
        'related_vocab' => ['fruit', 'orange', 'sweet']
    ],
    
    'grape' => [
        'id' => 'grape',
        'name' => 'Grape',
        'chinese' => '葡萄',
        'pinyin' => 'pútáo',
        'english' => 'Grape',
        'category' => 'Fruits',
        'hsk_level' => 2,
        'icon' => '🍇',
        'color' => '#800080',
        'audio_file' => 'putao.mp3',
        'description' => 'Grapes - attach this QR to grapes',
        'usage_example' => '我买了一些葡萄 (I bought some grapes)',
        'mywebar_id' => 'grape',
        'related_vocab' => ['fruit', 'grape', 'purple']
    ],
    
    'watermelon' => [
        'id' => 'watermelon',
        'name' => 'Watermelon',
        'chinese' => '西瓜',
        'pinyin' => 'xīguā',
        'english' => 'Watermelon',
        'category' => 'Fruits',
        'hsk_level' => 2,
        'icon' => '🍉',
        'color' => '#FF6347',
        'audio_file' => 'xigua.mp3',
        'description' => 'A watermelon - attach this QR to a watermelon',
        'usage_example' => '西瓜很大 (The watermelon is very big)',
        'mywebar_id' => 'watermelon',
        'related_vocab' => ['fruit', 'watermelon', 'big']
    ],
    
    'strawberry' => [
        'id' => 'strawberry',
        'name' => 'Strawberry',
        'chinese' => '草莓',
        'pinyin' => 'cǎoméi',
        'english' => 'Strawberry',
        'category' => 'Fruits',
        'hsk_level' => 3,
        'icon' => '🍓',
        'color' => '#FF0000',
        'audio_file' => 'caomei.mp3',
        'description' => 'A strawberry - attach this QR to strawberries',
        'usage_example' => '草莓很小 (Strawberries are small)',
        'mywebar_id' => 'strawberry',
        'related_vocab' => ['fruit', 'strawberry', 'small']
    ],
    
    'pear' => [
        'id' => 'pear',
        'name' => 'Pear',
        'chinese' => '梨',
        'pinyin' => 'lí',
        'english' => 'Pear',
        'category' => 'Fruits',
        'hsk_level' => 2,
        'icon' => '🍐',
        'color' => '#90EE90',
        'audio_file' => 'li.mp3',
        'description' => 'A pear fruit - attach this QR to a pear',
        'usage_example' => '我想吃梨 (I want to eat a pear)',
        'mywebar_id' => 'pear',
        'related_vocab' => ['fruit', 'pear']
    ],
    
    'peach' => [
        'id' => 'peach',
        'name' => 'Peach',
        'chinese' => '桃子',
        'pinyin' => 'táozi',
        'english' => 'Peach',
        'category' => 'Fruits',
        'hsk_level' => 2,
        'icon' => '🍑',
        'color' => '#FFB6C1',
        'audio_file' => 'taozi.mp3',
        'description' => 'A peach fruit - attach this QR to a peach',
        'usage_example' => '桃子很软 (The peach is very soft)',
        'mywebar_id' => 'peach',
        'related_vocab' => ['fruit', 'peach', 'soft']
    ],
    
    // ============================================
    // NUMBERS (数字 - shùzì)
    // ============================================
    
    'number_one' => [
        'id' => 'number_one',
        'name' => 'Number One',
        'chinese' => '一',
        'pinyin' => 'yī',
        'english' => 'One (1)',
        'category' => 'Numbers',
        'hsk_level' => 1,
        'icon' => '1️⃣',
        'color' => '#4169E1',
        'audio_file' => 'yi.mp3',
        'description' => 'The number one - attach this QR to any object representing "1"',
        'usage_example' => '我有一个苹果 (I have one apple)',
        'mywebar_id' => 'number_1',
        'related_vocab' => ['number', 'one', 'count']
    ],
    
    'number_two' => [
        'id' => 'number_two',
        'name' => 'Number Two',
        'chinese' => '二',
        'pinyin' => 'èr',
        'english' => 'Two (2)',
        'category' => 'Numbers',
        'hsk_level' => 1,
        'icon' => '2️⃣',
        'color' => '#FF6347',
        'audio_file' => 'er.mp3',
        'description' => 'The number two - attach this QR to any object representing "2"',
        'usage_example' => '我有两本书 (I have two books)',
        'mywebar_id' => 'number_2',
        'related_vocab' => ['number', 'two', 'count']
    ],
    
    'number_three' => [
        'id' => 'number_three',
        'name' => 'Number Three',
        'chinese' => '三',
        'pinyin' => 'sān',
        'english' => 'Three (3)',
        'category' => 'Numbers',
        'hsk_level' => 1,
        'icon' => '3️⃣',
        'color' => '#32CD32',
        'audio_file' => 'san.mp3',
        'description' => 'The number three - attach this QR to any object representing "3"',
        'usage_example' => '我三岁 (I am three years old)',
        'mywebar_id' => 'number_3',
        'related_vocab' => ['number', 'three', 'count']
    ],
    
    'number_four' => [
        'id' => 'number_four',
        'name' => 'Number Four',
        'chinese' => '四',
        'pinyin' => 'sì',
        'english' => 'Four (4)',
        'category' => 'Numbers',
        'hsk_level' => 1,
        'icon' => '4️⃣',
        'color' => '#FFD700',
        'audio_file' => 'si.mp3',
        'description' => 'The number four - attach this QR to any object representing "4"',
        'usage_example' => '四个人 (Four people)',
        'mywebar_id' => 'number_4',
        'related_vocab' => ['number', 'four', 'count']
    ],
    
    'number_five' => [
        'id' => 'number_five',
        'name' => 'Number Five',
        'chinese' => '五',
        'pinyin' => 'wǔ',
        'english' => 'Five (5)',
        'category' => 'Numbers',
        'hsk_level' => 1,
        'icon' => '5️⃣',
        'color' => '#FF69B4',
        'audio_file' => 'wu.mp3',
        'description' => 'The number five - attach this QR to any object representing "5"',
        'usage_example' => '五天 (Five days)',
        'mywebar_id' => 'number_5',
        'related_vocab' => ['number', 'five', 'count']
    ],
    
    'number_six' => [
        'id' => 'number_six',
        'name' => 'Number Six',
        'chinese' => '六',
        'pinyin' => 'liù',
        'english' => 'Six (6)',
        'category' => 'Numbers',
        'hsk_level' => 1,
        'icon' => '6️⃣',
        'color' => '#9370DB',
        'audio_file' => 'liu.mp3',
        'description' => 'The number six - attach this QR to any object representing "6"',
        'usage_example' => '六点钟 (Six o\'clock)',
        'mywebar_id' => 'number_6',
        'related_vocab' => ['number', 'six', 'count']
    ],
    
    'number_seven' => [
        'id' => 'number_seven',
        'name' => 'Number Seven',
        'chinese' => '七',
        'pinyin' => 'qī',
        'english' => 'Seven (7)',
        'category' => 'Numbers',
        'hsk_level' => 1,
        'icon' => '7️⃣',
        'color' => '#00CED1',
        'audio_file' => 'qi.mp3',
        'description' => 'The number seven - attach this QR to any object representing "7"',
        'usage_example' => '星期七 (Seven days)',
        'mywebar_id' => 'number_7',
        'related_vocab' => ['number', 'seven', 'count']
    ],
    
    'number_eight' => [
        'id' => 'number_eight',
        'name' => 'Number Eight',
        'chinese' => '八',
        'pinyin' => 'bā',
        'english' => 'Eight (8)',
        'category' => 'Numbers',
        'hsk_level' => 1,
        'icon' => '8️⃣',
        'color' => '#FF8C00',
        'audio_file' => 'ba.mp3',
        'description' => 'The number eight - attach this QR to any object representing "8"',
        'usage_example' => '八月 (August - the eighth month)',
        'mywebar_id' => 'number_8',
        'related_vocab' => ['number', 'eight', 'count']
    ],
    
    'number_nine' => [
        'id' => 'number_nine',
        'name' => 'Number Nine',
        'chinese' => '九',
        'pinyin' => 'jiǔ',
        'english' => 'Nine (9)',
        'category' => 'Numbers',
        'hsk_level' => 1,
        'icon' => '9️⃣',
        'color' => '#DC143C',
        'audio_file' => 'jiu.mp3',
        'description' => 'The number nine - attach this QR to any object representing "9"',
        'usage_example' => '九个 (Nine pieces)',
        'mywebar_id' => 'number_9',
        'related_vocab' => ['number', 'nine', 'count']
    ],
    
    'number_ten' => [
        'id' => 'number_ten',
        'name' => 'Number Ten',
        'chinese' => '十',
        'pinyin' => 'shí',
        'english' => 'Ten (10)',
        'category' => 'Numbers',
        'hsk_level' => 1,
        'icon' => '🔟',
        'color' => '#4B0082',
        'audio_file' => 'shi.mp3',
        'description' => 'The number ten - attach this QR to any object representing "10"',
        'usage_example' => '十块钱 (Ten yuan)',
        'mywebar_id' => 'number_10',
        'related_vocab' => ['number', 'ten', 'count']
    ]
];

/**
 * Get object by ID
 */
function getARObject($object_id) {
    global $ar_objects;
    return isset($ar_objects[$object_id]) ? $ar_objects[$object_id] : null;
}

/**
 * Get all AR objects
 */
function getAllARObjects() {
    global $ar_objects;
    return $ar_objects;
}

/**
 * Get objects by category
 */
function getARObjectsByCategory($category) {
    global $ar_objects;
    return array_filter($ar_objects, function($obj) use ($category) {
        return $obj['category'] === $category;
    });
}

/**
 * Get all categories
 */
function getAllCategories() {
    global $ar_objects;
    $categories = array_unique(array_column($ar_objects, 'category'));
    return array_values($categories);
}

/**
 * Get object count by category
 */
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