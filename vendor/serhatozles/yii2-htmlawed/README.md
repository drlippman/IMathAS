htmLawed
========
See: http://www.bioinformatics.org/phplabware/internal_utilities/htmLawed/more.htm

Installation
------------

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```
php composer.phar require --prefer-dist serhatozles/yii2-htmlawed "*"
```

or add

```
"serhatozles/yii2-htmlawed": "*"
```

to the require section of your `composer.json` file.


Usage
-----

Once the extension is installed, simply use it in your code by  :

```php
$in = ' <div>
<div>
<table
border="1"
style="background-color: red;">
<tr>    <td>A    cell</td><td colspan="2" rowspan="2">
<table border="1" style="background-color: green;"><tr><td>Cell</td><td colspan="2" rowspan="2"></td></tr><tr><td>Cell</td></tr><tr><td>Cell</td><td>Cell</td><td>Cell</td></tr></table>
</td></tr>
<tr><td>Cell</td></tr><tr><td>Cell</td><td>Cell</td><td>Cell</td></tr></table></div></div> ';

$out = serhatozles\htmlawed\htmLawed::htmLawed($in, array('tidy'=>'1')); 
echo $out;
```