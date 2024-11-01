<?php
class Wechat_reply {

	function __construct( $wechat, $settings ) {

		$this->wechat 	= $wechat;
		$this->settings = $settings;

		if( "text" == $this->wechat->getData( "MsgType" ) ) {

			$this->wechat->enqueue( array( $this, "callback" ), 10 );

		}

	}

	function callback() {

		$all_replies= $this->settings["reply"];
		$receive 	= array();
		$send 		= array();
		$index 		= 0;
		$received 	= $this->wechat->getData( "Content" );

		foreach ($all_replies as $one_reply) {

			$receive[$index]= $one_reply["receive"];
			$send[$index] 	= $one_reply["send"];
			$index += 1;

		}

		$current_index 		= array_search( $received, $receive );

		if( !empty( $current_index ) ) {

			$allData 			= $this->wechat->request;
			$allData["Content"]	= $receive[$current_index];
			return $allData;

		}

		return false;

	}

}

class Wechat_reply_admin {

	function __construct( $settings ) {

		$this->settings = $settings;
		$this->settings_option_name 	= "super_wechat_settings";

		add_action( "admin_menu", array($this, "admin_menu_callback") );

	}

	function admin_menu_callback() {

		add_options_page( __('Super Wechat Concierge', "super_wechat"), __('Super Wechat Concierge', "super_wechat"), 'manage_options', 'super-wechat-reply', array($this, "options_page_callback") );

	}

}
?>