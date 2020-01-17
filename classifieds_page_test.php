<html>
<head>
  <title>Test Classifieds Page</title>
</head>
<body>
<?php

$keyword = "jointer";

$buildURL = "https://classifieds.ksl.com/search?category[]=&subCategory[]=&keyword=".$keyword."&zip=84095&miles=50&sellerType[]=&marketType[]=Sale&hasPhotos[]=Has%20Photos&postedTime[]=";

$testURL = "https://classifieds.ksl.com/search?category[]=&subCategory[]=&keyword=jointer&priceFrom=&priceTo=&zip=&miles=25&sellerType[]=&marketType[]=&hasPhotos[]=&postedTime[]=";
//$testURL = "https://www.smartcareerbuilder.com/";

function curl_load($url){
    curl_setopt($ch=curl_init(), CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $response = curl_exec($ch);
    curl_close($ch);
    return $response;
}

//echo curl_load($testURL);

// Get classifieds search page content
$contents = file_get_contents($testURL);
echo "<pre>".$contents."</pre><br><hr><br>";
//var_dump($contents);

?>
</body>
