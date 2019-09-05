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

  $product_element = new Product($product_url);

  echo '<br>'.$product_element->url;
  echo '<br>'.$product_element->name;
  echo '<br>'.$product_element->price;
  echo '<br>'.$product_element->articul;
  echo '<br>';
  print_r($product_element->attributes);
  echo '<br>'.$product_element->category;
  echo '<br>'.$product_element->first_image;
  echo '<br>';
  print_r($product_element->other_images);




  echo '<br><hr><br>';
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

  function __construct($url){
    $this->url            = $url;
    $this->product_object = str_get_html(get_html_from_url($this->url));
    $this->parse();
  }

  function parse(){
    $this->get_product_title();
    $this->get_product_attributes();
    $this->get_product_articul();
    $this->get_product_price();
    $this->get_product_first_image();
    $this->get_product_other_images();
    $this->get_product_category();
  }

  function get_product_title(){
    $this->name = $this->product_object->find('h1', 0)->plaintext;
  }

  function get_product_description(){
    $this->description = $this->product_object->find('table[width]', 10)->children(2)->first_child();
    // foreach ($description->first_child()->find('text') as $key => $value) {
    //   echo '<hr>'.$key." ".$value;
    // }
    // echo $this->description->innertext;
    return false;
    $texter = '';
    foreach ($this->description as $text) {
      // echo $text;
      $texter = $texter . $text . '<br>';
    }
    return $texter;
  }

  function get_product_price(){
    $this->price = $this->product_object->find('div.uah', 0)->plaintext;
    $this->price = str_replace(' ', '', $this->price);
    $this->price = str_replace('грн.', '', $this->price);
  }

  function get_product_first_image(){
    $this->first_image = $this->full_image_url($this->product_object->find('a.highslide', 0)->href);
  }

  function get_product_other_images(){
    $other_images_objects = $this->product_object->find('a.highslide');
    $other_images_urls = array();
    foreach ($other_images_objects as $key=>$value){
      if ($key == 0){
        continue;
      }
      array_push($other_images_urls, $this->full_image_url($value->href));
    }
    $this->other_images = $other_images_urls;
  }

  function get_product_attributes(){
    $product_details_blocks = $this->product_object->find('div.short-detail');
    $product_detail = array();
    foreach ($product_details_blocks as $key => $value){
      $product_detailed = explode(": ", $value->plaintext);
      $product_detail[$product_detailed[0]] = $product_detailed[1];
    }
    $this->attributes = $product_detail;
  }

  function get_product_articul() {
    if ($this->attributes) {
      $this->articul = $this->attributes['Маркировка'];
    } else {
      $this->articul = false;
    }
  }

  function get_product_category() {
    $this->category = end($this->product_object->find('span[itemtype]'))->first_child()->first_child()->innertext;
  }

  function full_image_url($url){
    return 'https://effectstyle.com.ua/'.$url;
  }
}
