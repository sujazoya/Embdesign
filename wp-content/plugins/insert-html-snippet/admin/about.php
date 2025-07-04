<?php
if( !defined('ABSPATH') ){ exit();}
?>

<h1 style="visibility: visible;">Insert HTML Snippet (V <?php echo xyz_ihs_plugin_get_version(); ?>)</h1>

<div style="width: 99%">
<p style="text-align: justify">
Integrate HTML code seamlessly to your wordpress. This plugin lets you generate a shortcode corresponding
to any random HTML  code be is javascript, ad codes, vide embedding codes or any raw  HTML. The shortcodes
can be used in your pages, posts and widgets.  Insert HTML Snippet is developed and maintained by
<a target="_blank" href="http://xyzscripts.com">xyzscripts</a>.</p>


<p style="text-align: justify">
	If you would like to have more features , please try <a
		href="https://xyzscripts.com/wordpress-plugins/xyz-wp-insert-code-snippet/details"
		target="_blank">XYZ WP Insert Code Snippet</a> which is a premium version of this
	plugin. We have included a quick comparison of the free and premium
	plugins for your reference.
</p>
 </div>
 <table class="xyz-ihs-premium-comparison" cellspacing=0 style="width: 99%;">
	<tr style="background-color: #EDEDED">
		<td><h2>Feature group</h2></td>
		<td><h2>Feature</h2></td>
		<td><h2>Free</h2>
		</td>
		<td><h2>Premium</h2></td>
	</tr>
	<!-- Supported Media  -->
	<tr>
		<td rowspan="5"><h4>Shortcodes</h4></td>
		<td>Convert HTML snippets to shortcodes</td>
		<td><img src="<?php echo plugins_url('images/tick.png',XYZ_INSERT_HTML_PLUGIN_FILE);?>">
		</td>
		<td><img src="<?php echo plugins_url('images/tick.png',XYZ_INSERT_HTML_PLUGIN_FILE);?>">
		</td>
	</tr>

	<tr>
		<td>Convert Javascript codes to shortcodes</td>
		<td><img src="<?php echo plugins_url('images/tick.png',XYZ_INSERT_HTML_PLUGIN_FILE);?>">
		</td>
		<td><img src="<?php echo plugins_url('images/tick.png',XYZ_INSERT_HTML_PLUGIN_FILE);?>">
		</td>
	</tr>
	<tr>
		<td>Convert CSS codes to shortcodes</td>
		<td><img src="<?php echo plugins_url('images/tick.png',XYZ_INSERT_HTML_PLUGIN_FILE);?>">
		</td>
		<td><img src="<?php echo plugins_url('images/tick.png',XYZ_INSERT_HTML_PLUGIN_FILE);?>">
		</td>
	</tr>
	<tr>
		<td><b>Convert PHP snippets to shortcodes</b></td>
		<td><img src="<?php echo plugins_url('images/cross.png',XYZ_INSERT_HTML_PLUGIN_FILE);?>">
		</td>
		<td><img src="<?php echo plugins_url('images/tick.png',XYZ_INSERT_HTML_PLUGIN_FILE);?>">
		</td>
	</tr>
	<tr>
		<td><b>Passing custom parameters to shortcode</b></td>
		<td><img src="<?php echo plugins_url('images/cross.png',XYZ_INSERT_HTML_PLUGIN_FILE);?>">
		</td>
		<td><img src="<?php echo plugins_url('images/tick.png',XYZ_INSERT_HTML_PLUGIN_FILE);?>">
		</td>
	</tr>
<tr>
    <td rowspan="9"><h4>Snippet Placement Methods</h4></td>
    <td><b>Shortcode (Manual)</b></td>
    <td><img src="<?php echo plugins_url('images/tick.png',XYZ_INSERT_HTML_PLUGIN_FILE);?>"></td>
    <td><img src="<?php echo plugins_url('images/tick.png',XYZ_INSERT_HTML_PLUGIN_FILE);?>"></td>
</tr>
<tr>
    <td><b>Execute on Demand</b></td>
    <td><img src="<?php echo plugins_url('images/cross.png',XYZ_INSERT_HTML_PLUGIN_FILE);?>"></td>
    <td><img src="<?php echo plugins_url('images/tick.png',XYZ_INSERT_HTML_PLUGIN_FILE);?>"></td>
</tr>
<tr>
    <td><b>Automatic - Global:</b> (Site-wide PHP snippet)</td>
    <td><img src="<?php echo plugins_url('images/cross.png',XYZ_INSERT_HTML_PLUGIN_FILE);?>"></td>
    <td><img src="<?php echo plugins_url('images/tick.png',XYZ_INSERT_HTML_PLUGIN_FILE);?>"></td>
</tr>
<tr>
    <td><b>Automatic - Admin Panel (All Pages & Body):</b>  (Every Admin Page, Admin Body)</td>
    <td><img src="<?php echo plugins_url('images/cross.png',XYZ_INSERT_HTML_PLUGIN_FILE);?>"></td>
    <td><img src="<?php echo plugins_url('images/tick.png',XYZ_INSERT_HTML_PLUGIN_FILE);?>"></td>
</tr>
<tr>
    <td><b>Automatic - Admin Panel (Header & Footer):</b> (Admin Header, Admin Footer)</td>
    <td><img src="<?php echo plugins_url('images/tick.png',XYZ_INSERT_HTML_PLUGIN_FILE);?>"></td>
    <td><img src="<?php echo plugins_url('images/tick.png',XYZ_INSERT_HTML_PLUGIN_FILE);?>"></td>
</tr>
<tr>
    <td><b>Automatic - Front End (All Pages & Body):</b> (All Pages, Body of Front End)</td>
    <td><img src="<?php echo plugins_url('images/cross.png',XYZ_INSERT_HTML_PLUGIN_FILE);?>"></td>
    <td><img src="<?php echo plugins_url('images/tick.png',XYZ_INSERT_HTML_PLUGIN_FILE);?>"></td>
</tr>
<tr>
    <td><b>Automatic - Front End (Header & Footer):</b> (Front End Header, Front End Footer)</td>
    <td><img src="<?php echo plugins_url('images/tick.png',XYZ_INSERT_HTML_PLUGIN_FILE);?>"></td>
    <td><img src="<?php echo plugins_url('images/tick.png',XYZ_INSERT_HTML_PLUGIN_FILE);?>"></td>
</tr>
<tr>
    <td><b>Automatic - Specific Page:</b> (Before Content, After Content, Before Paragraph, After Paragraph)</td>
    <td><img src="<?php echo plugins_url('images/cross.png',XYZ_INSERT_HTML_PLUGIN_FILE);?>"></td>
    <td><img src="<?php echo plugins_url('images/tick.png',XYZ_INSERT_HTML_PLUGIN_FILE);?>"></td>
</tr>
<tr>
    <td><b>Automatic - Archive Pages: </b> (Insert Before Excerpt, Insert After Excerpt, Between Posts, Before Post, After Post)</td>
    <td><img src="<?php echo plugins_url('images/cross.png',XYZ_INSERT_HTML_PLUGIN_FILE);?>"></td>
    <td><img src="<?php echo plugins_url('images/tick.png',XYZ_INSERT_HTML_PLUGIN_FILE);?>"></td>
</tr>
		<!-- Posting Options  -->
	<tr>
		<td rowspan="3"><h4>Third party code</h4></td>
		<td>Insert adsense or any adcode</td>
		<td><img src="<?php echo plugins_url('images/tick.png',XYZ_INSERT_HTML_PLUGIN_FILE);?>">
		</td>
		<td><img src="<?php echo plugins_url('images/tick.png',XYZ_INSERT_HTML_PLUGIN_FILE);?>">
		</td>
	</tr>

	<tr>
		<td>Insert addthis or any social bookmarking code</td>
		<td><img src="<?php echo plugins_url('images/tick.png',XYZ_INSERT_HTML_PLUGIN_FILE);?>">
		</td>
		<td><img src="<?php echo plugins_url('images/tick.png',XYZ_INSERT_HTML_PLUGIN_FILE);?>">
		</td>
	</tr>

	<tr>
		<td>Insert flash, videos etc. to your posts,pages and widgets</td>
		<td><img src="<?php echo plugins_url('images/tick.png',XYZ_INSERT_HTML_PLUGIN_FILE);?>">
		</td>
		<td><img src="<?php echo plugins_url('images/tick.png',XYZ_INSERT_HTML_PLUGIN_FILE);?>">
		</td>
	</tr>

	<!-- Snippet Management  -->

	<tr>
	<td rowspan="4"><h4>Snippet Management</h4></td>
		<td><b>Syntax highlighter for snippet creation</b></td>
		<td><img src="<?php echo plugins_url('images/cross.png',XYZ_INSERT_HTML_PLUGIN_FILE);?>">
		</td>
		<td><img src="<?php echo plugins_url('images/tick.png',XYZ_INSERT_HTML_PLUGIN_FILE);?>">
		</td>
	</tr>


	<tr>
		<td><b>Option to duplicate snippet</b></td>
		<td><img src="<?php echo plugins_url('images/cross.png',XYZ_INSERT_HTML_PLUGIN_FILE);?>">
		</td>
		<td><img src="<?php echo plugins_url('images/tick.png',XYZ_INSERT_HTML_PLUGIN_FILE);?>">
		</td>
	</tr>

	<tr>
		<td><b>Export snippets</b></td>
		<td><img src="<?php echo plugins_url('images/cross.png',XYZ_INSERT_HTML_PLUGIN_FILE);?>">
		</td>
		<td><img src="<?php echo plugins_url('images/tick.png',XYZ_INSERT_HTML_PLUGIN_FILE);?>">
		</td>
	</tr>
	<tr>
		<td><b>Import snippets</b></td>
		<td><img src="<?php echo plugins_url('images/cross.png',XYZ_INSERT_HTML_PLUGIN_FILE);?>">
		</td>
		<td><img src="<?php echo plugins_url('images/tick.png',XYZ_INSERT_HTML_PLUGIN_FILE);?>">
		</td>
	</tr>

     <!-- Privilege Management  -->
	<tr>
	<td rowspan="3"><h4>Privilege Management</h4></td>
		<td><b>Role/User based Snippet Management Privilege</b></td>
		<td><img src="<?php echo plugins_url('images/cross.png',XYZ_INSERT_HTML_PLUGIN_FILE);?>">
		</td>
		<td><img src="<?php echo plugins_url('images/tick.png',XYZ_INSERT_HTML_PLUGIN_FILE);?>">
		</td>
	</tr>

	<tr>
		<td><b>Role/User based Snippet Usage Privilege</b></td>
		<td><img src="<?php echo plugins_url('images/cross.png',XYZ_INSERT_HTML_PLUGIN_FILE);?>">
		</td>
		<td><img src="<?php echo plugins_url('images/tick.png',XYZ_INSERT_HTML_PLUGIN_FILE);?>">
		</td>
	</tr>
	<tr>
		<td><b>Master Password for super admin</b></td>
		<td><img src="<?php echo plugins_url('images/cross.png',XYZ_INSERT_HTML_PLUGIN_FILE);?>">
		</td>
		<td><img src="<?php echo plugins_url('images/tick.png',XYZ_INSERT_HTML_PLUGIN_FILE);?>">
		</td>
	</tr>
	<!-- Snippet Targeting  -->
	<tr>
	<td rowspan="5"><h4>Snippet Targeting</h4></td>
		<td><b>Geographic Targeting</b></td>
		<td><img src="<?php echo plugins_url('images/cross.png',XYZ_INSERT_HTML_PLUGIN_FILE);?>">
		</td>
		<td><img src="<?php echo plugins_url('images/tick.png',XYZ_INSERT_HTML_PLUGIN_FILE);?>">
		</td>
	</tr>
 	<tr>
		<td><b>Device Targeting</b></td>
		<td><img src="<?php echo plugins_url('images/cross.png',XYZ_INSERT_HTML_PLUGIN_FILE);?>">
		</td>
		<td><img src="<?php echo plugins_url('images/tick.png',XYZ_INSERT_HTML_PLUGIN_FILE);?>">
		</td>
	</tr>
	<tr>
		<td><b>User Targeting</b></td>
		<td><img src="<?php echo plugins_url('images/cross.png',XYZ_INSERT_HTML_PLUGIN_FILE);?>">
		</td>
		<td><img src="<?php echo plugins_url('images/tick.png',XYZ_INSERT_HTML_PLUGIN_FILE);?>">
		</td>
	</tr>
	<tr>
		<td><b>Role Targeting</b></td>
		<td><img src="<?php echo plugins_url('images/cross.png',XYZ_INSERT_HTML_PLUGIN_FILE);?>">
		</td>
		<td><img src="<?php echo plugins_url('images/tick.png',XYZ_INSERT_HTML_PLUGIN_FILE);?>">
		</td>
	</tr>
	<tr>
		<td><b>Date & Time Targeting</b></td>
		<td><img src="<?php echo plugins_url('images/cross.png',XYZ_INSERT_HTML_PLUGIN_FILE);?>">
		</td>
		<td><img src="<?php echo plugins_url('images/tick.png',XYZ_INSERT_HTML_PLUGIN_FILE);?>">
		</td>
	</tr>

	<!-- Integration  -->

	<tr>
	<td rowspan="3"><h4>Integration</h4></td>
		<td>Integrate to posts/pages</td>
		<td><img src="<?php echo plugins_url('images/tick.png',XYZ_INSERT_HTML_PLUGIN_FILE);?>">
		</td>
		<td><img src="<?php echo plugins_url('images/tick.png',XYZ_INSERT_HTML_PLUGIN_FILE);?>">
		</td>
	</tr>

	<tr>
		<td>Support for shortcodes in widgets</td>
		<td><img src="<?php echo plugins_url('images/tick.png',XYZ_INSERT_HTML_PLUGIN_FILE);?>">
		</td>
		<td><img src="<?php echo plugins_url('images/tick.png',XYZ_INSERT_HTML_PLUGIN_FILE);?>">
		</td>
	</tr>

	<tr>
		<td>Dropdown menu in TinyMCE editor to pick snippets easily</td>
		<td><img src="<?php echo plugins_url('images/tick.png',XYZ_INSERT_HTML_PLUGIN_FILE);?>">
		</td>
		<td><img src="<?php echo plugins_url('images/tick.png',XYZ_INSERT_HTML_PLUGIN_FILE);?>">
		</td>
	</tr>
	<tr>
		<td rowspan="2"><h4>Other</h4></td>
		<td>Price</td>
		<td>FREE</td>
		<td>Starts from 19 USD</td>
	</tr>
	<tr>
		<td>Purchase</td>
		<td></td>
		<td style="padding: 2px" ><a target="_blank"href="https://xyzscripts.com/members/product/purchase/XYZWPICSPRE"  class="xyz_ihs_buy_button">Buy Now</a>
		</td>
	</tr>

</table>
<br/>
<div style="clear: both;"></div>
<?php
?>
