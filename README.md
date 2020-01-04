# image-online-converter
Image online converter using imagemagick &amp; jpegoptim

## System requirements

* Ubuntu 18.04 LTS

## Package  requirements

    sudo apt install php php-gd imagemagick jpegoptim wget

## Example

### BMP => JPG convert & compression

    [Path]] /image/?http://website.co.jp/Picrure.bmp
    [Generated file] /image/co/http%253A%252F%252Fwebsite.co.jp%252FPicture.jpg

### JPG compression

    [Path] /image/?http://website.co.jp/Photo.jpg
    [Generated file] /image/co/http%253A%252F%252Fwebsite.co.jp%252FPhoto.jpg

### JPG resize & compression

    [Path] /image/?u=http://website.co.jp/Photo.jpg&w=300&h=200
    [Generated file] /image/co/http%253A%252F%252Fwebsite.co.jp%252FPhoto_300x200.jpg

### No problem without domain name

    [Path] /image/?/photos/photo.jpg
    [Generated file] /image/co/http%3A%2F%2Fwebsite.co.jp%2Fphotos%2Fphoto.jpg

## References

* https://imagemagick.org/
* https://github.com/tjko/jpegoptim
