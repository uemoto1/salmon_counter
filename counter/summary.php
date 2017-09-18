<?php
$DBFILE = "data/database.sqlite";

$db = new PDO("sqlite:$DBFILE");
$db->setAttribute(PDO::ATTR_EMULATE_PREPARES, PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Download Count</title>
  <script src="https://code.jquery.com/jquery-3.2.1.min.js"></script>
  <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0-alpha.6/css/bootstrap.min.css" integrity="sha384-rwoIResjU2yc3z8GV/NPeZWAv56rSmLldC3R/AZzGRnGxQQKnKkoFVhFQhNUwEyJ" crossorigin="anonymous">
  <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0-alpha.6/js/bootstrap.min.js" integrity="sha384-vBWWzlZJ8ea9aCX4pEW3rVHjgjt7zpkNpZk+02D9phzyeVkE+jo0ieGizqPLForn" crossorigin="anonymous"></script>

</head>

<body>

<nav class="navbar navbar-toggleable-md navbar-light bg-faded">
<button class="navbar-toggler navbar-toggler-right" type="button" data-toggle="collapse" data-target="#navbarNavAltMarkup" aria-controls="navbarNavAltMarkup" aria-expanded="false" aria-label="Toggle navigation">
<span class="navbar-toggler-icon"></span>
</button>
<a class="navbar-brand" href="#">SALMON Counter [Beta]</a>
<div class="collapse navbar-collapse" id="navbarNavAltMarkup">
<div class="navbar-nav">
<a class="nav-item nav-link" href="index.html">Top</a>
<a class="nav-item nav-link active" href="summary.php">Summary</a>
<a class="nav-item nav-link" href="query.php">Query</a>
<a class="nav-item nav-link disabled" href="#">Admin</a>
</div>
</div>
</nav>

<div class="container">
    <div class="row">
      <div class="col col-sm-1"></div>
      <div class="col col-sm-10">

        <p><h3>Download summary by version</h3></p>
        <table class="table table-bordered table-hover table-">
          <thead>
            <tr>
              <th>File title</th>
              <th>Total downloads</th>
              <th>First download</th>
              <th>Recent download</th>
            </tr>
          </thead>
          <tbody>
            <?php
      $cur = $db->prepare("SELECT TITLE, COUNT(*), MIN(XDATE), MAX(XDATE) FROM DOWNLOAD GROUP BY TITLE ORDER BY TITLE;");
      $cur->execute();
      foreach ($cur as $item) {
          $query_title = "query.php?" . http_build_query(
          array("title" => htmlspecialchars($item["TITLE"]))
        );
          echo "<tr>";
          echo "<td><a href='$query_title'>" . htmlspecialchars($item["TITLE"]) . "</a></td>";
          echo "<td>" . htmlspecialchars($item["COUNT(*)"]) . "</td>";
          echo "<td>" . htmlspecialchars($item["MIN(XDATE)"]) . "</td>";
          echo "<td>" . htmlspecialchars($item["MAX(XDATE)"]) . "</td>";
          echo "<td></td>";
          echo "</tr>";
      }
      ?>
          </tbody>
        </table>

        <p><h3>Download summary by country</h3></p>
        <table class="table table-bordered table-hover table-">
          <thead>
            <tr>
              <th>Country</th>
              <th>Total downloads</th>
              <th>First download</th>
              <th>Recent download</th>
            </tr>
          </thead>
          <tbody>
            <?php
    $cur = $db->prepare("SELECT COUNTRY, COUNT(*), MIN(XDATE), MAX(XDATE) FROM DOWNLOAD GROUP BY COUNTRY ORDER BY COUNTRY;");
    $cur->execute();
    foreach ($cur as $item) {
        $query_country = "query.php?" . http_build_query(
        array("country" => htmlspecialchars($item["COUNTRY"]))
      );

        echo "<tr>";
        echo "<td><a href='$query_country'>" . htmlspecialchars($item["COUNTRY"]) . "</a></td>";
        echo "<td>" . htmlspecialchars($item["COUNT(*)"]) . "</td>";
        echo "<td>" . htmlspecialchars($item["MIN(XDATE)"]) . "</td>";
        echo "<td>" . htmlspecialchars($item["MAX(XDATE)"]) . "</td>";
        echo "</tr>";
    }
    ?>
          </tbody>
        </table>
      </div>
      <div class="col col-sm-1"></div>

    </div>
</div>
</body>
</html>
