<?php

/*
*
*myproattach Plugin
* Copyright 2011 mostafa shirali
* http://www.pctricks.ir
* No one is authorized to redistribute or remove copyright without my expressed permission.
*
*/



if(!defined("IN_MYBB"))
{
	die("Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.");
}


function myproattach_info()
{
	global $lang;
	$lang->load("myproattach");
return array(
	"name" => $lang->myproattach_pluginname,
	"description" => $lang->myproattach_plugindec,
	"website" => "http://www.pctricks.ir",
	"author" => "Mostafa shirali",
	"authorsite" => "www.pctricks.ir",
	"version" => "1.4",
	"guid"        => "cdfrgthyjulkiogqasqw",
	"compatibility"	=> "18*"
);
}
$plugins->add_hook('newthread_end', 'uploadpanel');
$plugins->add_hook('newreply_end', 'uploadpanel_reply');
$plugins->add_hook('editpost_end', 'uploadpanel_editpost');
$plugins->add_hook("xmlhttp", "myproattach_do_upload");
// This function runs when the plugin is activated.
function myproattach_activate()
{

global $mybb, $db;
	require_once MYBB_ROOT.'inc/adminfunctions_templates.php';

    find_replace_templatesets("headerinclude",'#'.preg_quote('{$stylesheets}').'#i', '
	<link rel="stylesheet" href="{$mybb->asset_url}/jscripts/myproattach.css" type="text/css" media="screen" />
	<script type="text/javascript" src="{$mybb->asset_url}/jscripts/jquery.form.js"></script>
	<script type="text/javascript" src="{$mybb->asset_url}/jscripts/myproattach.js"></script>
	{$stylesheets}');
    find_replace_templatesets("newthread",'#'.preg_quote('{$attachbox}').'#i', '$prevattachbox');
    find_replace_templatesets("newthread",'#'.preg_quote('</form>').'#i', "</form>\n{\$upload_panel}");
    find_replace_templatesets("newreply",'#'.preg_quote('{$attachbox}').'#i', '$prevattachbox');
    find_replace_templatesets("newreply",'#'.preg_quote('</form>').'#i', "</form>\n{\$upload_panel}");
    find_replace_templatesets("editpost",'#'.preg_quote('{$attachbox}').'#i', '$prevattachbox');
    find_replace_templatesets("editpost",'#'.preg_quote('{$footer}').'#i', "{\$upload_panel}\n{\$footer}");


}
/******************************************* Upload panel in New Thread****************************************/
function uploadpanel()
{
	global $mybb,$upload_panel,$lang,$db,$posthash,$fid;
	$lang->load("myproattach");
	$lang->load("myproattach", false, true);
	$pid=$db->escape_string($mybb->input['pid']);
	$query=$db->query("SELECT * FROM ".TABLE_PREFIX."attachments WHERE pid='$posthash' ");
	for($i=0;$i<$db->num_rows($query);$i++)
	{
	$fetch=$db->fetch_array($query);
	$file_size=round(($fetch['filesize']/1024)/1024,3);
	$myproattached_files .='<tr id="attach_file_'.$fetch['aid'].'"><td>'.$fetch['filename'].'</td><td><input type="text" id="myproattach_aliasname_'.$fetch['aid'].'" onKeydown="if(event.keyCode == 13){aliasname(event);}" value="'.$fetch['filename'].'" name="'.$fetch['aid'].'"></td><td>'.$fetch['filetype'].'</td><td>'.$file_size.$lang->myproattach_mb.'</td><td><div  class="myproattach_add" onclick="insertofeditor('.$fetch['aid'].')">'.$lang->myproattach_files_add_bt.'</div></td><td><div  class="myproattach_delete" onclick="myproattach_removeAttachment('.$fetch['aid'].')">'.$lang->myproattach_files_delete_bt.'</div></td><td><input type="checkbox" name="file_check" id="attach_select" value='.$fetch['aid'].' ></td></tr>';
	}
	/******************** USER ATTACHMENT INFO **********************/
	$uid=$mybb->user['uid'];
	$query_attached_size=$db->query("SELECT SUM(filesize) AS sumsize FROM ".TABLE_PREFIX."attachments WHERE uid='$uid' ");
	$fetch_attached_size=$db->fetch_array($query_attached_size);
	$query_ugroup=$db->query("SELECT * FROM ".TABLE_PREFIX."users WHERE uid='$uid' ");
	$fetch_ugroup=$db->fetch_array($query_ugroup);
	$usergroup=$fetch_ugroup['usergroup'];
	$pro_group=$db->query("SELECT * FROM ".TABLE_PREFIX."usergroups WHERE gid='$usergroup' ");
	$pro_group_fetch=$db->fetch_array($pro_group);
	$pro_group_maxsize=$pro_group_fetch['attachquota'];
	if($pro_group_maxsize==0)
	{
		$user_max_size=$lang->myproattach_user_max_ultimite;
	}
	else
	{
	$user_max_size=round($pro_group_maxsize/1024,3);
	}
	$attached_size=round(($fetch_attached_size['sumsize']/1024)/1024,3);
	$user_attach_list='<a href="usercp.php?action=attachments" target="_blank">'.$lang->myproattach_user_attach_list.'</a>';
	$forum_upload_permission=$db->query("SELECT * FROM ".TABLE_PREFIX."forumpermissions WHERE gid='$usergroup' AND fid='$fid'  ");
	$fetch_upload_permission=$db->fetch_array($forum_upload_permission);
	/******************** USER ATTACHMENT INFO **********************/
	if($db->num_rows($forum_upload_permission)!=0)
	{
	if($fetch_upload_permission['canpostattachments']==0)
	{
	$upload_panel="<div class=\"myproattach_error\" onclick=\"this.style.display='none';\">{$lang->myproattach_fp_nc_upload}</div>";
	}
	else
	{
		$upload_panel="<div id=\"progress\">
        <div id=\"bar\"></div >
        <div id=\"percent\">0%</div >
    </div>
    <div id=\"status\"></div>
	<div id=\"stat_message\"></div><br/>
	<table cellspacing='0' id='attachment_ajax_upload'>
<tr><th  width=\"100%\">
	<form id=\"myproattach_form\"  method=POST  enctype=\"multipart/form-data\">
	<input type=\"hidden\" name=\"action\"   value=\"upmyproattach\">
	<input type=\"hidden\" name=\"posthash\"   value=\"{$posthash}\">
	<div class=\"row fileupload-buttonbar\">
            <div class=\"myproattachdiv\">
                <!-- The fileinput-button span is used to style the file input field as button -->
                <span class=\"btn btn-success fileinput-button\">
                    <i class=\"icon-plus icon-white\"></i>
                    <span>{$lang->upload_file_add}</span>
                    <input type=\"file\" name=\"files[]\"   multiple=\"multiple\">
                </span>
                <input type=\"submit\"  id=\"uploader\" name=\"uploader\" value=\"{$lang->upload_file_start}\" class=\"btn btn-primary start\">
                </form>
				<input type=\"button\" id=\"delete_all\" onclick=\"deleteallfile()\" name=\"delete_all\" value=\"{$lang->delete_file_all}\" class=\"btn btn-danger delete\">
                <input type=\"button\"  id=\"insert_all\"  onclick=\"insertallfile()\" name=\"insert_all\" value=\"{$lang->insert_file_all}\" class=\"btn btn-primary start\">
            </div>
        </div>
	</th></tr>
	<tr><th>{$lang->myproattach_files_filename}</th><th>{$lang->myproattach_files_aliasname}</th><th>{$lang->myproattach_files_filetype}</th><th>{$lang->myproattach_files_filesize}</th><th>{$lang->myproattach_files_add}</th><th>{$lang->myproattach_files_delete}</th><th><input type=\"checkbox\" id=\"attach_selectall\" name=\"allCheck\" onclick='checkedAll(this);'></th></tr>
	{$myproattached_files}
	</table><table cellspacing='0' id='attachment_user_info'>
	<tr><td>{$lang->myproattach_user_attached_size}{$attached_size}{$lang->myproattach_mb}</td><td>{$lang->myproattach_user_maximum_size}{$user_max_size}</td><td>{$user_attach_list}</th></tr>
	</table>
	<div style=\"text-align: left; font-size: 10px;\"> Ajax Multiple Upload Attachment By<a href=\"http://www.pctricks.ir\" target=\"blank\">Mostafa</a></div>";
	}
	}
	else
	$upload_panel="<div id=\"progress\">
        <div id=\"bar\"></div >
        <div id=\"percent\">0%</div >
    </div>
    <div id=\"status\"></div>
	<div id=\"stat_message\"></div><br/>
	<table cellspacing='0' id='attachment_ajax_upload'>
<tr><th width=\"100%\">
	<form id=\"myproattach_form\"  method=POST  enctype=\"multipart/form-data\">
	<input type=\"hidden\" name=\"action\"   value=\"upmyproattach\">
	<input type=\"hidden\" name=\"posthash\"   value=\"{$posthash}\">
	<div class=\"row fileupload-buttonbar\">
            <div class=\"myproattachdiv\">
                <!-- The fileinput-button span is used to style the file input field as button -->
                <span class=\"btn btn-success fileinput-button\">
                    <i class=\"icon-plus icon-white\"></i>
                    <span>{$lang->upload_file_add}</span>
                    <input type=\"file\" name=\"files[]\"   multiple=\"multiple\">
                </span>
                <input type=\"submit\"  id=\"uploader\" name=\"uploader\" value=\"{$lang->upload_file_start}\" class=\"btn btn-primary start\">
                </form>
				<input type=\"button\" id=\"delete_all\" onclick=\"deleteallfile()\" name=\"delete_all\" value=\"{$lang->delete_file_all}\" class=\"btn btn-danger delete\">
                <input type=\"button\"  id=\"insert_all\"  onclick=\"insertallfile()\" name=\"insert_all\" value=\"{$lang->insert_file_all}\" class=\"btn btn-primary start\">
            </div>
        </div>
	</th></tr>
	<tr><th>{$lang->myproattach_files_filename}</th><th>{$lang->myproattach_files_aliasname}</th><th>{$lang->myproattach_files_filetype}</th><th>{$lang->myproattach_files_filesize}</th><th>{$lang->myproattach_files_add}</th><th>{$lang->myproattach_files_delete}</th><th><input type=\"checkbox\" id=\"attach_selectall\" name=\"allCheck\" onclick='checkedAll(this);'></th></tr>
	{$myproattached_files}
	</table><table cellspacing='0' id='attachment_user_info'>
	<tr><td>{$lang->myproattach_user_attached_size}{$attached_size}{$lang->myproattach_mb}</td><td>{$lang->myproattach_user_maximum_size}{$user_max_size}</td><td>{$user_attach_list}</th></tr>
	</table><div style=\"text-align: left; font-size: 10px;\"> Ajax Multiple Upload Attachment By<a href=\"http://www.pctricks.ir\" target=\"blank\">Mostafa</a></div>";


}
/******************************************* Upload panel in New Thread****************************************/

/******************************************* Upload panel in New Reply *******************************************/
function uploadpanel_reply()
{
	global $mybb,$upload_panel,$lang,$db;
	$lang->load("myproattach");
	$posthash=$db->escape_string($mybb->input['posthash']);
	$query=$db->query("SELECT * FROM ".TABLE_PREFIX."attachments WHERE posthash='$posthash' ");
	for($i=0;$i<$db->num_rows($query);$i++)
	{
	$fetch=$db->fetch_array($query);
	$file_size=round(($fetch['filesize']/1024)/1024,3);
	$myproattached_files .='<tr id="attach_file_'.$fetch['aid'].'"><td>'.$fetch['filename'].'</td><td><input type="text" id="myproattach_aliasname_'.$fetch['aid'].'" onKeydown="if(event.keyCode == 13){aliasname(event);}" value="'.$fetch['filename'].'" name="'.$fetch['aid'].'"></td><td>'.$fetch['filetype'].'</td><td>'.$file_size.$lang->myproattach_mb.'</td><td><div  class="myproattach_add" onclick="insertofeditor('.$fetch['aid'].')">'.$lang->myproattach_files_add_bt.'</div></td><td><div  class="myproattach_delete" onclick="myproattach_removeAttachment('.$fetch['aid'].')">'.$lang->myproattach_files_delete_bt.'</div></td><td><input type="checkbox" name="file_check" id="attach_select" value='.$fetch['aid'].' ></td></tr>';

	}

	$lang->load("myproattach", false, true);
		/******************** USER ATTACHMENT INFO **********************/
	$uid=$mybb->user['uid'];
	$query_attached_size=$db->query("SELECT SUM(filesize) AS sumsize FROM ".TABLE_PREFIX."attachments WHERE uid='$uid' ");
	$fetch_attached_size=$db->fetch_array($query_attached_size);
	$query_ugroup=$db->query("SELECT * FROM ".TABLE_PREFIX."users WHERE uid='$uid' ");
	$fetch_ugroup=$db->fetch_array($query_ugroup);
	$usergroup=$fetch_ugroup['usergroup'];
	$pro_group=$db->query("SELECT * FROM ".TABLE_PREFIX."usergroups WHERE gid='$usergroup' ");
	$pro_group_fetch=$db->fetch_array($pro_group);
	$pro_group_maxsize=$pro_group_fetch['attachquota'];
	if($pro_group_maxsize==0)
	{
		$user_max_size=$lang->myproattach_user_max_ultimite;
	}
	else
	{
		$user_max_size=round($pro_group_maxsize/1024,3);
	}
	$attached_size=round(($fetch_attached_size['sumsize']/1024)/1024,3);
	$user_attach_list='<a href="usercp.php?action=attachments" target="_blank">'.$lang->myproattach_user_attach_list.'</a>';
	$forum_upload_permission=$db->query("SELECT * FROM ".TABLE_PREFIX."forumpermissions WHERE gid='$usergroup' AND fid='$fid'  ");
	$fetch_upload_permission=$db->fetch_array($forum_upload_permission);
	/******************** USER ATTACHMENT INFO **********************/
		if($db->num_rows($forum_upload_permission)!=0)
	{
	if($fetch_upload_permission['canpostattachments']==0)
	{
	$upload_panel="<div class=\"myproattach_error\" onclick=\"this.style.display='none';\">{$lang->myproattach_fp_nc_upload}</div>";
	}
	else
	{
		$upload_panel="<div id=\"progress\">
        <div id=\"bar\"></div >
        <div id=\"percent\">0%</div >
    </div>
    <div id=\"status\"></div>
	<div id=\"stat_message\"></div><br/>
	<table cellspacing='0' id='attachment_ajax_upload'>
<tr><th width=\"100%\">
	<form id=\"myproattach_form\"  method=POST  enctype=\"multipart/form-data\">
	<input type=\"hidden\" name=\"action\"   value=\"upmyproattach\">
		<input type=\"hidden\" name=\"posthash\"   value=\"{$posthash}\">
	<div class=\"row fileupload-buttonbar\">
            <div class=\"myproattachdiv\">
                <!-- The fileinput-button span is used to style the file input field as button -->
                <span class=\"btn btn-success fileinput-button\">
                    <i class=\"icon-plus icon-white\"></i>
                    <span>{$lang->upload_file_add}</span>
                    <input type=\"file\" name=\"files[]\"   multiple=\"multiple\">
                </span>
                <input type=\"submit\" id=\"uploader\" name=\"uploader\" value=\"{$lang->upload_file_start}\" class=\"btn btn-primary start\">
               </form>
			   <input type=\"button\" id=\"delete_all\" onclick=\"deleteallfile()\" name=\"delete_all\" value=\"{$lang->delete_file_all}\" class=\"btn btn-danger delete\">
                <input type=\"button\"  id=\"insert_all\" onclick=\"insertallfile()\" name=\"insert_all\" value=\"{$lang->insert_file_all}\" class=\"btn btn-primary start\">
		   </div>
        </div> 
	</th></tr>
	<tr><th>{$lang->myproattach_files_filename}</th><th>{$lang->myproattach_files_aliasname}</th><th>{$lang->myproattach_files_filetype}</th><th>{$lang->myproattach_files_filesize}</th><th>{$lang->myproattach_files_add}</th><th>{$lang->myproattach_files_delete}</th><th><input type=\"checkbox\" id=\"attach_selectall\" name=\"allCheck\" onclick='checkedAll(this);' ></th></tr>
	{$myproattached_files}
	</table><table cellspacing='0' id='attachment_user_info'>
	<tr><td>{$lang->myproattach_user_attached_size}{$attached_size}{$lang->myproattach_mb}</td><td>{$lang->myproattach_user_maximum_size}{$user_max_size}</td><td>{$user_attach_list}</th></tr>
	</table>
	<div style=\"text-align: left; font-size: 10px;\"> Ajax Multiple Upload Attachment By<a href=\"http://www.pctricks.ir\" target=\"blank\">Mostafa</a></div>";
	}
	}
	else
	{
		$upload_panel="<div id=\"progress\">
        <div id=\"bar\"></div >
        <div id=\"percent\">0%</div >
    </div>
    <div id=\"status\"></div>
	<div id=\"stat_message\"></div><br/>
	<table cellspacing='0' id='attachment_ajax_upload'>
<tr><th width=\"100%\">
	<form id=\"myproattach_form\"  method=POST  enctype=\"multipart/form-data\">
	<input type=\"hidden\" name=\"action\"   value=\"upmyproattach\">
	<input type=\"hidden\" name=\"posthash\"   value=\"{$posthash}\">
	<div class=\"row fileupload-buttonbar\">
            <div class=\"myproattachdiv\">
                <!-- The fileinput-button span is used to style the file input field as button -->
                <span class=\"btn btn-success fileinput-button\">
                    <i class=\"icon-plus icon-white\"></i>
                    <span>{$lang->upload_file_add}</span>
                    <input type=\"file\" name=\"files[]\"   multiple=\"multiple\">
                </span>
                <input type=\"submit\" id=\"uploader\" name=\"uploader\" value=\"{$lang->upload_file_start}\" class=\"btn btn-primary start\">
               </form>
			   <input type=\"button\" id=\"delete_all\" onclick=\"deleteallfile()\" name=\"delete_all\" value=\"{$lang->delete_file_all}\" class=\"btn btn-danger delete\">
                <input type=\"button\"  id=\"insert_all\" onclick=\"insertallfile()\" name=\"insert_all\" value=\"{$lang->insert_file_all}\" class=\"btn btn-primary start\">
		   </div>
        </div> 
	</th></tr>
	<tr><th>{$lang->myproattach_files_filename}</th><th>{$lang->myproattach_files_aliasname}</th><th>{$lang->myproattach_files_filetype}</th><th>{$lang->myproattach_files_filesize}</th><th>{$lang->myproattach_files_add}</th><th>{$lang->myproattach_files_delete}</th><th><input type=\"checkbox\" id=\"attach_selectall\" name=\"allCheck\" onclick='checkedAll(this);' ></th></tr>
	{$myproattached_files}
	</table><table cellspacing='0' id='attachment_user_info'>
	<tr><td>{$lang->myproattach_user_attached_size}{$attached_size}{$lang->myproattach_mb}</td><td>{$lang->myproattach_user_maximum_size}{$user_max_size}</td><td>{$user_attach_list}</th></tr>
	</table>
	<div style=\"text-align: left; font-size: 10px;\"> Ajax Multiple Upload Attachment By<a href=\"http://www.pctricks.ir\" target=\"blank\">Mostafa</a></div>";
	}


}
/******************************************* Upload panel in New Reply *******************************************/
/******************************************* Upload panel in Edit Post *******************************************/
function uploadpanel_editpost()
{
	global $mybb,$upload_panel,$lang,$db;
	$lang->load("myproattach");
	$lang->load("myproattach", false, true);
	$pid=$db->escape_string($mybb->input['pid']);
	$query=$db->query("SELECT * FROM ".TABLE_PREFIX."attachments WHERE pid='$pid' ");
	for($i=0;$i<$db->num_rows($query);$i++)
	{
	$fetch=$db->fetch_array($query);
	$file_size=round(($fetch['filesize']/1024)/1024,3);
	$myproattached_files .='<tr id="attach_file_'.$fetch['aid'].'"><td>'.$fetch['filename'].'</td><td><input type="text" id="myproattach_aliasname_'.$fetch['aid'].'" onKeydown="if(event.keyCode == 13){aliasname(event);}" value="'.$fetch['filename'].'" name="'.$fetch['aid'].'"></td><td>'.$fetch['filetype'].'</td><td>'.$file_size.$lang->myproattach_mb.'</td><td><div  class="myproattach_add" onclick="insertofeditor('.$fetch['aid'].')">'.$lang->myproattach_files_add_bt.'</div></td><td><div  class="myproattach_delete" onclick="myproattach_removeAttachment('.$fetch['aid'].')">'.$lang->myproattach_files_delete_bt.'</div></td><td><input type="checkbox" name="file_check" id="attach_select" value='.$fetch['aid'].' ></td></tr>';
	}
		/******************** USER ATTACHMENT INFO **********************/
	$uid=$mybb->user['uid'];
	$query_attached_size=$db->query("SELECT SUM(filesize) AS sumsize FROM ".TABLE_PREFIX."attachments WHERE uid='$uid' ");
	$fetch_attached_size=$db->fetch_array($query_attached_size);
	$query_ugroup=$db->query("SELECT * FROM ".TABLE_PREFIX."users WHERE uid='$uid' ");
	$fetch_ugroup=$db->fetch_array($query_ugroup);
	$usergroup=$fetch_ugroup['usergroup'];
	$pro_group=$db->query("SELECT * FROM ".TABLE_PREFIX."usergroups WHERE gid='$usergroup' ");
	$pro_group_fetch=$db->fetch_array($pro_group);
	$pro_group_maxsize=$pro_group_fetch['attachquota'];
	if($pro_group_maxsize==0)
	{
		$user_max_size=$lang->myproattach_user_max_ultimite;
	}
	else
	{
		$user_max_size=round($pro_group_maxsize/1024,3);
	}
	$attached_size=round(($fetch_attached_size['sumsize']/1024)/1024,3);
	$user_attach_list='<a href="usercp.php?action=attachments" target="_blank">'.$lang->myproattach_user_attach_list.'</a>';
	$forum_upload_permission=$db->query("SELECT * FROM ".TABLE_PREFIX."forumpermissions WHERE gid='$usergroup' AND fid='$fid'  ");
	$fetch_upload_permission=$db->fetch_array($forum_upload_permission);
	/******************** USER ATTACHMENT INFO **********************/
		if($db->num_rows($forum_upload_permission)!=0)
	{
	if($fetch_upload_permission['canpostattachments']==0)
	{
	$upload_panel="<div class=\"myproattach_error\" onclick=\"this.style.display='none';\">{$lang->myproattach_fp_nc_upload}</div>";
	}
	else
	{
		$upload_panel="<div id=\"progress\">
        <div id=\"bar\"></div >
        <div id=\"percent\">0%</div >
    </div>
    <div id=\"status\"></div>
	<div id=\"stat_message\"></div><br/>
	<table cellspacing='0' id='attachment_ajax_upload'>
<tr><th width=\"100%\">
	<form id=\"myproattach_form\"  method=POST  enctype=\"multipart/form-data\" >
	<input type=\"hidden\" name=\"action\"   value=\"upmyproattach_edit\">
	<input type=\"hidden\" name=\"pid\"   value=\"{$pid}\">
	<div class=\"row fileupload-buttonbar\">
            <div class=\"myproattachdiv\">
                <!-- The fileinput-button span is used to style the file input field as button -->
                <span class=\"btn btn-success fileinput-button\">
                    <i class=\"icon-plus icon-white\"></i>
                    <span>{$lang->upload_file_add}</span>
                    <input type=\"file\" name=\"files[]\"   multiple=\"multiple\">
                </span>
                <input type=\"submit\" id=\"uploader\" name=\"uploader\" value=\"{$lang->upload_file_start}\" class=\"btn btn-primary start\">
                </form>
				<input type=\"button\" id=\"delete_all\" onclick=\"deleteallfile()\" name=\"delete_all\" value=\"{$lang->delete_file_all}\" class=\"btn btn-danger delete\">
                <input type=\"button\"  id=\"insert_all\" onclick=\"insertallfile()\" name=\"insert_all\" value=\"{$lang->insert_file_all}\" class=\"btn btn-success start\"> 
			</div>
        </div>

	</th></tr>
	<tr><th>{$lang->myproattach_files_filename}</th><th>{$lang->myproattach_files_aliasname}</th><th>{$lang->myproattach_files_filetype}</th><th>{$lang->myproattach_files_filesize}</th><th>{$lang->myproattach_files_add}</th><th>{$lang->myproattach_files_delete}</th><th><input type=\"checkbox\" id=\"attach_selectall\" name=\"allCheck\" onclick='checkedAll(this);'></th></tr>
	{$myproattached_files}
	</table><table cellspacing='0' id='attachment_user_info'>
	<tr><td>{$lang->myproattach_user_attached_size}{$attached_size}{$lang->myproattach_mb}</td><td>{$lang->myproattach_user_maximum_size}{$user_max_size}</td><td>{$user_attach_list}</th></tr>
	</table>
	<div style=\"text-align: left; font-size: 10px;\"> Ajax Multiple Upload Attachment By<a href=\"http://www.pctricks.ir\" target=\"blank\">Mostafa</a></div>";
	}
	}
	else
	{
		$upload_panel="<div id=\"progress\">
        <div id=\"bar\"></div >
        <div id=\"percent\">0%</div >
    </div>
    <div id=\"status\"></div>
	<div id=\"stat_message\"></div><br/>
	<table cellspacing='0' id='attachment_ajax_upload'>
<tr><th width=\"100%\">
	<form id=\"myproattach_form\"  method=POST  enctype=\"multipart/form-data\" >
	<input type=\"hidden\" name=\"action\"   value=\"upmyproattach_edit\">
	<input type=\"hidden\" name=\"pid\"   value=\"{$pid}\">
	<div class=\"row fileupload-buttonbar\">
            <div class=\"myproattachdiv\">
                <!-- The fileinput-button span is used to style the file input field as button -->
                <span class=\"btn btn-success fileinput-button\">
                    <i class=\"icon-plus icon-white\"></i>
                    <span>{$lang->upload_file_add}</span>
                    <input type=\"file\" name=\"files[]\"   multiple=\"multiple\">
                </span>
                <input type=\"submit\" id=\"uploader\" name=\"uploader\" value=\"{$lang->upload_file_start}\" class=\"btn btn-primary start\">
                </form>
				<input type=\"button\" id=\"delete_all\" onclick=\"deleteallfile()\" name=\"delete_all\" value=\"{$lang->delete_file_all}\" class=\"btn btn-danger delete\">
                <input type=\"button\"  id=\"insert_all\" onclick=\"insertallfile()\" name=\"insert_all\" value=\"{$lang->insert_file_all}\" class=\"btn btn-success start\"> 
			</div>
        </div>

	</th></tr>
	<tr><th>{$lang->myproattach_files_filename}</th><th>{$lang->myproattach_files_aliasname}</th><th>{$lang->myproattach_files_filetype}</th><th>{$lang->myproattach_files_filesize}</th><th>{$lang->myproattach_files_add}</th><th>{$lang->myproattach_files_delete}</th><th><input type=\"checkbox\" id=\"attach_selectall\" name=\"allCheck\" onclick='checkedAll(this);'></th></tr>
	{$myproattached_files}
	</table><table cellspacing='0' id='attachment_user_info'>
	<tr><td>{$lang->myproattach_user_attached_size}{$attached_size}{$lang->myproattach_mb}</td><td>{$lang->myproattach_user_maximum_size}{$user_max_size}</td><td>{$user_attach_list}</th></tr>
	</table>
	<div style=\"text-align: left; font-size: 10px;\"> Ajax Multiple Upload Attachment By<a href=\"http://www.pctricks.ir\" target=\"blank\">Mostafa</a></div>";
	}


}
/******************************************* Upload panel in Edit Post *******************************************/

/******************************************* Upload Files ****************************************/
function myproattach_do_upload()
{
	require_once MYBB_ROOT."inc/functions_upload.php";
	global $mybb,$pid,$lang,$db;
	
	$lang->load("myproattach");
	$lang->load("myproattach", false, true);
	if( $mybb->request_method != "post" AND ($mybb->input['action'] != "upmyproattach" OR $mybb->input['action'] != "proremoveAttachment" OR $mybb->input['action'] != "upmyproattach_edit" OR $mybb->input['action'] != "set_Aliasname" ))
	{
		return false;
	}
		//upload myproattach


	if($mybb->input['action'] == "upmyproattach")
	{
		
		$uploadmessage='';
		if(count($_FILES['files']['tmp_name'])==0)
		{
		$uploadmessage ="nofile|<div class=\"myproattach_error\" onclick=\"this.style.display='none';\">{$lang->myproattach_message_empty}</div>";
		echo $uploadmessage;
		exit();
		}
		else
		{
        for($i=0;$i<count($_FILES['files']['tmp_name']);$i++)
		{
	$update_attachments=false;
	$ext = get_extension($_FILES['files']['name'][$i]);
	$query = $db->simple_select("attachtypes", "*", "extension='".$db->escape_string($ext)."'");
	$attachtype = $db->fetch_array($query);
	if(!$attachtype['atid'])
	{
	$filename=$_FILES['files']['name'][$i];
	$uploadmessage_error .='<div class="myproattach_error" onclick="this.style.display=\'none\';">'.$lang->myproattach_message_file.$filename.$lang->myproattach_message_no_extesion.'</div>';
	}
	else 	// Check the size
	if($attachment['size'] > $attachtype['maxsize']*1024 && $attachtype['maxsize'] != "")
	{
	$filename=$_FILES['files']['name'][$i];
	$uploadmessage_error .='<div class="myproattach_error" onclick="this.style.display=\'none\';">'.$lang->myproattach_message_file.$filename.$lang->myproattach_message_no_size.'</div>';
	}
	else
	{
	$filename=$_FILES['files']['name'][$i];
	$uid=$mybb->user['uid'];
	$posthash=$db->escape_string($mybb->input['posthash']);
	$pid=$db->escape_string($mybb->input['pid']);
	$query_ugroup=$db->query("SELECT * FROM ".TABLE_PREFIX."users WHERE uid='$uid' ");
	$fetch_ugroup=$db->fetch_array($query_ugroup);
	$usergroup=$fetch_ugroup['usergroup'];
	/******************* Check User Permissions *******************************/
	$query_attachment_perm=$db->query("SELECT SUM(filesize) AS sumsize FROM ".TABLE_PREFIX."attachments WHERE uid='$uid' ");
	$fetch_attachment_perm=$db->fetch_array($query_attachment_perm);
	//ALL SIZE UPLODED WITH NEW UPLOAD
	$myproattach_all_uploaded=(intval($fetch_attachment_perm['sumsize'])+intval($_FILES['files']['size'][$i]))/1024;
	$pro_group=$db->query("SELECT * FROM ".TABLE_PREFIX."usergroups WHERE gid='$usergroup' ");
	$pro_group_fetch=$db->fetch_array($pro_group);
	//MAXIMUM SIZE THAT GROUP CAN UPLOAD IF=0 MEAN ULTIMATE
	$pro_group_maxsize=$pro_group_fetch['attachquota'];
	if(!empty($posthash))
	{
	$query_userun=$db->query("SELECT * FROM ".TABLE_PREFIX."attachments WHERE `posthash`='$posthash'");
	}
    else if(!empty($pid))
	{
	$query_userun=$db->query("SELECT * FROM ".TABLE_PREFIX."attachments WHERE `pid`='$pid'");
	}
	//NUMBER ATTACHMENT USER UPLODED
	$number_userun=$db->num_rows($query_userun);
	//NUMBER ATTACHMENT USER CAN UPLOAD
	$pro_max_attachment_number=$mybb->settings['maxattachments'];
	/******************* Check User Permissions *******************************/
	if($number_userun>=$pro_max_attachment_number)
	{
	$uploadmessage_error .='<div class="myproattach_error" onclick="this.style.display=\'none\';">'.$lang->myproattach_message_max_number.'</div>';
	}
	else if($myproattach_all_uploaded>$pro_group_maxsize AND $pro_group_maxsize>0 )
	{
	$uploadmessage_error .='<div class="myproattach_error" onclick="this.style.display=\'none\';">'.$lang->myproattach_message_max_one.$filename.$lang->myproattach_message_max_two.'</div>';
	}
	else
	{
		$file=array(
		'name' => $_FILES['files']['name'][$i],
		'type' => $_FILES['files']['type'][$i],
		'tmp_name' => $_FILES['files']['tmp_name'][$i],
		'size' => $_FILES['files']['size'][$i],
		'error' =>$_FILES['files']['error'][$i],
	     );
		
	$attachedfile=upload_attachment($file,$update_attachments);
	$aid = $attachedfile['aid'];
	$file_size=round(($_FILES['files']['size'][$i]/1024)/1024,3);
	$files_info .='<tr id="attach_file_'.$aid.'" ><td>'.$_FILES['files']['name'][$i].'</td><td><input type="text" id="myproattach_aliasname_'.$aid.'" onKeydown="if(event.keyCode == 13){aliasname(event);}" name="'.$aid.'"></td><td>'.$_FILES['files']['type'][$i].'</td><td>'.$file_size.$lang->myproattach_mb.'</td><td><div  class="myproattach_add" onclick="insertofeditor('.$aid.')">'.$lang->myproattach_files_add_bt.'</div></td><td><div  class="myproattach_delete" onclick="myproattach_removeAttachment('.$aid.')">'.$lang->myproattach_files_delete_bt.'</div></td><td><input type="checkbox" name="file_check" id="attach_select" value='.$aid.' ></td></tr>';
	$filename=$_FILES['files']['name'][$i];
	$uploadmessage .='<div class="myproattach_success" onclick="this.style.display=\'none\';">'.$lang->myproattach_message_file.$filename.$lang->myproattach_message_success.'</div>';
	 }
	 }
		}
		if($files_info=='')
		{
		$files_info='Max';
		$uploadmessage=$uploadmessage_error;
		}
		else
		{
		$uploadmessage=$uploadmessage.$uploadmessage_error;
		}
		$feedback=$files_info.'|'.$uploadmessage;
		echo $feedback;
		$files_info='';
		$feedback='';
		$uploadmessage='';
		exit();
		
		}
	}
	/******************************************************* Upload Attach Edit Post **********************/
			//upload myproattach


	if($mybb->input['action'] == "upmyproattach_edit")
	{
		
		$uploadmessage='';
		if(count($_FILES['files']['tmp_name'])==0)
		{
		$uploadmessage ="nofile|<div class=\"myproattach_error\" onclick=\"this.style.display='none';\">{$lang->myproattach_message_empty}</div>";
		echo $uploadmessage;
		exit();
		}
		else
		{
        for($i=0;$i<count($_FILES['files']['tmp_name']);$i++)
		{
	$update_attachments=false;
	$ext = get_extension($_FILES['files']['name'][$i]);
	$query = $db->simple_select("attachtypes", "*", "extension='".$db->escape_string($ext)."'");
	$attachtype = $db->fetch_array($query);
	if(!$attachtype['atid'])
	{
	$filename=$_FILES['files']['name'][$i];
	$uploadmessage_error .='<div class="myproattach_error" onclick="this.style.display=\'none\';">'.$lang->myproattach_message_file.$filename.$lang->myproattach_message_no_extesion.'</div>';
	}
	else 	// Check the size
	if($attachment['size'] > $attachtype['maxsize']*1024 && $attachtype['maxsize'] != "")
	{
	$filename=$_FILES['files']['name'][$i];
	$uploadmessage_error .='<div class="myproattach_error" onclick="this.style.display=\'none\';">'.$lang->myproattach_message_file.$filename.$lang->myproattach_message_no_size.'</div>';
	}
	else
	{
	$filename=$_FILES['files']['name'][$i];
	$uid=$mybb->user['uid'];
	$pid=$db->escape_string($mybb->input['pid']);
	$query_ugroup=$db->query("SELECT * FROM ".TABLE_PREFIX."users WHERE uid='$uid' ");
	$fetch_ugroup=$db->fetch_array($query_ugroup);
	$usergroup=$fetch_ugroup['usergroup'];
	/******************* Check User Permissions *******************************/
	$query_attachment_perm=$db->query("SELECT SUM(filesize) AS sumsize FROM ".TABLE_PREFIX."attachments WHERE uid='$uid' ");
	$fetch_attachment_perm=$db->fetch_array($query_attachment_perm);
	//ALL SIZE UPLODED WITH NEW UPLOAD
	$myproattach_all_uploaded=(intval($fetch_attachment_perm['sumsize'])+intval($_FILES['files']['size'][$i]))/1024;
	$pro_group=$db->query("SELECT * FROM ".TABLE_PREFIX."usergroups WHERE gid='$usergroup' ");
	$pro_group_fetch=$db->fetch_array($pro_group);
	//MAXIMUM SIZE THAT GROUP CAN UPLOAD IF=0 MEAN ULTIMATE
	$pro_group_maxsize=$pro_group_fetch['attachquota'];
	$query_userun=$db->query("SELECT * FROM ".TABLE_PREFIX."attachments WHERE pid='$pid' ");
	//NUMBER ATTACHMENT USER UPLODED
	$number_userun=$db->num_rows($query_userun);
	//NUMBER ATTACHMENT USER CAN UPLOAD
	$pro_max_attachment_number=$mybb->settings['maxattachments'];
	/******************* Check User Permissions *******************************/
	if($number_userun>=$pro_max_attachment_number)
	{
	$uploadmessage_error .='<div class="myproattach_error" onclick="this.style.display=\'none\';">'.$lang->myproattach_message_max_number.'</div>';
	}
	else if($myproattach_all_uploaded>$pro_group_maxsize AND $pro_group_maxsize>0 )
	{
	$uploadmessage_error .='<div class="myproattach_error" onclick="this.style.display=\'none\';">'.$lang->myproattach_message_max_one.$filename.$lang->myproattach_message_max_two.'</div>';
	}
	else
	{
		$file=array(
		'name' => $_FILES['files']['name'][$i],
		'type' => $_FILES['files']['type'][$i],
		'tmp_name' => $_FILES['files']['tmp_name'][$i],
		'size' => $_FILES['files']['size'][$i],
		'error' =>$_FILES['files']['error'][$i],
	     );
		
	$attachedfile=upload_attachment($file,$update_attachments);
	$aid = $attachedfile['aid'];
	$pid=$db->escape_string($mybb->input['pid']);
	$query=$db->query("UPDATE ".TABLE_PREFIX."attachments SET pid='$pid' WHERE aid='$aid'");
	$file_size=round(($_FILES['files']['size'][$i]/1024)/1024,3);
	$files_info .='<tr id="attach_file_'.$aid.'" ><td>'.$_FILES['files']['name'][$i].'</td><td><input type="text" id="myproattach_aliasname_'.$aid.'" onKeydown="if(event.keyCode == 13){aliasname(event);}" name="'.$aid.'"></td><td>'.$_FILES['files']['type'][$i].'</td><td>'.$file_size.$lang->myproattach_mb.'</td><td><div  class="myproattach_add" onclick="insertofeditor('.$aid.')">'.$lang->myproattach_files_add_bt.'</div></td><td><div  class="myproattach_delete" onclick="myproattach_removeAttachment('.$aid.')">'.$lang->myproattach_files_delete_bt.'</div></td><td><input type="checkbox" name="file_check" id="attach_select" value='.$aid.' ></td></tr>';
	$filename=$_FILES['files']['name'][$i];
	$uploadmessage .='<div class="myproattach_success" onclick="this.style.display=\'none\';">'.$lang->myproattach_message_file.$filename.$lang->myproattach_message_success.'</div>';
	 }
	 }
		}
		if($files_info=='')
		{
		$files_info='Max';
		$uploadmessage=$uploadmessage_error;
		}
		else
		{
		$uploadmessage=$uploadmessage.$uploadmessage_error;
		}
		$feedback=$files_info.'|'.$uploadmessage;
		echo $feedback;
		$files_info='';
		$feedback='';
		$uploadmessage='';
		exit();
		
		}
	}
	/******************************************************* Upload Attach Edit Post **********************/
	if($mybb->input['action'] == "proremoveAttachment")
	{
		if(defined('IN_ADMINCP'))
	{
	    $uploadpath = '../'.$mybb->settings['uploadspath'];
	}
	else
	{
	    $uploadpath = $mybb->settings['uploadspath'];
	}
	$aid=$db->escape_string($mybb->input['aid']);;
	$query=$db->query("SELECT * FROM ".TABLE_PREFIX."attachments WHERE aid='$aid'");
	$fetch=$db->fetch_array($query);
	unlink($uploadpath."/".$fetch['attachname']);
	unlink($uploadpath."/".$fetch['thumbnail']);
	$query=$db->query("DELETE FROM ".TABLE_PREFIX."attachments WHERE aid='$aid'");
	$info='success_'.$aid;
	echo $info;
	exit();
	}
	/******************************************************* Change Alias Name **********************/
	if($mybb->input['action'] == "set_Aliasname")
	{
	$aid=$db->escape_string($mybb->input['aid']);;
	$aliasname=$db->escape_string($mybb->input['aliasname']);
	$query=$db->query("SELECT * FROM ".TABLE_PREFIX."attachments WHERE aid='$aid'");
	if($db->num_rows($query)==0)
	{
	$info .='<div class="myproattach_error" onclick="this.style.display=\'none\';">'.$lang->myproattach_faild_aliasname.'</div>';
	echo $info;
	exit();
	}
	else
	{
	$fetch=$db->fetch_array($query);
	if($fetch['filename']=='')
	{
	$setalias=$db->query("UPDATE ".TABLE_PREFIX."attachments SET filename='$aliasname'  WHERE aid='$aid'");
	$info .='<div class="myproattach_success" onclick="this.style.display=\'none\';">'.$lang->myproattach_success_save.'</div>';
	echo $info;
	exit();
	}
	else
	{
	$setalias=$db->query("UPDATE ".TABLE_PREFIX."attachments SET filename='$aliasname'  WHERE aid='$aid'");
	$info .='<div class="myproattach_success" onclick="this.style.display=\'none\';">'.$lang->myproattach_success_update.'</div>';
	echo $info;
	exit();
	}
	
	}
	}
	/******************************************************* Change Alias Name **********************/

	
}
/******************************************* Upload Files ****************************************/

// This function runs when the plugin is deactivated.
function myproattach_deactivate()
{

global $mybb, $db;
rebuild_settings();
	require_once MYBB_ROOT.'inc/adminfunctions_templates.php';
    find_replace_templatesets("headerinclude",'#'.preg_quote('<script type="text/javascript" src="{$mybb->asset_url}/jscripts/jquery.form.js"></script>').'#', '',0);
    find_replace_templatesets("headerinclude",'#'.preg_quote('<script type="text/javascript" src="{$mybb->asset_url}/jscripts/myproattach.js"></script>').'#', '',0);
    find_replace_templatesets("headerinclude",'#'.preg_quote('<link rel="stylesheet" href="{$mybb->asset_url}/jscripts/myproattach.css" type="text/css" media="screen" />').'#', '',0);
	find_replace_templatesets("newthread",'#'.preg_quote('$prevattachbox').'#', '{$attachbox}');
	find_replace_templatesets("newthread",'#'.preg_quote('{$upload_panel}').'#', '',0);
	find_replace_templatesets("newreply",'#'.preg_quote('$prevattachbox').'#', '{$attachbox}');
	find_replace_templatesets("newreply",'#'.preg_quote('{$upload_panel}').'#', '',0);	
	find_replace_templatesets("editpost",'#'.preg_quote('$prevattachbox').'#', '{$attachbox}');
	find_replace_templatesets("editpost",'#'.preg_quote('{$upload_panel}').'#', '',0);
	find_replace_templatesets("headerinclude", '#'.preg_quote('<link rel="stylesheet" href="css/myproattach.css" type="text/css" media="screen" />').'#', '',0);



}




?>