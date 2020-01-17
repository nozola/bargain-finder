<?php

/*
---------------------------
~ KSL Classifieds Scraper ~
---------------------------

Simple Overview:
1. Get data from KSL
2. Check which ones have already been scraped (database with scraped listing IDs)
3. Send email with new listings
4. Save the new listing IDs in Database (for step 2)

*/


/*
OLD Cron Jobs

/usr/local/bin/php -q /home/nozola/public_html/classifieds/single.php --keyword=planer --email=nozola@gmail.com --zip=84095 --distance=50 --descriptionHilight=helicalxxbyrdxxcarbide >/dev/null 2>&1
/usr/local/bin/php -q /home/nozola/public_html/classifieds/single.php --keyword=jointer --descriptionHilight=helicalxxbyrdxxcarbide >/dev/null 2>&1
/usr/local/bin/php -q /home/nozola/public_html/classifieds/single.php --keyword=scrabble --email=shirleybeagley@gmail.com --zip=84095 --distance=50 >/dev/null 2>&1

*/

include_once('db.php');

// Get Cron Job Parameters
$keyword = "";
$email = "";
$zip = "";
$distance = "";
$price_low = "";
$price_high = "";
$description_hilights = [];
$description_hilights_string = "";

if(getopt(null, ["keyword:"])){  // Keyword
  $keyword_array = getopt(null, ["keyword:"]);
  $keyword = $keyword_array["keyword"];
} else { $keyword = "default"; }
if(getopt(null, ["email:"])){    // Email
  $email_array = getopt(null, ["email:"]);
  $email = $email_array["email"];
} else { $email = "nozola@gmail.com"; }
if(getopt(null, ["zip:"])){      // Zip
  $zip_array = getopt(null, ["zip:"]);
  $zip = $zip_array["zip"];
} else { $zip = "84095"; }
if(getopt(null, ["distance:"])){ // Distance
  $distance_array = getopt(null, ["distance:"]);
  $distance = $distance_array["distance"];
} else { $distance = "50"; }
//Optional
if(getopt(null, ["priceLow:"])){
  $price_low_array = getopt(null, ["priceLow:"]);
  $price_low = "&priceFrom=%24".$price_low_array["priceLow"];
}
if(getopt(null, ["priceHigh:"])){
  $price_high_array = getopt(null, ["priceHigh:"]);
  $price_high = "&priceTo=%24".$price_high_array["priceHigh"];
}
if(getopt(null, ["descriptionHilight:"])){
  $description_hilight_array = getopt(null, ["descriptionHilight:"]);
  $description_hilight = $description_hilight_array["descriptionHilight"];
  $description_hilights = explode("xx",$description_hilight);
  $description_hilights_string = str_replace("xx",", ",$description_hilight);
}

// Set up variables
$today = date('Y-m-d');
// $date_limit_date = date('d.m.Y',strtotime("-14 days"));
// $date_limit = $date->format('Y-m-d');
$listing_ids = [];
// Get listing IDs from DB
$listing_ids_query = "SELECT * FROM genealogy.classifieds"; // WHERE DATE_ADDED > '$date_limit'
$return_ids = $con->query($listing_ids_query);

if (!$return_ids) { die('Invalid query: ' / mysqli_error($con)); }
while ($row = $return_ids->fetch_assoc()) {
    array_push($listing_ids,$row['CLASSIFIED_ID']);
}

// Defaults: Has Photos, For Sale, No Category/Subcategory
$buildURL = "https://classifieds.ksl.com/search?category[]=&subCategory[]=&keyword=".$keyword.$priceLow.$priceHigh."&zip=".$zip."&miles=".$distance."&sellerType[]=&marketType[]=Sale&hasPhotos[]=Has%20Photos&postedTime[]=";

//Use file_get_contents to GET the URL in question.
$contents = file_get_contents($buildURL);

//If $contents is not a boolean FALSE value.
if($contents !== false){
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
      "description":"1 HP Delta-Milwaukee 10\u201d Table saw with built in 6\u201d jointer, attached router table. Includes a metal insert as well as a wooden ZCI with Kerf Keeper (riving knife alternative). Table is heavy duty cast iron, along with saw body and the jointer body. They don\u2019t make tools like this any more. Table has two miter slots and a fence with 25\u201d capacity. Fence could easily be expanded\u2014runs on round tube.\n\nSaw works great and has been a great tool for many projects. Need to downsize\u2014no room in metal shop.\n\n\nDoes not include router.",
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

?><!DOCTYPE html>
<html>
  <head>
    <meta charset="UTF-8">
    <title>Classifieds Test</title>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script>
  </head>
  <body>

    <h1>Get Classifieds Info</h1>
    <p><?php
    echo "argv: ".$argv."<br>";
    echo "keyword: ".$params['keyword']."<br>";
    echo "email: ".$params['email']."<br>";
    echo "distance: ".$params['distance']."<br>";
    echo "zip: ".$params['zip']."<br>";
    $message_listings = "";
    $send_email = false;
    $hilight = false;

    foreach ($someArray as $key => $value) {
      // Check if we haven't already scraped this listing
      if(!in_array($value['id'], $listing_ids)){
        $send_email = true;
        // Format Date/Time
        $posted_date = strtotime($value['displayTime']);
        $posted_date_formatted = date('d/M/Y h:i', $posted_date);
        // Format Photo URL
        $photoURL = "https:" . str_replace("\/","/",$value['photo']);
        // Check for hilight words
        $hilight_photo = "";
        $hilight_title = "";
        foreach ($description_hilights as $key1 => $value1) {
          if (stripos($value['description'], $value1) !== false || stripos($value['title'], $value1) !== false) {
            $hilight = true;
            $hilight_photo = " border: 2px solid #f7941d;";
            $hilight_title = " style=\"font-size: 18px; color: #f7941d;\" ";
          }
        }

        // Add listing to email message
        $message_listings .= "
        <tr>
          <td><a href=\"https://classifieds.ksl.com/listing/".$value['id']."\" target=\"_blank\" style=\"text-decoration: none; color: #000;\"><span style=\"background-image: url('".$photoURL."'); background-size: cover; background-position: center; width: 250px; height: 200px; display: block;".$hilight_photo."\"></span></a></td>
          <td><a href=\"https://classifieds.ksl.com/listing/".$value['id']."\" target=\"_blank\" style=\"text-decoration: none; color: #000;\"><span".$hilight_title.">".$value['title']."</span><hr><strong style=\"color: #f7941d; font-size: 20px;\">$".$value['price']."</strong><br><span>".$value['city']."</span><br><span>".$posted_date_formatted."</span></a></td>
        </tr>
        <tr style=\"margin-bottom: 20px;\">
          <td colspan=\"2\"><a href=\"https://classifieds.ksl.com/listing/".$value['id']."\" target=\"_blank\" style=\"text-decoration: none; color: #000;\"><span>".$value['description']."</span></a></td><hr>
        </tr><br>";

        // Insert into DB to remember this listing was alredy scraped
        $sql = "INSERT INTO genealogy.classifieds (ID, CLASSIFIED_ID, KEYWORD, DATE_ADDED)
        VALUES (NULL, '".$value["id"]."', '".$keyword."', '".$today."')";
        if ($con->query($sql) === TRUE) {
            //echo "New record created successfully";
        } else {
            echo "Error: " . $sql . "<br>" . $conn->error;
        }
      }
    }

    // Send email if there are new listings
    if($send_email){
      $to = $email;
      $subject = "New Listing: ".$keyword;
      $keyword_using = getopt(null, ["keyword:"]);
      $hilight_notification = $hilight ? "<h1 style=\"color: #f7941d;\">!!! Has Hilight !!!</h1>" : "";

      $message = "
      <html>
      <head>
      <title>New Listing: ".$keyword."</title>
      </head>
      <body>
        ".$hilight_notification."
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
            <th>Hilights</th>
          </tr>
          <tr>
            <td>".$keyword."</td>
            <td>".$zip."</td>
            <td>".$distance."</td>
            <td>".$priceLow."</td>
            <td>".$priceHigh."</td>
            <td>".$description_hilights_string."</td>
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
    ?></p>

  </body>
</html>
