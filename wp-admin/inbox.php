<?php
require_once('admin.php');

$title = __('Inbox');
$parent_file = 'inbox.php';

require_once('admin-header.php');

?>
<div class="wrap">
<form id="inbox-filter" action="" method="get">
<h2><?php _e('Inbox'); ?></h2>
<ul class="subsubsub">
<li><a href="#" class="current"><?php _e('Messages') ?></a></li> | <li><a href="#"><?php echo sprintf(__('Archived') . ' (%s)', '42'); ?></a></li>
</ul>
<div class="tablenav">
<div class="alignleft">
<select name="action">
<option value="" selected><?php _e('Actions'); ?></option>
<option value="archive"><?php _e('Archive'); ?></option>
</select>
<input type="submit" value="<?php _e('Apply'); ?>" name="doaction" class="button-secondary action" />
</div>
<br class="clear" />
</div>
<br class="clear" />
<table class="widefat">
	<thead>
	<tr>
	<th scope="col" class="check-column"><input type="checkbox"/></th>
	<th scope="col"><?php _e('Message'); ?></th>
	<th scope="col"><?php _e('Date'); ?></th>
	<th scope="col"><?php _e('From'); ?></th>
	</tr>
	</thead>
	<tbody>

<?php foreach ( wp_get_inbox_items() as $k => $item ) : ?>
	
	<tr id="message-<?php echo $k; ?>">
		<th scope="col" class="check-column"><input type="checkbox" name="messages[]" value="<?php echo $k; ?>" /></td>
		<td><?php
			echo $item->text;
			if ( strlen( $item->text ) > 180 )
				echo '<br/><a class="inbox-more" href="#">more...</a>';
		?></td>
		<td><a href="#link-to-comment"><abbr title="<?php echo "$item->date at $item->time"; ?>"><?php echo $item->date; ?></abbr></a></td>
		<td><?php
			echo $item->from;
			if ( 'comment' == 'type' )
				echo '<br/>on "<a href="#">Post</a>"';
		?></td>
	</tr>

<?php endforeach; ?>

</table>
</form>
<div class="tablenav"></div>
<br class="clear"/>
</div>
<?php include('admin-footer.php'); ?>