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

$link_node = $xpath_fit->query('//div[@id="biolink_block_id_11"]//a');
$base_url = "https://cl.alooytv1.xyz/tvseries/";
if ($link_node->length) {
    $href = $link_node[0]->getAttribute('href');
    $parsed = parse_url($href);
    $base_url = $parsed['scheme'] . '://' . $parsed['host'] . '/tvseries/';
}

// المتغيرات
$page = isset($_GET['page']) ? $_GET['page'] : 1;

// دالة لجلب المسلسلات
function fetch_home_page($base_url, $page) {
    if ($page === "old") {
        // أولاً نجيب الصفحة الرئيسية ونبحث عن رابط آخر صفحة
        $html_first = @file_get_contents($base_url . 'home');
        if (!$html_first) return [];

        libxml_use_internal_errors(true);
        $dom_first = new DOMDocument();
        $dom_first->loadHTML($html_first);
        $xpath_first = new DOMXPath($dom_first);

        // البحث عن آخر زر في الصفحات
        $last_page_link = $xpath_first->query('//ul[contains(@class,"pagination")]/li/a');
        $last_offset = 0;
        if ($last_page_link->length) {
            foreach ($last_page_link as $a) {
                $href = $a->getAttribute('href');
                if (preg_match('/home\/(\d+)\.html/', $href, $m)) {
                    if ((int)$m[1] > $last_offset) {
                        $last_offset = (int)$m[1];
                    }
                }
            }
        }
        $url = $last_offset > 0 ? $base_url . 'home/' . $last_offset . '.html' : $base_url . 'home';
    } else {
        $offset = ((int)$page - 1) * 24;
        $url = $page > 1 ? $base_url . 'home/' . $offset . '.html' : $base_url . 'home';
    }

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

$result = fetch_home_page($base_url, $page);
echo json_encode(['home' => $result], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
