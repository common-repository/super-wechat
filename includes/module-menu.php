<?php
class Wechat_menu {

	function __construct() {

	}

}

class Wechat_menu_admin {

	function __construct( $settings ) {

		$this->settings = $settings;
		$this->api 		= "https://api.weixin.qq.com/cgi-bin/menu/%s?access_token=%s";
		$this->settings_option_name 	= "super_wechat_settings";

		add_action( "wp_ajax_wechat_menu", array($this, "wp_ajax_wechat_menu_callback") );

	}

	function wp_ajax_wechat_menu_callback() {

		$menu 			= $_POST["menu"];
		$token 			= $_POST["token"];
		$output 		= new stdClass();
		$menu_items 	= wp_get_nav_menu_items( $menu );
		$menu_options 	= "menu_" . $menu;
		$output->button = array();

		if( !empty( $menu_items ) ) {

			foreach( (array) $menu_items as $key => $menu_item ) {

				$tmp_item 		= new stdClass();
				$tmp_item->type = "click";
				$tmp_item->name = $menu_item->title;
				$tmp_item->key 	= "V1001_" . $menu_item->ID;

				if( "0" == $menu_item->menu_item_parent ) {

					//Parent Level
					array_push( $output->button, $tmp_item );

				} else {

					//Only look for 2-level. Wechat doesn't support 3-level menu
					//Find Parent, which must exist
					$current_index;

					foreach( $output->button as $index => $button ) {

						$key = "V1001_" . $menu_item->menu_item_parent;

						if( $key == $button->key ) {

							$current_index = $index;

						}
					}

					$output->button[$index]->sub_button[] = $tmp_item;

				}

			}

			$this->setting["menu_" . $menu] = $output;
			update_option( $this->settings_option_name, $this->settings );

			wp_remote_post( sprintf( $this->api, "delete", $token ) );
			$post_data 	= json_decode( wp_remote_retrieve_body( wp_remote_post( sprintf( $this->api, "create", $token ), array(
				"body" => json_encode( $output )
			) ) ) );

		} else {

			$post_data 	= json_decode( wp_remote_retrieve_body( wp_remote_post( sprintf( $this->api, "delete", $token ) ) ) );

		}

		if( 0 == $post_data->errcode &&
			"ok" == strtolower( $post_data->errmsg ) ) {
			echo __("Action Succeed", "super_wechat");
		} else {
			echo sprintf( __("Action Failed With Code %s", "super_wechat"), $post_data->errcode );
		}

		die();

		return;

	}
}
?>