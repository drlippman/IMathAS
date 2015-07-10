
<?php
$this->title = 'OpenMath Help';
?>
<?php
if($section == "loggingin"){
?>
    <div><h2><a>OpenMath Help</a></h2></div>
    <div>
        <h2><a name="loggingin">Logging In</a></h2>

        At the Login page, you will be asked to supply your login credidentials (username and password).
        You will also be able to select Viewing/Accessibility options:
        <ul>
            <li>Most of the time, select Use defaults.  This will detect what your browser is capable of, and use the best option available.</li>
            <li>Force image-based display will force the use of images for math and graph display. This can be helpful if the default display is
                not working correctly for you, or if you want to be able to copy-and-paste math and graphs.</li>
            <li>MathJax display uses MathJax for math display, and detects the best settings for graphs.</li>
            <li>Use text-based display, for screen readers or older browsers.</li>
        </ul>
        <p><b>If you're not sure what to choose:</b> Pick "Use defaults"</p>
        <p>
            The options are set for your current session only, so you can set them differently depending on the support offered by the
            computer you're currently working at.
        </p>
    </div>
<?php } elseif ($section == "gbSettings"){?>
<div><h2><a>OpenMath Help</a></h2></div>
    <div>
        <h3><a name="gradebooksettings">Gradebook Settings and Categories</a></h3>
        <p>Click the "Gradebook Settings" to change Gradebook settings and create or modify categories.  This allows
            you to create a grading scheme.</p>
        <p>Your overall settings are:
        <ul>
            <li>Calculate total using:  Select "points earned" to use a points earned out of points possible grading scheme.  Select "Category weights" to
                assign a percentage weight to each category in calculation of total grade.</li>
            <li>Gradebook display:  Select whether to order the gradebook by item dates (Available until dates for assessments,
                Show After dates for offline grades) or if you want to group items by category</li>
        </ul></p>

        <p>The next section lets you define categories.  There is always the "Default" category, but it will not display if there are no items
            assigned to it.  To add a new category, click the "Add Category" link to add a new line to the table, then fill in the name and
            other info, then click "Update"</p>
        <p>For each category, you can specify:
        <ul>
            <li>Name:  The name of the category</li>
            <li>Scale (optional):  Scale the category total by specifying a point value or percent value to be scaled up to 100%.
                For example, if the category point value was 80 points, and a student earned 50 points, their category total would be 50 points.
                If you specified a scale of 60 points, then the students grade would become 80*(50/60) = 66.7 points out of 80.  You can specify whether
                grades that would end up over 100% should be chopped to 100%.  You can specify no scale by leaving the Scale box blank.</li>
            <li>Drops (optional):  You can specify whether to drop the lowest N scores, or keep the highest N scores from the category.  Set the number to 0 to keep all scores.</li>
            <li>Weight/Fix Category Point Total:  If you are using a "Category Weights" grading scheme, enter the percent weights for each category
                here.  If the category percents don't add to 100%, they are all scaled equally so they do add to 100%.  If you are using a "Points earned" grading scheme, you can
                fix the point value for the category here (optional).  For example, if the current category total is 50 points and a student earns 40 points,
                if you specified a fixed point value of 100 points, the students score would become 80 points.  Leave this blank to use the actual
                category point total.</li>
            <li>Remove:  When you remove a category, any items currently assigned to that category will be assigned to the Default category</li>
        </ul>


    </div>
<?php } ?>