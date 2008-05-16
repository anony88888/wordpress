<?php
require_once('admin.php');

if ( ! current_user_can('publish_posts') )
 	wp_die( __( 'Cheatin&#8217; uh?' ));

function press_it() {
	$quick['post_status'] = 'publish';
	$quick['post_category'] = $_REQUEST['post_category'];
	$quick['tags_input'] = $_REQUEST['tags_input'];
	$quick['post_title'] = $_REQUEST['post_title'];

	$content = '';
	switch ( $_REQUEST['post_type'] ) {
		case 'regular':
			$content = $_REQUEST['content'];
			if ($_REQUEST['content2'])
				$content .= '<p>' . $_REQUEST['content2']; 
			break;

		case 'quote':
			$content = '<blockquote>' . $_REQUEST['content'];
			if ($_REQUEST['content2']) {
				$content .= '</blockquote>';
				$content = $content . '<p>' . $_REQUEST['content2']; 
			}
			break;

		case 'photo':
			if ($_REQUEST['photo_link'])
				$content = '<a href="' . $_REQUEST['photo_link'] . '" target="_new">';

			$content .= '<img src="' . $_REQUEST['photo_src'] . '" style="float:left;padding:5px;">';

			if ($_REQUEST['photo_link'])
				$content .= '</a>';

			if ($_REQUEST['content'])
				$content = $content . '<br clear="all">' . $_REQUEST['content']; 

			break;
		case "video":
			list($garbage,$video_id) = split("v=", $_REQUEST['content']);
			$content = '<object width="425" height="350"><param name="movie" value="http://www.youtube.com/v/' . $video_id . '"></param><param name="wmode" value="transparent"></param><embed src="http://www.youtube.com/v/' . $video_id . '" type="application/x-shockwave-flash" wmode="transparent" width="425" height="350"></embed></object>';
			if ($_REQUEST['content2'])
				$content .= '</br><p>' . $_REQUEST['content2'] . '</p>';
			break;				
	}

	$quick['post_content'] = $content;

	$post_ID = wp_insert_post($quick, true);

	if ( is_wp_error($post_ID) )
		wp_die($wp_error);

	return $post_ID;
}

function tag_div() {
?>
	<h2><?php _e('Tags') ?></h2>
	<div id="tagsdiv">
		<div class="inside">
			<p id="jaxtag"><label class="hidden" for="newtag"><?php _e('Tags'); ?></label><input type="text" name="tags_input" class="tags-input" id="tags-input" size="40" tabindex="3" value="<?php echo get_tags_to_edit( $post->ID ); ?>" /></p>
<div id="tagchecklist"></div>
		</div>
	</div>
<?php
}

function category_div() {
?>
				<div id="categories">
					<h2><?php _e('Categories') ?></h2>
					<div id="categories-all">
						<ul id="categorychecklist" class="list:category categorychecklist form-no-clear">
						<?php wp_category_checklist() ?>
						</ul>
					</div>
				</div>
<?php
}

function get_images_from_uri($uri) {
	$content = wp_remote_fopen($uri);
	if ( false === $content )
		return '';

	$pattern = '/<img[^>]+src=[\'"]([^\'" >]+?)[\'" >]/is';
	preg_match_all($pattern, $content, $matches);
	if ( empty($matches[1]) )
		return '';

	/*
	$from_host = parse_url($uri);
	$from_host = $from_host['host'];
	$from_host = explode('.', $from_host);
	$count = count($from_host);
	$from_host = $from_host[$count - 2] . '.' . $from_host[$count - 1];
	*/

	$sources = array();
	foreach ($matches[1] as $src) {
		if ( false !== strpos($src, '&') )
			continue;

		/*
		$img_host = parse_url($src);
		$img_host = $img_host['host'];
		if ( false === strpos($img_host, $from_host) )
			continue;
		*/

		$sources[] = $src;
	}
	return "'" . implode("','", $sources) . "'";
}

function press_this_js_init() {
    if ( user_can_richedit() ) {
    	$language = ( '' == get_locale() ) ? 'en' : strtolower( substr(get_locale(), 0, 2) );
    ?>
			tinyMCE.init({
				mode: "textareas",
				editor_selector: "mceEditor",
				language : "<?php echo $language; ?>",
				width: "100%",
				theme : "advanced",
				theme_advanced_buttons1 : "bold,italic,underline,blockquote,separator,strikethrough,bullist,numlist,undo,redo,link,unlink",
				theme_advanced_buttons2 : "",
				theme_advanced_buttons3 : "",
				theme_advanced_toolbar_location : "top",
				theme_advanced_toolbar_align : "left",
				theme_advanced_statusbar_location : "bottom",
				theme_advanced_resizing : true,
				theme_advanced_resize_horizontal : false,
				skin : "wp_theme",
				dialog_type : "modal",
				relative_urls : false,
				remove_script_host : false,
				convert_urls : false,
				apply_source_formatting : false,
				remove_linebreaks : true,
				accessibility_focus : false,
				tab_focus : ":next",
				plugins : "safari,inlinepopups"
			});
    <?php } ?>
    	jQuery('#tags-input').hide();
		tag_update_quickclicks();
		// add the quickadd form
		jQuery('#jaxtag').prepend('<span id="ajaxtag"><input type="text" name="newtag" id="newtag" class="form-input-tip" size="16" autocomplete="off" value="'+postL10n.addTag+'" /><input type="button" class="button" id="tagadd" value="' + postL10n.add + '" tabindex="3" /><input type="hidden"/><input type="hidden"/><span class="howto">'+postL10n.separate+'</span></span>');
		jQuery('#tagadd').click( tag_flush_to_text );
		jQuery('#newtag').focus(function() {
			if ( this.value == postL10n.addTag )
				jQuery(this).val( '' ).removeClass( 'form-input-tip' );
		});
		jQuery('#newtag').blur(function() {
			if ( this.value == '' )
				jQuery(this).val( postL10n.addTag ).addClass( 'form-input-tip' );
		});

		// auto-save tags on post save/publish
		jQuery('#publish').click( tag_save_on_publish );
		jQuery('#save-post').click( tag_save_on_publish );
<?php
}

// Clean up the data being passed in
$title = wp_specialchars(stripslashes($_GET['t']));
$selection = wp_specialchars(stripslashes($_GET['s']));
$url = clean_url($_GET['u']);

if ( empty($_GET['tab']) ) {
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" <?php do_action('admin_xml_ns'); ?> <?php language_attributes(); ?>>
<head>
	<meta http-equiv="Content-Type" content="<?php bloginfo('html_type'); ?>; charset=<?php echo get_option('blog_charset'); ?>" />
	<title><?php _e('Quick Post') ?></title>
	
	<script type="text/javascript" src="../wp-includes/js/tinymce/tiny_mce.js"></script>
		
<?php
	wp_enqueue_script('jquery-ui-tabs');
	wp_enqueue_script('thickbox');
	wp_enqueue_script('post');
	do_action('admin_print_scripts'); do_action('admin_head');
	wp_admin_css('css/press-this');
	wp_admin_css( 'css/colors' );
?>
	<script type="text/javascript">
    <?php if ( user_can_richedit() ) { 
		$language = ( '' == get_locale() ) ? 'en' : strtolower( substr(get_locale(), 0, 2) );
		// Add TinyMCE languages
		@include_once( dirname(__FILE__).'/../wp-includes/js/tinymce/langs/wp-langs.php' );
		if ( isset($strings) ) echo $strings;
	?>

			(function() {
				var base = tinymce.baseURL, sl = tinymce.ScriptLoader, ln = "<?php echo $language; ?>";

				sl.markDone(base + '/langs/' + ln + '.js');
				sl.markDone(base + '/themes/advanced/langs/' + ln + '.js');
				sl.markDone(base + '/themes/advanced/langs/' + ln + '_dlg.js');
			})();
			
			tinyMCE.init({
				mode: "textareas",
				editor_selector: "mceEditor",
				language : "<?php echo $language; ?>",
				width: "100%",
				theme : "advanced",
				theme_advanced_buttons1 : "bold,italic,underline,blockquote,separator,strikethrough,bullist,numlist,undo,redo,link,unlink",
				theme_advanced_buttons2 : "",
				theme_advanced_buttons3 : "",
				theme_advanced_toolbar_location : "top",
				theme_advanced_toolbar_align : "left",
				theme_advanced_statusbar_location : "bottom",
				theme_advanced_resizing : true,
				theme_advanced_resize_horizontal : false,
				skin : "wp_theme",
				dialog_type : "modal",
				relative_urls : false,
				remove_script_host : false,
				convert_urls : false,
				apply_source_formatting : false,
				remove_linebreaks : true,
				accessibility_focus : false,
				tab_focus : ":next",
				plugins : "safari,inlinepopups"
			});
    <?php } ?>

	jQuery(document).ready(function() {
    <?php if ( preg_match("/youtube\.com\/watch/i", $_GET['u']) ) { ?>
		jQuery('#container > ul').tabs({ selected: 3 });
	<?php } elseif ( preg_match("/flickr\.com/i", $_GET['u']) ) { ?>
		jQuery('#container > ul').tabs({ selected: 1 });
	<?php } else { ?>
		jQuery('#container > ul').tabs();
	<?php } ?>
	});

	</script>
</head>
<body>

<?php
	if ( 'post' == $_REQUEST['action'] ) {
		check_admin_referer('press-this');
		$post_ID = press_it();
?>
		<script>if(confirm("<?php _e('Your post is saved. Do you want to view the post?') ?>")) {window.opener.location.replace("<?php echo get_permalink($post_ID);?>");}window.close();</script>
		</body></html>
<?php
		die;
	}
?>
	<div id="container">
		<ul>
			<li><a href="<?php echo clean_url(add_query_arg('tab', 'text', stripslashes($_SERVER['REQUEST_URI']))) ?>"><span><?php _e('Text/Link') ?></span></a></li>
		 	<li><a href="<?php echo clean_url(add_query_arg('tab', 'photo', stripslashes($_SERVER['REQUEST_URI']))) ?>"><span><?php _e('Photo') ?></span></a></li>
			<li><a href="<?php echo clean_url(add_query_arg('tab', 'quote', stripslashes($_SERVER['REQUEST_URI']))) ?>"><span><?php _e('Quote') ?></span></a></li>
			<li><a href="<?php echo clean_url(add_query_arg('tab', 'video', stripslashes($_SERVER['REQUEST_URI']))) ?>"><span><?php _e('Video') ?></span></a></li>
		</ul>
	</div>

</body>
</html>
<?php
exit;
} elseif ( 'photo' == $_GET['tab'] ) {
?>
	<script type="text/javascript">
		<?php press_this_js_init(); ?>
			var last = null;
			function pick(img) {
				if (last) last.style.backgroundColor = '#f4f4f4';
				if (img) {
					document.getElementById('photo_src').value = img.src;
					img.style.backgroundColor = '#44f';
				}
				last = img;
				return false;
			}
			jQuery(document).ready(function() {
				var img, img_tag, aspect, w, h, skip, i, strtoappend = "";
				var my_src = [<?php echo get_images_from_uri($url); ?>];

				for (i = 0; i < my_src.length; i++) {
 					img = new Image();
 					img.src = my_src[i];
 					img_attr = 'id="img' + i + '" onclick="pick(this);"';
 					skip = false;
					if (img.width && img.height) {
						if (img.width * img.height < 2500) skip = true;
						aspect = img.width / img.height;
						if (aspect > 1) {
							// Image is wide
							scale = 75 / img.width;
						} else {
							// Image is tall or square
							scale = 75 / img.height;
						}
						if (scale < 1) {
							w = parseInt(img.width * scale);
							h = parseInt(img.height * scale);
						} else {
							w = img.width;
							h = img.height;
						}
						img_attr += ' style="width: ' + w + 'px; height: ' + h + 'px;"';
					}
					if (!skip) {
						strtoappend += '<a href="' + img.src + '" title="" class="thickbox"><img src="' + img.src + '" ' + img_attr + '/></a>'
                	}
				}
				jQuery('#img_container').html(strtoappend);

				tb_init('a.thickbox, area.thickbox, input.thickbox');//pass where to apply thickbox
			});
	</script>

			<form action="press-this.php?action=post" method="post" id="photo_form">
				<?php wp_nonce_field('press-this') ?>
				<input type="hidden" name="source" value="bookmarklet"/>
				<input type="hidden" name="post_type" value="photo"/>
				<div id="posting">
					<h2><?php _e('Post Title') ?></h2>
					<input name="post_title" id="post_title" class="text" value="<?php echo attribute_escape($title);?>"/>

					<h2><?php _e('Caption') ?></h2>
					<div class="editor-container">
						<textarea name="content" id="photo_post_two" style="height:130px;width:100%;" class="mceEditor"><?php echo $selection;?>
						<br>&lt;a href="<?php echo $url;?>"&gt;<?php echo $title;?>&lt;/a&gt;</textarea>
					</div>

					<h2><?php _e('Photo URL') ?></h2>
					<input name="photo_src" id="photo_src" class="text" onkeydown="pick(0);"/>

					<style type="text/css">
						#img_container img {
					    	width:          75px;
					        height:         75px;
					        padding:        2px;
					        background-color: #f4f4f4;
					        margin-right:   7px; 
					        margin-bottom:  7px; 
					        cursor:         pointer;
					    }
					</style>
					<div id="img_container" style="border:solid 1px #ccc; background-color:#f4f4f4; padding:5px; width:370px; margin-top:10px; overflow:auto; height:100px;">
					</div>

					<h2><?php _e('Link Photo to following URL') ?></h2><?php _e('(leave blank to leave the photo unlinked)') ?>
					<input name="photo_link" id="photo_link" class="text" value="<?php echo attribute_escape($url);?>"/>

					<?php tag_div(); ?>
      
					<div>         
						<input type="submit" value="<?php _e('Create Photo') ?>" style="margin-top:15px;"	onclick="document.getElementById('photo_saving').style.display = '';"/>&nbsp;&nbsp;

						<a href="#" onclick="if (confirm('<?php _e('Are you sure?') ?>')) { self.close(); } else { return false; }" style="color:#007BFF;"><?php _e('Cancel') ?></a>&nbsp;&nbsp;
						<img src="/images/bookmarklet_loader.gif" alt="" id="photo_saving" style="width:16px; height:16px; vertical-align:-4px; display:none;"/>
					</div>
				</div>
				<?php category_div() ?>
			</form>
<?php
exit;
} elseif ( 'text' == $_GET['tab'] ) {
?>
		<script type="text/javascript">
		<?php press_this_js_init(); ?>
		</script>

		  <form action="press-this.php?action=post" method="post" id="regular_form">
		  		<?php wp_nonce_field('press-this') ?>
				<input type="hidden" name="source" value="bookmarklet"/>
				<input type="hidden" name="post_type" value="regular"/>
				<div id="posting">
					<h2><?php _e('Post Title') ?></h2>
					<input name="post_title" id="post_title" class="text" value="<?php echo attribute_escape($title);?>"/>

				  	<h2><?php _e('Post') ?></h2>
					<div class="editor-container">
						<textarea name="content" id="regular_post_two" style="height:170px;width:100%;" class="mceEditor"><?php echo $selection;?><br>&lt;a href="<?php echo $url;?>"&gt;<?php echo $title;?>&lt;/a&gt;</textarea>
					</div>        

					<?php tag_div(); ?>
       
					<div>         
						<input type="submit" value="<?php _e('Create Post') ?>" style="margin-top:15px;" onclick="document.getElementById('regular_saving').style.display = '';"/>&nbsp;&nbsp;
						<a href="#" onclick="if (confirm('<?php _e('Are you sure?') ?>')) { self.close(); } else { return false; }" style="color:#007BFF;"><?php _e('Cancel') ?></a>&nbsp;&nbsp;
						<img src="/images/bookmarklet_loader.gif" alt="" id="regular_saving" style="width:16px; height:16px; vertical-align:-4px; display:none;"/>
					</div>
				</div>
				<?php category_div() ?>
			 </form>
<?php
exit;
} elseif ( 'quote' == $_GET['tab'] ) {
?>
		<script type="text/javascript">
		<?php press_this_js_init(); ?>
		</script>

			<form action="press-this.php?action=post" method="post" id="quote_form">
				<?php wp_nonce_field('press-this') ?>
				<input type="hidden" name="source" value="bookmarklet"/>
				<input type="hidden" name="post_type" value="quote"/>
				<div id="posting">
					<h2><?php _e('Post Title') ?></h2>
					<input name="post_title" id="post_title" class="text" value="<?php echo attribute_escape(sprintf(__('Quote by %s'), $title)); ?>"/>

					<h2><?php _e('Quote') ?></h2>
					<div class="editor-container">
						<textarea name="content" id="quote_post_one" style="height:130px;width:100%;" class="mceEditor"><?php echo $selection;?></textarea>
					</div>

					<h2><?php _e('Source <span class="optional">(optional)</span>') ?></h2>
					<div class="editor-container">
						<textarea name="content2" id="quote_post_two" style="height:130px;width:100%;" class="mceEditor"><br>&lt;a href="<?php echo $url;?>"&gt;<?php echo $title;?>&lt;/a&gt;</textarea>
					</div>

					<?php tag_div(); ?>

					<div>         
						<input type="submit" value="<?php echo attribute_escape(__('Create Quote')) ?>" style="margin-top:15px;" onclick="document.getElementById('quote_saving').style.display = '';"/>&nbsp;&nbsp;
						<a href="#" onclick="if (confirm('<?php _e('Are you sure?') ?>')) { self.close(); } else { return false; }" style="color:#007BFF;"><?php _e('Cancel') ?></a>&nbsp;&nbsp;
						<img src="/images/bookmarklet_loader.gif" alt="" id="quote_saving" style="width:16px; height:16px; vertical-align:-4px; display:none;"/>
					</div>
				</div>
				<?php category_div() ?>
			</form>
<?php
exit;
} elseif ( 'video' == $_GET['tab'] ) {
?>
		<script type="text/javascript">
		<?php press_this_js_init(); ?>
		</script>

			<form action="press-this.php?action=post" method="post" id="video_form">
				<?php wp_nonce_field('press-this') ?>
				<input type="hidden" name="source" value="bookmarklet"/>
				<input type="hidden" name="post_type" value="video"/>
				<div id="posting">
					<h2><?php _e('Post Title') ?></h2>
					<input name="post_title" id="post_title" class="text" value="<?php echo attribute_escape($title);?>"/>

					<?php 
					if ( preg_match("/youtube\.com\/watch/i", $url) ) { 
						list($domain, $video_id) = split("v=", $url);
					?>
					<input type="hidden" name="content" value="<?php echo attribute_escape($url); ?>" />
					<img src="http://img.youtube.com/vi/<?php echo $video_id; ?>/default.jpg" align="right" style="border:solid 1px #aaa;" width="130" height="97"/><br clear="all" />
					<?php } else { ?>
					<h2><?php _e('Embed Code') ?></h2>
					<textarea name="content" id="video_post_one" style="height:80px;width:100%;"></textarea>
					<?php } ?>

					<h2><?php _e('Caption <span class="optional">(optional)</span>') ?></h2>

					<div class="editor-container">
						<textarea name="content2" id="video_post_two" style="height:130px;width:100%;" class="mceEditor"><?php echo $selection;?><br>&lt;a href="<?php echo $url;?>"&gt;<?php echo $title;?>&lt;/a&gt;</textarea>
					</div>

					<?php tag_div(); ?>

					<div>               
						<input type="submit" value="<?php _e('Create Video') ?>" style="margin-top:15px;" onclick="document.getElementById('video_saving').style.display = '';"/>&nbsp;&nbsp;
						<a href="#" onclick="if (confirm('<?php _e('Are you sure?') ?>')) { self.close(); } else { return false; }" style="color:#007BFF;"><?php _e('Cancel'); ?></a>&nbsp;&nbsp;
						<img src="/images/bookmarklet_loader.gif" alt="" id="video_saving" style="width:16px; height:16px; vertical-align:-4px; display:none;"/>
					</div>
				</div>
				<?php category_div() ?>
			</form>
<?php
exit;
}
?>			