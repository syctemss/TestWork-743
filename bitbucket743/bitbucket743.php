<?php

/**
 * Plugin Name: Bitbucket743
 * Description: Test Assignment | AbeloHost
 * Autor:       Oleksandr Bilyk
 * Version:     1.0
 */

// Create Template from plugin
class PageTemplater {
    private static $instance;
    protected $templates;
    public static function get_instance() {
        if ( null == self::$instance ) { self::$instance = new PageTemplater(); } 
        return self::$instance;
    }
    private function __construct() {
        $this->templates = array();
        if ( version_compare( floatval( get_bloginfo( 'version' ) ), '4.7', '<' ) ) {
            add_filter( 'page_attributes_dropdown_pages_args', array( $this, 'register_project_templates' ) );
        } else { add_filter( 'theme_page_templates', array( $this, 'add_new_template' ) ); }
        add_filter( 'wp_insert_post_data', array( $this, 'register_project_templates' ) );
        add_filter( 'template_include', array( $this, 'view_project_template') );
        $this->templates = array( 'front_add_product.php' => 'Front Add Product', );
    } 
    public function add_new_template( $posts_templates ) {
        $posts_templates = array_merge( $posts_templates, $this->templates );
        return $posts_templates;
    }
    public function register_project_templates( $atts ) {
        $cache_key = 'page_templates-' . md5( get_theme_root() . '/' . get_stylesheet() );
        $templates = wp_get_theme()->get_page_templates();
        if ( empty( $templates ) ) { $templates = array(); } 
        wp_cache_delete( $cache_key , 'themes');
        $templates = array_merge( $templates, $this->templates );
        wp_cache_add( $cache_key, $templates, 'themes', 1800 );
        return $atts;
    } 
    public function view_project_template( $template ) {
        global $post;
        if ( ! $post ) { return $template; }
        if ( ! isset( $this->templates[get_post_meta( $post->ID, '_wp_page_template', true )] ) ) {
            return $template;
        }
        $file = plugin_dir_path( __FILE__ ). get_post_meta( $post->ID, '_wp_page_template', true );
        if ( file_exists( $file ) ) { return $file; } else {            
            get_header(); ?>
            <main id="site-content">
                <article class="post-2 page type-page status-publish hentry" id="post-2">
                    <header class="entry-header has-text-align-center header-footer-group">
                        <div class="entry-header-inner section-inner medium">
                            <h1 class="entry-title">Front Add Product</h1>
                        </div>
                    </header>
                    <div class="post-inner thin ">
                        <div class="entry-content">
                            <form class="form-horizontal" name="form" method="post" enctype="multipart/form-data">
                                <input type="hidden" name="ispost" value="1" />
                                <input type="hidden" name="userid" value="" />
                                <div class="col-md-12">
                                    <label class="control-label">Title</label>
                                    <input type="text" class="form-control" name="title" />
                                </div>
                                <div class="col-md-12">
                                    <label class="control-label">Price</label>
                                    <input type="number" class="form-control" rows="8" name="price" />
                                </div>
                                <div class="col-md-12">
                                    <label class="control-label">Choose Category</label>
                                    <select name="category" class="form-control">
                                        <?php
                                            $args = array(
                                                'number'     => $number,
                                                'orderby'    => $orderby,
                                                'order'      => $order,
                                                'hide_empty' => $hide_empty,
                                                'include'    => $ids
                                            );
                                            $product_categories = get_terms( 'product_cat', $args );
                                            $args = array(
                                                'taxonomy'   => "product_cat",
                                                'number'     => $number,
                                                'orderby'    => $orderby,
                                                'order'      => $order,
                                                'hide_empty' => $hide_empty,
                                                'include'    => $ids
                                            );
                                            $catList = get_terms($args);
                                            foreach($catList as $listval) { echo '<option value="'.$listval->term_id.'">'.$listval->name.'</option>'; }
                                        ?>
                                    </select>
                                </div>
                                <div class="col-md-12">
                                    <label class="control-label">Upload Post Image</label>
                                    <input type="file" name="sample_image" class="form-control" />
                                </div>
                                <div class="col-md-12">
                                    <input type="submit" class="btn btn-primary" value="SUBMIT" name="submitpost" />
                                </div>
                            </form>
                        </div>
                    </div>
                </article>
            </main>
            <script>
                function returnformValidations() {
                    var title = document.getElementById("title").value;
                    var price = document.getElementById("price").value;
                    var category = document.getElementById("category").value;
                    if(title=="") { alert("Please enter post title!"); return false; }
                    if(price=="") { alert("Please enter post price!"); return false; }
                    if(category=="") { alert("Please choose post category!"); return false; }
                }
            </script>
            <?php get_footer(); 
            if(is_user_logged_in()) { 
                if(isset($_POST['ispost'])) {
                    global $current_user;
                    get_currentuserinfo();
                    $user_login = $current_user->user_login;
                    $user_id = $current_user->ID;
                    $post_title = $_POST['title'];
                    $post_price = $_POST['price'];
                    $new_post = array(
                        'post_title' => $post_title,                 
                        //'post_content' => $post_content,
                        'post_status' => 'publish',
                        'post_type' => $post_type,
                        'post_type' => 'product',
                        'post_category' => $category
                    );
                    $pid = wp_insert_post($new_post);
                    add_post_meta($pid, 'meta_key', true);
                    if (!function_exists('wp_generate_attachment_metadata')) {
                        require_once(ABSPATH . "wp-admin" . '/includes/image.php');
                        require_once(ABSPATH . "wp-admin" . '/includes/file.php');
                        require_once(ABSPATH . "wp-admin" . '/includes/media.php');
                    }
                    update_post_meta( $pid, '_price', $post_price );
                    if ($_FILES) {
                        foreach ($_FILES as $file => $array) {
                            if ($_FILES[$file]['error'] !== UPLOAD_ERR_OK) {
                                return "upload error : " . $_FILES[$file]['error'];
                            }
                            $attach_id = media_handle_upload( $file, $pid );
                        }         
                    }
                    if ($attach_id > 0) {
                        update_post_meta($pid, '_thumbnail_id', $attach_id);
                    }
                    $my_post1 = get_post($attach_id);
                    $my_post2 = get_post($pid);
                    $my_post = array_merge($my_post1, $my_post2);
                }
            } else { echo "<h2 style='text-align:center;'>User must be login for add post!</h2>"; }
        }
        //return $template;
    }
} 
add_action( 'plugins_loaded', array( 'PageTemplater', 'get_instance' ) );
// End Template

// Create Page with Template from Plugin (just activate plugin) | duplicating todo, easy 
function add_page() {
    $my_post = array(
      'post_title'    => wp_strip_all_tags( 'Front Add Product' ),
      'post_status'   => 'publish',
      'post_author'   => 1,
      'post_type'     => 'page',
    );
    update_post_meta( wp_insert_post( $my_post ), '_wp_page_template', 'front_add_product.php' );
}
register_activation_hook(__FILE__, 'add_page');
//End Page

// Add Custom Fields and Buttons in admin
add_action( 'add_meta_boxes', function() { add_meta_box( 'my-metaboxx1', 'Product Media', 'Media_', get_post_types(), 'side', 'core' ); },);
add_action( 'add_meta_boxes', function() { add_meta_box( 'my-metaboxx2', 'Create Date', 'Date_', get_post_types(), 'side', 'core' ); },);
add_action( 'add_meta_boxes', function() { add_meta_box( 'my-metaboxx3', 'Select', 'Select_', get_post_types(), 'side', 'core' ); },);
add_action( 'add_meta_boxes', function() { add_meta_box( 'my-metaboxx4', 'Save', 'Save_', get_post_types(), 'side', 'core' ); },-999);
add_action( 'add_meta_boxes', function() { add_meta_box( 'my-metaboxx5', 'Clear', 'Clear_', get_post_types(), 'side', 'core' ); },-999);

function Media_($post) {
    $url =get_post_meta($post->ID,'product_media', true);   
    ?>
        <label for="media_URL">
        <input id="media_URL" type="text" name="media_URL" value="<?php echo $url;?>" />
        <input id="upload_media_button" class="button" type="button" value="Media" />
        <a style="color: red; display:" id="clear_img">Remove</a><span id="clear_msg"></span>
        <script>
            jQuery(document).ready(function($) {
                $('#media-metabox.postbox').css('margin-top','30px');
                var custom_uploader;
                $('#upload_media_button').click(function(e) {
                    e.preventDefault();
                    if (custom_uploader) { custom_uploader.open(); return; }
                    custom_uploader = wp.media.frames.file_frame = wp.media( {
                        title: 'Choose a Media',
                        button: { text: 'Choose a Media' },
                        multiple: false
                    } );
                    custom_uploader.on('select', function() {
                        attachment = custom_uploader.state().get('selection').first().toJSON();
                        $('#media_URL').val(attachment.url);
                    } );
                    custom_uploader.open();
                });
            });
            document.getElementById("clear_img").onclick = function(e) {
                document.getElementById("media_URL").value = "";
                $('#clear_msg').text('Need Saving'); 
            }
        </script>
    <?php
}
add_action( 'save_post', function ($post_id) { 
    if (isset($_POST['media_URL'])) { update_post_meta($post_id, 'product_media',$_POST['media_URL']); } 
});

function Date_($post){
    $the_date1 = get_the_date( 'l F j, Y' );
    $_format = ! empty( $format ) ? $format : get_option( 'date_format' );
    $the_date2 = get_post_time( $_format, false, $post, true );
    ?>
        <label for="Date_">Created at:
        <input id="Date_" type="text" name="Date_" value="<?php echo $the_date1;?>" />
        </span><?php //echo($the_date2); ?></span>
    <?php
}

function Select_() {
    global $product, $post;
    ?>
        <label class="control-label">Choose Type</label>
        <select name="category" class="form-control">
        <?php
            $args = array(
                'number'     => $number,
                'orderby'    => $orderby,
                'order'      => $order,
                'hide_empty' => $hide_empty,
                'include'    => $ids
            );
            $product_categories = get_terms( 'product_type', $args );
            $args = array(
                'taxonomy'   => "product_type",
                'number'     => $number,
                'orderby'    => $orderby,
                'order'      => $order,
                'hide_empty' => $hide_empty,
                'include'    => $ids
            );
            $catList = get_terms($args);
            foreach($catList as $listval) { echo '<option value="'.$listval->term_id.'">'.$listval->name.'</option>'; }
        ?>
        </select>
    <?php

    // woocommerce_wp_select( [
    //     'id'      => '_select',
    //     'label'   => 'Выпадающий список',
    //     'options' => [
    //         'one'   => __( 'Option 1', 'woocommerce' ),
    //         'two'   => __( 'Option 2', 'woocommerce' ),
    //         'three' => __( 'Option 3', 'woocommerce' ),
    //     ],
    // ] );

    echo ("<br> Current Type: " . get_the_terms( $product_id,'product_type')[0]->slug);
}
add_action( 'save_post', function ($post_id) { 
    if (isset($_POST['Select_'])) { update_post_meta($post_id, 'Select_',$_POST['Select_']); }
});

// AJAX
add_action( 'save_post', function ( $post_id ) { if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) { return; }
    if ( $_POST['post_type'] !== 'post' ) {
        if ( isset( $_POST['save_post_ajax'] ) && $_POST['save_post_ajax'] == true ) { wp_send_json_success(); }
    }
} );
function Save_() {
    ?>
        <button type="button" id="Save_">Load</button>
        <span id="save_result"></span>
    <?php
    if ( get_post_type() !== 'post' ) {
        ?>
        <script>
            (function ($) {
                $(document).ready(function () {
                    $('#Save_').click(function(e) {
                        e.preventDefault()
                        var url = '<?= admin_url( 'post.php' ) ?>'
                        var data = $('form#post').serializeArray()
                        data.push({name: 'save_post_ajax', value: 1})
                        var ajax_updated = false
                        $(window).unbind('beforeunload.edit-post')
                        $(window).on('beforeunload.edit-post', function () {
                            var editor = typeof tinymce !== 'undefined' && tinymce.get('content')
                            if ((editor && !editor.isHidden() && editor.isDirty()) ||
                                (wp.autosave && wp.autosave.getCompareString() !== ajax_updated)) {
                                return postL10n.saveAlert
                            }
                        })
                        $.post(url, data, function (response) {
                            if (response.success) {
                                ajax_updated = wp.autosave.getCompareString()
                                $('#save_result').text('Saved post successfully'); 
                                //console.log('Saved post successfully')
                            } else { console.log('ERROR: Server returned false. ', response) }
                        }).fail(function (response) { console.log('ERROR: Could not contact server. ', response) 
                        }).done(function () {
                            if ( wp.autosave ) { wp.autosave.enableButtons(); }
                            $( '#publishing-action .spinner' ).removeClass( 'is-active' );
                        })
                        return false
                    })
                })
            } ) (jQuery)
        </script>
        <?php
    }
}

function Clear_($post) {
    ?>
        <button id="clearButton2">Clear2</button>
        <span id="clear_2"></span>
        <script>
            // document.getElementById("clearButton").onclick = function(e) {
            //     document.getElementById("media_URL").value = "";
            //     $('#clear_').text('Need Saving'); 
            // }
        </script>
    <?php
}

add_action( 'admin_footer-post.php', 'my_post_type_xhr', 999 );
add_action( 'admin_footer-post-new.php', 'my_post_type_xhr', 999 );
