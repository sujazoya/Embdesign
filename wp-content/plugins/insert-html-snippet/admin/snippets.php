<?php
if ( ! defined( 'ABSPATH' ) )
    exit;
    
    global $wpdb;
    $_GET = stripslashes_deep($_GET);
    $xyz_ihs_message = $search_name_db=$search_name='';
   
    if(isset($_GET['xyz_ihs_msg'])){
        $xyz_ihs_message = intval($_GET['xyz_ihs_msg']);
    }
    if($_POST)
     {
         if(isset($_POST['search']))
         {
            if(!isset($_REQUEST['_wpnonce'])||!wp_verify_nonce($_REQUEST['_wpnonce'],'snipp-manage_') ){
                wp_nonce_ays( 'snipp-manage_' );
                exit;
            }
         }
        //  if(isset($_POST['textFieldButton2']))
        //  {
        //      if(!isset($_REQUEST['_wpnonce'])||!wp_verify_nonce($_REQUEST['_wpnonce'],'bulk_actions_ihs') ){
        //          wp_nonce_ays( 'bulk_actions_ihs' );
        //          exit;
        //      }
        //  }
        if (isset($_POST['apply_ihs_bulk_actions'])){
            if (isset($_POST['ihs_bulk_actions_snippet'])){
	    if(!isset($_REQUEST['_wpnonce'])||!wp_verify_nonce($_REQUEST['_wpnonce'],'bulk_actions_ihs') )
            {
                 wp_nonce_ays( 'bulk_actions_ihs' );
                 exit;
             }
                $ihs_bulk_actions_snippet=$_POST['ihs_bulk_actions_snippet'];
                if (isset($_POST['xyz_ihs_snippet_ids']))
                    $xyz_ihs_snippet_ids = $_POST['xyz_ihs_snippet_ids'];
                    $xyz_ihs_pageno = isset( $_GET['pagenum'] ) ? absint( $_GET['pagenum'] ) : 1;
                    
                    if (empty($xyz_ihs_snippet_ids))
                    {
                        header("Location:".admin_url('admin.php?page=insert-html-snippet-manage&xyz_ihs_msg=8&pagenum='.$xyz_ihs_pageno));
                        exit();
                    }
                    if ($ihs_bulk_actions_snippet==2)//bulk-delete
                    {
                        foreach ($xyz_ihs_snippet_ids as $snippet_id)
                        {
                            $wpdb->query($wpdb->prepare( 'DELETE FROM  '.$wpdb->prefix.'xyz_ihs_short_code  WHERE id=%d',$snippet_id)) ;
                        }
                        header("Location:".admin_url('admin.php?page=insert-html-snippet-manage&xyz_ihs_msg=3&pagenum='.$xyz_ihs_pageno));
                        exit();
                    }
                    elseif ($ihs_bulk_actions_snippet==0)//bulk-Deactivate
                    {
                        foreach ($xyz_ihs_snippet_ids as $xyz_ihs_snippetId)
                            $wpdb->update($wpdb->prefix.'xyz_ihs_short_code', array('status'=>2), array('id'=>$xyz_ihs_snippetId));
                            header("Location:".admin_url('admin.php?page=insert-html-snippet-manage&xyz_ihs_msg=4&pagenum='.$xyz_ihs_pageno));
                            exit();
                    }
                    elseif ($ihs_bulk_actions_snippet==1)//bulk-activate
                    {
                        foreach ($xyz_ihs_snippet_ids as $xyz_ihs_snippetId)
                            $wpdb->update($wpdb->prefix.'xyz_ihs_short_code', array('status'=>1), array('id'=>$xyz_ihs_snippetId));
                            header("Location:".admin_url('admin.php?page=insert-html-snippet-manage&xyz_ihs_msg=4&pagenum='.$xyz_ihs_pageno));
                            exit();
                    }
                    elseif ($ihs_bulk_actions_snippet==-1)//no action selected
                    {
                        header("Location:".admin_url('admin.php?page=insert-html-snippet-manage&xyz_ihs_msg=7&pagenum='.$xyz_ihs_pageno));
                        exit();
                    }
            }
            
        }

        
    }
    if($xyz_ihs_message == 1){
        ?>
<div class="xyz_ihs_system_notice_area_style1" id="xyz_ihs_system_notice_area">
<span id="system_notice_area_common_msg">
HTML Snippet successfully added.&nbsp;&nbsp;&nbsp;
</span>
<span
id="xyz_ihs_system_notice_area_dismiss">Dismiss</span>
</div>
<?php

}
if($xyz_ihs_message == 2){

	?>
<div class="xyz_ihs_system_notice_area_style0" id="xyz_ihs_system_notice_area">
<span id="system_notice_area_common_msg">HTML Snippet not found.&nbsp;&nbsp;&nbsp;
</span>
<span
id="xyz_ihs_system_notice_area_dismiss">Dismiss</span>
</div>
<?php

}
if($xyz_ihs_message == 3){

	?>
<div class="xyz_ihs_system_notice_area_style1" id="xyz_ihs_system_notice_area">
<span id="system_notice_area_common_msg">
HTML Snippet successfully deleted.&nbsp;&nbsp;&nbsp;
</span>
<span
id="xyz_ihs_system_notice_area_dismiss">Dismiss</span>
</div>
<?php

}
if($xyz_ihs_message == 4){

	?>
<div class="xyz_ihs_system_notice_area_style1" id="xyz_ihs_system_notice_area">
<span id="system_notice_area_common_msg">
HTML Snippet status successfully changed.&nbsp;&nbsp;&nbsp;
</span>
<span
id="xyz_ihs_system_notice_area_dismiss">Dismiss</span>
</div>
<?php

}
if($xyz_ihs_message == 5){

	?>
<div class="xyz_ihs_system_notice_area_style1" id="xyz_ihs_system_notice_area">
<span id="system_notice_area_common_msg">
HTML Snippet successfully updated.&nbsp;&nbsp;&nbsp;
</span>
<span
id="xyz_ihs_system_notice_area_dismiss">Dismiss</span>
</div>
<?php
}
if($xyz_ihs_message == 7)
{
?>
 <div class="xyz_ihs_system_notice_area_style1" id="xyz_ihs_system_notice_area">
			<span id="system_notice_area_common_msg">
Please select an action to apply.&nbsp;&nbsp;&nbsp;
</span>
		<span id="xyz_ihs_system_notice_area_dismiss">Dismiss</span>
 </div>
<?php 
}
if($xyz_ihs_message == 8)
{
	?>
	<div class="xyz_ihs_system_notice_area_style1" id="xyz_ihs_system_notice_area">
<span id="system_notice_area_common_msg">		
Please select at least one snippet to perform this action.&nbsp;&nbsp;&nbsp;
</span		
<span id="xyz_ihs_system_notice_area_dismiss">Dismiss</span>
	</div>
<?php
}
?>
<div >


	<form method="post">
		<?php wp_nonce_field( 'bulk_actions_ihs');?>
 		<fieldset
			style="width: 99%; border: 1px solid #F7F7F7; padding: 10px 0px;">
			<legend><h3>HTML Snippets</h3></legend>
			<?php 
			global $wpdb;
			$pagenum = isset( $_GET['pagenum'] ) ? absint( $_GET['pagenum'] ) : 1;
			$limit = get_option('xyz_ihs_limit');			
			$offset = ( $pagenum - 1 ) * $limit;
			$field=get_option('xyz_ihs_sort_field_name');
			$order=get_option('xyz_ihs_sort_order');
			if(isset($_POST['snippet_name']))
			{
			$search_name=sanitize_text_field($_POST['snippet_name']);
			$search_name_db=esc_sql($search_name);
    		        }

	               if(isset($_POST['insertionMethod']))
			{
				$insertionMethod =intval($_POST["insertionMethod"]); 
			}
			else
			{
				$insertionMethod =0;
			}
			$strInsertionMethod='';
			if (intval($insertionMethod)>0)
			{
			$strInsertionMethod=" AND insertionMethod=$insertionMethod";
			}
		

			$entries = $wpdb->get_results( "SELECT * FROM ".$wpdb->prefix."xyz_ihs_short_code  	WHERE title like '%".$search_name_db."%'".$strInsertionMethod." ORDER BY  $field $order LIMIT $offset,$limit" );

			?>
			
			

			<input  id="xyz_ihs_submit"	style="cursor: pointer; margin-bottom:10px; margin-left:8px;" type="button"	name="textFieldButton2" value="Add New HTML Snippet" onClick='document.location.href="<?php echo admin_url('admin.php?page=insert-html-snippet-manage&action=snippet-add');?>"'>
			<br>
			
						
			
									
						
<span style="padding-left: 6px;color:#21759B;">With Selected : </span>
 <select name="ihs_bulk_actions_snippet" id="ihs_bulk_actions_snippet" style="width:130px;height:29px;">
	<option value="-1">Bulk Actions</option>
	<option value="0">Deactivate</option>
	<option value="1">Activate</option>
	<option value="2">Delete</option>
</select>
<input type="submit" title="Apply" name="apply_ihs_bulk_actions" value="Apply" style="color:#21759B;cursor:pointer;padding: 5px;background:linear-gradient(to top, #ECECEC, #F9F9F9) repeat scroll 0 0 #F1F1F1;border: 2px solid #DFDFDF;">
		
</form>		
<form name="manage_snippets" action="" method="post">
							 <?php wp_nonce_field('snipp-manage_');?>
							<div class="xyz_ihs_search_div"  style="float:right;margin:5px;">
				            	<table class="xyz_ihs_search_div_table" style="width:100%;">
				                	<tr>
				            
				                  	<div><span>Snippet Placement</span>&nbsp;</div>
   <div>
   <select name="insertionMethod" id="insertionMethod" >
  <option value="0" <?php if($insertionMethod==0) { echo "selected"; } ?>>All</option>
	  <option value="1" <?php if($insertionMethod==1) { echo "selected"; } ?>>Automatic</option>
	<option value="2" <?php if($insertionMethod==2) { echo "selected"; } ?>>Short Code</option>
	
		</select>
	</div>
				                  		 	
		<div> <input type="text" name="snippet_name" value= "<?php if(isset($search_name)){echo esc_attr($search_name);}?>"  placeholder="Search" ></div>
	<div><input style="padding:5px;margin-left: 5px;margin-right: 5px;" type="submit" name="search" value="Go" /></div>
				                 	
				              		</tr>
				           		</table>
	          				</div>	
</form>		
		
		
			<table class="widefat" style="width: 99%; margin: 0 auto; border-bottom:none;">
				<thead>
					<tr>
					<th scope="col" width="3%"><input type="checkbox" id="chkAllSnippets" /></th>
						<th scope="col" >Tracking Name</th>
			<th scope="col">Snippet Placement 

</th>
						<th scope="col" >Status</th>
						<th scope="col" colspan="3" style="text-align: center;">Action</th>
					</tr>
				</thead>
				<tbody>
					<?php 
					
					if(!empty($entries))//if( count($entries)>0 )
        				  {
						$count=1;
						$class = '';
						foreach( $entries as $entry ) {
							$class = ( $count % 2 == 0 ) ? ' class="alternate"' : '';
							$snippetId=intval($entry->id);
							?>
					<tr <?php echo $class; ?>>
					<td style="vertical-align: middle !important;padding-left: 18px;">
					<input type="checkbox" class="chk" value="<?php echo $snippetId; ?>" name="xyz_ihs_snippet_ids[]" id="xyz_ihs_snippet_ids" />
					</td>
						<td id="xyz_ihs_vAlign"><?php 
						echo esc_html($entry->title);
						?></td>
<td id="xyz_ihs_vAlign">
    <?php 
    if ($entry->status == 2) {
        echo 'NA';
    } else { 
        echo ($entry->insertionMethod == 1) ? 
            'Automatic' : 
            (($entry->insertionMethod == 2) ? 
                '<span onclick="xyz_ihs_copy_shortcode(' . $entry->id . ')" class="xyz_ihs_copy_shortcode" id="xyz_ihs_shortcode_' . $entry->id . '">[xyz-ihs snippet="' . esc_html($entry->title) . '"]</span>' .
                '<span onclick="xyz_ihs_copy_shortcode(' . $entry->id . ')"><img class="xyz_ihs_img xyz_ihs_img_table" title="Click to copy" src="' . plugins_url('insert-html-snippet/images/copy-document.png') . '"></span>' 
            : 
            '');
    }
    ?>
</td>
						<td id="xyz_ihs_vAlign">
							<?php 
								if($entry->status == 2){
									echo "Inactive";	
								}elseif ($entry->status == 1){
								echo "Active";	
								}
							
							?>
						</td>
						<?php 
								if($entry->status == 2){
								$stat1 = admin_url('admin.php?page=insert-html-snippet-manage&action=snippet-status&snippetId='.$snippetId.'&status=1&pageno='.$pagenum);
						?>
						<td style="text-align: center;"><a
							href='<?php echo wp_nonce_url($stat1,'ihs-stat_'.$snippetId); ?>'><img
								id="xyz_ihs_img" title="Activate"
								src="<?php echo plugins_url('images/activate.png', XYZ_INSERT_HTML_PLUGIN_FILE )?>">
						</a>
						</td>
							<?php 
								}elseif ($entry->status == 1){
									$stat2 = admin_url('admin.php?page=insert-html-snippet-manage&action=snippet-status&snippetId='.$snippetId.'&status=2&pageno='.$pagenum);
								?>
						<td style="text-align: center;"><a
							href='<?php echo wp_nonce_url($stat2,'ihs-stat_'.$snippetId); ?>'><img
								id="xyz_ihs_img" title="Deactivate"
								src="<?php echo plugins_url('images/pause.png',XYZ_INSERT_HTML_PLUGIN_FILE)?>">
						</a>
						</td>		
								<?php 	
								}
								
							?>
						
						<td style="text-align: center;"><a
							href='<?php echo admin_url('admin.php?page=insert-html-snippet-manage&action=snippet-edit&snippetId='.$snippetId.'&pageno='.$pagenum); ?>'><img
								id="xyz_ihs_img" title="Edit Snippet"
								src="<?php echo plugins_url('images/edit.png',XYZ_INSERT_HTML_PLUGIN_FILE)?>">
						</a>
						</td>

						<?php $delurl = admin_url('admin.php?page=insert-html-snippet-manage&action=snippet-delete&snippetId='.$snippetId.'&pageno='.$pagenum);?>
						<td style="text-align: center;" ><a
							href='<?php echo wp_nonce_url($delurl,'ihs-del_'.$snippetId); ?>'
							onclick="javascript: return confirm('Please click \'OK\' to confirm ');"><img
								id="xyz_ihs_img" title="Delete Snippet"
								src="<?php echo plugins_url('images/delete.png',XYZ_INSERT_HTML_PLUGIN_FILE)?>">
						</a></td>
					</tr>
					<?php
					$count++;
						}
					} else { ?>
					<tr>
						<td colspan="6" >HTML Snippets not found</td>
					</tr>
					<?php } ?>
				</tbody>
			</table>
			
			<input  id="xyz_ihs_submit"	style="cursor: pointer; margin-top:10px;margin-left:8px;" type="button"	name="textFieldButton2" value="Add New HTML Snippet" onClick='document.location.href="<?php echo admin_url('admin.php?page=insert-html-snippet-manage&action=snippet-add');?>"'>
			
			<?php
			$total = $wpdb->get_var( "SELECT COUNT(`id`) FROM ".$wpdb->prefix."xyz_ihs_short_code" );
			$num_of_pages = ceil( $total / $limit );

			$page_links = paginate_links( array(
					'base' => add_query_arg( 'pagenum','%#%'),
				    'format' => '',
				    'prev_text' =>  '&laquo;',
				    'next_text' =>  '&raquo;',
				    'total' => $num_of_pages,
				    'current' => $pagenum
			) );

			if ( $page_links ) {
				echo '<div class="tablenav" style="width:99%"><div class="tablenav-pages" style="margin: 1em 0">' . $page_links . '</div></div>';
			}

			?>
		</fieldset>
	
</div>
<script type="text/javascript">
jQuery(document).ready(function(){
	jQuery("#chkAllSnippets").click(function(){
		jQuery(".chk").prop("checked",jQuery("#chkAllSnippets").prop("checked"));
    }); 
});
const xyz_ihs_copy_shortcode = (id) => {

    var span = document.getElementById("xyz_ihs_shortcode_" + id);
    var tempTextarea = document.createElement("textarea");
    tempTextarea.value = span.textContent;
    document.body.appendChild(tempTextarea);
    tempTextarea.select();
    tempTextarea.setSelectionRange(0, 99999); // For mobile devices
    document.execCommand("copy");
    document.body.removeChild(tempTextarea);


  (typeof xyz_ihs_notice === 'function')? xyz_ihs_notice('Short code copied successfully',1):null;

};


const xyz_ihs_notice = (msg = '', flag = 0) => {


const noticeElement = jQuery('#xyz_ihs_system_notice_area');
if (noticeElement.length > 0) 
{

  jQuery('#system_notice_area_common_msg').text(msg);
  if (flag === 0) {
  if(noticeElement.hasClass('xyz_ihs_system_notice_area_style1'))
  noticeElement.removeClass('xyz_ihs_system_notice_area_style1')
  if(! noticeElement.hasClass('xyz_ihs_system_notice_area_style0'))
  noticeElement.addClass('xyz_ihs_system_notice_area_style0');

  } else {
  if(noticeElement.hasClass('xyz_ihs_system_notice_area_style0'))
  noticeElement.removeClass('xyz_ihs_system_notice_area_style0')
  if(! noticeElement.hasClass('xyz_ihs_system_notice_area_style1'))
  noticeElement.addClass('xyz_ihs_system_notice_area_style1');

  }
  noticeElement.animate({
    opacity: 'show',
    height: 'show'
  }, 500);

}
else{



  let noticeElementString = 
  `<div class="system_notice_area_style${flag}" id="xyz_ihs_system_notice_area">
    <span id="system_notice_area_common_msg">${msg}.&nbsp;&nbsp;&nbsp;</span>
    <span id="xyz_ihs_system_notice_area_dismiss">Dismiss</span>
  </div>`;

  let noticeElement = jQuery(noticeElementString);
  jQuery('body').append(noticeElement);
  noticeElement.animate({
    opacity: 'show',
    height: 'show'
  }, 500); 





}

};


</script>
