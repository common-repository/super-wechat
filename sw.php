<?php
require_once( dirname( dirname( dirname( dirname(__FILE__) ) ) ) . "/wp-blog-header.php" );
require_once( "includes/wechat.class.php" );

$settings 	= get_option( "super_wechat_settings" );
$wechat 	= new Wechat( array( "token" => $settings["token"] ) );

foreach( $settings["modules"] as $module ) {
	if( "menu" == $module ) continue;
	include_once( "includes/module-{$module}.php" );
	$current_class = "Wechat_" . $module;
	$$module = new $current_class( $wechat, $settings );
}

$wechat->validate(true);
$wechat->process();
?>