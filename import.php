<?php
ini_set('max_execution_time', '600');
echo "Page to import products from old site to new <br/>";


include 'simple-html-dom.php';


// if debug==true, no more than 1 product not will import
$debug = true;
$save_products = false;
$file = 'category_urls.txt';


if (file_exists($file)){
  $lines = file($file);
  $category_url = strval(trim($lines[0]));
  if ($debug == false) {
    unset($lines[0]);
    file_put_contents($file, implode('', $lines));
  }
} else {
  echo 'nope';
  $category_url = 'http://beltrakt.ru/traktory/mini-traktory/';
}


$links = get_product_links($category_url);

foreach($links as $index=>$product_url){
  if ($debug == true && $index > 0) {
    break;
  }

  $product = new Product();
  $product->parse($product_url);
  if ($debug == true) {
    foreach ($product->display_info() as $key => $value){
      echo '<b>'.$key.':</b><br>';
      print_r($value);
      echo '<br><hr>';
    }
  }
  if ($save_products) {
    $new_url = $product->save_woocommerce();

    echo $product_url.'<br>';
    echo '<a href="'.$new_url.'">'.$new_url.'</a><br>';
    echo '<br><hr><br>';
  }
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
  public $slug;

  function parse($url){
    $this->url = $url;
    $this->product_object = str_get_html(get_html_from_url($this->url));
    $this->get_product_title();
    $this->get_product_description();
    $this->get_product_price();
    $this->get_product_attributes();
    $this->get_product_first_image();
    $this->get_product_other_images();
    $this->get_product_category();
    $this->get_product_slug();
  }

  function get_product_title(){
    $this->name = $this->product_object->find('h1', 0)->plaintext;
  }

  function get_product_description(){
    $temp = $this->product_object->find('article', 0);
    $temp->find('ul.nav-tabs', 0)->outertext = '';
    $temp->find('div.tab-content', 0)->outertext = '';
    $this->description = $temp->innertext;
  }

  function get_product_price(){
    $this->price = $this->product_object->find('span[itemprop=price]', 0)->plaintext;
    $this->price = str_replace(' ', '', $this->price);
    $this->price = str_replace('грн.', '', $this->price);
  }

  function get_product_first_image(){
    $this->first_image = strval($this->full_image_url($this->product_object->find('img.thumbnail', 0)->src));
  }

  function get_product_other_images(){
    $other_images_objects = $this->product_object->find('div#foto', 0)->find('img');
    if (empty($other_images_objects)){
    }
    $other_images_urls = array();
    foreach ($other_images_objects as $key=>$value){
      // if ($key == 0){
      //   continue;
      // }
      array_push($other_images_urls, $this->full_image_url($value->src));
    }
    $this->other_images = $other_images_urls;
  }

  function get_product_attributes(){
    $product_details_blocks = $this->product_object->find('div#tth', 0)->find('tr');
    $product_detail = array();
    foreach ($product_details_blocks as $key => $value){
      $product_detail[$value->find('td', 0)->plaintext] = $value->find('td', 1)->plaintext;
    }
    $this->attributes = $product_detail;
  }

  function get_product_articul() {
    if ($this->attributes) {
      $this->articul = $this->attributes['Маркировка'];
      unset($this->attributes['Маркировка']);
    } else {
      $this->articul = false;
    }
  }

  function get_product_category() {
    $this->category = $this->product_object->find('div.path', 0)->last_child()->plaintext;
  }

  function get_product_slug() {
    $temp = explode('/', $this->url);
    $this->slug = $temp[count($temp)-2];
  }

  function display_info(){
    $temp = $this->product_object;
    unset($this->product_object);
    return get_object_vars($this);
    $this->product_object = $temp;
  }

  function full_image_url($url){
    return 'http://beltrakt.ru'.$url;
  }

  function save_woocommerce() {
    if ($this->product_exists()) {
      
    }
    $post = array(
        'post_author' => $user_id,
        'post_content' => '',
        'post_status' => "publish",
        'post_title' => $this->name,
        'post_parent' => '',
        'post_type' => "product",
    );
      //Create post
    $post_id = wp_insert_post( $post, $wp_error );
    if($post_id && $this->first_image){
        add_post_meta($post_id, '_thumbnail_id', $this->image_download_woocommerce($this->first_image));
    }
    wp_set_object_terms( $post_id, $this->category, 'product_cat' );
    wp_set_object_terms($post_id, 'simple', 'product_type');

    update_post_meta( $post_id, '_visibility', 'visible' );
    update_post_meta( $post_id, '_stock_status', 'instock');
    update_post_meta( $post_id, 'total_sales', '0');
    update_post_meta( $post_id, '_downloadable', 'no');
    update_post_meta( $post_id, '_virtual', 'no');
    update_post_meta( $post_id, '_purchase_note', "" );
    update_post_meta( $post_id, '_featured', "no" );
    update_post_meta( $post_id, '_weight', "" );
    update_post_meta( $post_id, '_length', "" );
    update_post_meta( $post_id, '_width', "" );
    update_post_meta( $post_id, '_height', "" );
    update_post_meta($post_id, '_sku', $this->articul);
    update_post_meta( $post_id, '_product_attributes', $this->attributes_woocommerce());
    update_post_meta( $post_id, '_price', $this->price);
    update_post_meta( $post_id, '_sold_individually', "" );
    update_post_meta( $post_id, '_manage_stock', "no" );
    update_post_meta( $post_id, '_backorders', "no" );
    update_post_meta( $post_id, '_stock', "" );
    if ($this->other_images) {
      foreach($this->other_images as $key=>$url) {
        update_post_meta( $post_id, '_product_image_gallery', $this->image_download_woocommerce($url));
      }
    }
    return get_permalink($post_id);
  }

  function attributes_woocommerce(){
    $i = 0;
    foreach ($this->attributes as $name => $value) {
        $product_attributes[$i] = array (
            'name' => htmlspecialchars( stripslashes( $name ) ),
            'value' => $value,
            'position' => 1,
            'is_visible' => 1,
            'is_variation' => 1,
            'is_taxonomy' => 0
        );
        $i++;
    }
    return $product_attributes;
  }

  function image_download_woocommerce($url) {
    $image_url        = $url;
    $image_name       = end(explode('/', $url));
    $image_data       = get_html_from_url($url);
    $upload_dir       = wp_upload_dir();
    $unique_file_name = wp_unique_filename( $upload_dir['path'], $image_name );
    $filename         = basename( $unique_file_name );
    if( wp_mkdir_p( $upload_dir['path'] ) ) {
        $file = $upload_dir['path'] . '/' . $filename;
    } else {
        $file = $upload_dir['basedir'] . '/' . $filename;
    }
    file_put_contents( $file, $image_data );
    $wp_filetype = wp_check_filetype( $filename, null );
    $attachment = array(
        'post_mime_type' => $wp_filetype['type'],
        'post_title'     => sanitize_file_name( $filename ),
        'post_content'   => '',
        'post_status'    => 'inherit'
    );
    $attach_id = wp_insert_attachment( $attachment, $file, $post_id );
    require_once(ABSPATH . 'wp-admin/includes/image.php');
    $attach_data = wp_generate_attachment_metadata( $attach_id, $file );
    wp_update_attachment_metadata( $attach_id, $attach_data );
    return $attach_id;
  }
}

function get_html_from_url($url) {
  if (!$url) {
    return false;
  }
  $ch = curl_init($url);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_BINARYTRANSFER, true);
  $test = curl_exec($ch);
  $http_code = curl_getinfo( $ch, CURLINFO_HTTP_CODE );
  // echo $url.' - '.$http_code;
  curl_close($ch);
  return $test;
}

function get_product_links($category_url){
  $category_html = get_html_from_url($category_url);
  $domobject = str_get_html($category_html);
  unset($category_html);
  $links = array();
  foreach ($domobject->find('a.cat_title') as $element){
    array_push($links, 'http://beltrakt.ru' . $element->href);
  }
  unset($domobject);
  unset($category_html);
  return $links;
}
