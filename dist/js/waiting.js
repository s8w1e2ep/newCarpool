$(document).ready(function() {
	url = window.location.toString();
	initialize();
});

$('#accept').click(function() {
	var value = $('#accept').val();
	confirmCarpool();
});

$('#reject').click(function() {
	cancelCarpool();
});

var url = "";
var id = "";
var tid = "";
var json = "";

var server = "http://noname0930.no-ip.org/carpool/api/";
var local = "file:///android_asset/www/";

function initialize()
{
	var str = url.substring(url.indexOf("{"), url.length);
	json = JSON.parse(decodeURIComponent(str));
	id = json.id;
	json = decodeURIComponent(str);
	setURL();
}

function setURL()
{
	var temp = '?data={"id":"' + id + '"}';

	$('#wall').attr('href', local + 'wall.html' + temp);
	$('#friendlist').attr('href', local + 'friendlist.html' + temp);
	$('#about').attr('href', local + 'about.html' + temp);
	$('#setting').attr('href', local + 'setting.html' + temp);
	$('#logo').attr('href', local + 'index.html' + temp);
}

// passenger: {"role":"passenger","id":"838717559541922","did":"1046779538684826"}
function confirmCarpool()
{
	window.location = local + 'trace.html?data={"role":"passenger","id":"' + id + '","did":"'+ tid + '"}';
}
function cancelCarpool()
{
	window.location = local + 'initialization.html?data={"id":"' + id + '"}';
}