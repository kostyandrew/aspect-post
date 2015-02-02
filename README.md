ASPECT-Post
===========
PHP Class for WordPress which help create type of post, taxonomies and metabox

You can use standart arguments and labels setting WordPress using setArgument and setLabel methods

### Creating post type: ###
```php
$slides = new Aspect_Type('slide');
$slides
  ->addSupport('thumbnail') // add support thumbnail
  ->setArgument('public', true) // public argument
  ->setArgument('show_in_nav_menus', false); // hide in creating menu
```
### Creating taxonomy: ###
```php
$slides_type = new Aspect_Taxonomy('type');
$slides_type
  ->setType($slides); // post type where will be created taxonomy
```

### Creating metabox: ###
```php
$slides_settings = new Aspect_Box('slide setting');
$slides_settings
  ->setType($slides); // post type
```

### Creating metabox input: ###
```php
$text_color = new Aspect_Input('text color');
$text_color
  ->attachTo($slides_settings) // metabox
  ->attach('white', 'black') // values for select
  ->setArgument('type', 'select') // type of input
  ->setArgument('default', 'black'); // default
```
