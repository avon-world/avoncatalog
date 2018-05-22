var temp = (new Date()).getTime();
(function(w, d){
	var o = d.location.origin + '/'
w.domUrl = o == "http://git.avoncat.my/" ? null : o;
yepnope('assets/templates/book/js/appjs.js?'+temp, undefined, function() {
	yepnope('assets/templates/book/js/main.js?'+temp, undefined, function(){
	
	})
});
}(window, document));