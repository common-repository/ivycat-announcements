<?php
/*
Plugin Name: IvyCat Announcements
Plugin URI: http://www.ivycat.com/wordpress-plugins/ivycat-announcements/
Description: Display announcements on pages and posts, and within the WordPress Dashboard.
Author: IvyCat Web Services
Author URI: http://www.ivycat.com
Version: 1.0.3
License: GPLv3
**/

/**

------------------------------------------------------------------------
Copyright 2012 IvyCat, Inc.

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA

*/

if( !load_plugin_textdomain( 'ivycat-announcement-txtd', WP_CONTENT_DIR.'/languages/' ) )
	load_plugin_textdomain(' ivycat-announcement-txtd', dirname( __FILE__) );

if ( !defined( 'ICA_VERSION') )
    define ( 'ICA_VERSION', '1.0.1');

require_once 'classes/ivycat_announcements.php';
require_once 'classes/custompt.php';

/**
 *  Initionalization of the custon post type arguments.
 */
$cpt = 'bulletin';

$tax = array( 'hierarchical' => true,
    'label' => 'Bulletin Types',
    'singular_label' => 'Bulletin Type',
    'rewrite' => false );

$supports = array( 'title', 'editor', 'thumbnail' );

/**
 *  Meta Box init information.
 */
$datetime_box = array(
    'fields'=> array(
        array(
            'type' => 'date',
            'field-name' => 'begin-date',
            'label' => __( 'Begin Date' ),
            'meta-key'=>'begdate'
        ),
        array(
            'type' => 'time',
            'field-name' => 'begin-time',
            'label' => __( 'Time' ),
            'meta-key' => 'begtime'
        ),
        array(
            'type' => 'date',
            'field-name' => 'end-date',
            'label' => __('End Date'),
            'meta-key' => 'enddate'
        ),
        array(
            'type' => 'time',
            'field-name' => 'end-time',
            'label' => __('Time'),
            'meta-key' => 'endtime'
        ),
        array(
            'type' => 'radio',
            'field-name' => 'active',
            'label' => __('Active'),
            'options' => array(
                array(
                'rad-label' => __('Yes'),
                'rad-name' => 'active',
                'rad-value' => 'y'
                ),
                array(
                'rad-label' => __('No'),
                'rad-name' => 'active',
                'rad-value' => 'n'
                )
                
            ),
            'meta-key' => 'active'
        )
    )
);

// init of main custom post type - creation stuff
    $announcement = new ICACustomPostType( $cpt, $supports );
// registration of taxonomy
    $announcement->register_tax( 'announcement-type', $tax );
// building the metabox
    $announcement->build_metabox( 'Date-Time', $datetime_box );
    
    
// initialize the IvyCat Announcement class which does all of the controling  
    $ica_announce = new ICAnnounce();

register_activation_hook( __FILE__, array( &$ica_announce, 'on_activation' ) );
// hooks for styles and scripts
add_action ( 'admin_init' , 'ic_announcement_scripts' );
add_action( 'admin_init', 'ic_announcement_styles' );


function ic_announcement_styles(){
    wp_enqueue_style( 'announce-admincss', WP_PLUGIN_URL."/ivycat-announcements/css/ivycat_annc_admin.css");
    wp_enqueue_style('announce-admin-uijs', WP_PLUGIN_URL."/ivycat-announcements/css/jquery.datepick.css" );  
}

function ic_announcement_scripts(){
    wp_enqueue_script('jquery-ui', WP_PLUGIN_URL . '/ivycat-announcements/js/jquery.datepick.min.js', array('jquery'));
    wp_enqueue_script('announce-admincjs', WP_PLUGIN_URL."/ivycat-announcements/js/ivycat-annc-js.js", array('jquery'));  
}

// custom columns for the post type.
add_filter( 'manage_posts_columns', 'cpt_custom_columns' );
add_action( 'manage_posts_custom_column','cpt_column_data' );
add_filter( 'display_post_states', 'cpt_post_status' );

/**
 * creates the custom column
 */
function cpt_custom_columns( $columns ){
    global $post;
    if( isset( $_GET['post_type'] ) && $_GET['post_type'] == 'bulletins' ){
        switch( $_GET['post_type'] ){
            case 'bulletins':
               $columns['begdate'] = __( "Begin Date" );
               $columns['enddate'] = __( "End Date" );
               $columns['groups'] = __( "Groups" );
               unset( $columns['date'] );
                break;
        }     
    }
    return $columns;
}

/**
 *  Fills the column with the specific data
 */
function cpt_column_data( $column ){
    global $post;
    switch($column){
        case 'begdate':
            echo get_post_meta( $post->ID, 'begdate', true );
            break;
        case 'enddate':
            echo get_post_meta( $post->ID, 'enddate', true );
            break;
        case 'groups':
            ica_get_annc_grps( $post->ID );
            break;
    }
}

/**
 *  Creates a custom Post Status (Inactive) in the case of an announcement being inactive
 */
function cpt_post_status( $states ){
    global $post, $typenow;
    if( get_post_type() != 'bulletins' ) return $states;
    $show_custom_state = get_post_meta( $post->ID, 'active', true );
    if ( $show_custom_state == 'n' ) {
      $states[] = __( 'Inactive' );
    }
    return $states;
}

/**
 *  Used to output the groups associated with an announcement.
 */
function ica_get_annc_grps( $id ){
    
    $terms = get_the_terms( $id, 'announcement-type' );
    $total = count( $terms );
    $curr_count = 1;
    if( $terms ):
        foreach( $terms as $row ){
            echo __( $row->name );
            echo ( $curr_count == $total )? '' : ', ' ;
            $curr_count++;
        }
    endif;
    
}
