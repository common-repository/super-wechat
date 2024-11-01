<?php
class Wechat {

	/*
	RECEIVE MESSAGE
		Common Param: ToUserName, FromUserName, CreateTime, MsgId
		MsgType and assoc Param:
			- text 			Content
			- image 		PicUrl
			- location 		Location_X, Location_Y, Scale, Label
			- link 			Title, Description, Url
			- event 		Event	(can be subscribe, unsubscribe, CLICK), EventKey

	SEND MESSAGE
		Common Param: ToUserName, FromUserName, CreateTime, FuncFlag
		MsgType and assoc Param:
			- text 			Content
			- music 		MusicUrl, HQMusicUrl
			- news 			ArticleCount (< 10), Articles, Title, Description, PicUrl, Url
	*/

	function __construct( $options = array() ) {

		//$this->request 		= $this->parseData();
		$this->request 		= $_REQUEST;
		$this->options 		= shortcode_atts( array(
			"token"		=> "",
			"protocol"	=> "http"
		), $options );
		$this->template 	= array(
			"text"		=> "
				<xml>
					<ToUserName><![CDATA[%s]]></ToUserName>
					<FromUserName><![CDATA[%s]]></FromUserName>
					<CreateTime>%s</CreateTime>
					<MsgType><![CDATA[text]]></MsgType>
					<Content><![CDATA[%s]]></Content>
					<FuncFlag>%d</FuncFlag>
				</xml>
			",
			"music"		=> "
				<xml>
					<ToUserName><![CDATA[%s]]></ToUserName>
					<FromUserName><![CDATA[%s]]></FromUserName>
					<CreateTime>%s</CreateTime>
					<MsgType><![CDATA[music]]></MsgType>
					<Music>
						<Title><![CDATA[%s]]></Title>
						<Description><![CDATA[%s]]></Description>
						<MusicUrl><![CDATA[%s]]></MusicUrl>
						<HQMusicUrl><![CDATA[%s]]></HQMusicUrl>
					</Music>
					<FuncFlag>%d</FuncFlag>
				</xml>
			",
			"newsBegin"	=> "
				<xml>
					<ToUserName><![CDATA[%s]]></ToUserName>
					<FromUserName><![CDATA[%s]]></FromUserName>
					<CreateTime>%s</CreateTime>
					<MsgType><![CDATA[news]]></MsgType>
					<ArticleCount><![CDATA[%s]]></ArticleCount>
					<Articles>
			",
			"newsEnd"	=> "
					</Articles>
					<FuncFlag>%d</FuncFlag>
				</xml>
			",
			"newsItem"	=> "
				<item>
					<Title><![CDATA[%s]]></Title>
					<Description><![CDATA[%s]]></Description>
					<PicUrl><![CDATA[%s]]></PicUrl>
					<Url><![CDATA[%s]]></Url>
				</item>
			"
		);
		$this->callback 	= array();

	}

	function validate( $return = false ) {

		$echoStr = $this->request["echoStr"];

		if( $return && $echoStr ) {
			return $this->checkSignature() ? $echoStr : false;
		}
		if( $echoStr ) {
			return $this->checkSignature() ? die( $echoStr ) : die( "failed validation" );
		}

		return false;

	}

	function checkSignature() {

		$args = array("signature", "timestamp", "nonce");

		foreach ($args as $arg) {
			if ( !isset($this->request[$arg]) ) return false;
		}

		$signature 	= $this->request["signature"];
		$timestamp 	= $this->request["timestamp"];
		$nonce 		= $this->request["nonce"];

		$tmpArr = array($this->options["token"], $timestamp, $nonce);
		sort( $tmpArr );
		$tmpStr = implode( $tmpArr );
		$tmpStr = sha1( $tmpStr );

		return ( $tmpStr == $signature );

	}

	function parseData() {

		$postStr = $GLOBALS["HTTP_RAW_POST_DATA"];

	  	if( !empty( $postStr ) ) {

	  		/* For Testing Purpose */
	  		$postObj = (array) simplexml_load_string( $postStr, 'SimpleXMLElement', LIBXML_NOCDATA );
	  		$postObj = json_encode( $postObj );
	  		$postObj = json_decode( $postObj, 1 );
	  		
	  		return $postObj;

	  	} else {

	  		return array();

	  	}

	}

	function enqueue( $callback, $index = false ) {

		if( !empty($index) ) {

			$this->callback[$index] = $callback;

		} else {
			
			$this->callback[] 		= $callback;
		
		}

		return;

	}

	function process() {

		$result_data;

		//Auto Skips Empty Ones
		foreach( $this->callback as $callback ) {

			//Process callback associated. First tangible result wins
			$result_data = call_user_func( $callback );
			if( !empty( $result_data ) ) break;

		}

		//Default to return the message without callback
		if( empty( $result_data ) ) {

			$result_data = $this->request;

		}

		//Switch sender/receiver
		list( $result_data["FromUserName"], $result_data["ToUserName"] ) = array( $result_data["ToUserName"], $result_data["FromUserName"] );

		return $this->composeMsg( $result_data );

	}	

	function composeMsg( $data ) {

	  	$flag 	= isset( $data["FuncFlag"] ) ? $data["FuncFlag"] : 1;
	  	$time 	= time();
	  	$output = "";

	  	switch ( $data["MsgType"] ) {
	  		case 'text':
	  			$output = sprintf( $this->template["text"], $data["ToUserName"], $data["FromUserName"], $time, $data["Content"], $flag );
	  			break;
	  		case 'music':
	  			$output = sprintf( $this->template["music"], $data["ToUserName"], $data["FromUserName"], $time, $data["Title"], $data["Description"], $data["MusicUrl"], $data["HQMusicUrl"], $flag );
	  			break;
	  		case 'news':
	  			$output = sprintf( $this->template["newsBegin"], $data["ToUserName"], $data["FromUserName"], $time, $data["ArticleCount"] );
	  			for($i = 0; $i < $data["ArticleCount"]; $i += 1) {
	  				$output .= sprintf($this->template["newsItem"], $data["items"][$i]["Title"], $data["items"][$i]["Description"], $data["items"][$i]["PicUrl"], $data["items"][$i]["Url"]);
	  			}
	  			$output .= sprintf($this->template["newsEnd"], $flag );
	  			break;
	  	}

	  	echo $output;

	}

	function getData( $part ) {

		return strtolower( $this->request[$part] );

	}

	function getCurl( $url, $refer = false ) {

		$curl 		= curl_init( $url );
		$refer 		= !empty( $refer ) ? $refer : $this->options["protocol"] . "://mp.weixin.qq.com/";

		curl_setopt_array( $curl, array(
			CURLOPT_RETURNTRANSFER => true,				// return web page
			CURLOPT_HEADER         => false,			// don't return headers
			CURLOPT_FOLLOWLOCATION => true,				// follow redirects
			CURLOPT_ENCODING       => "",				// handle all encodings
			CURLOPT_USERAGENT      => "",				// who am i
			CURLOPT_AUTOREFERER    => true,				// set referer on redirect
			CURLOPT_CONNECTTIMEOUT => 120,				// timeout on connect
			CURLOPT_TIMEOUT        => 120,				// timeout on response
			CURLOPT_MAXREDIRS      => 10,				// stop after 10 redirects
			CURLOPT_SSL_VERIFYHOST => 0,				// don't verify ssl
			CURLOPT_SSL_VERIFYPEER => false,			//
			CURLOPT_REFERER        => $refer,
		) );

		$response	= curl_exec( $curl );

		curl_close( $curl );

		return $response;

	}

}
?>