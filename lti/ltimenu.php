<?php

function getLTIMenuButton() {
    return '<div class="dropdown inlinediv">
  <button 
    tabindex=0 
    class="dropdown-toggle" 
    data-toggle="dropdown" 
    aria-haspopup="true" 
    aria-expanded="false"
  ><span class="arrow-down">LTI</span></button>
<div role="menu" class="dropdown-menu ltimenu">
  <div role=heading class="dropdown-header">Manage Assessment</div>
  <ul class="dropdown-ul">
    <li><a href="#">Preview</a></li>
    <li><a href="#">Questions</a></li>
    <li><a href="#">Settings</a></li>
  </ul>
  <div role=heading class="dropdown-header">Course Management</div>
  <ul class="dropdown-ul">
    <li><a href="#">Roster</a></li>
    <li><a href="#">Gradebook</a></li>
    <li><a href="#">CourseSettings</a></li>
  </ul>
</div></div>';
}


