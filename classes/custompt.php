<?php
/**
 *  Main Custom Post Type class for Creation.
 *  Responsible for the creation of the post type, registering the taxonomy
 *  and associating a post type with a custom meta box (or multiple)
 */
if(!class_exists( 'ICACustomPostType' )):
class ICACustomPostType{
    
    private $cpt_name, $supports, $args, $tax, $custom_arr, $view_name, $cpt_plural_name, $metabox_data;
    static $instance;
    
    /**
     * Main class constructor.
     * 
     * @param string $cpt_name REQUIRED --singluar,lowercase-- sets the custom post type's name
     * @param array $tax OPTIONAL -- taxonomy, multi-dimensional associative array.  In format : array("taxonomy_name"=>array())
     * @param array $supports OPTIONAL -- supports arguments for args array.  Set to title,editor,thumbnail as default
     * @param array $args OPTIONAL -- args array (see Wordpress Docs)
     * @param array $custom_arr OPTIONAL -- Custom array of meta-box options.  In format array(array("Name"=>"label_name","db_field"=>"db_field_name","post_name"=>"name_attribute")
     * @param string $view_name OPTIONAL -- name of php file that contains the meta box form.  - in classes/views in plugin directory
     */
    public function __construct( $cpt_name, $supports=false, $args=false, $plural=true ){
        self::$instance = $this;
        $this->cpt_name = $cpt_name;
        $this->supports = $supports;
        $this->args = $args;
        $this->cpt_plural_name = ( $this->cpt_plural )? $this->cpt_name : $this->cpt_name.'s';
        add_action( 'init', array( &$this, 'post_type_init' ), 0 );
    }
    
    /**
     *  post_type_init - builds and registers post type
     */
    public function post_type_init() {
        $cpt_name = ( $this->cpt_plural )? $this->cpt_name : $this->cpt_name.'s';
        
        $labels = array(
            'name'=>__( ucwords( $this->cpt_plural_name ) ),
            'singular_name'=>__( ucwords( $this->cpt_name ) ),
            'add_new'=>__( 'New '.ucwords( $this->cpt_name ), ucwords( $this->cpt_name ) ),
            'add_new_item'=>__( 'Add New ' . ucwords( $this->cpt_name ) ),
            'edit_item'=>__( 'Edit ' . ucwords( $this->cpt_name ) ),
            'new_item'=>__( 'Add New ' . ucwords( $this->cpt_name ) ),
            'view_item'=>__( 'View ' . ucwords( $this->cpt_name ) ),
            'search_items'=>__( 'Search ' . ucwords( $this->cpt_plural_name ) )
        );
        
        if( !$this->supports ){
            $supports = array( 'title', 'editor', 'thumbnail' );
        }else{
            $supports = $this->supports;
        }
        
        if( !$this->args ){
            $args = array(
            'label'=> __( ucwords( $this->cpt_plural_name ) ),
            'labels'=>$labels,
            'public' => true,
            'publicly_queryable' => false,
            'show_ui' => true, 
            'query_var' => false,
            'rewrite' => true,
            'capability_type' => 'post',
            'hierarchical' => false,
            'menu_position' => 4,
            'supports' => $supports
            );
        }else{
            $args = $this->args;
        }

        register_post_type( $this->cpt_plural_name, $args );
        
    }
    
    /**
     *  register_tax - registers all taxonomies passed to it (string & array or multidimensional array)
     */
    public function register_tax($tax,$args=false){
        if( is_array( $tax ) ){
            foreach( $tax as $k=>$v ) register_taxonomy( $k, array( $this->cpt_plural_name ), $v );
        }else{
            if( $args ){
                register_taxonomy( $tax, $this->cpt_plural_name, $args );
            }else return false;
        }
        
    }
    /**
    *  build_metabox - Builds all of the metaboxes from the data passed to the function. Currently only
    *  the string portion works properly.  The array option needs some work.
    **/
    public function build_metabox( $metabox, $args=false ){
        if( is_array( $metabox ) ){
            foreach( $metabox as $k=>$v ){
                $this->metabox_data[] = new ICAcptMetaBox( $v['metabox-name'], $this->cpt_plural_name, $v );
            }
        }else{
            if( $args ){
                $this->metabox_data = new ICAcptMetaBox( $metabox, $this->cpt_plural_name, $args );
                
            }else{ return false; }
        }
        wp_deregister_script( 'autosave' );  // needs to be deregistered to keep autosave from "unsaving" information
    }
    
}endif;

/**
 * Metabox object class.  Creates the metabox for a custom post type as well as builds the forms within.  
 */
if(!class_exists( 'ICAcptMetaBox' )):
class ICAcptMetaBox{
    
    var $meta_name, $post_custom, $args, $cpt_name, $context, $priority;
    
    public function __construct( $meta_name, $post_type_name, $args ){
        $this->meta_name = $meta_name;
        $this->cpt_name = $post_type_name;
        $this->args = $args['fields'];
        $this->context = ( isset($args['context'] ) ) ? $args['context'] : 'advanced';
        $this->priority = ( isset( $args['priority'] ) ) ? $args['priority'] : 'low';
        add_action( 'admin_init' , array( &$this, 'admin_init' ) );
        add_action( 'save_post' , array( &$this, 'save_cpt_info' ) );
        
    }
    
    private function cptMetaBox( $meta_name, $post_type_name, $args ){
        self::__construct( $meta_name, $custom_arr, $args );
    }
    
    /**
     *  initializes the meta box.
     */
    public function admin_init(){
        $title = self::set_meta_title();
        
        add_meta_box(
            $this->meta_name.'Info-meta',
            $title,
            array( &$this, 'meta_options' ),
            $this->cpt_name, $this->context, $this->priority
        );
    }
    
    /**
     *  Sets the meta box title, simply explodes dashes that are needed for IDs and such.  
     */
    public function set_meta_title(){
        $tmp = explode( '-', $this->meta_name );
        $title = '';
        if(is_array( $tmp )){
            foreach( $tmp as $key=>$value ){
                $title .= $value." ";
            }
        }else{
            return $this->meta_name;
        }
        return $title;
    }
    
    /**
     *  meta-options - Builds the form for the meta box, pulls data from database (if it exists)
     */
    public function meta_options(){
        global $post;
        $this->post_custom = get_post_custom( $post->ID );
        foreach( $this->args as $k=>$v ){
            $myfunc = 'add_'.$v['type'].'_field';
            try{
                self::$myfunc( $v );
            } catch ( Exception $e ) {
                echo 'Caught exception: '. $e->getMessage();
            }
            
        }
    }
    
    /**
     *  save_cpt_info - On saving a post, updates custom fields in the meta box.
     */
    public function save_cpt_info(){
        global $post;
        if( $_SERVER['REQUEST_METHOD'] == 'POST' ): // checks if post is set so Quick Edit doesn't "zero out" custom fields.
            foreach( $this->args as $k=>$v ){
                if( $v['type'] == "checkbox" ){
                    update_post_meta( $post->ID, $v['meta-key'], base64_encode( serialize( $_POST[ $v['field-name'] ] ) ) );
                }elseif($v['type'] == 'time'){
                   $value = $_POST[ $v['field-name'] . '-hr' ] . '|' . $_POST[ $v['field-name'] . '-min' ] .'|'. $_POST[ $v['field-name'] . '-ap' ];
                   update_post_meta( $post->ID, $v['meta-key'], $value );
                }else{
                    update_post_meta( $post->ID, $v['meta-key'], addslashes( $_POST[ $v['field-name'] ] ) );
                } 
            }
        endif;
    }
    
    private function add_date_field( $field_data ){
        $value = stripslashes( $this->post_custom[$field_data['meta-key']][0] );

        $input_class = ( isset( $field_data['input-class'] ) ) ? $field_data['input-class'] : $this->meta_name.'-data';
        $input_id = ( isset( $field_data['input-id'] ) ) ? $field_data['input-id'] : $this->meta_name. '-' . $field_data['field-name'];
        
        $label = '<label for="'.$input_id.'">'.$field_data['label'].'</label>';
        
        $input_field = '<input id="' . $input_id . '" class="' . $input_class . ' date hasDatepicker" type="text" name="' . $field_data['field-name'] . '" value="' . $value . '"/>';
        
        echo ( $field_data['label_right'] ) ? $input_field . ' ' . $label : $label . ' ' . $input_field;
    }
    
    private function add_time_field( $field_data ){
        $value = stripslashes( $this->post_custom[$field_data['meta-key']][0] );
        $values = explode( '|', $value );
    
        $input_class = ( isset( $field_data['input-class'] ) ) ? $field_data['input-class'] : $this->meta_name.'-data';
        $input_id = ( isset( $field_data['input-id'] ) ) ? $field_data['input-id'] : $this->meta_name . '-' . $field_data['field-name'];
        $label = '<label for="' . $input_id . '-hour">' . $field_data['label'] . '</label>';
        $pmselect = ( $values[2] == 'pm' ) ? ' selected="selected"' : '';
        $input_field = '<select id="' . $input_id . '-hour" class="' . $input_class . ' date" name="' . $field_data['field-name'] . '-hr">' . self::get_time( true, $values[0] ) . '</select>';
        $input_field .= '<select class="' . $input_class . ' date" name="' . $field_data['field-name'] . '-min">' . self::get_time( false, $values[1] ) . '</select>';
        $input_field .= '<select class="' . $input_class . ' date" name="' . $field_data['field-name'] . '-ap">
            <option value="am">am</option>
            <option value="pm"' . $pmselect . '>pm</option>
        </select><br/>';
        
        echo ( $field_data["label_right"] ) ? $input_field . " " . $label : $label . " " . $input_field ;
    }
    
    /**
     *  Creates a text input field
     */
    private function add_text_field( $field_data ){
       
        $value = stripslashes( $this->post_custom[$field_data['meta-key']][0] );

        $input_class = ( isset( $field_data['input-class'] ) ) ? $field_data['input-class'] : $this->meta_name . '-data';
        $input_id = ( isset( $field_data['input-id'] ) ) ? $field_data['input-id'] : $this->meta_name . '-' . $field_data['field-name'];
        
        $label = '<label for="' . $input_id . '">' . $field_data['label'] . '</label>';
        
        $input_field = '<input id="' . $input_id . '" class="' . $input_class . '" type="text" name="' . $field_data['field-name'] . '" value="' . $value . '"/>';
        
        echo ( $field_data['label_right'] ) ? $input_field .' '. $label : $label . ' ' . $input_field ;
        
    }  
    
    /**
     *  Creates two or more radio buttons 
     */
    private function add_radio_field($field_data){
        $value = $this->post_custom[$field_data['meta-key']][0];
        $input_class = (isset($field_data['input-class'])) ? $field_data['input-class'] : $this->meta_name."-data";
        $input_id = (isset($field_data['input-id'])) ? $field_data['input-id'] : $this->meta_name."-".$field_data['field-name'];
        echo "<h2 class='label'>".$field_data['label']."</h2>";
        foreach ($field_data['options'] as $k=>$v){
            $checked = ($value[$v['rad-name']] == $v['rad-value'])? ' checked="checked"' : '';
            $label = "<label for='".$input_id."-".$v['rad-name']."'>".$v['rad-label']."</label>";
            $input_field = '<input type="radio" id="'.$input_id.'-'.$v['rad-name'].'" class="'.$input_class.'" name="'.$field_data['meta-key'].'" value="'.$v['rad-value'].'"'.$checked.'/>';
        
        echo ($field_data['label_right']) ? $label.' '.$input_field : $input_field.' '.$label ;
        }
    }
    
    private function get_time( $hour = true, $curr_time = false ){
        $time = '';
        if( $hour ){
            $x = 12;
            while( $x >= 1 ){
                $time .= '<option';
                if( $x <= 9 ){
                    $time .= " value = '0$x'";
                }else{
                    $time .= " value = '$x'";
                }
                if( $x == $curr_time || $curr_time == "0$x" ){
                    $time .= ' selected="selected"';
                }
               $time .= ">$x</option>";
                $x--;
            }
        }else{
            $x = 0;
             while( $x <= 55 ){
                $time .= '<option';
                if( $x <= 9 ){
                    $time .= " value = '0$x'";
                }else{
                    $time .= " value = '$x'";
                }
                if( $x == $curr_time || $curr_time == "0$x" ){
                    $time .= ' selected="selected"';
                }
               $time .= ">$x</option>";
                $x += 5;
            }
        }
        return $time;
    }
}endif;