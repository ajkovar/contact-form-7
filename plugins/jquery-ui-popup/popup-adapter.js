/**
* @description:
* @author: Alex Kovar
*/

(function($){
	if(!window.wpcf7) window.wpcf7={};
	$.extend(wpcf7, {
		showPopup: function(on, message){
			on.popup({text:message});
		},
	
		removePopup: function(on){
			on.popup("close");
		}
	});
	
}(jQuery))

