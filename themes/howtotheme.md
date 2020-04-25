Theming IMathAS
===============

Themes can be selected by instructors in their Course Settings to customize
the look and feel of their course.  Themes are implemented as custom CSS files,
stored in `/themes/`.

Any student or instructor can request in their profile to use a high contrast 
theme, which will override the course default theme.  These are implemented in 
`/themes/highcontrast.css` and `/themes/highcontrast_dark.css`.

Basics
------
IMathAS's core CSS comes from three files:

1. `/imascore.css`, which is included on most pages
2. `/assessments/mathtest.css`, which is included on assessment pages
3. `/handheld.css`, which is included by media query on `max-width:480px`

The theme file will typically overwrite and extend the styles from these 
primary CSS files.

Config
------
###Default Theme
To specify the default system theme, define in `/config.php`

`$CFG['CPS']['theme'] = array('mytheme.css',1);`

If you wish to make the default theme the only theme that can be used, change
the `1` to a `0` in that declaration to make the theme unchangable in a course.

###Theme List
By default, IMathAS will provide a listing of all `.css` files in the 
`/themes/` directory for the instructor to select from. It is advisable to 
instead provide a customized list.  Do so by providing two lists, one providing
the `.css` files and the second providing friendly names for the themes.

```
$CFG['CPS']['themelist'] = "theme1.css,theme2.css,theme3.css";
$CFG['CPS']['themenames'] = "My Theme,Super Theme,Fancy Theme";
```

Fluid and Fixed Width
---------------------
There are a number of pages and usecases where it is preferrable to have
IMathAS fluid width, such as when it is embedded via LTI or when a page is
shown in a modal iframe.  

Your theme should be written to provide a fluid full-width view by default.
To provide a fixed-width option of 1000px or 1920px, add `_fw1000` or 
`_fw1920` to your theme `.css` file name in the theme list, like:

```
$CFG['CPS']['themelist'] = "theme1.css,theme1.css_fw1000,theme3.css_fw1920";
$CFG['CPS']['themenames'] = "My Theme,My Theme Fixed,My Theme Wide";
```

Doing so will add the `fw1000` or `fw1920` class to the `<body>` element.
The main theme files, `imascore.css` and `mathtest.css` will then automatically 
provide basic fixed-width display.  Use these classes in your theme file to
customize the display at the fixed width.

Additional Customization
------------------------
To provide custom header content, which is loaded on most pages (except when
embedded or other special cases), specify the location, relative to the 
IMathAS root directory, using this `config.php` option:

`$CFG['GEN']['headerinclude'] = 'myheadercode.php';`

If you have any special scripts that need to get loaded in the `<head>`, 
specify them using

`$CFG['GEN']['headerscriptinclude'] = 'javascript/myheaderscript.js';`

Likewise, if you have any scripts that need to get loaded at the very end of the
document (just before the closing `</body>`), specify them using

`$CFG['GEN']['footerscriptinclude'] = 'javascript/myheaderscript.js';`







