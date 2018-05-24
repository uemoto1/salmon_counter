<?php

$DBFILE = "data/database.sqlite";
//$LOGDIR = "../../../www/log/*/*/";

/*
$LOGDIR = "..";
$MODE = 0;
$PATTERN = '/salmon-access\.log\.([0-9]+)/';
$DATE1 = 20170614;
$DATE2 = 20180514;
*/

$LOGDIR = "../../../log";
$MODE = 1;
$PATTERN = '/access_log_([0-9]+)\.gz/';
$DATE1 = 20180515; // Start to switch the sakura internet server
$DATE2 = 99999999; // End

$BASEDIR = array(
  "/download"
);

$IGNORE = array(
  "/download/example.bin",
  "/download/extract_session.php",
  "/download/salmon-v.0.0.1.tar.gz",
  "/download/SALMON-v0.0.1.tar.gz"
);

include_once("geoip-api-php/src/geoipcity.inc");
include_once("geoip-api-php/src/timezone.php");
$geoip = geoip_open("data/GeoLiteCity.dat", GEOIP_STANDARD);

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

function purse_log($text, $mode, $basedir) {
  if (preg_match("|^(.*?)\s+\[(.*?)\]\s+\"(.*?)\"\s+200|", $text, $matches)) {
    $temp1 = explode(" ", $matches[1]);
    $temp2 = explode(" ", $matches[2]);
    $temp3 = explode(" ", $matches[3]);
    $item = $temp3[1];

    if (in_array(dirname($item), $basedir)) {
      preg_match("|^(.*?):([\d:]+)$|", $temp2[0], $matches);
      $xdate = $matches[1];
      $xtime = $matches[2];
      if ($mode == 0) {
        $addr = $temp1[0];
      } else if  ($mode == 1) {
        $addr = gethostbyname($temp1[1]);
      }
      return array($addr, $xdate, $xtime, $item);
    }
  }
  return array();
}

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
$logfile_list = glob("$LOGDIR/*log*");
$ncount_logfile = 0; # 読み込んだログ数カウント
foreach ($logfile_list as $logfile) {

    $logfile_title = pathinfo($logfile, PATHINFO_BASENAME);
    
    if (! preg_match($PATTERN, $logfile_title, $matches)) {
      continue;
    }
    
    $yyyymmdd = $matches[1];
    
    if (($yyyymmdd < $DATE1) || ($DATE2 <= $yyyymmdd)) {
      continue;
    }
    
    $mtime =  filemtime($logfile);
    if (array_key_exists($logfile_title, $prevous_log)) {
      if ($prevous_log[$logfile_title][0] == $mtime) {
        continue;
      }
    }
    
    $yyyy = substr($yyyymmdd, 0, 4);
    $mm = substr($yyyymmdd, 4, 2);
    $dd = substr($yyyymmdd, 6, 2);
    $xdate = "$yyyy/$mm/$dd";
  
    $cur = $db->prepare('INSERT OR REPLACE INTO LOGFILE(FILENAME, MTIME) VALUES(:filename, :mtime)');
    $cur->bindValue(":filename", $logfile_title, PDO::PARAM_STR);
    $cur->bindValue(":mtime", $mtime, PDO::PARAM_INT);
    $cur->execute();
  
    $cur = $db->prepare($sql_del_download);
    $cur->bindValue(":xdate", $xdate, PDO::PARAM_STR);
    $cur->execute();
  
    $ncount_dl = 0;
    //$fp = fopen($logfile, "r");
    $fp = gzopen($logfile, "r");
    while ($line = fgets($fp)) {
        
        $result = purse_log($line, $MODE, $BASEDIR);
        if (count($result) > 0) {
          
          
          $ipaddr = $result[0];
          $xdate_en = $result[1];
          $xtime = $result[2];
          $title = $result[3]; 
          
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

          $ncount_dl++;
        }
    }
    fclose($fp);
    
    echo "$logfile is updated and $ncount_dl downloads detected.\n";
    $ncount_logfile++;
}
echo "$ncount_logfile files are updated.\n";

echo "Updating Databese:$DBFILE...\n";
$db->commit();


echo "Success\n";

//geoip_close( $gi );
