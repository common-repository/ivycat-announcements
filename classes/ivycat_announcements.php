<?php
/**
 *  Main Plugin Controller.  
 */
if(!class_exists( 'ICAnnounce' )):
class ICAnnounce{
   
    var $today;
    protected $output;
    static $instance;
    
/**
 *  Class Constructor
 */
    function __construct(){
        self::$instance = $this;
        $this->output = '';
        $this->today = current_time( 'timestamp' );
        self::set_shortcodes();
        add_action( 'wp_dashboard_setup', array( &$this, 'add_dashboard_widget' ) );
    }
/**
 *  Set the Shortcode(s)
 */
    function set_shortcodes(){
        add_shortcode( 'ica_announcement' , array( &$this, 'ica_announcement' ) );
    }
    
/**
 *  Short Code controller. Three possible shortcodes
 *  [ica_announcement] = all announcements that are active / pertinant.
 *  [ica_announcement id='1'] = Pass the announcement ID along for a specific message.
 *  [ica_announcement group='xxx'] = Show messages belonging to a particular group.  (supports WP native groups only at this point)
 */
    function ica_announcement( $atts=false ){
        if( !$atts ):
            self::display_announcements();
            return $this->output;
        else:
            if( array_key_exists( 'id', $atts ) ): 
                return self::get_announcement( $atts['id'] );
            elseif( array_key_exists( 'group', $atts ) ):
                return self::get_announcements_by_group( $atts['group'] );
            endif;
        endif;
    }
    
/**
 *  Get's a specific Announcement based on the post ID.
 */    
    function get_announcement($id){
        $output = '';
        $postdata = get_post( $id, ARRAY_A ); 
        if( is_array( $postdata ) ):
            $data[0] = (object) $postdata;
            $output .= '<div class="ivycat_annc_container">'. self::output_posts( $data ) . '</div>';
        endif;
        return $output;
    }
    
/**
*  Get all announcements that belong to a group.
*/    
    function get_announcements_by_group( $group ){
        $grp = get_terms( 'announcement-type', array( 'slug' => $group ) );
        
        if( $grp[0]->slug != $group ) return false;
        
        $myfunc = $group.'_posts';
        
        try{
            self::output_posts( self::$myfunc() );
        } catch ( Exception $e ) {
            return false;
        }
        
    }
    
/**
*  On activations creates all of the terms necessary (base WordPress groups)
*/    
    function on_activation(){
        // adds terms.
        $tax = 'announcement-type';
        if( !term_exists( 'Admin',$tax ) ) wp_insert_term( 'Admin', $tax, array( 'slug' => 'admin' ) );
        if(!term_exists( 'Editor',$tax ) ) wp_insert_term( 'Editor', $tax, array( 'slug' => 'editor' ) );
        if(!term_exists( 'Author', $tax ) ) wp_insert_term( 'Author', $tax, array( 'slug' => 'author' ) );
        if(!term_exists( 'Subscriber', $tax ) ) wp_insert_term( 'Subscriber', $tax, array( 'slug' => 'subscriber' ) );
        if(!term_exists( 'Public',$tax ) ) wp_insert_term( 'Public', $tax, array( 'slug' => 'public' ) );
    }
    
/**
*  Creates a handy dashboard widget to see announcements
*/    
    function add_dashboard_widget(){
        
        wp_add_dashboard_widget( 'ivycat_annc_dbw', 'Important Announcements!', array( &$this, 'display_announcements' ) );
    }
    
/**
*  Main entry point for displaying announcements (aside from the group or ID specific functions)
*  Checks WP permission levels and dishes out the announcements based on those.
*/    
    function display_announcements(){
        global $current_user;
        wp_get_current_user();
        ?>
        <div class='ivycat_annc_container'>
            <?php
                switch( $current_user->roles[0] ){
                    case 'administrator':
                        self::admin_posts();
                        break;
                    case 'editor':
                        self::editor_posts();
                        break;
                    case 'author':
                        self::author_posts();
                        break;
                    case 'subscriber':
                        self::subscriber_posts();
                        break;
                    default:
                        self::public_posts();
                        break;
                }
            ?>
        </div>
        <?php
        
    }
    
/**
*  Gets All posts
*/    
    function admin_posts(){
        self::output_posts( get_posts( array( 'post_type' => 'bulletins' ) ) );
    }
    
/**
*  All posts except Admin
*/    
    function editor_posts(){
        $tax_query = array( 'relation'=>'NOT IN',
            array( 'taxonomy' => 'announcement-type', 'terms' => array( 'admin'  ) )
        );
        self::output_posts( get_posts( array( 'post_type' => 'bulletins', 'announcement-type' => 'editor' ) ) );
    }
    
/**
*  All posts except Admin and Editor
*/    
    function author_posts(){
        $tax_query = array( 'relation' => 'NOT IN',
            array( 'taxonomy' => 'announcement-type', 'terms' => array( 'admin', 'editor' ) )
        );
        self::output_posts( get_posts( array( 'post_type' => 'bulletins','announcement-type' => 'author' ) ) );
    }
    
/**
*  Only subscriber posts and public posts
*/    
    function subscriber_posts(){
        $tax_query = array( 'relation' => 'NOT IN',
            array( 'taxonomy'=> 'announcement-type', 'terms' => array( 'admin', 'editor', 'author' ) )
        );
        self::output_posts( get_posts( array( 'post_type' => 'bulletins', 'announcement-type' => 'subscriber' ) ) );
    }
    
/**
*  Only public posts
*/    
    function public_posts(){
        $tax_query = array( 'relation' => 'NOT IN',
            array( 'taxonomy' => 'announcement-type', 'terms' => array( 'admin', 'editor', 'author', 'subscriber') )
        );
        self::output_posts( get_posts( array( 'post_type' => 'bulletins', 'announcement-type' => 'public' ) ) );
    }
    
/**
*  Outputs the announcements. Checks date and makes the announcement inactive if it is past the date.  Only outputs posts
*  if they are currently active. 
*/    
    function output_posts( $data, $grp=false ){
        if( is_object( $data[0] ) ):
            foreach( $data as $row ){
                
                if( get_post_meta( $row->ID, 'active', true) == "y" ){
                    $enddate = get_post_meta( $row->ID, 'enddate', true );
                    $enddatetime = false;
                    if($enddate && $enddate != ""){ //enddate is not set, has no ending
                        $etime = explode("|", get_post_meta( $row->ID, 'endtime', true ));
                        $btime = explode("|", get_post_meta( $row->ID, 'begtime', true ));
                        $enddatetime = strtotime(  $enddate . " " . $etime[0] . ":" . $etime[1]  . $etime[2] );
                        $begdatetime = strtotime( get_post_meta( $row->ID, 'begdate', true ) . " ".  $btime[0] . ":" . $btime[1]  . $btime[2] );
                    }
                    
                    if( $this->today > $begdatetime ){ // is not a future event.
                    
                        if( $enddatetime && $this->today > $enddatetime ){ // if expired, deactivate
                            update_post_meta( $row->ID, 'active', 'n' );
                        }else{  // otherwise print content.
                            $this->output .= '<div class="ic-announcement">'.$row->post_content.'</div>';
                        }
                    }
                }
                
            }
            
        endif;
        return $this->output;
    }
    
}endif;