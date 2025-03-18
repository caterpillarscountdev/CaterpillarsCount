<?php
require('tools/phpqrcode.php');

function pathToQRBack($code) {
  $url = "https://caterpillarscount.unc.edu/submitObservations/?plantCode=" . $code;

  $path = "../images/tags/qr" . $code . ".png";
  $outpath = "../images/tags/back" . $code . ".png";

  if (file_exists($outpath)) {
    return $outpath;
  }

  QRCode::png($url, $path, QR_ECLEVEL_L, 8, 1);

  $src = new Imagick('../images/plantTagBack.png');
  $src2 = new Imagick($path);


  $src->setImageVirtualPixelMethod(Imagick::VIRTUALPIXELMETHOD_TRANSPARENT);
  $src->compositeImage($src2, Imagick::COMPOSITE_DEFAULT, 593, 318);
  $src->writeImage($outpath);
  return $outpath;
}

?>                                            