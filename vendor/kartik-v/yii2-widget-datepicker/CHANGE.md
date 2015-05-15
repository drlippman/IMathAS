version 1.3.2
=============
**Date:** 25-Feb-2015

- (enh #21): Add new remove button to clear dates. Applicable only for following `DatePicker` types:
    - `DatePicker::TYPE_COMPONENT_PREPEND` and 
    - `DatePicker::TYPE_COMPONENT_APPEND` 
- (enh #22): Ability to configure picker button options. Applicable only for following `DatePicker` types:
    - `DatePicker::TYPE_COMPONENT_PREPEND` and 
    - `DatePicker::TYPE_COMPONENT_APPEND` 
- (bug #24): Removes BOM charecters from the messages/ru/kvdate.php.
- (enh #25): Improve validation to retrieve the right translation messages folder.

version 1.3.1
=============
**Date:** 13-Feb-2015

- (enh #19): Store date picker widget type as data attribute
- Update datepicker plugin to the latest release.
- Use minified js files for locales.
- Set copyright year to current.

version 1.3.0
=============
**Date:** 25-Jan-2015

- Update to latest release of datepicker plugin.
- (enh #8): Create Tajikistan translations.
- (bug #16): Fix directory separator for assets path in setLanguage.

version 1.2.0
=============
**Date:** 04-Dec-2014

- (enh #1): Add a new markup TYPE_BUTTON with hidden input.
- (bug #3): Fix setLanguage asset locales registration.
- (enh #4): Auto validate disability using new `disabled` and `readonly` properties in InputWidget
- (enh #5): Include styling of markup rightly based on type for `disabled` and `readonly`

version 1.1.0
=============
**Date:** 29-Nov-2014

- (enh #2): Enhance language locale file parsing and registering
- Set release to stable

version 1.0.0
=============
**Date:** 08-Nov-2014

- Initial release 
- Sub repo split from [yii2-widgets](https://github.com/kartik-v/yii2-widgets)