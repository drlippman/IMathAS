# Yii2 Basic Application Setup
- Before starting this setup, make sure that you are using PHP 5.4 or above.
- There are two ready to go stacks available for Yii2, that in one basic and another is advanced.
- If you are only going to develop web application then use Basic stack otherwise if you are about to develop both web end and backend (APIs) in the application then use advance stack.
-  ###### Here we are used Yii2 basic stack.
#### Following are the steps to setup Yii2
 - Download Basic Yii2 achive files from following url
http://www.yiiframework.com/download/
 - Extract this archive and copy it into your web root directory,Say that we use name basic.
 - For Windows user: C:/XAMPP/htdocs
 - For Linux user: /var/www (For Ubuntu 14.04 /var/www/html)
 - For Mac user: /Applications/XAMPP/htdocs

 ##### Yii2 Basic setup
 - Try to access  basic stack using url http://localhost/basic/web, It will probably show you error with permissions.
 - Create assets directory in web (web/assets)
 - Give correct permissions to project directory
    - Command for linux and mac: sudo chmod -R 777 path/to/these/directory.(Note:Don't use 777 permissions on servers, just use it on your local machine).
    - Now again try to hit same url, It will show you error with cookie validations.
    - To fix this you need to set value for cookieValidationKey  in config file located <project-root>/config/web.php.
    - Set some random alphanumeric key here.

           'request' => [
            // !!! insert a secret key in the following (if it is empty) - this is required by cookie validation
            'cookieValidationKey' => 'RqIC5VL10EWZYovFLgGbVYDtKTl73u7G',
      		'parsers' => [
      				        'application/json' => 'yii\web\JsonParser',
        				]
           ],
    - Thats it! Its all DONE and you are ready to go from here with this Basic Yii stack.
    - For more detail go through the attached PDF, which is also available at link http://stuff.cebe.cc/yii2-guide.pdf

 ##### Following are some useful links:
 - http://www.yiiframework.com/download/
 - http://www.yiiframework.com/doc-2.0/guide-tutorial-advanced-app.html
 - http://stuff.cebe.cc/yii2-guide.pdf
 - http://stackoverflow.com/questions/25788838/whats-a-difference-between-yii-2-advanced-application-and-basic
 - https://www.youtube.com/watch?v=SKXh0mGlnLM
