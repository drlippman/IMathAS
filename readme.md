# IMathAS

The most recent version of the code is available on  [GitHub](https://github.com/drlippman/imathas)

## What is IMathAS

IMathAS is an Internet Mathematics Assessment System. It is primarily a web-based math assessment tool for delivery and automatic grading of math homework and tests, similar to publisher-offered systems. Questions are algorithmically generated and numerical and math expression answers can be computer graded. IMathAS includes learning management tools, including posting files, discussion forums and a full gradebook. The system can be used as an LTI tool, integrated with an LMS.

IMathAS powers MyOpenMath.com, WAMAP.org, Lumen OHM, XYZhomework, and others.

## Installation

### Requirements
IMathAS is designed for simple installation with minimal requirements.  The system
requires PHP 7.2+, and MySQL 5.6+.  PHP has the following recommended or required extensions:
- mbstring (required)
- pdo_mysql (required)
- gettext (required)
- gd with freetype (recommended) for image creation
- curl (recommended) necessary for LTI grade passback
- openssl (recommended) necessary for LTI 1.3 services

### Installation Steps
1.  Download IMathAS, extract it, and copy the files to your webserver.
2.  Alternatively, if you have shell access to your server, enter the directory you want IMathAS in, and checkout the code from Github. Using Git greatly simplifies upgrading.
3.  Create a database for IMathAS
4.  Open a browser and access `/install.php`. This script will write the `config.php` file, change directory permissions and set up the database. It will also create local copies of `loginpage.php`, `infoheader.php`, and `newinstructor.php`.  At the end of the install you will be given the opportunity to install a small set of example questions.
5.  Check to make sure the following directories have directory permissions to allow the web server to write files into them:
    -   `/assessment/libsassessment/qimages`
    -   `/admin/import`
    -   `/course/files`
    -   `/filter/graph/imgs`
    -   `/filestore` (if you're not using S3 for file storeage)
7.  Log into IMathAS. If you didn't change the initial imathas user settings when running install.php, log in as 'root' with password 'root'.
8.  Edit `loginpage.php` and `infoheader.php` if desired, which allow you to customize the login page. If you plan to use the new instructor account request page, you may customize `newinstructor.php`.

## Upgrading

To update the software, either copy the updated files onto the webserver or run `git pull` if you installed using git.  After updating the files, access `/upgrade.php` to run any necessary database migrations.  Be sure to watch the output as occasionally messages about needed config changes are displayed.

## Configuration

IMathAS can be configured through variables set in `config.php`.

### Basic Options

These are all added to the `config.php` by the install script.

-   `$dbserver`: The address of your database server. Probably www.yoursite.edu or localhost
-   `$dbname`: The name of the IMathAS database
-   `$dbusername`: The username of the IMathAS database user.
-   `$dbpassword`: The password for the IMathAS database user.
-   `$installname`: The name of your installation, for personalization.
-   `$longloginprompt`: How you want to prompt new students for a username
-   `$loginprompt`: How you want to prompt students for a username.  
-   `$loginformat`: Enforce a format requirement on the username. This should be a regular expression string, like `'/^\w+$/'`
-   `$emailconfirmation`: If set to true, new users will have to respond to an email sent by the system before being able to enroll in any classes.
-   `$sendfrom`: An email address to send confirmation and notification emails from.
-   `$imasroot`: The web root of the imathas install.  An empty string if installed at the web root, or something like `'/imathas'` if installed in a directory.
-   `$mathimgurl`: An absolute path or full url to a [Mimetex](http://www.forkosh.com/mimetex.html) installation, for math image fallback
-   `$colorshift`: Whether icons should change colors as due date approaches. I thought this was cute, but others might find it annoying.
-   `$smallheaderlogo`: Text or an HTML image tag for a small (120 x 80) logo to display at the top right of course pages.
-   `$allownongrouplibs`: Whether non-admins should be allowed to create non-group libraries. On a single-school install, set to true; for larger installs that plan to use the Groups features, set to false.
-   `$allowcourseimport`: Whether anyone should be able to import/export questions and libraries from the course page. Intended for easy sharing between systems, but the course page is cleaner if turned off.
-   `$enablebasiclti`: Set to true to enable use of IMathAS as an LTI producer (tool).
-   `$AWSkey, $AWSsecret, $AWSbucket`: To allow students and teachers to upload files through the text editor, and to enable file upload questions, this specifies an Amazon S3 key, secret, and bucket to use for file storage. If not specified, local storage will be used instead.
- `$CFG['GEN']['newpasswords']`:  all new installations should set this to `'only'` to use good-quality password security.  It can be set to `'transition'` for very old systems to transition to the new storage.  If omitted the system will use md5 for passwords, which is highly discouraged.

### System Defaults

Many system defaults can be adjusted using config changes.

- Assessment settings.   Most assessment option defaults can be defined.  For example, `$CFG['AMS']['displaymethod']` allows you to set the default displaymethod for assessments.  Review the code in `/course/addassessment.php` for all options. Setting $CFG['AMS']['caltag'] = 'use_name' causes assessment names to appear in the calendar by default.
- Gradebook settings.  Most defaults can be defined.  For example, `$CFG['GBS']['defgbmode']` allows you to define the default gradebook mode.  See `/course/gbsettings.php` for all options.
- Forum settings.  A few defaults can be defined.  See `/course/addforum.php` for all options.
- Course settings.  Most default can be defined, and most can be forced to a value.  For example, `$CFG['CPS']['theme'] = array("modern.css",1);` sets the default theme but allows the user to change it.  Using `0` instead of `1` in the second position would set the default and not allow the user to change it.  See `/admin/forms.php` for all options.

### Generally Useful Options

- `$CFG['GEN']['domainlevel']`:  Used to set the appropriate specificity for cookies.  By default, only the last two parts of the domain are used for cookies, for example `sitename.com` would be used from `www.sitename.com`.  If you are running your site on a subdomain, you may need to include more parts.  Specify the number, with a negative, so `$CFG['GEN']['domainlevel'] = -3` to keep 3 parts.
- `$CFG['GEN']['doSafeCourseDelete']`: If set to true, deleting a course will hide it instead of actually deleting it.  An admin can un-delete it later if needed.
- `$CFG['coursebrowser']`:  If you wish to use the course browser feature, copy `/javascript/coursebrowserprops.js.dist` to `/javascript/coursebrowserprops.js`, edit it, then set:
    - `$CFG['coursebrowser'] = 'coursebrowserprops.js'`.
    - `$CFG['coursebrowsermsg']`: Optionally set this to override the default "Copy a template or promoted course" message.
- `$CFG['GEN']['enrollonnewinstructor']`:  Set to an array of course IDs that new instructors should automatically be enrolled in (like a support course or training course).
- `$CFG['GEN']['guesttempaccts']`: Set to true to allow logging on as `guest` with no password.  Also enables the "guest access" option in course settings, which defines which courses a guest will get auto-enrolled in.
-  `$CFG['use_csrfp']`: Set this to true to enable cross-site request forgery protection.
- File Storage:  By default, all user-uploaded files are stored on the webserver.  The system supports using Amazon S3 for file storage instead.  To use S3:
    - Set `$AWSkey`, `$AWSsecret`, `$AWSbucket` to your AWS key, secret, and bucket name respectively.  
    - `$GLOBALS['CFG']['GEN']['AWSforcoursefiles']`:   If the variables above are set, by default S3 will be used only for user uploads through the text editor, and local storage will still be used for course files and question images.  Set this option to true to also use S3 for these types of files.
- `$CFG['GEN']['noFileBrowser']`: Set this to true to prevent the use of the file and image uploads through the text editor.  Do not define this to allow use of the file browser.
- `$CFG['GEN']['sendquestionproblemsthroughcourse']`:  By default, clicking the "Report problem with question" will open email.  To send using an IMathAS message instead, set this option to a course ID, ideally one that all instructors are participants in.
- `$CFG['GEN']['qerrorsendto']`:  Normally question errors are reported to the question author.  To have them sent do a different user, set this option.  Set to a user ID to send to that user.  You can also force the delivery method by defining this is an array of `array(userid, sendmethod, title, alsosendtoowner)`, like `array(2, "email", "Contact Support", true)`.  The email address for the specified user ID will be used.  If alsosendtoowner is set to true, the message will be sent both to the question owner as well.
- `$CFG['GEN']['meanstogetcode']`:  To comply with the IMathAS Community License, a means to get a copy of question code must be provided.  By default the system says to email the `$sendfrom` email address.  You can define this variable to display a different message on the license info page.
- `$CFG['GEN']['LTIorgid']`:  A value to use as the LTI organization ID when IMathAS is acting as an LTI consumer.  Defaults to the domain name.
- `$CFG['UP']`:  An associative array overriding the default User Preference values.  See the `$prefdefaults` definition in `/includes/userprefs.php` for the appropriate format.
- `$CFG['GEN']['extrefsize']`: Set to an array of (width,height) to set the popup size for Text and Written Solution question help buttons
- `$CFG['GEN']['vidextrefsize']`: Set to an array of (width,height) to set the popup size for Video question help buttons
- `$CFG['GEN']['ratelimit']`: Set to a number of seconds (like 0.2) to limit the rate at
 which pages can be accessed/refreshed.
- `$CFG['GEN']['COPPA']`: Set to enable an "I am 13 years old or older" checkbox on new student account creation. If not checked, requires a course ID and key to create an account.

### Additional Validation
These provide additional validation options beyond `$loginformat`.

- `$CFG['acct']['emailFormat']`:  A regular expression to check email addresses with
- `$CFG['acct']['passwordMinlength']`:  Minimum password length.  Defaults to 6.
- `$CFG['acct']['passwordFormat']`:  A regular expression or array of regexs to check the password against.  If an array is given, _all_ regexes must match against the password.
- `$CFG['acct']['importLoginformat']`:  If set, this regular expression replaces `$loginformat` when using the  "import students from file" option.
- `$CFG['acct']['SIDformaterror']`: A message to display if the username/SID has invalid format.
- `$CFG['acct']['passwordFormaterror']`: A message to display if the password has invalid format.
- `$CFG['acct']['emailFormaterror']`: A message to display if the email doesn't meet the custom 'emailFormat' pattern.

### Access Limits
- `$CFG['GEN']['addteachersrights']`: Set to the minimum rights level needed to Add/Remove Teachers in a course.  Defaults to 40 (Limited Course Creator rights).
- `$CFG['GEN']['noimathasexportfornonadmins']`: Set to true to prevent non-admins from exporting a course in IMathAS backup/transfer format.
- `$CFG['coursebrowserRightsToPromote']`: Set to the minimum rights level needed to Promote a course into the course browser.  Defaults to 40 (Limited Course Creator rights).  Requires setting up the course browser.
- `$CFG['GEN']['noInstrExternalTools']`:  Set to true to prevent instructors from setting up new LTI tools (where IMathAS would be acting as consumer).  They'll still be able to use any LTI tools set up by an Admin.
- `$CFG['GEN']['noimathasimportfornonadmins']`:  Set to true to prevent non-admins from using the "Import Course Items" feature.
- `$CFG['GEN']['noInstrUnenroll']`: Set to true to prevent instructors from Unenrolling students; they will only be able to lock out students.
- `$CFG['GEN']['allowinstraddstus']`: Set to true to allow instructors to create new student accounts or import students from a file.  Default to true.  Generally not recommended on multi-school installs.
- `$CFG['GEN']['allowinstraddbyusername']`: Set to true to prevent instructors from adding students to their course using usernames.  Defaults to false.
- `$CFG['GEN']['allowinstraddtutors']`:  Set to false to prevent teachers from adding tutors.  Default to true.

### Personalization

In addition to the `$CFG['CPS']['theme']` option described above for setting the default theme and even forcing the use of the default theme, you can also provide users with a limited selection of themes by defining the following:

- `$CFG['CPS']['themelist']`: a comma-separated list of theme css files to allow as themes.
- `$CFG['CPS']['themenames']`: a comma-separated list of display names for the theme css files you included in `themelist`.
- `$CFG['CPS']['usecourselevel']`: add a course-level selector to the course settings page. Set to `'required'` to make it required.  This pulls the
course list from the course browser options, so you must also have `$CFG['coursebrowser']` set.
- `$CFG['GEN']['favicon']`:  Set this to override the default `/favicon.ico` path for a site favicon.
- `$CFG['GEN']['appleicon']`:  Set this add a path for an apple-touch-icon.
- `$CFG['GEN']['headerscriptinclude']`:  Set to a file path, relative to web root.  This file is included at the end of the `<head>` on every page.  Handy for including custom script tags or additional CSS files.
- `$CFG['GEN']['headerinclude']`:  Set to a file path, relative to web root.  This file is included in the `<div class="headerwrapper">` at the top of the body on all pages.  Handy for custom headers.
- `$CFG['GEN']['logopad']`: Set to something like "50px" to override the default padding on `<span class="padright">` used to leave room for `$smallheaderlogo`.
- `$CFG['GEN']['homelinkbox']`:  Set to anything to hide the default site tools from the upper right of the Home page.
- `$CFG['GEN']['hidedefindexmenu']`: Set to anything to hide the "Change User Info" and "Change password" links from the upper right of the Home page.
- `$CFG['GEN']['hometitle']`:  Set to a message to display at the top of the Home page, in place of "Welcome to _____, ______"
- `$CFG['CPS']['leftnavtools']`:  Set to `"limited"` to remove from the course left navigation tools that are also in the top navigation.  Set to false to remove the entire Tools block from the course left navigation.
- `$CFG['GEN']['deflicense']`:  The default license for new questions.  See `/course/moddataset.php` for valid values.  Defaults to 1 (IMathAS community license).
- `$CFG['GEN']['defGroupType']`: Set to change the default group type for newly created groups (def: 0)

### LTI

- `$CFG['LTI']['noCourseLevel']`: set to true to hide course level LTI key and secret from users. Use this if you want to require use of global LTI key/secrets.
- `$CFG['LTI']['noGlobalMsg']`: When the `noCourseLevel` option above is set, use this option to define a message that will be displayed on the export page when no global LTI is set for the group.
- `$CFG['LTI']['showURLinSettings']`: Set to true to show the LTI launch URL on the course settings page.  Normally omitted to avoid confusion (since it's not needed in most LMSs).
- `$CFG['LTI']['instrrights']`:  If a global LTI key is setup, and the option is enabled to allow auto-creation of instructor accounts, this option sets the rights level for those auto-created accounts.  Defaults to 40 (Limited Course Creator).
- `$CFG['GEN']['addwww']`:  If your website starts with `www.`, set this to true to ensure Canvas LTI tools use the full URL.
- `$CFG['LTI']['usequeue']`:  By default, LTI grade updates are sent immediately after the submission is scored.  Set this option to true to use a delayed queue to reduce the number of grade updates sent.  _This option requires additional setup._
    - To operate properly, the `/admin/processltiqueue.php` script needs to be called regularly, ideally once a minute.  If running on a single server, you can set this up as a cron job.  Alternatively, you could define `$CFG['LTI']['authcode']` and make a scheduled web call to  `/admin/processltiqueue.php?authcode=####` using the code you define.
    - `$CFG['LTI']['queuedelay']` defines the delay (in minutes) between the students' last submission and when the score is sent to the LMS.  Defaults to 5.
    - `$CFG['LTI']['logltiqueue']` set to true to log LTI queue results in /admin/import/ltiqueue.log
- `$CFG['LTI']['usesendzeros']`: Set to true to enable the "send zeros after due date" course setting.  _This option requires additional setup._
    - To operate, the `/lti/admin/sendzeros.php` script needs to be called on a schedule, typically once or a few times a day.  If running on a single server, you can set this up as a cron job.  Alternatively, you could define `$CFG['LTI']['authcode']` and make a scheduled web call to `/lti/admin/sendzeros.php?authcode=####` using the code you define.
- `$CFG['LTI']['useradd13']`: Set to true to allow teacher users to add LTI1.3 platforms.

### Email
By default, emails are sent using the built-in PHP `mail()` function.  This can sometimes have reliability issue, so there are options to override the mail sender or reduce the use of email in the system.
- `$CFG['GEN']['noEmailButton']`:  Set to true to remove the "Email" option from the Roster and Gradebook
- `$CFG['email']['new_acct_replyto']`: Set to an array of email addresses to add to the "Reply-to" field on new instructor approval emails.
- `$CFG['email']['new_acct_bcclist']`: Set to an array of email addresses to add to the "Bcc" field on new instructor approval emails.
- `$CFG['email']['handler']`:  Use this to override the default mail sending mechanism.  Set to `array('filename.php', 'functionname')`, where `filename.php` contains the function `functionname` that accepts the arguments described in `/includes/email.php`.
    - The system ships with support for Amazon SES (in us-west-2), by defining `$CFG['email']['handler'] = array('mailses.php', 'send_SESemail');`.  You'll also need to define:
      - `$CFG['email']['SES_KEY_ID']` or an environment variable `SES_KEY_ID`
      -  `$CFG['email']['SES_SECRET_KEY']` or an environment variable `SES_SECRET_KEY`
      - `$CFG['email']['SES_SERVER']` or the default of `email.us-west-2.amazonaws.com` will be used.
    - `$CFG['email']['handlerpriority']` can be set to define a breakpoint between using the default `mail()` delivery and the custom handler.   See `/includes/email.php` for values.


### Push Notifications

If you wish to enable users to request browser push notifications (does not work in all browsers), you'll need to setup an a project with [Firebase Cloud Messaging](https://firebase.google.com/docs/cloud-messaging/), and define these values.

- `$CFG['FCM']['SenderId']`: Your SenderId, from the Firebase project console.
- `$CFG['FCM']['webApiKey']`: Your web API key, from the Firebase project console.
- `$CFG['FCM']['serverApiKey']`: Your server key, from the Firebase project console.
- `$CFG['FCM']['icon']`: an absolute web path to an icon to show on notifications.

### Internationalization

The student side of the system is pretty well set up for i18n, but the instructor side is  not yet.  Currently the only translation pack available is `de` (German).  See `/i18n/translating.md` for more information about generating translations.  To enable a translation:
- `$CFG['locale']`: Set this to the desired language code, like `de`

### IPEDS / NCES 

The system can create associations with IPEDS/NCES records. For this, you will need to manually 
[populate the `imas_ipeds` table](https://github.com/drlippman/IMathAS-Extras/blob/master/ipeds/ipeds.md). 
- `$CFG['use_ipeds']`: set to true to enable UI features for editing associations.

Look to `newinstructor-ipeds.php.dist` for an example of how to collect ipeds data during 
account request.  The account approval process will auto-create group associations when 
account requests are collected with this data.

### Development
- `$CFG['GEN']['uselocaljs']`: Set to true to use local javascript files instead of CDN versions.  Requires installing a local copy of MathJax in `/mathjax/`.

### Course Cleanup
Automated course cleanup (unenrolling students from a course) can be enabled.  To use this, you'll need to set up a cron job (good on a single server setup) or scheduled web call (better for multi-server environments) to run:
- `/util/tagcoursecleanup.php`:  Run this once a day
- `/util/runcoursecleanup.php`:  Run this about every 10 minutes. Can be limited to slow usage periods.

If allowing guest logins:
- `/util/deloldguests.php`:  Run about once a day (once for every 50 guests)

If using a scheduled web call, you'll need to define:
- `$CFG['cleanup']['authcode']`:  define this and pass it in the query string, like `runcoursecleanup.php?authcode=####`

Options:
- `$CFG['cleanup']['old']`:  a number of days after which a course is tagged for deletion.  Measured from the course end date or last date of student activity (def: 610)
- `$CFG['cleanup']['delay']`:    a number of days to delay after notifying the teacher before emptying the course (def: 120)
- `$CFG['cleanup']['msgfrom']`:  the userid to send notification message from (def: 0)
- `$CFG['cleanup']['keepsent']`:   set =0 to keep a copy of sent notifications in sent list
- `$CFG['cleanup']['allowoptout']`:   (default: true) set to false to prevent teachers opting out
- `$CFG['cleanup']['groups']`: You can specify different old/delay values for different groups by defining
`$CFG['cleanup']['groups'] = array(groupid => array('old'=>days, 'delay'=>days));`

## Additional Feature Setup
### LivePoll
IMathAS supports a clicker-style assessment format called LivePoll.  To use it, a Node websocket server must be set up to handle the live syncing.  You can find the code and basic setup instructions on the [IMathAS-Extras](https://github.com/drlippman/imathas-extras) Github page.  Once set up, define:
- `$CFG['GEN']['livepollserver']`: The address for the websocket server, like `livepoll.mysite.com`.  The system assumes the server is running on port 3000.
- `$CFG['GEN']['livepollpassword']`:  This can optionally be defined to provide additional security to livepoll by signing all messages sent via sockets.

### Pandoc
IMathAS can convert assessments to Word format using pandoc setup as a web service.  It doesn't use pandoc locally for security and stability reasons.  To enable converting assessments to word, you'll need to install pandoc on a webserver and set up the front end found on the [IMathAS-Extras](https://github.com/drlippman/imathas-extras) Github page.  Once set up, define:
- `$CFG['GEN']['pandocserver']`:  The address for the pandoc server, like `pandoc.mysite.com` or `pandoc.mysite.com/path`, to where `html2docx.php` is installed.

## Modifying the System

### Building
Most of the system does not require a build process if changes are made.  
There are a couple exceptions.

If you change any of the javascript files with a `_min.js` version, you'll need
to re-minify the javascript.  You can do this using the shell script
`/assess2/vue-src/buildmin.sh`.

If you change any of the Vue files for the assessment player, you'll need to
re-build the distribution files.  See `/assess2/vue-src/README.md` for more
info, and how to configure your system for development mode testing.

If tinymce is changed, or the plugins used are changed, you will need to run
`/tinymce4/maketinymcebundle.php` to re-generate `tinymce_bundled.js`.

### Changing Mathquill
The version of Mathquill used in this repo has it's source in the repo
`https://github.com/drlippman/mathquill`, in the `imathas-master` branch.
