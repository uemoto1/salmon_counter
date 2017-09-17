<?php
echo "Starting...\n";

include_once("./geoip/geoipcity.inc");
include_once("./geoip/timezone.php");
$geoip = geoip_open("./geoip/GeoLiteCity.dat", GEOIP_STANDARD);


$DBFILE = "./download_count.sqlite";
$LOGDIR = "../log";
$PATTERN = "|^([0-9\.]+).*?\[([0-9a-zA-Z/]+):([0-9:]+).*?GET /download/(.+?) .*$|";

$IGNORE = array(
  "example.bin",
  "extract_session.php",
  "salmon-v.0.0.1.tar.gz",
  "SALMON-v0.0.1.tar.gz"
);

$sql_create_db = <<< SQL
CREATE TABLE IF NOT EXISTS LOGFILE (
  FILENAME TEXT PRIMARY KEY,
  MTIME INTEGER
);

CREATE TABLE IF NOT EXISTS DOWNLOAD (
  TITLE TEXT,
  IPADDR TEXT,
  XTIME TEXT,
  XDATE TEXT,
  XDATE_EN TEXT,
  COUNTRY TEXT,
  CITY TEXT
);
SQL;

$sql_insert_log = <<< SQL
INSERT OR REPLACE INTO LOGFILE(FILENAME, MTIME) VALUES(:filename, :mtime)
SQL;

$sql_del_download = <<< SQL
DELETE FROM DOWNLOAD WHERE XDATE=:xdate;
SQL;

$sql_ins_download = <<< SQL
INSERT INTO DOWNLOAD (TITLE,IPADDR,XTIME,XDATE,XDATE_EN,COUNTRY,CITY)
VALUES (:title,:ipaddr,:xtime,:xdate,:xdate_en,:country,:city);
SQL;




# データベースへ接続
echo "Connecting to Datebase: $DBFILE\n";
$db = new PDO("sqlite:$DBFILE");
$db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
$db->exec($sql_create_db);
# 前回の更新ログをロード
$cur = $db->prepare('SELECT FILENAME, MTIME FROM LOGFILE;');
$cur->execute();
$prevous_log = $cur->fetchAll(PDO::FETCH_COLUMN | PDO::FETCH_GROUP);

$db->beginTransaction();

# ログファイル一覧を取得
echo "Scanning apache logs from $LOGDIR\n";
$logfile_list = glob("$LOGDIR/*/*/salmon-access.log.*");
$log_count = 0; # 読み込んだログ数カウント
foreach ($logfile_list as $logfile) {
    $mtime =  filemtime($logfile);
    if (array_key_exists($logfile, $prevous_log) && ($prevous_log[$logfile][0] == $mtime)) {
        continue;
    }

    $xdate = pathinfo($logfile, PATHINFO_EXTENSION);
  
    $cur = $db->prepare('INSERT OR REPLACE INTO LOGFILE(FILENAME, MTIME) VALUES(:filename, :mtime)');
    $cur->bindValue(":filename", $logfile, PDO::PARAM_STR);
    $cur->bindValue(":mtime", $mtime, PDO::PARAM_INT);
    $cur->execute();
  
    $cur = $db->prepare($sql_del_download);
    $cur->bindValue(":xdate", $xdate, PDO::PARAM_STR);
    $cur->execute();
  
    $dl_count = 0;
    $fp = fopen($logfile, "r");
    while ($line = fgets($fp)) {
        if (preg_match($PATTERN, $line, $result)) {
            $ipaddr = $result[1];
            $xdate_en = $result[2];
            $xtime = $result[3];
            $title = $result[4];
      
            if (in_array($title, $IGNORE)) {
                continue;
            }
  
            $geo = GeoIP_record_by_addr($geoip, $ipaddr);
            $country = $geo->country_name;
            $city = $geo->city;
      
            $cur = $db->prepare($sql_ins_download);
            $cur->bindValue(":ipaddr", $ipaddr, PDO::PARAM_STR);
            $cur->bindValue(":xdate_en", $xdate_en, PDO::PARAM_STR);
            $cur->bindValue(":xtime", $xtime, PDO::PARAM_STR);
            $cur->bindValue(":title", $title, PDO::PARAM_STR);
            $cur->bindValue(":xdate", $xdate, PDO::PARAM_STR);
            $cur->bindValue(":country", $country, PDO::PARAM_STR);
            $cur->bindValue(":city", $city, PDO::PARAM_STR);
            $cur->execute();

            $dl_count++;
        }
    }
    fclose($fp);
    
    echo "$logfile is updated and $dl_count downloads detected.\n";
    $log_count++;
}
echo "$log_count files are updated.\n";

echo "Updating Databese:$DBFILE...\n";
$db->commit();


echo "Done.\n";

//geoip_close( $gi );
