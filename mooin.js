$(".course_box").mouseenter(function() {
	
	if ($(this).data("act") != "1") {
		//$(this).find(".upper").hide();
		$(this).data("act",1);
 	$(this).find('.info').fadeIn("fast");
	}
});
$(".course_box").mouseleave(function() {
	if ($(this).data("act") != 0) {
		var t = $(this);
		$(this).data("act",0);
 		$(this).find('.info').fadeOut("fast",function() {
 		//	console.log(t.find(".upper"));
 		//	t.find('.upper').show(3);
 		});
	}
});
