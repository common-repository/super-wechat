<?php
class Wechat_repost {

	function __construct( $wechat, $settings ) {

		$this->wechat 	= $wechat;
		$this->settings = $settings;

		if( "link" == $this->wechat->getData("MsgType") ) {

			$this->wechat->enqueue( array($this, "callback"), 10 );

		}

	}

	function callback() {

		$token 		= "96866628601c7e4026d6a4088b88226fb0689721";
		$parse_url 	= "https://readability.com/api/content/v1/parser?token={$token}&url=" . urlencode( $this->wechat->getData( "Url" ) );
		$post_data 	= wp_remote_retrieve_body( wp_remote_get( $parse_url ) );

		if( is_wp_error($post_data) || !isset($post_data['body']) ) {

			return false;

		} else {

			$post_data 		= json_decode( $post_data );
			$result_data 	= array(
				"FromUserName"	=> $this->wechat->getData( "FromUserName" ),
				"ToUserName"	=> $this->wechat->getData( "ToUserName" ),
				"MsgType"		=> "text",
				"Content"		=> $this->settings["feedback"]
			);
			$post_id 		= wp_insert_post( array(
				"post_category"	=> $this->settings["repost_category"],
				"post_content"	=> $post_data->content,
				"post_excerpt"	=> $post_data->excerpt,
				"post_author"	=> $this->settings["repost_author"],
				"post_status"	=> $this->settings["repost_status"],
				"post_title"	=> $post_data->title
			), true );

			if( !empty( $post_data->lead_image_url ) &&
				$post_id != 0 ) {

				require_once( ABSPATH . 'wp-admin/includes/image.php' );
				$upload_dir = wp_upload_dir();
				$image_data = @file_get_contents( $post_data->lead_image_url );
				$filename 	= basename( $post_data->lead_image_url );

				if( wp_mkdir_p( $upload_dir['path'] ) ) {
					$file = $upload_dir['path'] . '/' . $filename;
				} else {
					$file = $upload_dir['basedir'] . '/' . $filename;
				}
				file_put_contents( $file, $image_data );
				
				$wp_filetype 	= wp_check_filetype( $filename, null );
				$attachment 	= array(
					'post_mime_type' 	=> $wp_filetype['type'],
					'post_title' 		=> sanitize_file_name( $filename ),
					'post_content' 		=> '',
					'post_status' 		=> 'inherit'
				);
				$attach_id 		= wp_insert_attachment( $attachment, $file, $post_id );
				$attach_data 	= wp_generate_attachment_metadata( $attach_id, $file );
				wp_update_attachment_metadata( $attach_id, $attach_data );
				set_post_thumbnail( $post_id, $attach_id );

			}

			return !empty( $post_id ) ? $result_data : false;

		}


	}

}
?>