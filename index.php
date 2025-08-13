<?php
header('Content-Type: application/json; charset=utf-8');

// --- جلب الرابط الحالي من fitnur.com ---
$fitnur_html = @file_get_contents('https://fitnur.com/alooytv');
if(!$fitnur_html) exit(json_encode(['error'=>'Failed to fetch data from fitnur.com']));

libxml_use_internal_errors(true);
$dom = new DOMDocument();
$dom->loadHTML($fitnur_html);
libxml_clear_errors();
$xpath = new DOMXPath($dom);

// البحث عن الرابط داخل div#biolink_block_id_3 a
$linkNode = $xpath->query("//div[@id='biolink_block_id_3']//a")->item(0);
$cl_alooytv_url = $linkNode ? $linkNode->getAttribute('href') : '';
if(!$cl_alooytv_url) exit(json_encode(['error'=>'Failed to get current cl.alooytv URL']));

// إزالة أي شيء بعد الدومين
$parsed = parse_url($cl_alooytv_url);
$domain_only = $parsed['scheme'] . '://' . $parsed['host'] . '/'; // https://cl.alooytv1.xyz/

// --- API البحث ---
if(isset($_GET['search'])){
    $search = urlencode($_GET['search']);
    $url = $domain_only . "search?q=$search";

    $html = @file_get_contents($url);
    if(!$html) exit(json_encode(['error'=>'Failed to fetch data']));

    libxml_use_internal_errors(true);
    $dom = new DOMDocument();
    $dom->loadHTML($html);
    libxml_clear_errors();
    $xpath = new DOMXPath($dom);

    $nodes = $xpath->query("//div[contains(@class,'latest-movie-img-container')]");
    $results = [];
    foreach($nodes as $node){
        $imgTag = $xpath->query(".//img[contains(@class,'img-responsive')]", $node)->item(0);
        $img = $imgTag ? $imgTag->getAttribute('data-src') : '';

        $linkTag = $xpath->query(".//a[contains(@class,'ico-play')]", $node)->item(0);
        $link = $linkTag ? $linkTag->getAttribute('href') : '';

        $titleTag = $xpath->query(".//div[contains(@class,'movie-title')]/h3/a", $node)->item(0);
        $title = $titleTag ? trim($titleTag->textContent) : '';

        $episodesTag = $xpath->query(".//div[contains(@class,'video_quality')]/span", $node)->item(0);
        $episodes = $episodesTag ? trim($episodesTag->textContent) : '';

        $results[] = [
            'title' => $title,
            'link' => $link,
            'image' => $img,
            'episodes' => $episodes
        ];
    }

    echo json_encode($results, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

// --- API استخراج الحلقات ---
if (isset($_GET['url'])) {
    $page_url = str_replace('\\', '', $_GET['url']);
    $page_url = urldecode($page_url);

    $html = @file_get_contents($page_url);
    if (!$html) exit(json_encode(['error' => 'Failed to fetch data'], JSON_UNESCAPED_UNICODE));

    // إجبار DOMDocument على قراءة الصفحة كـ UTF-8
    libxml_use_internal_errors(true);
    $dom = new DOMDocument();
    $dom->loadHTML('<meta charset="UTF-8">' . $html);
    libxml_clear_errors();
    $xpath = new DOMXPath($dom);

    // جلب اسم المسلسل
    $titleNode = $xpath->query("//span[contains(@class,'pull-left title')]");
    $title = $titleNode->length > 0 ? trim($titleNode->item(0)->textContent) : null;

    // جلب الحلقات
    $nodes = $xpath->query("//a[contains(@class,'btn-ep')]");
    $episodes = [];
    foreach ($nodes as $node) {
        $episodes[] = [
            'title' => trim($node->textContent),
            'link' => $node->getAttribute('href')
        ];
    }

    // جلب Genre
    $genreNode = $xpath->query("//p[strong[contains(text(),'Genre')]]/a");
    $genre = $genreNode->length > 0 ? trim($genreNode->item(0)->textContent) : null;

    // جلب Quality
    $qualityNode = $xpath->query("//p[strong[contains(text(),'Quality')]]/span");
    $quality = $qualityNode->length > 0 ? trim($qualityNode->item(0)->textContent) : null;

    // جلب Rating
    $ratingNode = $xpath->query("//div[contains(@class,'rating_selection')]//strong[@id='rated']");
    $rating = $ratingNode->length > 0 ? trim($ratingNode->item(0)->textContent) : null;

    // إخراج النتيجة
    $result = [
        'title' => $title,
        'genre' => $genre,
        'quality' => $quality,
        'rating' => $rating,
        'episodes' => $episodes
    ];

    echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}



// --- API روابط الفيديو ---
if(isset($_GET['ep'])){
    $ep_url = str_replace('\\', '', $_GET['ep']);
    $ep_url = urldecode($ep_url);

    $html = @file_get_contents($ep_url);
    if(!$html) exit(json_encode(['error'=>'Failed to fetch data']));

    libxml_use_internal_errors(true);
    $dom = new DOMDocument();
    $dom->loadHTML($html);
    libxml_clear_errors();
    $xpath = new DOMXPath($dom);

    $nodes = $xpath->query("//video/source");
    $videos = [];
    foreach($nodes as $node){
        $src = $node->getAttribute('src');
        $src = str_replace('\\', '', $src); // إزالة backslashes
        $src = preg_replace('#(?<!:)//+#', '/', $src); // إزالة // زائد
        $videos[] = $src;
    }

    echo json_encode($videos, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit;
}

echo json_encode(['error'=>'No valid parameter provided']);
