<?php

/*
---------------------------
~ KSL Classifieds Scraper ~
---------------------------

Simple Overview:
1. Get info for different searches
2. Get data from KSL
3. Check which ones have already been scraped (database with scraped listing IDs)
4. Send email with new listings
5. Save the new listing IDs in Database (for step 3)

*/

include_once('db.php');

$REFRESH_RATE = "10";
if(getopt(null, ["rate:"])){  // Keyword
  $rate_array = getopt(null, ["rate:"]);
  $REFRESH_RATE = $rate_array["rate"];
}

// Set up variables
$today = date('Y-m-d');
//$date_limit = date("Y-m-d",strtotime("-14 days"));

// Get Searches
$searches = [];
$searches_query = "SELECT * FROM genealogy.classifieds_searches WHERE REFRESH_RATE = ".$REFRESH_RATE;
$return_searches = $con->query($searches_query);
if($return_searches->num_rows == 0) { die('No Results!'); }
if (!$return_searches) { die('Invalid query: ' / mysqli_error($con)); }
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

// Get listing IDs from DB
$listing_ids = [];
$listing_ids_query = "SELECT * FROM genealogy.classifieds WHERE KEYWORD = ".$keywords; // WHERE DATE_ADDED > '$date_limit'
$return_ids = $con->query($listing_ids_query);
if (!$return_ids) { die('Invalid query: ' / mysqli_error($con)); }
while ($row = $return_ids->fetch_assoc()) {
    array_push($listing_ids,$row['CLASSIFIED_ID']);
}

// loop through searches
foreach ($searches as $key => $search) {
  $priceLow = "";  if ($search["PRICE_LOW"] != "0") {  $priceLow = "&priceFrom=%24".$search["PRICE_LOW"]; }
  $priceHigh = ""; if ($search["PRICE_HIGH"] != "0") { $priceHigh = "&priceTo=%24".$search["PRICE_HIGH"]; }
  $description_highlights = explode("|",$search["HIGHLIGHT"]);
  $description_highlights_string = str_replace("|",", ",$search["HIGHLIGHT"]);

  // Defaults: Has Photos, For Sale, Unspecified Category/Subcategory
  $buildURL = "https://classifieds.ksl.com/search?category[]=&subCategory[]=&keyword=".$search["KEYWORD"].$priceLow.$priceHigh."&zip=".$search["ZIP"]."&miles=".$search["MILES"]."&sellerType[]=&marketType[]=Sale&hasPhotos[]=Has%20Photos&postedTime[]=";

  // Get classifieds search page content
  $contents = file_get_contents($buildURL);

  //If $contents is not FALSE
  if($contents !== false){
      // Format the data as JSON
      preg_match('/(?<=listings\: \[\{)(.*)(?=\}\])/', $contents, $matches);
      $results_RAW = "[{".$matches[1]."}]";

      // Convert JSON string to Array
      $someArray = json_decode($results_RAW, true);

      /*
      {
        "id":57815936,
        "memberId":3299963,
        "createTime":"2019-10-11T19:47:56Z",
        "displayTime":"2019-10-31T22:31:29Z",
        "modifyTime":"2019-10-31T22:31:29Z",
        "expireTime":"2019-11-30T23:31:29Z",
        "category":"Industrial",
        "subCategory":"Power and Hand Tools",
        "price":380,
        "title":"10\u201d Table Saw, Jointer, Router Table 1HP Delta",
        "description":"1 HP Delta-Milwaukee 10\u201d Table saw with built in 6\u201d jointer, attached router table.",
        "marketType":"Sale",
        "city":"Salt Lake City",
        "state":"UT",
        "zip":"84102",
        "name":"James Trull",
        "homePhone":"(208) 446-7625",
        "cellPhone":"(208) 446-7625",
        "email":1,
        "sellerType":"Private",
        "lat":40.7599,
        "lon":-111.8642,
        "emailCanonical":"jwtrull@gmail.com",
        "photo":"\/\/img.ksl.com\/mx\/mplace-classifieds.ksl.com\/3299963-1570823278-530270.jpg",
        "mongoId":"5da0dc6cea1c892298595e27",
        "pageviews":494,
        "favorited":6,
        "listingType":"normal",
        "source":"classifiedListing",
        "contentType":"free"
      }
      */
  }

  $message_listings = "";
  $send_email = false;
  $highlight = false;

  foreach ($someArray as $key => $value) {
    // Check if we haven't already scraped this listing
    if(!in_array($value['id'], $listing_ids)){
      $send_email = true;
      // Format Date/Time
      $posted_date = strtotime($value['displayTime']);
      $posted_date_formatted = date('d/M/Y h:i', $posted_date);
      // Format Photo URL
      $photoURL = "https:" . str_replace("\/","/",$value['photo']);
      // Check for highlight words
      $highlight_photo = "";
      $highlight_title = "";
      foreach ($description_highlights as $key1 => $value1) {
        if (stripos($value['description'], $value1) !== false || stripos($value['title'], $value1) !== false) {
          $highlight = true;
          $highlight_photo = " border: 2px solid #f7941d;";
          $highlight_title = " style=\"font-size: 18px; color: #f7941d;\" ";
        }
      }

      // Add listing to email message
      $message_listings .= "
      <tr>
        <td><a href=\"https://classifieds.ksl.com/listing/".$value['id']."\" target=\"_blank\" style=\"text-decoration: none; color: #000;\"><span style=\"background-image: url('".$photoURL."'); background-size: cover; background-position: center; width: 250px; height: 200px; display: block;".$highlight_photo."\"></span></a></td>
        <td><a href=\"https://classifieds.ksl.com/listing/".$value['id']."\" target=\"_blank\" style=\"text-decoration: none; color: #000;\"><span".$highlight_title.">".$value['title']."</span><hr><strong style=\"color: #f7941d; font-size: 20px;\">$".$value['price']."</strong><br><span>".$value['city']."</span><br><span>".$posted_date_formatted."</span></a></td>
      </tr>
      <tr style=\"margin-bottom: 20px;\">
        <td colspan=\"2\"><a href=\"https://classifieds.ksl.com/listing/".$value['id']."\" target=\"_blank\" style=\"text-decoration: none; color: #000;\"><span>".$value['description']."</span></a></td><hr>
      </tr><br>";

      // Insert into DB to remember this listing was alredy scraped
      $sql = "INSERT INTO genealogy.classifieds (ID, CLASSIFIED_ID, KEYWORD, DATE_ADDED)
      VALUES (NULL, '".$value["id"]."', '".$search["KEYWORD"]."', '".$today."')";
      if ($con->query($sql) === TRUE) {
          //echo "New record created successfully";
      } else {
          echo "Error: " . $sql . "<br>" . $conn->error;
      }
    }
  }

  // Send email if there are new listings
  if($send_email){
    $to = $search["EMAIL"];
    $subject = "New Listing: ".$search["KEYWORD"];
    $highlight_notification = $highlight ? "<h1 style=\"color: #f7941d;\">!!! Has highlight !!!</h1>" : "";

    $message = "
    <html>
    <head>
    <title>New Listing: ".$search["KEYWORD"]."</title>
    </head>
    <body>
      ".$highlight_notification."
      <table>
        <tr>
          <th>Image</th>
          <th>Details</th>
        </tr>".$message_listings."
      </table>
      <hr><hr>
      <h2>Parameters</h2>
      <table border=\"1\">
        <tr>
          <th>Keyword</th>
          <th>Zip</th>
          <th>Miles</th>
          <th>$ Low</th>
          <th>$ High</th>
          <th>highlights</th>
        </tr>
        <tr>
          <td>".$search["KEYWORD"]."</td>
          <td>".$search["ZIP"]."</td>
          <td>".$search["MILES"]."</td>
          <td>".$priceLow."</td>
          <td>".$priceHigh."</td>
          <td>".$description_highlights_string."</td>
        </tr>
      </table>
    </body>
    </html>
    ";

    // Always set content-type when sending HTML email
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";

    // More headers
    //$headers .= 'From: <webmaster@example.com>' . "\r\n";

    mail($to,$subject,$message,$headers);
  }
}

?>
