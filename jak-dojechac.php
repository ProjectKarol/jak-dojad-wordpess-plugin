<?php
/*
Plugin Name:  Jak dojechac
Plugin URI:   https://github.com/ProjectKarol/jak-dojad-wordpess-plugin
Description:  Pokazuje jak dojechac
Version:      0.1
Author:       Karol Szczęsny
Author URI:   www.karolszczesny.com
License:      GPL2
License URI:  https://www.gnu.org/licenses/gpl-2.0.html
Text Domain:  jak-dojechac
Domain Path:  /languages
*/



if ( !class_exists( 'JakDojechac' ) ) {

    class JakDojechac {

public $jdadmin = null;

	// Lets run some basics
	function __construct($class_admin) {
		
		$this->jdadmin = $class_admin;
		
		// Add support for translations
		load_plugin_textdomain( 'jak-dojechac', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
		
		// Scripts
		add_action('wp_head', array( $this, 'load_scripts' ) );

		//styles
		
			add_action('wp_head', array( $this, 'load_syle' ) );
		
	}
	


	
	// Load scripts
	function load_scripts() {
		

wp_register_script( 'jquery', '//code.jquery.com/jquery-1.11.1.min.js', array(''), '', true);
		wp_enqueue_script( 'jquery' );
		wp_register_script( 'googleapis', '//maps.googleapis.com/maps/api/js?sensor=true&libraries=places', array(''), '', true);
		wp_enqueue_script( 'googleapis' );

wp_register_script( 'bootstrapcdn', '//netdna.bootstrapcdn.com/bootstrap/3.1.1/js/bootstrap.min.js', array(''), '', true);
		wp_enqueue_script( 'bootstrapcdn' );

wp_register_script( 'placepicker', plugins_url( '/admin/js/jquery.placepicker.js', __FILE__), array(),  true);
	wp_enqueue_script( 'placepicker' );

		


		
		
	}


	function load_syle (){

wp_register_style( 'bootstrapcdnstyle', 'http://netdna.bootstrapcdn.com/bootstrap/3.1.1/css/bootstrap.min.css', array(''), '', true);
		wp_enqueue_style( 'bootstrapcdnstyle' );
	}
	

	function required() {
	require_once( plugin_dir_path( __FILE__ ) . '/assets/form.php' );
}

    } //clas jak dojechac end

} // if end


  JakDojechac::load_scripts();
  JakDojechac::required();