<?php
if (!isset($_GET['link'])) {
    echo "No link provided";
    exit;
}

$link = $_GET['link'];

// استدعاء الـ API الأصلي للحصول على بيانات الحلقة
$api_url = $link; 
$json = @file_get_contents($api_url);
if (!$json) {
    echo "Failed to fetch episode data";
    exit;
}

$data = json_decode($json, true);
if (!$data || !isset($data['videos'][0])) {
    echo "Invalid episode data";
    exit;
}

// بيانات الحلقة
$title = $data['title'] ?? "Episode";
$poster = $data['poster'] ?? "";
$video = $data['videos'][0] ?? "";
?>
<!DOCTYPE html>
<html lang="ar">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo htmlspecialchars($title); ?></title>
<style>
html, body {
    margin:0;
    padding:0;
    width:100%;
    height:100%;
    background:#000;
    font-family: Arial, sans-serif;
}
body {
    display:flex;
    flex-direction:column;
    align-items:center;
    justify-content:center;
}
#video-container {
    position: fixed;
    top:0;
    left:0;
    width:100%;
    height:100%;
    background:#000;
    display:flex;
    justify-content:center;
    align-items:center;
    overflow: hidden;
}
video {
    max-width: 100%;
    max-height: 100%;
    width: auto;
    height: auto;
    object-fit: contain;
    background:#000;
    display:none; /* الفيديو مخفي أولًا */
}
#poster-overlay {
    position:absolute;
    top:0;
    left:0;
    width:100%;
    height:100%;
    cursor:pointer;
    display:flex;
    align-items:center;
    justify-content:center;
    background:#000;
    z-index:2;
}
#poster-overlay img {
    max-height:100%;
    max-width: 100%;
    object-fit: contain;
    display:block;
}
#play-btn {
    width: 120px;
    height: 120px;
    background: rgba(255,255,255,0.8);
    clip-path: polygon(25% 20%, 25% 80%, 75% 50%);
    cursor:pointer;
    transition: transform 0.2s, background 0.2s;
    position:absolute;
}
#play-btn:hover {
    transform: scale(1.3);
    background: rgba(255,255,255,1);
}
h1 {
    position: absolute;
    top: 20px;
    width: 100%;
    text-align: center;
    color: #fff;
    z-index: 3;
    text-shadow: 0 0 10px #000;
    font-size: 1.5rem;
}
@media (max-width:768px){
    h1 { font-size: 1.2rem; }
    #play-btn { width: 90px; height:90px; }
}
</style>
</head>
<body>

<h1><?php echo htmlspecialchars($title); ?></h1>

<div id="video-container">
    <video id="player" controls preload="metadata">
        <source src="<?php echo $video; ?>" type="video/mp4">
        متصفحك لا يدعم تشغيل الفيديو.
    </video>

    <div id="poster-overlay" onclick="playVideo()">
        <img src="<?php echo $poster; ?>" alt="Poster">
        <div id="play-btn"></div>
    </div>
</div>

<script>
function playVideo() {
    var overlay = document.getElementById('poster-overlay');
    var video = document.getElementById('player');
    
    overlay.style.transition = "opacity 0.5s";
    overlay.style.opacity = 0;
    
    setTimeout(function(){
        overlay.style.display = "none";
        video.style.display = "block";
        video.play();
    }, 500);
}
</script>

</body>
</html>
