<?php
include_once('db.php');

$today = date('Y-m-d');
//$date_limit = date("Y-m-d",strtotime("-14 days"));

$searches = [];

$searches_query = "SELECT * FROM genealogy.classifieds_searches WHERE REFRESH_RATE = 60 ORDER BY EMAIL";// WHERE REFRESH_RATE = 10";
$return_searches = $con->query($searches_query);
if (!$return_searches) { die('Invalid query: ' / mysqli_error($con)); }
$number_of_results = $return_searches->num_rows;
while ($row = $return_searches->fetch_assoc()) {
    array_push($searches,$row);
}

$keywords = "";

foreach ($searches as $key => $search) {
  if ($keywords == ""){
    $keywords .= "'".strtolower($search["KEYWORD"])."'";
  } else {
    $keywords .= " OR KEYWORD = '".strtolower($search["KEYWORD"])."'";
  }
}

$listing_ids = [];

$listing_ids_query = "SELECT * FROM genealogy.classifieds"; // WHERE DATE_ADDED > '$date_limit'
$return_ids = $con->query($listing_ids_query);
if (!$return_ids) { die('Invalid query: ' / mysqli_error($con)); }
while ($row = $return_ids->fetch_assoc()) {
    array_push($listing_ids,$row['CLASSIFIED_ID']);
}


?>
<html>
<head><title>Test Query</title></head>
<body>
  <p>TEST</p>
  <h3><?php echo $keywords; ?></h3>
  <h3>Number of results: <?php echo $number_of_results; ?></h3>
  <br>
  <pre>
    <?php var_dump($listing_ids); echo "<hr>"; var_dump($searches); ?>
  </pre>
</body>
</html>
