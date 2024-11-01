<?php
/**
 * @package YD_Recent_Images
 * @author Yann Dubois
 * @version 0.2.1
 */

/*
 Plugin Name: YD Recent Images
 Plugin URI: http://www.yann.com/en/wp-plugins/yd-recent-images
 Description: Shows latest recent images attachments of your WP image library. | Funded by <a href="http://www.completement.nu">Completement Nu</a>
 Version: 0.2.1
 Author: Yann Dubois
 Author URI: http://www.yann.com/
 License: GPL2
 */

/**
 * @copyright 2010  Yann Dubois  ( email : yann _at_ abc.fr )
 *
 *  Original development of this plugin was kindly funded by http://www.abc.fr
 *
 *  This program is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program; if not, write to the Free Software
 *  Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */

/**
 Revision 0.1.0:
 - Original beta release
 Revision 0.1.1:
 - Minor bugfixes and framework upgrade
 Revision 0.2.0:
 - Framework upgrade
 - Added an option to select only attached images
 Revision 0.2.1:
 - Option to choose number of images to display
 - Option to select images with a specific tag
 - Specific tag-based selection (using custom meta field)
 */

include_once( 'inc/yd-widget-framework.inc.php' );

$junk = new YD_Plugin( 
	array(
		'name' 				=> 'YD Recent Images',
		'version'			=> '0.2.1',
		'has_option_page'	=> true,
		'has_shortcode'		=> false,
		'has_widget'		=> true,
		'widget_class'		=> 'YD_RecentImageWidget',
		'has_cron'			=> false,
		'crontab'			=> array(
			//'daily'		=> array( 'YDWPCSI', 'daily_update' ),
			//'hourly'		=> array( 'YDWPCSI', 'hourly_update' )
		),
		'has_stylesheet'	=> true,
		'stylesheet_file'	=> 'css/yd_recent-images.css',
		'has_translation'	=> false,
		'translation_domain'=> '', // must be copied in the widget class!!!
		'translations'		=> array(
			//array( 'English', 'Yann Dubois', 'http://www.yann.com/' ),
			//array( 'French', 'Yann Dubois', 'http://www.yann.com/' )
		),		
		'initial_funding'	=> array( 'Yann.com', 'http://www.yann.com' ),
		'additional_funding'=> array(),
		'form_blocks'		=> array(
			'Main Plugin Options' => array( 
				'attach_only'	=> 'bool',
				'number'		=> 'text',
				'use_ri_tag'	=> 'bool',
				'meta_field'	=> 'text'
			)
		),
		'option_field_labels'=>array(
				'attach_only' 	=> 'Only show attached images',
				'number'		=> 'Number of images to display',
				'use_ri_tag'	=> 'Select images based on tag',
				'meta_field'	=> 'Custom field name for tag-based selection'
		),
		'option_defaults'	=> array(
				'attach_only'	=> 1,
				'number'		=> 10,
				'use_ri_tag'	=> 0,
				'meta_field'	=> 'ri_tag'
		),
		'form_add_actions'	=> array(
				//'Manually update hourly stats'	=> array( 'YDWPCSI', 'hourly_update' ),
				//'Manually update daily stats'	=> array( 'YDWPCSI', 'daily_update' ),
				//'Check latest updates'			=> array( 'YDWPCSI', 'check_update' )
		),
		'has_cache'			=> false,
		'option_page_text'	=> 'Welcome to the plugin settings page. ',
							//	. 'Your Wordpress API key is: ' . get_option( 'wordpress_api_key' ),
		'backlinkware_text' => 'Recent Images Plugin developed by YD',
		'plugin_file'		=> __FILE__		
 	)
);

class YD_RecentImageWidget extends YD_Widget {
    
	const option_key 		= 'yd-recent-images';
	
	public $widget_name		= 'Recent Images';
	public $tdomain			= 'yd-recent-images'; // used for translation domain
	
    public $fields = array (
		'title'		=> 'text'
	);
	public $field_labels = array (
		'title' 	=> 'Title:'
	);
	
    function display() {
    	
    	global $wpdb;
    	global $post;
    	
    	$options = get_option( self::option_key );
    	
    	if( $options['attach_only'] ) {
    		$andwhere = " AND at.post_parent != '' ";
    		if( $options['use_ri_tag'] 
    			&& $mytag = get_post_meta( $post->ID, $options['meta_field'], true ) 
    		) {
    			$andfrom = "
    				, $wpdb->posts AS po
    				, $wpdb->term_relationships AS tr
    				, $wpdb->term_taxonomy AS tt
    				, $wpdb->terms AS te 
    			";
    			$andwhere .= "
    				AND po.ID = at.post_parent
    				AND tr.object_id = po.ID
    				AND tt.term_taxonomy_id = tr.term_taxonomy_id
    				AND te.term_id = tt.term_id
    				AND te.name = '$mytag' 
    			";	
    		}
    	} else {
    		$andwhere = '';
    	}
    	if( $options['number'] ) {
    		$number = $options['number'];
    	} else {
    		$number = 10;
    	}
    	// get the IDs of the latest attachments
    	$query = "
    		SELECT 
    			at.ID, at.post_title, at.post_parent
    		FROM $wpdb->posts AS at
    		$andfrom
    		WHERE 
    			at.post_type = 'attachment'
    			$andwhere 
    		AND
    			at.post_mime_type LIKE 'image/%'
    		ORDER BY at.post_date DESC
    		LIMIT $number
    	";
    	$latest_attachments = $wpdb->get_results( $query, ARRAY_A );
    	echo '<div class="yd_ri">';
    	echo '<ul>';
    	foreach( $latest_attachments as $attachment ) {
    		$image = wp_get_attachment_image_src( $attachment['ID'] );
    		$data = wp_get_attachment_metadata( $attachment['ID'] );
    		if( $attachment['post_parent'] ) {
    			$link = get_permalink( $attachment['post_parent'] );
    		} else {
    			$link = '?s=' . preg_replace( '/\-/', '+', sanitize_title( $attachment['post_title'] ) );
    		}
    		echo '<li>';
    		echo '<a href="' . $link . '">';
    		echo '<img class="yd_rii" src="' . $image[0] . '"';
			echo ' title="' . $attachment['post_title'] . '"';
			echo ' style="width:' . $image[1] . 'px;';
			echo 'height:' . $image[2] . 'px;"';
    		echo '/>';
    		echo '</a>';
    		echo '</li>';
    	}
    	echo '</ul>';
    	echo '</div>';
    }
}
?>