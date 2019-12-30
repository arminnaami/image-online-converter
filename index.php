<?php
/*
 * image-online-converter
 * Image online converter using imagemagick & jpegoptim
 *
 * * BMP => JPG convert & compression
 *   /image/?http://website.co.jp/Picrure.bmp
 *   ** File ** /image/co/http%253A%252F%252Fwebsite.co.jp%252FPicture.jpg
 *
 * * JPG compression
 *   /image/?http://website.co.jp/Photo.jpg
 *   ** File ** /image/co/http%253A%252F%252Fwebsite.co.jp%252FPhoto.jpg
 *
 * * JPG resize & compression
 *   /image/?u=http://website.co.jp/Photo.jpg&w=300&h=200
 *   ** File ** /image/co/http%253A%252F%252Fwebsite.co.jp%252FPhoto_300x200.jpg
 * 
 * * No problem without domain name
 *   /image/?/photos/photo.jpg
 *   ** File ** /image/co/http%3A%2F%2Fwebsite.co.jp%2Fphotos%2Fphoto.jpg
 */

$url = empty($_GET['u']) ? h($_SERVER['QUERY_STRING']) : h($_GET['u']);

if( empty($url) ){
    $s =  (empty($_SERVER['HTTPS']) ? 'http://' : 'https://') . $_SERVER['HTTP_HOST'];
    header("Location: ${s}");
    exit;
}

## prohibit reading /../../../etc/passwd
$url = preg_replace('/\.\./','',$url);

## add host name if the url doesn't have any domain (host) 
$url_info = parse_url($url);
if ( empty($url_info['host']) ) {
    $url = (empty($_SERVER['HTTPS']) ? 'http://' : 'https://') .  $_SERVER['HTTP_HOST'] . $url;
}
## domain filter
if ( !empty($url_info['host']) && !is_available_domain( $url_info['host'] ) ) {
    exit(1);
}

$encoded_url = urlencode($url);
$dir = dirname(__FILE__) . '/'; # current directory
$ca_dir = 'ca/';  # ca => cached
$co_dir = 'co/';  # co => compressed
$re_dir = 're/';  # re => resized

if( !file_exists($ca_dir) ){ mkdir($ca_dir); }
if( !file_exists($co_dir) ){ mkdir($co_dir); }
if( !file_exists($re_dir) ){ mkdir($re_dir); }

$ca_file = $dir . $ca_dir . $encoded_url;

if( !empty($_GET['w']) && !empty($_GET['h']) && is_numeric($_GET['w']) && is_numeric($_GET['h']) ){
    $co_file = $dir . $co_dir . build_co_file_name($encoded_url,$_GET['w'],$_GET['h']);
}else{
    $co_file = $dir . $co_dir . build_co_file_name($encoded_url);
}

if( file_exists($co_file) ) {
    $last_modified_time = filemtime( $co_file );
    $etag = md5_file( $co_file );
    header( 'Last-Modified: ' . gmdate('D, d M Y H:i:s', $last_modified_time ) . ' GMT' );
    header( 'Etag: '. $etag );
    if ( @strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']) == $last_modified_time
            || @strtotime($_SERVER['HTTP_LAST_MODIFIED']) == $last_modified_time
            || @trim($_SERVER['HTTP_IF_NONE_MATCH']) == $etag ) {
        header( 'HTTP/1.1 304 Not Modified' );
        exit;
    }
}

## original image download
if( !file_exists($ca_file) ){
    exec("wget --no-check-certificate -q {$url} -O {$ca_file}", $out, $ret);
    if ($ret == 1) { print implode('',$out); exit(1); }
}

$pathinfo = pathinfo($ca_file);
$ext = strtolower($pathinfo['extension']);

if ( $ext == 'bmp' || $ext == 'jpg' || $ext == 'jpeg' ) {
    ## convert (bmp => jpg)
    if( $ext == 'bmp' ){
        $jpg_path =  $dir . $ca_dir . $pathinfo['filename'] . '.jpg';
        if( !file_exists($jpg_path) ||is_expired_file($jpg_path) ){
            exec("convert \"{$ca_file}\" {$jpg_path}", $out, $ret);
            if ($ret == 1) { print implode('',$out); exit(1); }
        }
        $encoded_url = $pathinfo['filename'] . '.jpg';
        $ca_file = $dir . $ca_dir . $encoded_url;
        $co_file = $dir . $co_dir . $encoded_url;
    }

    ## resize
    if( !empty($_GET['w']) && !empty($_GET['h']) && is_numeric($_GET['w']) && is_numeric($_GET['h']) ){
        $w = $_GET['w'];    
        $h = $_GET['h'];    
        $resized_img_path =  $dir . $re_dir . $pathinfo['filename'] .'_'. $w .'x'. $h .'.jpg';
        if( !file_exists($resized_img_path) || is_expired_file($resized_img_path) ) {
            exec("convert -geometry {$w}x{$h} \"{$ca_file}\" {$resized_img_path}", $out, $ret);
            if ($ret == 1) { print implode('',$out); exit(1); }
        }
        $encoded_url = $pathinfo['filename'] .'_'. $w .'x'. $h .'.jpg';
        $ca_file = $dir . $re_dir . $encoded_url;
        $co_file = $dir . $co_dir . $encoded_url;
    }

    ## compress (jpg)
    if( !file_exists($co_file) || is_expired_file($co_file) ) {
        exec("jpegoptim --strip-all -o -m30 \"{$ca_file}\" --dest={$dir}{$co_dir}", $out, $ret);
        if ($ret == 1) { print implode('',$out); exit(1); }
    }
}else if ( $ext == 'png' ){
    ## resize
    if( !empty($_GET['w']) && !empty($_GET['h']) && is_numeric($_GET['w']) && is_numeric($_GET['h']) ){
        $w = $_GET['w'];    
        $h = $_GET['h'];    
        $resized_img_path =  $dir . $re_dir . $pathinfo['filename'] .'_'. $w .'x'. $h .'.png';
        if( !file_exists($resized_img_path) || is_expired_file($resized_img_path) ) {
            exec("convert -geometry {$w}x{$h} \"{$ca_file}\" {$resized_img_path}", $out, $ret);
            if ($ret == 1) { print implode('',$out); exit(1); }
        }
        $encoded_url = $pathinfo['filename'] .'_'. $w .'x'. $h .'.png';
        $ca_file = $dir . $re_dir . $encoded_url;
        $co_file = $dir . $co_dir . $encoded_url;
    }
    ## compress (png)
    if( !file_exists($co_file) || is_expired_file($co_file) ) {
        exec("cp \"{$ca_file}\" \"{$co_file}\" && optipng -q -o7 \"{$co_file}\"", $out, $ret);
        if ($ret == 1) { print implode('',$out); exit(1); }
    }
}else{
    ## gif etc. => do nothing
    header('Location: ' . $url );
    exit;
}

$co_file_info = getimagesize( $co_file );
header( 'Content-type: '. $co_file_info['mime'] );
readfile( $co_file );
exit;


function h( $str ){
    return htmlentities($str, ENT_QUOTES, 'UTF-8');
}

function is_available_domain( $target_host ){
    $host_list = array( $_SERVER['HTTP_HOST'] );
    foreach( $host_list as $host ){
        if( strpos( $target_host, $host ) !== false ) {
            return true;
        }
    }
    return false;
}

function is_expired_file( $filepath ){
    # 1 minute = 60 seconds
    # 1 hour   = 3600 seconds
    # 1 day    = 86400 seconds
    # 7 days   = 604800 seconds
    # 30 days  = 2592000 seconds
    $extention_time = 2592000; 
    return ( ( mktime() - filemtime($filepath) ) > $extention_time ) ? true : false;
}

function build_co_file_name($encoded_url, $w=null, $h=null){
    if( !empty($w) && !empty($h) && is_numeric($w) && is_numeric($h) ){
        $pathinfo = pathinfo($encoded_url);
        $ext = strtolower($pathinfo['extension']);
        return $pathinfo['filename'] .'_'. $w .'x'. $h .'.'. $ext;
    }else{
        return $encoded_url;
    }
}
?>
