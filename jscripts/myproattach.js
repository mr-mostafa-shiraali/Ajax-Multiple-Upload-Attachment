$(document).ready(function() {
$('#myproattach_form').on('submit', function (e) {
    e.preventDefault(); 
$(this).ajaxSubmit({
	url:'xmlhttp.php',
    beforeSend: function(xhr) {
        $('#status').empty();
        var percentVal = '0%';
		document.getElementById('progress').style.display="block";
        document.getElementById('bar').style.width=percentVal;
         document.getElementById('percent').innerHTML=percentVal;
    },
    uploadProgress: function(event, position, total, percentComplete) {
        var percentVal = percentComplete + '%';
        document.getElementById('bar').style.width=percentVal;
        document.getElementById('percent').innerHTML=percentVal;
    },
	success:showResponse

});

}); 


});  
	function showResponse(responseText, statusText, xhr, $form) 
		{ 
		document.getElementById('progress').style.display="none";
		upload_ret=responseText;
		var text=upload_ret.split('|');
		if($.trim(text[0])!="nofile" && $.trim(text[0])!="Max" )
		{
		document.getElementById('attachment_ajax_upload').innerHTML +=(text[0]);
		}
		document.getElementById('stat_message').innerHTML +=(text[1]);
		}
		
function myproattach_removeAttachment(aid)
{

	act="proremoveAttachment";
	    var request = $.ajax({
        url: "xmlhttp.php",
        type: "POST",
        data: {
            action: act,
			aid:aid
        },
        dataType: "html"
    });
    request.done(function(msg) {
string=msg;
var text=string.split('_');
  aid=text[1];
if(text[0]=='success')
{
document.getElementById('attach_file_'+aid).style.display='none';

}
    });
}
		
//SELECT ALL CHECKBOX

function checkedAll(bx) {
  var cbs = document.getElementsByTagName('input');
  for(var i=0; i < cbs.length; i++) {
    if(cbs[i].type == 'checkbox' && cbs[i].name=='file_check' ) {
      cbs[i].checked = bx.checked;
    }
  }
}

//SELECT ALL CHECKBOX	

//INSERT MULTI FILE IN EDITOR
function insertallfile(){
  var cbs = document.getElementsByTagName('input');
  for(var i=0; i < cbs.length; i++) {
    if(cbs[i].type == 'checkbox' && cbs[i].name=='file_check' && cbs[i].checked==true ) {
      fileaid =cbs[i].value ;
	  $('#message').sceditor('instance').insertText("[attachment="+fileaid+"]");
    }
  }

}
function insertofeditor(aid){
	  $('#message').sceditor('instance').insertText("[attachment="+aid+"]");

}
//INSERT MULTI FILE IN EDITOR
//DELETE MULTI FILE 
function deleteallfile(){
  var cbs = document.getElementsByTagName('input');
  for(var i=0; i < cbs.length; i++) {
    if(cbs[i].type == 'checkbox' && cbs[i].name=='file_check' && cbs[i].checked==true ) {
      fileaid =cbs[i].value ;
	  myproattach_removeAttachment(fileaid);
    }
  }

}
//DELETE MULTI FILE 
//Insert Alias Name

	function aliasname(e)
   {
   x=e.currentTarget;
   id=x.name;
    var act = "set_Aliasname";
	var aliasname=document.getElementById('myproattach_aliasname_'+id).value;
    var request = $.ajax({
        url: "xmlhttp.php",
        type: "POST",
        data: {
            action: act,
			aid:id,
			aliasname:aliasname
        },
        dataType: "html"
    });
    request.done(function(msg) {
     document.getElementById('stat_message').innerHTML=msg;
    });
    }
//Insert Alias Name