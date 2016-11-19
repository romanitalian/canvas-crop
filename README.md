# CanvasCrop
Crop your image

##Example of use

```php
<?php
require 'CanvasCrop.php';
$cc = new CanvasCrop();

$cc->loadImage('original1.png');
$cc->cropBySize(100, 100, ccBOTTOMRIGHT);
$cc->saveImage('final1.png');

$cc->flushImages();

$cc->loadImage('original2.png');
$cc->cropByPercent(15, 50, ccCENTER);
$cc->saveImage('final2.jpg', 90);

$cc->flushImages();

$cc->loadImage('original3.png');
$cc->cropToDimensions(67, 37, 420, 255);
$cc->showImage('png');
```
