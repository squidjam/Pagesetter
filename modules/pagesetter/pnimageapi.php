<?php
// $Id: pnimageapi.php,v 1.1 2005/01/11 20:32:05 jornlind Exp $
// =======================================================================
// Pagesetter by Jorn Lind-Nielsen (C) 2003-2005.
// ----------------------------------------------------------------------
// For POST-NUKE Content Management System
// Copyright (C) 2002 by the PostNuke Development Team.
// http://www.postnuke.com/
// ----------------------------------------------------------------------
// LICENSE
//
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License (GPL)
// as published by the Free Software Foundation; either version 2
// of the License, or (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WithOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// To read the license please visit http://www.gnu.org/copyleft/gpl.html
// =======================================================================


function pagesetter_imageapi_createThumbnailFromFile($args)
{
  $im = pnModAPIFunc( 'pagesetter', 'image', 'createImageFromFile', $args );

  if (!$im)
    return false;

  $ok =  pnModAPIFunc( 'pagesetter', 'image', 'createThumbnailFromGD',
                       array('image' => $im,
                             'thumbnailFilePath' => $args['thumbnailFilePath']) );
  
  imagedestroy($im);

  return $ok;
}


function pagesetter_imageapi_createImageFromFile($args)
{
  $filename = $args['imageFilePath'];
  $mimeType = $args['mimeType'];

  $im = false;

  if ($mimeType == 'image/jpeg' || $mimeType == 'image/pjpeg')
  {
    $im = @imagecreatefromjpeg($filename);
  }
  else if ($mimeType == 'image/gif')
  {
    $im = @imagecreatefromgif($filename);
  }
  else if ($mimeType == 'image/png')
  {
    $im = @imagecreatefrompng($filename);
  }
  else
    return pagesetterErrorApi(__FILE__, __LINE__, _PGUNKNOWNIMAGEFORMAT . " '$mimeType' ($filename)");

  if (!$im)
    return pagesetterErrorAPI(__FILE__, __LINE__, _PGUNSUPPORTEDIMAGEFORMAT . " '$mimeType' ($filename)");

  return $im;
}


function pagesetter_imageapi_createThumbnailFromGD($args)
{
  $im = $args['image'];
  $thumbnailFilePath = $args['thumbnailFilePath'];

  $thumbnailSize = pnModGetVar('pagesetter', 'thumbnailsize');
  $thumbnailSize = 100;

  $xs = imagesx($im);
  $ys = imagesy($im);

    // Calculate thumbnail X and Y sizes
  if ($xs > $ys)
  {
    $thumbnailXSize = $thumbnailSize;
    $thumbnailYSize = ($ys*$thumbnailSize) / $xs;
  }
  else
  {
    $thumbnailYSize = $thumbnailSize;
    $thumbnailXSize = ($xs*$thumbnailSize) / $ys;
  }

  $xoffset = $yoffset = 0;

  $isTrueColor = false;

  $thumbnail = pagesetterCreateImageDirect($thumbnailXSize, $thumbnailYSize, $mimeType, $isTrueColor);

    // Copy resized image into center of thumbnail
  if ($isTrueColor  &&  function_exists("imagecopyresampled"))
  {
    if (!@imagecopyresampled($thumbnail,$im, $xoffset,$yoffset, 0,0, $thumbnailXSize, $thumbnailYSize, $xs,$ys))
      pagesetterImageCopyResampleBicubic($thumbnail, $im, $xoffset,$yoffset, 0,0, $thumbnailXSize, $thumbnailYSize, $xs,$ys);
  }
  else
    imagecopyresized($thumbnail,$im, $xoffset,$yoffset, 0,0, $thumbnailXSize, $thumbnailYSize, $xs,$ys);

    // Save image to file and then copy file into memory buffer (PHP cannot do it directly)

  imagepng($thumbnail, $thumbnailFilePath);

  imagedestroy($thumbnail);

  return true;
}


function pagesetterCreateImageDirect($xsize, $ysize, $mimeType, &$isTrueColor)
{
    // Detect presense of ImageCreateTrueColor() - although this apparently doesn't say anything about GD2 existing ...
  $hasTrueColorImage = false;
  $gdfunctions = get_extension_funcs("gd");
  foreach ($gdfunctions as $f)
    if ($f == 'imagecreatetruecolor')
      $hasTrueColorImage = true;

    // If the check for imagecreatetruecolor fails then insert the next line
  // $hasTrueColorImage = false;

  $isTrueColor = false;

    // Create actual image
  if ($mimeType == 'image/gif'  ||  !$hasTrueColorImage)
    $image = ImageCreate($xsize,$ysize); // Create white background image
  else
  {
    $image = @ImageCreateTrueColor($xsize,$ysize); // Create black background image

      // Didn't work ... perhaps ImageCreateTrueColor doesn't exist?
    if (!$image)
      $image = ImageCreate($xsize,$ysize); // Create white background image
    else
      $isTrueColor = true;
  }

  return $image;
}


/* This functions allows the resize images bicubic without GD2 */
function pagesetterImageCopyResampleBicubic ($dst_img, $src_img, $dst_x, $dst_y, $src_x, $src_y, $dst_w, $dst_h, $src_w, $src_h)
{
	/*
	 * port to PHP by John Jensen July 10 2001 -- original code (in C, for the PHP GD Module) by jernberg@fairytale.se
  */

	// Left out code for indexed images
  $scaleX = ($src_w - 1) / $dst_w;
  $scaleY = ($src_h - 1) / $dst_h;

  $scaleX2 = $scaleX / 2.0;
  $scaleY2 = $scaleY / 2.0;

  for ($j = $src_y; $j < $dst_h; $j++)
  {
	  $sY = $j * $scaleY;
    for ($i = $src_x; $i < $dst_w; $i++)
  	{
    	$sX = $i * $scaleX;

      $c1 = ImageColorsForIndex ($src_img, ImageColorAt ($src_img, (int) $sX, (int) $sY + $scaleY2));
      $c2 = ImageColorsForIndex ($src_img, ImageColorAt ($src_img, (int) $sX, (int) $sY));
      $c3 = ImageColorsForIndex ($src_img, ImageColorAt ($src_img, (int) $sX + $scaleX2, (int) $sY + $scaleY2));
      $c4 = ImageColorsForIndex ($src_img, ImageColorAt ($src_img, (int) $sX + $scaleX2, (int) $sY));

      $red = (int) (($c1['red'] + $c2['red'] + $c3['red'] + $c4['red']) / 4);
	    $green = (int) (($c1['green'] + $c2['green'] + $c3['green'] + $c4['green']) / 4);
      $blue = (int) (($c1['blue'] + $c2['blue'] + $c3['blue'] + $c4['blue']) / 4);

      $color = ImageColorClosest ($dst_img, $red, $green, $blue);
      ImageSetPixel ($dst_img, $i + $dst_x, $j + $dst_y, $color);
    }
  }
}


?>
