# Translating IMathAS system messages

First, be aware that while most of the student-facing interface is set up for
internationatlization, thanks to the work of Stefan Baecker and others from 
Univ Koblenz, much of the instructor-facing interface is not yet.

## Extracting Messages
In the following, replace `es` with the desired [two-letter language code](https://www.w3schools.com/tags/ref_language_codes.asp). 
Run the commands on the command line from the main imathas directory.

Make a backup copy of the existing .po file, if one exists

`cp i18n/es.po i18n/es.po.bak`

If this is the first run, you'll need to create the language file

`touch i18n/es.po`

Now, build the .po file. This will process the appropriate git versioned files.

`git ls-tree -r master --name-only | grep -E '(php|js)$' | grep -v 'i18n/' | xargs xgettext -d es -p i18n -j --from-code=UTF-8`

## Translate

Translate i18n/es.po manually

## Compile the translations

If needed, make the i18n/local/es/LC_MESSAGES

`mkdir i18n/locale/es/LC_MESSAGES`

Compile the .po file into the .mo file

`msgfmt -o i18n/locale/es/LC_MESSAGES/imathas.mo i18n/es.po`

Build the javascript messages file

`php i18n/extractjsfrompo.php es`

## Using the tranlations

Add to config.php:

`$CFG['locale'] = 'es';`
