<?php
namespace app\components;

use yii\base\Component;

class Calendar extends Component
{
    public static function showCalendar()
    {
//        AppUtility::dump('hey');
        global $currentTime, $courseId;
        ?>
        <div class="item" style="padding-bottom: 15px; padding-right: 15px">
            <div class="col-lg-12 padding-alignment calendar-container">
                <div class='calendar padding-alignment calendar-alignment col-lg-9 pull-left'>
                    <input type="hidden" class="current-time" value="<?php echo $currentTime ?>">

                    <div id="demo" style="display:table-cell; vertical-align:middle;"></div>
                    <input type="hidden" class="calender-course-id" value="<?php echo $courseId ?>">
                </div>
                <div class="calendar-day-details-right-side pull-left col-lg-3">
                    <div class="day-detail-border">
                        <b>Day Details:</b>
                    </div>
                    <div class="calendar-day-details"></div>
                </div>
            </div>
        </div>
   <?php }
}