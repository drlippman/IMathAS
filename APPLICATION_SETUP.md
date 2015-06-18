# OpenMath Application Setup
- Before starting this setup, make sure that you are using PHP 5.4 or above.
- MySql

#### Following are the steps to setup OpenMath application
 - Goto root directory of your machine.
 - For Windows user: C:/XAMPP/htdocs
 - For Linux user: /var/www (For Ubuntu 14.04 /var/www/html)
 - For Mac user: /Applications/XAMPP/htdocs
 - Take the clone of application from Github (git clone -b branch https://github.com/lumenlearning/IMathAS.git openmath).
 
##### Database Configuration
 - Edit the file config/db.php with real data, for example:
     - return [
      'class' => 'yii\db\Connection',
      'dsn' => 'mysql:host=localhost;dbname=imathasdb',
      'username' => 'root',
      'password' => '1234',
      'charset' => 'utf8',
     ];

##### Application Assets
 - Try to access  basic stack using url http://localhost/openmath/web, It will probably show you error with permissions.
 - Create assets directory in web (web/assets)
 - Give correct permissions to project directory
    - Command for linux and mac: sudo chmod -R 777 path/to/these/directory.(Note:Don't use 777 permissions on servers, just use it on your local machine).
    - Now again try to hit same url, It may show you error with cookie validations. 
    - To fix this you need to set value for cookieValidationKey  in config file located <project-root>/config/web.php.
    - Set some random alphanumeric key here.

           'request' => [
            // !!! insert a secret key in the following (if it is empty) - this is required by cookie validation
            
            'cookieValidationKey' => 'RqIC5VL10EWZYovFLgGbVYDtKTl73u7G',
            
      		'parsers' => [
      		
      				        'application/json' => 'yii\web\JsonParser',
      				        
        				]
        				
           ], 
           
    - Thats it! Its all DONE and you are ready to go from here with OpenMath application.
    - For more detail about Yii2 go through http://stuff.cebe.cc/yii2-guide.pdf

 ##### Following are some useful links: 
 - http://www.yiiframework.com/download/
 - http://www.yiiframework.com/doc-2.0/guide-tutorial-advanced-app.html
 - http://stuff.cebe.cc/yii2-guide.pdf
 - http://stackoverflow.com/questions/25788838/whats-a-difference-between-yii-2-advanced-application-and-basic
 - https://www.youtube.com/watch?v=SKXh0mGlnLM

