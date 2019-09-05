<?php
echo "Page to import products from old site to new <br/>";

$auto_category = true;
$category_url = 'https://effectstyle.com.ua/category_81.html';
// if debug==true, no more than 1 product not will import
$debug = true;

#Import DOM file
include 'simple-html-dom.php';

function get_html_from_url($url) {
  if (!$url) {
    return false;
  }
  #Get html from url
  $ch = curl_init($url);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_BINARYTRANSFER, true);
  $test = curl_exec($ch);
  curl_close($ch);
  return $test;
}
$category_html = get_html_from_url($category_url);

#Create DOM element
$domobject = str_get_html($category_html);


#Get links from url
$links = array();
foreach ($domobject->find('td.center', 0)->children(7)->childNodes() as $element){
  foreach ($element->childNodes() as $elementer) {
    array_push($links, 'https://effectstyle.com.ua/' . $elementer->find(a, 0)->href);
  }
}

#Delete not used strings
unset($category_html);
unset($domobject);

#Links loop walker
foreach($links as $index=>$product_url){
  if ($debug == true && $index > 0) {
    break;
  }

  $product_element = get_all_product_details($product_url);




  echo '<br><hr><br>';
}

function get_product_title($html){
  $title = $html->find('h1', 0)->plaintext;
  return $title;
}

function get_product_description($html){
  $description = $html->find('table[width]', 10)->children(2)->first_child();
  // foreach ($description->first_child()->find('text') as $key => $value) {
  //   echo '<hr>'.$key." ".$value;
  // }
  echo $description->innertext;
  return false;
  $texter = '';
  foreach ($description as $text) {
    echo $text;
    $texter = $texter . $text . '<br>';
  }
  return $texter;
}

function get_product_price($html){
  $price = $html->find('div.uah', 0)->plaintext;
  $price = str_replace(' ', '', $price);
  $price = str_replace('грн.', '', $price);
  return $price;
}

function get_product_first_image($html){
  $url = $html->find('a.highslide', 0)->href;
  return full_image_url($url);
}

function get_product_other_images($html){
  $other_images_objects = $html->find('a.highslide');
  $other_images_urls = array();
  foreach ($other_images_objects as $key=>$value){
    if ($key == 0){
      continue;
    }
    array_push($other_images_urls, full_image_url($value->href));
  }
  return $other_images_urls;
}

function get_product_articul($attributes) {
  return $attributes['Маркировка'];
}

function get_product_category($html) {
  $test_var = $html->find('span[itemtype]');
  return end($test_var)->first_child()->first_child()->innertext;
}

function get_product_attributes($html){
  $product_details_blocks = $html->find('div.short-detail');
  $product_detail = array();
  foreach ($product_details_blocks as $key => $value){
    $product_detailed = explode(": ", $value->plaintext);
    $product_detail[$product_detailed[0]] = $product_detailed[1];
  }
  return $product_detail;
}

class Product {
  public $url;
  public $name;
  public $description;
  public $price;
  public $articul;
  public $attributes;
  public $category;
  public $first_image;
  public $other_images;
}

function get_all_product_details($product_url){
  $producter                = new Product();
  $producter->url           = $product_url;
  $product_object           = str_get_html(get_html_from_url($producter->url));
  $producter->name          = get_product_title($product_object);
  $producter->attributes    = get_product_attributes($product_object);
  $producter->articul       = get_product_articul($product_attributes);
  $producter->price         = get_product_price($product_object);
  $producter->front_image   = get_product_first_image($product_object);
  $producter->other_images  = get_product_other_images($product_object);
  $producter->category      = get_product_category($product_object);

  // Not working
  // $product_description = get_product_description($product_object);
  // echo $product_description.'<br>';
}
















function full_image_url($url){
  return 'https://effectstyle.com.ua/'.$url;
}
