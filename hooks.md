# Hooks

Hook includes should be provided relative to the main directory.

## admin/actions

Include via `$CFG['hooks']['admin/actions']`

On addition of a new course:
`onAddCourse(course ID, user ID, myrights, user groupid)`

On modification of a course:
`onModCourse(course ID, user ID, myrights, user groupid)`

On modification of an instructor group:
`onModGroup(modified group ID, user ID, myrights, user groupid)`

## admin/approvepending  (admin/approvepending2)

Include via `$CFG['hooks']['admin/approvepending']`

On approval of an account:
`getApproveMessage(firstname, lastname, username, group ID)`
Return:  string message to be emailed to user

`getApproveBcc()`
Return:  array of email addresses to use for approvals.
Overrides `$CFG['email']['new_acct_bcclist']`

`getDenyBcc()`
Return:  array of email addresses to use for approvals.
Overrides `$CFG['email']['new_acct_bcclist']`

## admin/forms

Include via `$CFG['hooks']['admin/forms']`

Gets called before the header
`getHeaderCode()`
Returns HTML to be placed in <head>

Called in the add/mod course form, in the Availability and Access block
`getCourseSettingsForm(action, myrights, courseid)`
action is "addcourse" or "modify"
If action is "addcourse", then courseid will be null

Called in the modify group form. Can use to provide a form element for grouptype.
`getModGroupForm(group ID, grouptype, myrights)`

## actions

Include via `$CFG['hooks']['actions']`

When there's a validation error on the new user signup
`onNewUserError()`
Should output some kind of "Try Again" link

After a student enrolls in a new course:
`onEnroll(course ID)`
Include exit to prevent display of default message

## assessment/showtest

Include via `$CFG['hooks']['assessment/showtest']`

## bltilaunch

Include via `$CFG['hooks']['bltilaunch']`

On creation of a new course:
`onAddCourse(course ID, user ID)`

On identification of local user ID:
`onHaveLocalUser(user ID)`

## delete

Include via `$CFG['hooks']['delete']`

Called to delete all items from a course
`delete_custom_items_by_course($courseid)`

Called to delete one item. 
$itemtype is imas_items.itemtype
$typeid id imas_items.typeid
`delete_custom_item_by_id($itemtype, $typeid)`

## header

Include via `$CFG['hooks']['header']`

Called to insert elements into the `<head>` element:
`insertIntoHead()`

## ltihome

Include via `$CFG['hooks']['ltihome']`

On creation of a new course:
`onAddCourse(course ID, user ID)`

## init

Include via `$CFG['hooks']['init']`
Included immediately after config.php load

## includes/copyiteminc  

Include via `$CFG['hooks']['includes/copyiteminc']`

Called to determine if an item type can be handled by the hook,
called with $itemtype this is imas_items.itemtype
`copyitem_can_handle_type($itemtype)`

Called to trigger copying an item.  Called with
$itemtype: imas_items.itemtype,
$typeid: imas_items.typeid
Should return $newtypeid, the value for the new imas_items.typeid
`copyitem_copy_item($itemtype, $typeid)`

## validate

Include via `$CFG['hooks']['validate']`

Called after login is successful, before redirect back to requested page
`onLogin()`

Called after determining user is already logged in
`alreadyLoggededIn(user ID)`

Called when checking if LTI user can access the requested page
`allowedInAssessment()`
return an array of base filenames that are allowed to be accessed by a user
accessing an assessment via LTI.  This is merged with the default list.

Called to determine if a user should be redirected to a diagnostic assessment
`isDiagnostic()`

## util/batchcreateinstr

Include via `$CFG['hooks']['util/batchcreateinstr']`

On creation of a new course:
`onAddCourse(course ID, owner ID)`

## forms

Include via `$CFG['hooks']['forms']`

Called before the userinfoprofile fieldset
`chguserinfoExtras($userid, $myrights, $groupid)`
Returns HTML to be placed above editable fieldsets (e.g., to display extra data about user account).
