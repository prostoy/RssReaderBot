<html>
<?php
$servername = "localhost";
$username = "root";
$password = "1234";
$dbname = "haber";
$image_path = 'C:/xampp/htdocs/saha/public/upload/news/';
set_time_limit(5000);
register_shutdown_function( "fatal_handler" );



function fatal_handler() {
    $errfile = "unknown file";
    $errstr  = "shutdown";
    $errno   = E_CORE_ERROR;
    $errline = 0;

    $error = error_get_last();

    if($error !== NULL) {
        $errno   = $error["type"];
        $errfile = $error["file"];
        $errline = $error["line"];
        $errstr  = $error["message"];

        logError("fatal_handler", "Error No:".$errno ." Error String: ". $errstr ." Error File: ". $errfile ." Error Line: ". $errline);
    }
}

function web_image_exists($url)
 {
    $file_headers = @get_headers($url);
  if (strpos($file_headers[0], '404')  !== false ) {
    return false;
  }
  else {
    return true;
  }
}
function getIsHaberExist($servername, $username, $password, $dbname , $slug, $pubdate)
{
  $conn = new mysqli($servername, $username, $password, $dbname);
  $ret = array();
  if ($conn->connect_error) {  
    logError("getHaberExist ",$conn->connect_error);
    die("Connection failed: " . $conn->connect_error);
  
    return $ret;
  }

  $sql = "select slug from news where slug = '".$slug."' and publishedAt = '".$pubdate."'" ;
  $result = $conn->query($sql);

  // Put them in array
  for ($i = 0; $ret[$i] = mysqli_fetch_assoc($result); $i++);

  // Delete last empty one
  array_pop($ret);
  $conn->close();
  
  if (count($ret) > 0) return true;

  return false;
}
function getCategoryList($servername, $username, $password, $dbname)
{
  $conn = new mysqli($servername, $username, $password, $dbname);
  $ret = array();
  if ($conn->connect_error) {  
    logError("getCategoryList ",$conn->connect_error);
    die("Connection failed: " . $conn->connect_error);
  
    return $ret;
  }

  $sql = "select id , slug from categories ";
  $result = $conn->query($sql);

  // Put them in array
  for ($i = 0; $ret[$i] = mysqli_fetch_assoc($result); $i++);

  // Delete last empty one
  array_pop($ret);

  $conn->close();
  return $ret;
}

function logError($funcname,$str)
{
  file_put_contents("bot_error.txt","In Function: ".getdate()." ".$funcname . " ErrorDetail:".$str."\n" );
}

function replace_extension($filename, $new_extension)
{
  $info = pathinfo($filename);
  return ($info['dirname'] ? $info['dirname'] . DIRECTORY_SEPARATOR : '')
    . $info['filename']
    . '.'
    . $new_extension;
}
function downloadImage($img_src, $download_path) // function returns new_image_name
{
 
  if (strlen($img_src) < 5 || web_image_exists($img_src) === false )
  {
    echo "<h2 style='color:red;'>Dosya Karşı Sunucuda Bulunamadı " . $img_src."</h2>";
    return "";
  }
  $new_image_name = md5($img_src) . '.' . pathinfo($img_src, PATHINFO_EXTENSION);

  

  echo "Downloading Source:" . $img_src . " Downloaded Path: " . $download_path . 'large/' . $new_image_name . "<br>";
  try {
    $ch = curl_init($img_src);
    $fp = fopen($download_path . 'large/' . $new_image_name, 'wb'); // ilk önce büyük olanı indiriyoruz
    curl_setopt($ch, CURLOPT_FILE, $fp);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_exec($ch);
    curl_close($ch);
    fclose($fp);
  } catch (Exception $e) {
    echo "Error Message: " . $e;
    echo "Error in function downloadImage: Image Source: " . $img_src . " Downloaded Path: " . $download_path . 'large/' . $new_image_name;
    logError("downloadImage",$e." "."Image Source: " . $img_src . " Downloaded Path: " . $download_path . 'large/' . $new_image_name);
    return "";
  }
  $extension = pathinfo($img_src, PATHINFO_EXTENSION);
  $buyuk_image_yolu = $download_path . 'large/' . $new_image_name;

  if (strtolower($extension) === "webp") // if webp than convert to jpg 
  {
    try {
      $im = imagecreatefromwebp($buyuk_image_yolu);

      $buyuk_image_yolu = replace_extension($buyuk_image_yolu, '.jpeg');
      $new_image_name = replace_extension($new_image_name, '.jpeg');
      $extension = "jpeg";
      imagejpeg($im, $buyuk_image_yolu, 100); // Convert it to a jpeg file with 100% quality
      imagedestroy($im);
    } catch (Error $e) {
      echo "imagecreatefromwebp : Error Message: " . $e;
      logError("downloadImage.imagecreatefromwebp ",$e." "."Image Source: " . $img_src . " Downloaded Path: " . $download_path . 'large/' . $new_image_name);
      return "";
    }
  }



  $image = null;
  if (strtolower($extension) === "jpg" || strtolower($extension) === "jpeg") {
    try {
      $image = imagecreatefromjpeg($buyuk_image_yolu); // For JPEG

      $imgResizedThumb = imagescale($image, 480, 300); // for thumb 
      imagejpeg($imgResizedThumb, $download_path . 'thumb/' . $new_image_name);

      $imgResizedshowcase = imagescale($image, 480, 370); // for showcase
      imagejpeg($imgResizedshowcase, $download_path . 'showcase/' . $new_image_name);

      imagedestroy($image);
    } catch (Error $e) {
      echo "imagecreatefromjpeg : Error Message: " . $e;
      logError("downloadImage.imagecreatefromjpeg ",$e." "."Image Source: " . $img_src . " Downloaded Path: " . $download_path . 'large/' . $new_image_name);
    }
  } else if (strtolower($extension) === "png") {
    try {
      $image = imagecreatefrompng($buyuk_image_yolu);   // For PNG
      $imgResizedThumb = imagescale($image, 480, 300); // thumb için
      imagepng($imgResizedThumb, $download_path . 'thumb/' . $new_image_name);

      $imgResizedshowcase = imagescale($image, 480, 370); // showcase için
      imagepng($imgResizedshowcase, $download_path . 'showcase/' . $new_image_name);

      imagedestroy($image);
    } catch (Error $e) {
      echo "imagecreatefrompng : Error Message: " . $e;
      logError("downloadImage.imagecreatefrompng ",$e." "."Image Source: " . $img_src . " Downloaded Path: " . $download_path . 'large/' . $new_image_name);
    }
  } else {
    echo "downloadImage : Bilinmeyen img uzantisi: ";
    return "";
  }

  return  $new_image_name;
  

}
function getImageSource($description, $content)
{

  $dom = new DOMDocument;
  $dom->loadHTML($description);
  $source = "";
  foreach ($dom->getElementsByTagName("img") as $tag) {
    foreach ($tag->attributes as $attribName => $attribNodeVal) {
      if ($attribName === "src") {
        $source = $tag->getAttribute($attribName);
        if (strlen($source > 10))
          break;
      }
    }
  }

  if ($source === null || strlen($source <= 5)) {
    $dom->loadHTML($content);
    foreach ($dom->getElementsByTagName("img") as $tag) {
      foreach ($tag->attributes as $attribName => $attribNodeVal) {
        if ($attribName === "src") {
          $source = $tag->getAttribute($attribName);
          if (strlen($source > 10))
            break;
        }
      }
    }
  }

  return $source;
}


$count = 0;

$kategoriler = getCategoryList($servername, $username, $password, $dbname);
foreach ($kategoriler as $kat) {
  
  
  echo "<h1>". $kat["slug"]  . "Cathegory is taked...</h3><br>";




  // Define the feed URL
  $feed_url = "https://www.bha.net.tr/kategori/" . $kat["slug"] . "/feed";

  // Get the contents of the feed
  $content = file_get_contents($feed_url);


  $rss = new DOMDocument();
  $rss->load($feed_url);
  $feed = array();
  foreach ($rss->getElementsByTagName('item') as $node) {
    $item = array(
      'title' => $node->getElementsByTagName('title')->item(0)->nodeValue,
      'link' => $node->getElementsByTagName('link')->item(0)->nodeValue,
      'pubDate' => $node->getElementsByTagName('pubDate')->item(0)->nodeValue,
      'description' => $node->getElementsByTagName('description')->item(0)->nodeValue,
      'content' => $node->getElementsByTagName('encoded')->item(0)->nodeValue

    );


    $title = $item["title"];
    $link = $item["link"];
    $datetime = new DateTime($item["pubDate"]);
    $datetime->add(new DateInterval('PT3H0M0S'));
    $pubdate = $datetime->format('Y-m-d H:i:s');
    $temp = explode('/', $link); // linki slaclara böl
    $slug = end($temp); // linkteki son slaçtan sonraki kısım bizim slug oluyor
  if (getIsHaberExist($servername, $username, $password, $dbname,$slug , $pubdate) === false)  // true ise zaten vardır almaya gerek yok
  {

    $description = mb_convert_encoding($item["description"],  'UTF-8'); //'ISO-8859-1',
    $img_src = getImageSource($item["description"], $item["content"]);


    echo "Kategori: " . $kat["slug"] . " Title: " . $title . " Link: " . $link . "<br>";

    $new_image_name = downloadImage($img_src, $image_path); 

    
      $content = mb_convert_encoding($item["content"], 'html-entities', 'utf-8');
      $summary_dom = new DOMDocument();
      $summary_dom->loadHTML("<html>" . $content . "</html>");
      $summary = "";
      if ($summary_dom->getElementsByTagName('h2')->item(0) !== null)
        $summary = mb_convert_encoding($summary_dom->getElementsByTagName('h2')->item(0)->nodeValue, 'html-entities', 'utf-8');
      if ($summary_dom->getElementsByTagName('h3')->item(0) !== null)
        $summary = mb_convert_encoding($summary_dom->getElementsByTagName('h3')->item(0)->nodeValue, 'html-entities', 'utf-8');




      $conn = new mysqli($servername, $username, $password, $dbname);

      if ($conn->connect_error) {
        logError("scriptbody  ",$conn->connect_error);
        die("Connection failed: " . $conn->connect_error);
      }

     
      $sql = "INSERT INTO news ( categoryId, authorId, title, listTitle, slug, summary, image, hideImage, content, metaTitle, metaDescription, metaKeywords, status, visited, language, publishedAt, createdAt, updatedAt) " .
        " VALUES (" . $kat["id"] . ",NULL , '" . $title . "', '','" . $slug . "','" . $summary . "' , '" . $new_image_name . "' , 0 , '" . $conn->real_escape_string($content) . "' , '','','','published' , 0 , 'tr','" . $pubdate . "' , now() , now() )";


      try {
        if ($conn->query($sql) === FALSE) {
          echo "Error: " . $sql . "<br>" . $conn->error;
          echo $sql;
          return;
        }
      } catch (mysqli_sql_exception $e) {
        // $e is the exception, you can use it as you wish 
        //echo $conn->error. "<br>" ;
        echo $feed_url . "<br>";
        echo "Error: " . "<br>" .  $sql .  $conn->error;
        logError("scriptbody . INSERT INTO news...  ",$e . " Link: ".$link . " Kategori: ".$kat["slug"]);
      }

      $conn->close();

      $count = $count + 1;
    }
    else
        echo  "<h3 style='color:red;'>".$title." Zaten Var. Atlanıyor...</h3>";
    
  }

  echo "<h3 style='color:green;'>".$kat["slug"] . ' kategorisinden ' . $count . ' adet haber eklendi</h3>';
  $count = 0;
}
?>

</html>
