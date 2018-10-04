<?php
/**
 * Plugin Name: WPJM Company Profile Page
 * Plugin URI:  
 * Description: Adds a company profile page to WP Job Manager companies
 * Author:      Gabriel Maldonado
 * Author URI:  
 * Version:     0.1
 * Text Domain: wpjm-company-profile-page
 */

//include(locate_template('company-archive-page-template.php'));

//### Prevent direct access data leaks: If this file is accessed directly in the URL, do not load the rest of the file's content in the browser. If file is accessed within the WordPress Environment, continue to load the file's content
if (! defined( 'ABSPATH' )) {
	exit;
}

// ### Check if WP Job Manager is installed and active, prompt error message otherwise
if ( !class_exists( 'WP_Job_Manager' ) ) {

	add_action( 'admin_notices', 'gma_wpjmcpp_admin_notice__error' );

} else {
	add_action( 'single_job_listing_meta_end', 'gma_wpjmcpp_display_job_meta_data' );
	add_action( 'init', 'gma_wpjmcpp_job_taxonomy_init');
	add_action( 'template_include', 'gma_wpjmccp_companies_archive_page_template' );

	/**/
	// https://developer.wordpress.org/reference/hooks/create_taxonomy/
	/*
	* Meta for the custom "Company" taxonomy
	*/
	add_filter( 'manage_edit-companies_columns', 'gma_wpjmcpp_edit_term_columns' );
	add_action( 'companies_add_form_fields', 'gma_wpjmcpp_add_form_field_term_meta_text' );
	add_action( 'companies_edit_form_fields', 'gma_wpjmcpp_edit_form_field_term_meta_text' );
	//add_action( 'companies_edit_form_fields', 'gma_wpjmcpp_save_term_meta_text' );
	
	//add_action( 'companies_edit_form_fields', '___edit_form_field_term_meta_text' );
	//add_action( 'create_category', '___save_term_meta_text' );
	
	//add_action( 'edit_companies',   'gma_wpjmcpp_get_term_meta_text' );
	add_action( 'edit_companies',   'gma_wpjmcpp_save_term_meta_text' );
	add_action( 'create_companies',   'gma_wpjmcpp_save_term_meta_text' );

	// Scripts
	add_action( 'wp_enqueue_scripts', 'add_gma_wpjmccp_scripts' );
}

function add_gma_wpjmccp_scripts(){
	
	wp_enqueue_style( 'gma_wpjmccp_style', plugins_url() . '/wpjm-company-profile-page/style.css',false,'1.1','all');
}

// ## Template loader to edit archives
function gma_wpjmccp_companies_archive_page_template( $template ){

	//locate_template( 'company-archive-page-template.php', true, true );
	//$plugin_directory = dirname(__FILE__);
	$plugins_url = plugins_url();
	$plugin_dir_path = plugin_dir_path( __FILE__ );
	$company_template_url = $plugin_dir_path . 'company-archive-page-template.php';
	


	if ( is_tax('companies') ) {

		$template = $company_template_url;
		
		return $template;
	
	}

	return $template;
	
}

// ### Creates custom job taxonomy
function gma_wpjmcpp_job_taxonomy_init(){

	register_taxonomy(
		'companies',
		//'page',
		'job_listing',
		array(
			'label' => __( 'Companies' ),
			'rewrite' => array( 'slug' => 'company')
		)
	);
}

/*
* Creating new taxonomy data. Testing from here: https://wordpress.stackexchange.com/questions/211703/need-a-simple-but-complete-example-of-adding-metabox-to-taxonomy
*/

// Getter
// function ___get_term_meta_text( $term_id ) {
//   $value = get_term_meta( $term_id, '__term_meta_text', true );
//   //$value = ___sanitize_term_meta_text( $value );
//   return $value;
// }

/* 
* Taxonomy metadata : Adds a new column to the Companies term page.
*/
function gma_wpjmcpp_edit_term_columns( $columns ) {

    $columns['__term_meta_text'] = __( 'Term Meta Text', 'wpjm-company-profile-page' );

    return $columns;
}

/* 
* Taxonomy metadata : Adds new field to the Companies term page.
*/
function gma_wpjmcpp_add_form_field_term_meta_text() { 

	// used to validate that the contents of the form request came from the current site
    //wp_nonce_field( basename( __FILE__ ), 'term_meta_text_nonce' );
    
    ?>

    <div class="form-field term-meta-text-wrap">
        <label for="term-meta-text">
        	<?php _e( 'Term Meta Text', 'wpjm-company-profile-page' ); ?>
		</label>
        <input type="text" name="term_meta_text" id="term-meta-text" value="" class="term-meta-text-field" />
    </div>

	<?php 
}

/* 
* Taxonomy metadata : Adds new field to the Companies edit term page.
*/
function gma_wpjmcpp_edit_form_field_term_meta_text( $term ){


	$value = gma_wpjmcpp_get_term_meta_text( $term->term_id );
	//$value = get_term_meta( $term_id, '__term_meta_text', true );
	?>

    <tr class="form-field term-meta-text-wrap">
        
        <th scope="row">
        	<label for="term-meta-text">
        		<?php _e( 'Term Meta Text', 'wpjm-company-profile-page' ); ?>
        	</label>
        </th>
        
        <td>
            <input type="text" name="term_meta_text" id="term-meta-text" value="<?php echo $value ?>" class="term-meta-text-field"  />
        </td>
    </tr>

	<?php 
}

/*
* Taxonomy metadata: Gets the term metadata
*/
function gma_wpjmcpp_get_term_meta_text( $term_id ) {
  
  $value = get_term_meta( $term_id, '__term_meta_text', true );
  //$value = "DEBUG: metadata is here!";
  return $value;
}

/* 
* Taxonomy metadata : Save term metadata
*/
function gma_wpjmcpp_save_term_meta_text( $term_id ){


	$old_value  = gma_wpjmcpp_get_term_meta_text( $term_id );

    if (isset( $_POST['term_meta_text'] )) {
    	$new_value = $_POST['term_meta_text'];
    }

	update_term_meta( $term_id, '__term_meta_text', $new_value );

}

/* 
* Taxonomy metadata : Display metadata in Columns
*/
add_filter( 'manage_companies_custom_column', 'gma_wpjmcpp_manage_term_custom_column', 10, 3 );

function gma_wpjmcpp_manage_term_custom_column( $out, $column, $term_id ) {

    if ( '__term_meta_text' === $column ) {

        $value  = gma_wpjmcpp_get_term_meta_text( $term_id );

        if ( ! $value )
            $value = '';

        $out = sprintf( '<span class="term-meta-text-block" style="" >%s</div>', esc_attr( $value ) );
    }

    return $out;
}


/* 
* Taxonomy metadata : Saves metadata on taxonomy creation
*/
// function get_taxonomy_archive_link( $taxonomy ) {
//   $tax = get_taxonomy( $taxonomy ) ;
//   $test = get_bloginfo( 'url' ) . '/' . $tax->rewrite['slug'];
//   var_dump($test);
// }

function gma_wpjmcpp_display_job_meta_data() {
  
  global $post;
  //print_r($post);

  //$data = get_post_meta( $post->ID, "", true);
  $data = get_post_meta( $post->ID, "_company_name", true);

  $the_new_company_taxonomy = wp_get_post_terms($post->ID, 'companies');
  //print_r($the_new_company_taxonomy[0]->slug);
  //print_r($the_new_company_taxonomy);
  $single_company_slug = $the_new_company_taxonomy[0]->slug;

  //$url = "https://google.com";
  $url = site_url() . '/company/' . $single_company_slug;
  //##TODO: escape and html secure input for $url and $data
  //##TODO: internationalize profile string
  //$taxonomy_base_url = get_terms('job_listing');
  //var_dump($taxonomy_base_url);
  //get_taxonomy_archive_link('_company_name');
  if (!empty($data)) {
  	$company_name = "<li><a href='" . $url . "'>" . $data . " profile</a></li>";	
  } else {
  	$company_name = "<li><a href='" . $url . "'>" . $data . "Company profile</a></li>";	
  }
  
  //var_dump($data);
  echo $company_name;

  // echo ' || ';
  // var_dump(get_post_meta( $post->ID ));
  // echo ' || ';
  // $the_company_taxonomy = get_the_terms($post->ID, 'companies', true);
  // echo ' || ';
  // var_dump($the_company_taxonomy[0]->slug);
  // echo ' || ';
  //$the_new_company_taxonomy = wp_get_post_terms($post->ID, 'companies');
  //print_r($the_new_company_taxonomy[0]->slug);
  //$the_company_slug = $the_company_taxonomy['slug'];
  //echo $the_company_slug;

}

// ### Add company column to wp-admin > All Jobs


// ### Create a category for companies in the URL permalink.


// ### Create an empty page for each company via maybe wp_insert_post, post_type = page and post_title = company name , post_status = publish, content = company info.




// ### Admin notice + debug log error if core plugin is not active

function gma_wpjmcpp_admin_notice__error(){

	$class = 'notice notice-error';
	$message = __( 'An error has occurred. WP Job Manager must be installed in order to use WPJM Company Profile Page plugin', 'wpjm-company-profile-page' );

	error_log( print_r( $message , true ) );
	printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), esc_html( $message ) ); 
}



