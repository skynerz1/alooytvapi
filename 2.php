<?php
header('Content-Type: application/json; charset=utf-8');

// جلب الدومين من fitnur
$fitnur_url = "https://fitnur.com/alooytv";
$fitnur_html = @file_get_contents($fitnur_url);
if (!$fitnur_html) {
    exit(json_encode(['error' => 'Failed to fetch domain']));
}

libxml_use_internal_errors(true);
$dom_fit = new DOMDocument();
$dom_fit->loadHTML($fitnur_html);
$xpath_fit = new DOMXPath($dom_fit);

// نجيب الرابط من div اللي فيه id="biolink_block_id_11"
$link_node = $xpath_fit->query('//div[@id="biolink_block_id_11"]//a');
$base_url = "https://cl.alooytv1.xyz/genre/"; // افتراضياً
if ($link_node->length) {
    $href = $link_node[0]->getAttribute('href');
    // ناخذ الجزء قبل اسم الصفحة (https://cl.alooytv1.xyz/genre/)
    $parsed = parse_url($href);
    $base_url = $parsed['scheme'] . '://' . $parsed['host'] . '/genre/';
}

// التصنيفات نفسها
$genres = [
    'turki' => 'turki',
    'arabic' => 'arabic',
    'kleeji' => 'kleeji',
    'ramadan-arabi-2025' => 'ramadan-arabi-2025',
    'ramadan-kleeji-2025' => 'ramadan-kleeji-2025',
    'ramadan-arabi-2024' => 'ramadan-arabi-2024',
    'ramadan-kleeji-2024' => 'ramadan-kleeji-2024',
    'ramadan-arabi' => 'ramadan-arabi',
    'ramadan-kleeji' => 'ramadan-kleeji',
    'korean-movies' => 'Korean-movies',
    'foreign-movies' => 'foreign-movies',
    'anmi' => 'anmi',
    'farisi' => 'farisi',
    'foreign-series' => 'Foreign-series',
    'korean-series' => 'Korean-series',
    'asia-series' => 'asia-series',
];

// بقية كود جلب المسلسلات مثل ما سبق
$genre = isset($_GET['genre']) ? $_GET['genre'] : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;

function fetch_genre_page($genre_url, $page, $base_url) {
    $offset = ($page - 1) * 50;
    $url = $page > 1 ? $base_url . $genre_url . '/' . $offset . '.html' : $base_url . $genre_url . '.html';
    
    $html = @file_get_contents($url);
    if (!$html) return [];

    libxml_use_internal_errors(true);
    $dom = new DOMDocument();
    $dom->loadHTML($html);
    $xpath = new DOMXPath($dom);

    $movies = [];
    $nodes = $xpath->query('//div[contains(@class,"latest-movie")]/div[contains(@class,"row")]/div[contains(@class,"movie-container")]/div[contains(@class,"col-md-2")]');

    foreach ($nodes as $node) {
        $titleNode = $xpath->query('.//div[@class="movie-title"]/h3/a', $node);
        $title = $titleNode->length ? trim($titleNode[0]->nodeValue) : '';
        $link = $titleNode->length ? $titleNode[0]->getAttribute('href') : '';

        $imgNode = $xpath->query('.//div[@class="movie-img"]/img', $node);
        $image = $imgNode->length ? $imgNode[0]->getAttribute('data-src') : '';

        $epNode = $xpath->query('.//div[@class="video_quality"]/span', $node);
        $episodes = $epNode->length ? trim($epNode[0]->nodeValue) : '';

        if ($title && $link) {
            $movies[] = [
                'title' => $title,
                'link' => $link,
                'image' => $image,
                'episodes' => $episodes
            ];
        }
    }
    return $movies;
}

if (!$genre) {
    $all = [];
    foreach ($genres as $key => $url) {
        $all[$key] = fetch_genre_page($url, $page, $base_url);
    }
    echo json_encode($all, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
} else {
    if (!isset($genres[$genre])) {
        echo json_encode(['error' => 'Genre not found']);
        exit;
    }
    $result = fetch_genre_page($genres[$genre], $page, $base_url);
    echo json_encode([$genre => $result], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
}
?>
