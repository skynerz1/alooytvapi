<?php
header('Content-Type: application/json; charset=utf-8');

function getBaseDomain($url){
    $parts = parse_url($url);
    return $parts['scheme'].'://'.$parts['host'].'/';
}

// جلب الدومين الحالي من fitnur
$fitnur_url = "https://fitnur.com/alooytv";
$fitnur_html = @file_get_contents($fitnur_url);
if(!$fitnur_html) exit(json_encode(['error'=>'Failed to fetch fitnur']));

if(preg_match('#<div id="biolink_block_id_3".*?<a href="([^"]+)"#s', $fitnur_html, $matches)){
    $base_url = getBaseDomain($matches[1]); // الدومين الأساسي
}else{
    exit(json_encode(['error'=>'No link found in fitnur']));
}

// الصفحة الرئيسية
$page_url = $base_url;
$html = @file_get_contents($page_url);
if(!$html) exit(json_encode(['error'=>'Failed to fetch data']));

libxml_use_internal_errors(true);
$dom = new DOMDocument();
$dom->loadHTML($html);
libxml_clear_errors();
$xpath = new DOMXPath($dom);

// ------------------- المسلسلات الشائعة -------------------
$popular_nodes = $xpath->query("//div[@id='hot-tvseries']//div[contains(@class,'latest-movie-img-container')]");
$popular = [];
foreach($popular_nodes as $node){
    $imgTag = $xpath->query(".//img[contains(@class,'img-responsive')]", $node)->item(0);
    $img = $imgTag ? $imgTag->getAttribute('data-src') : '';

    $linkTag = $xpath->query(".//a[contains(@class,'ico-play')]", $node)->item(0);
    $link = $linkTag ? $linkTag->getAttribute('href') : '';

    $titleTag = $xpath->query(".//div[contains(@class,'movie-title')]/h3/a", $node)->item(0);
    $title = $titleTag ? trim($titleTag->textContent) : '';

    $episodesTag = $xpath->query(".//div[contains(@class,'video_quality')]/span", $node)->item(0);
    $episodes = $episodesTag ? trim($episodesTag->textContent) : '';

    if($link && !str_starts_with($link, 'http')){
        $link = $base_url . ltrim($link, '/');
    }

    $popular[] = [
        'title' => $title,
        'link' => $link,
        'image' => $img,
        'episodes' => $episodes
    ];
}

// ------------------- الأكثر مشاهدة -------------------
$top_nodes = $xpath->query("//div[@id='top-rating-tvseries']//div[contains(@class,'latest-movie-img-container')]");
$top = [];
foreach($top_nodes as $node){
    $imgTag = $xpath->query(".//img[contains(@class,'img-responsive')]", $node)->item(0);
    $img = $imgTag ? $imgTag->getAttribute('data-src') : '';

    $linkTag = $xpath->query(".//a[contains(@class,'ico-play')]", $node)->item(0);
    $link = $linkTag ? $linkTag->getAttribute('href') : '';

    $titleTag = $xpath->query(".//div[contains(@class,'movie-title')]/h3/a", $node)->item(0);
    $title = $titleTag ? trim($titleTag->textContent) : '';

    $episodesTag = $xpath->query(".//div[contains(@class,'video_quality')]/span", $node)->item(0);
    $episodes = $episodesTag ? trim($episodesTag->textContent) : '';

    if($link && !str_starts_with($link, 'http')){
        $link = $base_url . ltrim($link, '/');
    }

    $top[] = [
        'title' => $title,
        'link' => $link,
        'image' => $img,
        'episodes' => $episodes
    ];
}

// ------------------- إخراج JSON حسب الباراميتر -------------------
if(isset($_GET['pop'])){
    echo json_encode(['popular' => $popular], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
}elseif(isset($_GET['top'])){
    echo json_encode(['top' => $top], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
}else{
    echo json_encode([
        'popular' => $popular,
        'top' => $top
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
}

exit;
