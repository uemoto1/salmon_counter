<?php
$DBFILE = "data/database.sqlite";

$ni_per_page = 20;

$db = new PDO("sqlite:$DBFILE");
$db->setAttribute(PDO::ATTR_EMULATE_PREPARES, PDO::FETCH_ASSOC);

if (array_key_exists('title', $_GET)) {
    $title = htmlspecialchars($_GET['title']);
    $msg = "Download Log of <mark>'$title'</mark>";
    $cur = $db->prepare("SELECT XDATE, XTIME, IPADDR, CITY, COUNTRY, TITLE FROM DOWNLOAD WHERE TITLE=:title ORDER BY XDATE, XTIME;");
    $cur->bindValue(":title", $title, PDO::PARAM_STR);
} elseif (array_key_exists('country', $_GET)) {
    $country = htmlspecialchars($_GET['country']);
    $msg = "Download Log from <mark>'$country'</mark>";
    $cur = $db->prepare("SELECT XDATE, XTIME, IPADDR, CITY, COUNTRY, TITLE FROM DOWNLOAD WHERE COUNTRY=:country ORDER BY XDATE, XTIME;");
    $cur->bindValue(":country", $country, PDO::PARAM_STR);
} else {
    $msg = "Download Log of All Items";
    $cur = $db->prepare("SELECT XDATE, XTIME, IPADDR, CITY, COUNTRY, TITLE FROM DOWNLOAD ORDER BY XDATE, XTIME;");
}
$cur->execute();

if (array_key_exists('export', $_GET)) {
  header('Content-Disposition: attachment;filename=salmon_download.csv');
  $fp = fopen('php://temp', 'r+b');
  fputcsv($fp, array("Date", "Time", "IP address", "City", "Country", "Filetitle"));
  foreach($cur as $item) {
    fputcsv($fp, array($item['XDATE'], $item['XTIME'], $item['IPADDR'], $item['CITY'], $item['COUNTRY'], $item['TITLE']));
  }
  rewind($fp);
  $tmp = str_replace(PHP_EOL, "\r\n", stream_get_contents($fp));
  echo mb_convert_encoding($tmp, 'SJIS-win', 'UTF-8');
  exit(0);
}


if (array_key_exists('page', $_GET)) {
    $page = intval($_GET['page']);
} else {
    $page = 0;
}
$ii1 = $page * $ni_per_page + 1;
$ii2 = ($page + 1) * $ni_per_page;

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
    <a class="nav-item nav-link" href="summary.php">Summary</a>
    <a class="nav-item nav-link active" href="query.php">Query</a>
    <a class="nav-item nav-link disabled" href="#">Admin</a>
    </div>
    </div>
    </nav>

    <div class="container">
      <div class="row">
        <div class="col col-sm-1"></div>
        <div class="col col-sm-10">

          <p><h3><?php echo "$msg [$ii1-]" ?></h3></p>
          
          <div class="card"><div class="card-block">
            <p> Export all table data as CSV file format. </p>  
            <?php
              $query_export = $_SERVER['self'] . "?" . http_build_query(
                array_merge($_GET, array("export" => "csv"))
              );
              echo "<a href='$query_export' class='btn btn-info' role='button'>Download</a>";
            ?>
          </div></div>
          
          <br/>
        
          <table class="table table-bordered table-hover table-">
            <thead>
              <tr>
                <th>Index</th>
                <th>Date</th>
                <th>Time</th>
                <th>IP address</th>
                <th>City</th>
                <th>Country</th>
                <th>File title</th>
              </tr>
            </thead>
            <tbody>
            <?php
              $ncount = 0;
              foreach ($cur as $item) {
                $ncount ++;
                if (($ii1 <= $ncount) && ($ncount <= $ii2)) {
                  $query_country = $_SERVER['PHP_SELF'].'?'.http_build_query(array('country' => $item['COUNTRY']));
                  $query_title = $_SERVER['PHP_SELF'].'?'.http_build_query(array('title' => $item['TITLE']));
                  echo "<tr>";
                  echo "<th>$ncount</th>";
                  echo "<td>" . htmlspecialchars($item["XDATE"]) . "</td>";
                  echo "<td>" . htmlspecialchars($item["XTIME"]) . "</td>";
                  echo "<td>" . htmlspecialchars($item["IPADDR"]) . "</td>";
                  echo "<td>" . htmlspecialchars($item["CITY"]) . "</td>";
                  echo "<td><a href='$query_country'>" . htmlspecialchars($item['COUNTRY']) . "</a></td>";
                  echo "<td><a href='$query_title'>" . htmlspecialchars($item['TITLE']) . "</a></td>";
                  echo "</tr>";
                }
              }
            ?>
            </tbody>
          </table>

          
          <div style="text-align:center;">
            <p>Total <mark><?php echo $ncount; ?></mark> downloads</p>
            
            <nav aria-label="Page navigation example">
              <ul class="pagination">
                <?php
                $npage = intval(($ncount + 1) / $ni_per_page);
                for ($p=0; $p<=$npage; $p++) {
                    $page_class = ($p==$page) ? "page-item active": "page-item";
                    $query_page = $_SERVER['PHP_SELF'].'?'.http_build_query(
                      array_merge($_GET, array('page' => $p))
                    );
                    $pi = $p * $ni_per_page + 1;
                    echo "<li class='$page_class'><a class='page-link' href='$query_page'>$pi-</a></li>\n";
                }
                ?>
              </ul>
            </nav>

          </div>

        </div>
        <div class="col col-sm-1"></div>
      </div>
    </div>
  </body>

  </html>
