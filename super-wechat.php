<?php
/*
 * Plugin Name: Super Wechat
 * Plugin URI: http://angelawang.me/
 * Description: All You Need For Wechat
 * Version: 1.0
 * Author: Angela Wang
 * Author URI: http://angelawang.me/
 * License: GPL2
 *
 * Copyright 2013 Angela Wang (email : idu.angela@gmail.com)
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License, version 2, as
 * published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

global $super_wechat_sections;
$super_wechat_sections = array(
	"main_configuration"	=> array(
		"label"		=> __("Super Wechat Configuration", "super_wechat"),
	),
	"menu_configuration"	=> array(
		"label" 	=> __("Module - Menu Configuration", "super_wechat"),
		"js"		=> array("module-menu.min.js"),
		"inline"	=> true,
	),
	"repost_configuration"	=> array(
		"label" 	=> __("Module - Repost By Link Configuration", "super_wechat"),
	),
	/*
	"reply_configuration"	=> array(
		"label" 	=> __("Module - Auto Reply Configuration", "super_wechat"),
	)*/
);

class Super_Wechat {

	function __construct() {

		global $super_wechat_sections;

		$menus = get_terms( 'nav_menu', array( 'hide_empty' => true ) );
		$menu_options = array();
		foreach( $menus as $menu ) {
			array_push( $menu_options, array(
				"id"	=> $menu->term_id,
				"label"	=> $menu->name
			) );
		}
		unset($menus);

		$cats = get_categories( array(
			'type'		=> 'post',
			'order'		=> 'ASC',
			'orderby'	=> 'name',
			'taxonomy'	=> 'category',
			'hide_empty'=> true,
		) );
		$cat_options = array();
		foreach ($cats as $cat) {
			array_push( $cat_options, array(
				"id"	=> $cat->term_taxonomy_id,
				"label"	=> $cat->category_nicename
			) );
		}
		unset($cats);

		$authors = get_users( array(
			/*'role'		=> 'author',*/
			'order'		=> 'ASC',
			'orderby'	=> 'nicename',
		) );
		$author_options = array();
		foreach ($authors as $author) {
			array_push( $author_options, array(
				"id"	=> $author->ID,
				"label"	=> $author->user_nicename
			) );
		}
		unset($authors);


		$this->options 	= array(
			array(
				"id"		=> "interface",
				"type"		=> "plain",
				"label"		=> __("File", "super_wechat"),
				"section"	=> "main_configuration",
				"default"	=> plugins_url( "sw.php", __FILE__ ),
			),
			array(
				"id"		=> "token",
				"type"		=> "text",
				"label"		=> __("Token", "super_wechat"),
				"section"	=> "main_configuration",
				"default"	=> "",
			),
			array(
				"id"		=> "access_token",
				"type"		=> "text",
				"label"		=> __("Access Token", "super_wechat"),
				"section"	=> "main_configuration",
				"default"	=> "",
			),
			array(
				"id"		=> "feedback",
				"type"		=> "text",
				"label"		=> __("Success Message", "super_wechat"),
				"section"	=> "main_configuration",
				"default"	=> "",
			),
			array(
				"id"		=> "modules",
				"type"		=> "checkbox",
				"label"		=> __("Modules", "super_wechat"),
				"values"	=> array(
					array(
						"id"	=> "menu",
						"label"	=> __("Menu", "super_wechat")
					),
					/*
					array(
						"id"	=> "reply",
						"label"	=> __("Auto Reply", "super_wechat")
					),*/
					array(
						"id"	=> "repost",
						"label"	=> __("Repost By Link", "super_wechat")
					)
				),
				"section"	=> "main_configuration",
				"default"	=> array(),
			),
			array(
				"id"		=> "menu",
				"type"		=> "dropdown",
				"label"		=> __("Wechat Menu", "super_wechat"),
				"values"	=> $menu_options,
				"section"	=> "menu_configuration",
				"default"	=> "",
			),
			/*
			array(
				"id"		=> "reply",
				"type"		=> "matrix",
				"label"		=> __("Auto Reply Messages", "super_wechat"),
				"section"	=> "reply_configuration",
				"default"	=> array()
			),*/
			array(
				"id"		=> "repost_category",
				"type"		=> "dropdown",
				"label"		=> __("Default Posting Category", "super_wechat"),
				"values"	=> $cat_options,
				"section"	=> "repost_configuration",
				"default"	=> "",
			),
			array(
				"id"		=> "repost_status",
				"type"		=> "dropdown",
				"label"		=> __("Default Post Status", "super_wechat"),
				"values"	=> array(
					array(
						"id"	=> "publish",
						"label"	=> __("Publish", "super_wechat"),
					),
					array(
						"id"	=> "draft",
						"label"	=> __("Draft", "super_wechat"),
					),
					array(
						"id"	=> "pending",
						"label"	=> __("Pending", "super_wechat")
					)
				),
				"section"	=> "repost_configuration",
				"default"	=> "draft",
			),
			array(
				"id"		=> "repost_author",
				"type"		=> "dropdown",
				"label"		=> __("Default Author", "super_wechat"),
				"values"	=> $author_options,
				"section"	=> "repost_configuration",
				"default"	=> "",
			),
		);

		$this->default 	= array();
		foreach( $this->options as $option ) {
			$this->default[$option["id"]] = $option["default"];
		}

		$this->settings_option_name 	= "super_wechat_settings";
		$this->sections = $super_wechat_sections;
		$this->settings = get_option( $this->settings_option_name );
		$this->opt_page = "super-wechat";

		foreach( $this->options as $one_option ) {
			$this->settings[ $one_option["id"] ] = isset( $this->settings[ $one_option["id"] ] ) ? $this->settings[ $one_option["id"] ] : $this->default[ $one_option["id"] ];
		}

		add_action( "init", array($this, "init_callback") );
		add_action( "admin_init", array($this, "admin_init_callback") );
		add_action( "admin_menu", array($this, "admin_menu_callback") );
		add_action( "admin_enqueue_scripts", array($this, "admin_enqueue_scripts_callback") );

		load_plugin_textdomain( "super_wechat", false, plugin_dir_path( __FILE__ ) . 'languages/' );

		register_deactivation_hook( __FILE__, array($this, "uninstall") );

	}

	function uninstall() {

		delete_option( $this->settings_option_name );

		foreach( $this->options as $index => $option ) {
			unregister_setting( $this->opt_page, $option["id"] );
			delete_option( $option["id"] );
		}

	}

	function init_callback() {

		foreach( $this->sections as $id => $section ) {

			$section_name = split("_", $id);

			if( isset( $section["inline"] ) && 
				!empty( $section["inline"] ) ) {

				$module 		= $section_name[0];
				$current_class 	= "Wechat_" . $module . "_admin";

				include_once( "includes/module-{$module}.php" );
				$$module 		= new $current_class( $this->options );

			}

		}

	}

	function admin_init_callback() {

		//Get Ready for some popping
		$temp_options 	= $this->options;

		register_setting( $this->opt_page, $this->settings_option_name );

		foreach( $this->sections as $id => $section ) {

			$section_name = split("_", $id);

			if( "main_configuration" == $id ||
				!empty( $this->settings["modules"] ) &&
				in_array( $section_name[0], $this->settings["modules"] ) ) {

				add_settings_section( $id, $section["label"], false, $this->opt_page );

				foreach( $temp_options as $index => $option ) {

					if( $id == $option["section"] ) {
						
						add_settings_field( $option["id"], $option["label"], array($this, "field_callback"), $this->opt_page, $option["section"], $option );

						unset( $temp_options[$index] );

					}

				}

			}

		}

	}

	function field_callback( $option ) {

		$all_values = $this->settings;

		if( "text" == $option["type"] ) {

			?>
			<input type="text" name="<?php echo $this->settings_option_name . "[{$option['id']}]"; ?>" id="<?php echo $option["id"]; ?>" value="<?php echo $all_values[ $option["id"] ]; ?>">
			<?php

		} if( "checkbox" == $option["type"] ) {

			foreach( $option["values"] as $value ) {
				?>
				<input
					type="checkbox"
					name="<?php echo $this->settings_option_name . "[{$option['id']}][]"; ?>"
					value="<?php echo $value["id"]; ?>"
					class="checkbox"
					<?php
						if( !empty( $all_values[ $option["id"] ] ) &&
							in_array( $value["id"], $all_values[ $option["id"] ] ) )
							echo 'checked="checked"';
					?>
				> <?php echo $value["label"]; ?>
				<?php
			}

		} else if( "dropdown" == $option["type"] ) {

			?>
			<select name="<?php echo $this->settings_option_name . "[{$option['id']}]"; ?>" id="<?php echo $option["id"]; ?>">
				<option value=""></option>
			<?php
			foreach( $option["values"] as $value ) {
				?>
				<option
					value="<?php echo $value["id"]; ?>"
					<?php
						if( !empty( $all_values[ $option["id"] ] ) &&
							$value["id"] == $all_values[ $option["id"] ] )
							echo 'selected=selected"';
					?>
				><?php echo $value["label"]; ?></option>
				<?php
			}
			?>
			</select>
			<?php

		} else if( "plain" == $option["type"] ) {

			echo $option["default"];

		}


	}

	function admin_menu_callback() {

		add_options_page( __('Super Wechat', "super_wechat"), __('Super Wechat', "super_wechat"), 'manage_options', $this->opt_page, array($this, "options_page_callback") );

	}

	function admin_enqueue_scripts_callback() {

		//Go through each section to enqueue JS
		foreach( $this->sections as $id => $section ) {

			if( isset( $section["js"] ) ) {

				foreach( $section["js"] as $one_script ) {

					wp_enqueue_script( $one_script, plugins_url( "includes/{$one_script}", __FILE__ ), array( "jquery" ), false, true );

				}
				
			}

		}

	}

	function options_page_callback() {
		?>
		<div class="wrap">  
			<div class="icon32" id="icon-options-general"></div>  
			<form action="options.php" method="post">
				<input type="hidden" id="ajaxurl" name="ajaxurl" value="<?php echo admin_url('admin-ajax.php'); ?>">

				<?php
					settings_fields( $this->opt_page );

					do_settings_sections( $this->opt_page );

					submit_button();
				?>
			</form>
		</div><!-- wrap -->  
		<?php
	}

}

if( is_admin() ) {
	$wechat = new Super_Wechat();
}
?>