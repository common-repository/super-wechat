(function($) {

	var $menu = $("#menu");
	$menu.parent("td").append("<p class='menu_result'></p>");

	$menu.on("change", function() {

		if( $("#access_token").val() !== "" &&
			$menu.val() !== "" ) {

			$.post($("#ajaxurl").val(), {
				menu 	: $menu.val(),
				token 	: $("#access_token").val(),
				action 	: "wechat_menu",
			}, function(response) {
				$menu.parent("td").find(".menu_result").html(response);
			});

		}

	});

})(jQuery);