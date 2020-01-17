<?php
include_once('db.php');

if (!empty($_POST)) {
  // Add New Search
  $keyword = htmlspecialchars($_POST["keyword"]);
  $email = htmlspecialchars($_POST["email"]);
  $zip = htmlspecialchars($_POST["zip"]);
  $miles = htmlspecialchars($_POST["miles"]);
  $priceLow = htmlspecialchars($_POST["priceLow"]);
  $priceHigh = htmlspecialchars($_POST["priceHigh"]);
  $highlights = htmlspecialchars($_POST["highlights"]);
  $exclude = htmlspecialchars($_POST["exclude"]);
  $refreshRate = htmlspecialchars($_POST["refreshRate"]);
  $dateStarted = date('Y-m-d');

  if($priceLow == "") { $priceLow = "0"; }
  if($priceHigh == "") { $priceHigh = "0"; }
  $highlights = strtolower(str_replace(' ', '', implode("|",explode(",",$highlights))));
  $exclude = strtolower(str_replace(' ', '', implode("|",explode(",",$exclude))));
  $keyword = strtolower(str_replace(' ', '', $keyword));


  $add_search_query = "INSERT INTO genealogy.classifieds_searches (`ID`, `KEYWORD`, `EMAIL`, `ZIP`, `MILES`, `PRICE_LOW`, `PRICE_HIGH`, `HIGHLIGHT`, `EXCLUDE`, `REFRESH_RATE`, `DATE_STARTED`) VALUES (NULL, '$keyword', '$email', '$zip', '$miles', '$priceLow', '$priceHigh', '$highlights', '$exclude', '$refreshRate', '$dateStarted')";
  $add_search = $con->query($add_search_query);
  if (!$add_search) { die('Invalid query: ' / mysqli_error($con)); }
} else if (isset($_GET["delete"])) {
  $delete_id = htmlspecialchars($_GET["delete"]);
  $delete_search_query = "DELETE FROM genealogy.classifieds_searches WHERE ID = ".$delete_id;
  $delete_search = $con->query($delete_search_query);
  if (!$delete_search) { die('Invalid query: ' / mysqli_error($con)); }
}

// Get Searches
$searches = [];
$searches_query = "SELECT * FROM genealogy.classifieds_searches ORDER BY EMAIL,KEYWORD";
$return_searches = $con->query($searches_query);
if (!$return_searches) { die('Invalid query: ' / mysqli_error($con)); }
while ($row = $return_searches->fetch_assoc()) {
    array_push($searches,$row);
}

$current_searches = "";
foreach ($searches as $key => $search) {
  $display_highlights = implode(", ",explode("|",$search["HIGHLIGHT"]));
  $display_exclude = implode(", ",explode("|",$search["EXCLUDE"]));
  $price_low = "";
  $price_high = "";
  if ($search["PRICE_LOW"] != "0") { $price_low = $search["PRICE_LOW"]; }
  if ($search["PRICE_HIGH"] != "0") { $price_high = $search["PRICE_HIGH"]; }
  $search_rate = "";
  switch ($search["REFRESH_RATE"]) {
    case '10':
      $search_rate = "10 Minutes";
      break;
    case '30':
      $search_rate = "30 Minutes";
      break;
    case '60':
      $search_rate = "1 Hour";
      break;
    case '1440':
      $search_rate = "1 Day";
      break;
    case '10080':
      $search_rate = "1 Week";
      break;
  }
  $current_searches .= "<tr>
  <td>".$search["KEYWORD"]."</td>
  <td>".$search["ZIP"]."</td>
  <td>".$search["MILES"]."</td>
  <td>".$price_low."</td>
  <td>".$price_high."</td>
  <td>".$display_highlights."</td>
  <td>".$display_exclude."</td>
  <td>".$search_rate."</td>
  <td>".$search["EMAIL"]."</td>
  <td><a href=\"".htmlspecialchars($_SERVER["PHP_SELF"])."?delete=".$search["ID"]."\">DELETE</a></td>
  </tr>";
}

?>
<!DOCTYPE html>
<html>
  <head>
    <meta charset="UTF-8">
    <title>title</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
    * {
      box-sizing: border-box;
    }
    div,label,input,select {
      position: relative;
    }
    #container {
      display: flex;
      flex-direction: row;
    }
    #container > div {
      border-left: 2px solid #999;
      padding: 0 20px;
    }
    .input-container {
      margin-bottom: 10px;
    }
    label {
      display: block;
    }
    label.required:before {
      content: "*";
      display: block;
      height: 20px;
      width: 20px;
      font-size: 20px;
      color: red;
      position: absolute;
      left: -10px;
      top: 0;
    }

    @media only screen and (max-width: 600px) {
      #container {
        display: block;
      }
      #container > div {
        border-left: none;
        border-bottom: 2px solid #999;
        padding: 10px;
      }
    }
    </style>
  </head>
  <body>
    <h1>AutoSearch For Classified Listings</h1>
    <div id="container">
      <div>
        <h2>Create A New Search</h2>
        <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
          <div class="input-container">
            <label for="keyword" class="required">Keyword</label>
            <input type="text" name="keyword" placeholder="Table, watch, etc..." class="required" required>
          </div>
          <div class="input-container">
            <label for="zip" class="required">ZIP Code</label>
            <input type="number" min="0" max="9999999999" step="1" name="zip" placeholder="Enter ZIP Code..." class="required" required>
          </div>
          <div class="input-container">
            <label for="miles" class="required">Miles</label>
            <input type="number" min="5" max="9999" step="1" name="miles" value="50" class="required" required>
          </div>
          <div class="input-container">
            <label for="priceLow">Price Low</label>
            <input type="number" min="0" step="0.01" name="priceLow" class="input-money">
          </div>
          <div class="input-container">
            <label for="priceHigh">Price High</label>
            <input type="number" min="0" step="0.01" name="priceHigh" class="input-money">
          </div>
          <div class="input-container">
            <label for="highlights">Highlight listings with keywords</label>
            <p>(separate words with a comma)</p>
            <input type="text" name="highlights" placeholder="Enter Highlight Keywords here...">
          </div>
          <div class="input-container">
            <label for="exclude">Exclude listings with keywords</label>
            <p>(separate words with a comma)</p>
            <input type="text" name="exclude" placeholder="Enter Exclude Keywords here...">
          </div>
          <div class="input-container">
            <label for="refreshRate" class="required">Search Rate</label>
            <select name="refreshRate" class="required" required>
              <option value="10">10 Minutes</option>
              <option value="30">30 Minutes</option>
              <option value="60">1 Hour</option>
              <option value="1440">24 Hours</option>
              <option value="10080">1 Week</option>
            </select>
          </div>
          <div class="input-container">
            <label for="email" class="required">Email</label>
            <input type="email" name="email" placeholder="Enter email for updates" class="required" required>
          </div>
          <input type="submit" name="submit" value="CREATE NEW SEARCH">
      </div>
      <div>
        <h2>Current Searches</h2>
        <table id="searches" border="1">
          <tr>
            <th>Keyword</th>
            <th>ZIP</th>
            <th>Miles</th>
            <th>$ Low</th>
            <th>$ High</th>
            <th>Highlights</th>
            <th>Excluded</th>
            <th>Search Rate</th>
            <th>Email</th>
            <th></th>
          </tr>
          <?php echo $current_searches; ?>
        </table>
      </div>
    </div>
  </body>
</html>
