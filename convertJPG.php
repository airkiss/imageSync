<?php
$filename = "./data/m_4juniors0013_1.jpg";
function Convert1($filename)
{
	$img = new Imagick($filename);
	$w = $img->getImageWidth();
	$h = $img->getImageHeight();
	$l = $img->getImageLength();
	echo $w . ' ' .$h . ' ' . $l;
	$img->setImageResolution(72,72);
	$img->resampleImage(72,72,Imagick::FILTER_LANCZOS,1); 
	if($h > 200)
	{
		$img->scaleImage(200,200,true);
	}
	$img->setImageCompression(Imagick::COMPRESSION_JPEG); 
	$img->setImageCompressionQuality(80);
	$img->stripImage();
	$img->writeImage('./data/test.jpg');
	$img->destroy(); 
}
function Convert2($filename)
{
	$img = new Imagick($filename);
	$w = $img->getImageWidth();
	$h = $img->getImageHeight();
	$l = $img->getImageLength();
	echo $w . ' ' .$h . ' ' . $l;
	$img->setImageResolution(72,72);
	$img->resampleImage(72,72,Imagick::FILTER_LANCZOS,1); 
	if($h > 200)
	{
		$img->thumbnailImage(200,200,true);
	}
	$img->setImageCompression(Imagick::COMPRESSION_JPEG); 
	$img->setImageCompressionQuality(80);
	$img->stripImage();
	$img->writeImage('./data/test2.jpg');
	$img->destroy(); 
}
Convert1($filename);
Convert2($filename);
