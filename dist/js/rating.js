$(document).ready(function() {
	initialize();
});

var url = window.location.toString();
var id = "";
var role = "";
var target = "";
var index = 0;
var rate = 0;

var server = "http://noname0930.no-ip.org/carpool/api/";
var local = "file:///android_asset/www/";

function initialize()
{
	var str = url.substring(url.indexOf("{"), url.length);
	var json = JSON.parse(decodeURIComponent(str));

	id = json.id;
	role = json.role;

	getTarget();

	setURL();
}

function getTarget()
{
	var url = server + 'get_unrated.php?data={"id":"' + id + '","role":"' + role + '"}';
	var xmlhttp = new XMLHttpRequest();
	xmlhttp.open("GET", url, true);
	xmlhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
	xmlhttp.onreadystatechange = function()
	{
		if(xmlhttp.readyState == 4 && xmlhttp.status == 200)
		{
			target = JSON.parse(xmlhttp.responseText);
			setTarget();
		}
	}
	xmlhttp.send();
}

function setTarget()
{
	console.log(index);
	$('#pimage').attr('src', 'http://graph.facebook.com/' + target[index].id + '/picture');
	getName(target[index].id, "pname");
}

function getName(data, mode)
{
	var url = server + 'get_name.php?data={"id":"' + data + '"}';
	var xmlhttp = new XMLHttpRequest();
	xmlhttp.open("GET", url, true);
	xmlhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
	xmlhttp.onreadystatechange = function()
	{
		if(xmlhttp.readyState == 4 && xmlhttp.status == 200) 
		{
			$('#' + mode).html(xmlhttp.responseText);
			console.log($('#' + mode).html());
		}
	}
	xmlhttp.send();
}

function setURL()
{
	$('#wall').attr('href', local + 'wall.html?data={"id":"' + id + '"}');
	$('#friendlist').attr('href', local + 'friendlist.html?data=' + '{"id":"' + id + '"}');
	$('#about').attr('href', local + 'about.html?data={"id":"' + id + '"}');
	$('#setting').attr('href', local + 'setting.html?data=' + '{"id":"' + id + '"}');
	$('#logo').attr('href', local + 'index.html?data=' + '{"id":"' + id + '"}');
	$('#image').attr('src', 'http://graph.facebook.com/' + id + '/picture');

	getName(id, "name");
}

function addRating()
{
	var comment = $('#comment').val();
	var data = '{"id":"' + id + '","uid":"' + target[index].id + '","role":"' + role + '","rating":"' + rate + '","comment":"' +  comment + '"}';
	var url = server + 'add_Rating.php?data=' + data;
	var xmlhttp = new XMLHttpRequest();
	xmlhttp.open("GET", url, true);
	xmlhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
	xmlhttp.onreadystatechange = function()
	{
		if(xmlhttp.readyState == 4 && xmlhttp.status == 200) 
		{
			console.log(xmlhttp.responseText);
		}
	}
	xmlhttp.send();
}

function nextStep()
{
	addRating();
	index++;

	if(index < target.length)
	{
		setTarget();
	}

	$('#bar').attr('style','width:' + (index) * 100/ target.length + '%');
	$('#comment').val('');

	if(index == target.length - 1)
	{
		setTimeout(function() { window.location = local + 'index.html?data=' + '{"id":"' + id + '"}'}, 5000);
	}

	if(index < target.length)
	{
		addRating();

		index++;

		if(index < target.length - 1)
		{
			setTarget();
		}
		$('#bar').attr('style','width:' + (index) * 100/ target.length + '%');
		$('#comment').val('');
	}
	else
		window.location = local + 'index.html?data=' + '{"id":"' + id + '"}';
}