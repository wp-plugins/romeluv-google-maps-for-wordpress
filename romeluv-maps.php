<?php
/*
Plugin Name: RomeLuv Maps
Plugin URI: http://www.romeluv.com/maps-plugin-testrun/
Description:  Supercharge your posts with Google Maps! Places a Google Map inside every post you want just filling the Address field while editing a post. Generates a Global Map with all the posts,   in a standard WordPress Page via a simple shortcode: [GLOBALMAP] . Easy to hack for your custom Google Maps implementation
Version: 1.0.2
Author: RomeLuv
Author URI: http://www.romeluv.com
License: GPL v2
 
This program is free software; you can redistribute it and/or modify 
it under the terms of the GNU General Public License as published by 
the Free Software Foundation; version 2 of the License.

This program is distributed in the hope that it will be useful, 
but WITHOUT ANY WARRANTY; without even the implied warranty of 
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the 
GNU General Public License for more details. 

You should have received a copy of the GNU General Public License 
along with this program; if not, write to the Free Software 
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA 
*/










/* Define the custom box */

// WP 3.0+
// add_action( 'add_meta_boxes', 'romeluv_maps_add_custom_box' );

// backwards compatible
add_action( 'admin_init', 'romeluv_maps_add_custom_box', 1 );

/* Do something with the data entered */
add_action( 'save_post', 'romeluv_maps_save_postdata' );

/* Adds a box to the main column on the Post and Page edit screens */
function romeluv_maps_add_custom_box() {
    add_meta_box( 
        'romeluv_maps_sectionid',
        __( 'Maps', 'romeluv_maps_textdomain' ),
        'romeluv_maps_inner_custom_box',
        'post' 
    );
    add_meta_box(
        'romeluv_maps_sectionid',
        __( 'Maps', 'romeluv_maps_textdomain' ), 
        'romeluv_maps_inner_custom_box',
        'page'
    );
}

/* Prints the box content */
function romeluv_maps_inner_custom_box() {
global $post;
  // Use nonce for verification
  wp_nonce_field( plugin_basename( __FILE__ ), 'romeluv_maps_noncename' );

  // The actual fields for data entry
  echo '<label for="romeluv_maps_address_field">';
       _e("Address", 'romeluv_maps_textdomain' );
  echo '</label> ';
  echo '<input type="text" id="romeluv_maps_address_field" name="romeluv_maps_address_field" value="'. get_post_meta($post->ID,'address',true).'" size="35" /> example: Viale Kant 2, Roma';
}

/* When the post is saved, saves our custom data */
function romeluv_maps_save_postdata( $post_id ) {
  // verify if this is an auto save routine. 
  // If it is our form has not been submitted, so we dont want to do anything
  if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) 
      return;

  // verify this came from the our screen and with proper authorization,
  // because save_post can be triggered at other times

  if ( !wp_verify_nonce( $_POST['romeluv_maps_noncename'], plugin_basename( __FILE__ ) ) )
      return;

  
  // Check permissions
  if ( 'page' == $_POST['post_type'] ) 
  {
    if ( !current_user_can( 'edit_page', $post_id ) )
        return;
  }
  else
  {
    if ( !current_user_can( 'edit_post', $post_id ) )
        return;
  }

  // OK, we're authenticated: we need to find and save the data

 $data=$_POST['romeluv_maps_address_field'];
 
	update_post_meta($_POST['post_ID'], 'address', $data);
  // Do something with $mydata 
  // probably using add_post_meta(), update_post_meta(), or 
  // a custom table (see Further Reading section below)

   return $mydata;
}





 
add_filter('the_content', 'romeluv_add_map'); ///adds a map in single posts right before the content

function romeluv_add_map($content)

{
    global $post,$mapdone,$mapheight;
    if (!$mapdone && is_single())	{
	$mapdone=TRUE;
	 $mapheight=300;
	 $address = get_post_meta($post->ID,'address',true);
 
	if (strlen($address)>5) $maphtml= '<div class="single-post-map">'.romeluv_single_map('').'</div>';


	 return  $maphtml.$content;
	 
	 }
	 
	 
	
	else return $content;
}










add_action('admin_menu', 'google_maps_api_menu');




function google_maps_api_menu() {
add_options_page('Google Maps API Key', 'Google Maps API Key', 'manage_options', 'your-unique-identifier', 'gma_plugin_options');
}





function gma_plugin_options() {
?>
<div>
<h2>Google Maps API</h2>
<?php
if (array_key_exists('maps_key',$_POST)) {
update_option('maps_key',$_POST['maps_key']);
}
?>
<form method="post" action="<?php echo str_replace( '%7E', '~', $_SERVER['REQUEST_URI']); ?>">
<?php
settings_fields('maps_key');
?>
<table>
<tr valign="top">
<th scope="row">Google Maps API Key</th>
<td><input type="text" name="maps_key" value="<?php echo get_option('maps_key'); ?>" /></td>
</tr>
</table>
<input type="hidden" name="page_options" value="maps_key" />
<p>
<input type="submit" value="<?php _e('Save Changes') ?>" />
</p>
</form>
</div>
<?
}































add_shortcode('GLOBALMAP', 'romeluv_global_map');


 



function romeluv_global_map($atts)
{
    
	if (is_single()) return('');  //hack not to make trouble if looking at a single post. The  GLOBALMAP shorttag is meant to be used in pages only.
    
	
	global $wpdb,$post,$mapheight;
	$savepost=$post;
	
	 
	if ($mapheight<10) $mapheight=600; 

	
	 
	
	
	 $querystr = "
	   SELECT wposts.* 
	   FROM $wpdb->posts wposts 
	   WHERE  wposts.post_status = 'publish' 
	   AND wposts.post_type = 'post' ". $whereadditional ."
	   ORDER BY wposts.post_date DESC
	";
       //echo $querystr; 
	$pageposts = $wpdb->get_results($querystr, OBJECT);
       
       
       
       
	if ($pageposts):
	
	if (isset($_GET[cat]))  echo '<h3>'.get_cat_name($_GET[cat]).'</h3>';
	
	
	$out='
	       <script src="http://maps.google.com/maps?file=api&amp;v=2&amp;sensor=false&amp;key='. get_option('maps_key').'" type="text/javascript"></script>
	       
		<script src="http://maps.google.com/maps/api/js?sensor=false" 
			type="text/javascript"></script>
	      
	      
	      <div id="map" style="width: 100%; height:'. $mapheight.'px; font-size:11px"></div>
	      
		<script type="text/javascript">
	       
		  var map = new google.maps.Map(document.getElementById("map"), {
		    zoom: 15,
		    
		    mapTypeId: google.maps.MapTypeId.ROADMAP
		  });
	      
		  var infowindow = new google.maps.InfoWindow();
	      	var bounds = new google.maps.LatLngBounds();
		  var marker, i;
	       ';
	      
	      
	      
	global $post; 
	foreach ($pageposts as $post): 
			       
			       setup_postdata($post); $count++;  
			       
			       if (isset($_GET[cat])) if (!in_category($_GET[cat],$post->ID)) continue;
			       
			      $address=get_post_meta($post->ID,'address',true);
			      $latitude=get_post_meta($post->ID, 'latitude');
			      $longitude=get_post_meta($post->ID, 'longitude');
			      $latitude=$latitude[0]; $longitude=$longitude[0];
			       
			       
			       if (!$latitude>0 or !$longitude>0) { //no data set: update the post custom fields according to the address
					      global  $post_ID;
					      $post_ID=$post->ID;
					      romeluv_maps_handle_savepost();
					      $latitude=get_post_meta($post->ID, 'latitude');
					      $longitude=get_post_meta($post->ID, 'longitude');
					       $latitude=$latitude[0]; $longitude=$longitude[0];
					      }
					      
					      
			      if ($latitude>0 && $longitude>0) {
				      
					      $out.= '
				       
					      
					    marker = new google.maps.Marker({
					      position: new google.maps.LatLng('.$latitude.', '.$longitude.'),
					      map: map
					    });
				      bounds.extend(marker.position);
					    google.maps.event.addListener(marker, "click", (function(marker, i) {
					      return function() {
						infowindow.setContent("<a style=\'font-size:18px; color:#555;line-height:15px; \' href=\''.get_permalink($post->ID).'\'>'.get_the_title().'</a><br /> ';
						
						
					       
						$image_url= wp_get_attachment_image_src(get_post_thumbnail_id(), 'home-thumb');
						if ($image_url)  $out.="<a href='".get_permalink($post->ID)."'><img width='100' src='".$image_url[0]."' style='float:left;margin-right:7px'></a>";
						foreach((get_the_category()) as $category) { 
							      $out.= '<i>'.$category->cat_name . '</i>  '; 
							  }
							setup_postdata($post->ID);   
					      $out.='<br />'."Address: <b>".get_post_meta($post->ID,'address',true).' </b><br /> '. get_the_excerpt();
					      
					       
					      
						$out.='");
						infowindow.open(map, marker);
					      }
					    })(marker, i));
					    
					      
					     ';
				      }
					       
					      
			      
				      
       
	endforeach;  
	
	
	$out.=' 
	   //  Fit these bounds to the map
	map.fitBounds(bounds);
	 </script>
	<div style="font-size:11px; text-align:right;width:99%;height:20px"> Map By <a href="http://www.romeluv.com/maps-plugin-testrun/">RomeLuv</a> </div>
	 ';
	 
	
	else : 
	
	$out.='No elements to show on the map.';
	 
	endif; ?>
	
	<?php    
	      
	
	
	 $post=$savepost;

	return $out;

  
 
	}
















function romeluv_maps_handle_savepost() {
	global  $post_ID;
	 
	 
	 //geocode with service the address
	 $address=get_post_meta($post_ID,'address',true);
	if (strlen($address)<8) {
				update_post_meta($post_ID, 'longitude', 0);
				update_post_meta($post_ID, 'latitude', 0);
				return ('');
				
				}
	 $address=str_replace(' ','+',$address);
	 $address1 ="http://maps.google.com/maps/geo?q=1020+".$address."&output=xml&key=".get_option('maps_key');
	$page = file_get_contents($address1);
	$xml = new SimpleXMLElement($page);
	list($longitude, $latitude, $altitude) = explode(",",
	$xml->Response->Placemark->Point->coordinates);


	update_post_meta($post_ID, 'longitude', $longitude);
	update_post_meta($post_ID, 'latitude', $latitude);
	 
}

add_action('save_post', 'romeluv_maps_handle_savepost');




function romeluv_single_map()
{  
	
	global $wpdb,$post,$mapheight;
	$savepost=$post;
	
	if ($mapheight<10) $mapheight=600; 

	
	       
	   ///get values from post custom fields
	   
	  $address=get_post_meta($post->ID,'address',true);
	  $latitude=get_post_meta($post->ID, 'latitude');
	  $longitude=get_post_meta($post->ID, 'longitude');
	  $latitude=$latitude[0]; $longitude=$longitude[0];
	   
	   
	   if (!$latitude>0 or !$longitude>0) { //no data set: update the post custom fields according to the address
			  global  $post_ID;
			  $post_ID=$post->ID;
			  romeluv_maps_handle_savepost();
			  $latitude=get_post_meta($post->ID, 'latitude');
			  $longitude=get_post_meta($post->ID, 'longitude');
			   $latitude=$latitude[0]; $longitude=$longitude[0];
			  }
			  
			  
	  if ($latitude>0 && $longitude>0) {
		  
		  
		  //initialize the map
		  
		  $out='
			    <script src="http://maps.google.com/maps?file=api&amp;v=2&amp;sensor=false&amp;key='. get_option('maps_key').'" type="text/javascript"></script>
			    
			     <script src="http://maps.google.com/maps/api/js?sensor=false" 
				     type="text/javascript"></script>
			   
			   
			   <div id="map" style="width: 100%; height:'. $mapheight.'px; font-size:11px"></div>
			   
			     <script type="text/javascript">
			    
			       var map = new google.maps.Map(document.getElementById("map"), {
				 zoom: 17,
				 center: new google.maps.LatLng('.$latitude.', '.$longitude.'),
				 mapTypeId: google.maps.MapTypeId.ROADMAP
			       });
			   
			       var infowindow = new google.maps.InfoWindow();
			   
			       var marker, i;
	       ';
	      
	      
	      //add marker to the map and the popup
		$out.= '
		   
			  
		    marker = new google.maps.Marker({
		      position: new google.maps.LatLng('.$latitude.', '.$longitude.'),
		      map: map
		    });
	      
		    google.maps.event.addListener(marker, "click", (function(marker, i) {
		      return function() {
			infowindow.setContent("<a style=\'font-size:16px; color:#555; \' href=\''.get_permalink($post->ID).'\'>'.get_the_title().'</a><br /> ';
			
			
	       
	       $image_url= wp_get_attachment_image_src(get_post_thumbnail_id(), 'home-thumb');
		
		//list categories and add them into the Google map popup
		if ($image_url) $out.="<a href='".get_permalink($post->ID)."'><img width='120' src='".$image_url[0]."' style='float:left;margin-right:7px'></a>";
		foreach((get_the_category()) as $category) { 
			      $out.= '<i>'.$category->cat_name . '</i>  '; 
			  }
			setup_postdata($post->ID);
			
			
	      $out.='<br />'."Address: <b>".get_post_meta($post->ID,'address',true).' </b><br /> '. get_the_excerpt();
	      
	       
	      
		$out.='");
		infowindow.open(map, marker);
	      }
	    })(marker, i));
	    
	      
	     ';
	
	     
      //close the map
	$out.='   </script>';
	
      }  //end if
			   
					      
			      
				      
  
	 $post=$savepost;

	return $out;

  
 
	} //end function single map








?>
