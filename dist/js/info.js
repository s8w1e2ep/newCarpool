$(document).ready(function() {
	url = window.location.toString();
	initialize();
});

function initialize()
{
	var str = url.substring(url.indexOf("{"), url.length);		
	$('#back').attr("href","result.html?data=" + str);
	console.log("result.html?data=" + str);
	var map;
	function initMap() {
		var myLatLng = {lat: 22.639801, lng: 120.313876};
		map = new google.maps.Map(document.getElementById('map'), {
			center: myLatLng,
			zoom: 15
		});
	}
}
