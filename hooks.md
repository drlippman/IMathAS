# Hooks

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




