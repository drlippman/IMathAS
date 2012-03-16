<?php
//Google Post and Message reader for imathas
//(c) 2009 David Lippman
header('content-type: text/xml'); 
echo '<?xml version="1.0" encoding="UTF-8" ?>'."\n";
echo '<Module>';
$dbsetup = true;
include("config.php");
?>

  <ModulePrefs title="<?php echo $installname?> Messages and Posts" scrolling="true" height="200"/>
  <UserPref name="showlimit" 
     display_name="Show from courses I "
     datatype="enum"
     default_value="both">
  <EnumValue value="teach" display_value="teach"/>
  <EnumValue value="take" display_value="take"/>
  <EnumValue value="both" display_value="both"/>
</UserPref>
  <UserPref name="key" display_name="Access Key" datatype="string" default_value=""/>
  <Content type="html">
<![CDATA[ 
  
  <div id="content_div"></div>

  <script type="text/javascript">        

function displayInfo() {	  	  
      // XML post/msg  data
	  
    
	  var prefs = new _IG_Prefs(__MODULE_ID__);
	  
	  var showlimit = prefs.getString("showlimit");
          var key = prefs.getString("key");
	  if (key=='') {
	     _gel("content_div").innerHTML = "<i>Edit settings and supply an Access Key.</i>";
	  }
	  var thedate = new Date();  
	  var tzoffset = thedate.getTimezoneOffset();
          var url = 'http://<?php echo $_SERVER['HTTP_HOST'].$imasroot?>/getxml.php?key=' + key + '&limit=' + showlimit + '&tzoffset=' + tzoffset;
	  
      _IG_FetchXmlContent(url, function (response) {
         if (response == null || typeof(response) != "object" || response.firstChild == null) {
           _gel("content_div").innerHTML = "<i>Invalid data.</i>";
           return;
        }	

		// Start building HTML string that will be displayed in <div>.
	html = '';
	// Set the style for the title>.
	
	//html +="<div style='text-align:center; font-size: 90%; color: black; font-weight: 700;'>";     
	//html += "<?php echo $installname;?> messages and posts</div>";
	
	// Set style for msg notice.
	html += "<div style='text-align:left;font-size:80%;background-color: #ff9;'>";     
 	var nummsgs = response.getElementsByTagName("msg").length;
	var numposts = response.getElementsByTagName("post").length;
	html += "You have "+nummsgs+" new messages and "+numposts+" new posts";
	html += "</div>";
	
	var baseurl = response.getElementsByTagName("baseurl").item(0).firstChild.nodeValue;
	
	if (numposts>0) {
		html += "<div style='font-size:80%; font-weight: 700; background-color: #ccf; margin-below:5px;'>New Posts</div>";
		
		var postlist = response.getElementsByTagName("postslist").item(0);
		var postcourses = postlist.getElementsByTagName("course");
		for (var i=0; i<postcourses.length; i++) {
			html += "<div style='font-size:75%; color: #606; font-weight: 700; '>"+postcourses.item(i).getAttribute("name") + "</div>";
			html += "<div style='margin-left: 5px;'>";
			var cid = postcourses.item(i).getAttribute("id");
			var forums = postcourses.item(i).getElementsByTagName("forum");
			for (var j=0; j<forums.length; j++) {
				html += "<div style='font-size:75%; color: green;'>"+forums.item(j).getAttribute("name") + "</div>";
				html += "<div style='margin-left: 5px;'>";
				var fid = forums.item(j).getAttribute("id");
				var posts = forums.item(j).getElementsByTagName("post");
				for (var m=0; m<posts.length; m++) {
					var nodelist = posts.item(m).childNodes;
					for (var k=0; k<nodelist.length; k++) {
						var node = nodelist.item(k);
						if (node.nodeName=="id") {
							var postid = node.firstChild.nodeValue;
						}
						if (node.nodeName=="subject") {
							var postsubject = node.firstChild.nodeValue;
						}
						if (node.nodeName=="author") {
							var postauthor = node.firstChild.nodeValue;
						}
						if (node.nodeName=="date") {
							var postdate = node.firstChild.nodeValue;
						}
					}
					var url = baseurl + "/forums/posts.php?page=-2&amp;cid="+cid+"&amp;forum="+fid+"&amp;thread="+postid;
					html += "<div style='font-size:75%;'>";
					html += "<a style='color: blue;' href='"+url+"' target='_new'>"+postsubject+"</a>";
					html += " <span style='color: black;'>"+postauthor+"</span>";
					html += " <span style='color: gray;'>"+postdate+"</span></div>";
				}
				html += '</div>';
			}
			html += '</div>';
		}
	}
	if (nummsgs>0) {
		html += "<div style='font-size:80%; font-weight: 700; background-color: #ccf; '>New Messages</div>";
		
		var msglist = response.getElementsByTagName("msglist").item(0);
		
		var msgs = msglist.getElementsByTagName("msg");
		for (var m=0; m<msgs.length; m++) {
			var nodelist = msgs.item(m).childNodes;
			for (var k=0; k<nodelist.length; k++) {
				var node = nodelist.item(k);
				if (node.nodeName=="id") {
					var msgid = node.firstChild.nodeValue;
				}
				if (node.nodeName=="subject") {
					var msgsubject = node.firstChild.nodeValue;
				}
				if (node.nodeName=="author") {
					var msgauthor = node.firstChild.nodeValue;
				}
				if (node.nodeName=="date") {
					var msgdate = node.firstChild.nodeValue;
				}
			}
			var url = baseurl + "/msgs/viewmsg.php?cid=0&amp;type=msg&amp;msgid="+msgid;	
			html += "<div style='font-size:80%;'>";
			html += "<a style='color: blue;' href='"+url+"' target='_new'>"+msgsubject+"</a>";
			html += " <span style='color: black;'>"+msgauthor+"</span>";
			html += " <span style='color: gray;'>"+msgdate+"</span></div>";
		}
	}
        
        html += "</div>";

		// Display HTML string in <div>
		_gel('content_div').innerHTML = html;   		 
	 }, { refreshInterval: 10 });
	 setTimeout("displayInfo()",300000); //recheck every 5 minutes
  }

  _IG_RegisterOnloadHandler(displayInfo);
  
  </script>

  ]]> 
  </Content>
  </Module>

