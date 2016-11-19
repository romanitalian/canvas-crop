<?php

require_once __DIR__ . '/src/CanvasCrop.php';

$CanvasCrop = new CanvasCrop();

$CanvasCrop->loadImage('original1.png');
$CanvasCrop->cropBySize(100, 100, CanvasCrop::ccBOTTOMRIGHT);
$CanvasCrop->saveImage('final1.png');

$CanvasCrop->flushImages();

$CanvasCrop->loadImage('original2.png');
$CanvasCrop->cropByPercent(15, 50, CanvasCrop::ccCENTER);
$CanvasCrop->saveImage('final2.jpg', 90);

$CanvasCrop->flushImages();

$CanvasCrop->loadImage('original3.png');
$CanvasCrop->cropToDimensions(67, 37, 420, 255);
$CanvasCrop->showImage('png');
