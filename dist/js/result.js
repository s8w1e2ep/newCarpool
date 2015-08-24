$(document).ready(function() {
	url = window.location.toString();
	initialize();
});

// $('#submit').click(function() {
	// sendGCM();
// });

// $('#cancel').click(function() {
	// $('#dialog').css("display", "none");
// });

var url = "";
var id = "";
var did = "";
var did2 = "";
var did3 = "";

var json = "";

var server = "http://127.0.0.1/car/api/";
var local = "file:///android_asset/www/";			

function initialize()
{
	var str = url.substring(url.indexOf("{"), url.length);
	json = JSON.parse(decodeURIComponent(str));				
	id = json.id;
	
	var result = json.result;	
	var temp = json.result[0].did;
	
	result = JSON.stringify(result);
	json = decodeURIComponent(str);
	
	//set Table
	console.log("json:" + json);
	requestAPI(server + "result.php", json, "table1");
	requestAPI(server + "result2.php", json, "table2");
	//requestAPI(server + "result3.php", json, "table3");
}

function requestAPI(url, data, mode)
{
	var xmlhttp = new XMLHttpRequest();
	url += "?data=" + data;
	xmlhttp.onreadystatechange = function() 
	{
		if (xmlhttp.readyState == 4 && xmlhttp.status == 200) 
		{
			document.getElementById(mode).innerHTML = xmlhttp.responseText;
		}
	}
	xmlhttp.open("GET", url, true);
	xmlhttp.send();		
}

function setDialog(value)
{	
	$('#dialog').css("display", "inline");
	$('#dialog_image').attr('src', 'http://graph.facebook.com/' + value + '/picture');
	requestAPI(server + "get_name.php", '{"id":"' + value + '"}', "dialog_name");
	
	did = value;
}

function setDialog2(value1, value2)
{	
	$('#dialog').css("display", "inline");
	$('#dialog_image').attr('src', 'http://graph.facebook.com/' + value1 + '/picture');
	requestAPI(server + "get_name.php", '{"id":"' + value1 + '"}', "dialog_name");
	$('#dialog_image2').attr('src', 'http://graph.facebook.com/' + value2 + '/picture');
	$('#dialog_image2').css("display", "inline");
	requestAPI(server + "get_name.php", '{"id":"' + value2 + '"}', "dialog_name2");
	
	did = value1;
	did2 = value2;
	
}

function showResult2(value){
	var cid = '#child' + value;
	$(cid).css("display", "inline");
	$("#table1").hide();
}

function showResult3(value1, value2){
	var cid = '#lastchild' + (value1 * 10 + value2);
	$(cid).css("display", "inline");
	$("#table2").hide();
}

function confirm(){
	var wait_str = 'waiting.html?data={"id":"' + id + '", "result":[{"did":';
	if(did != ""){
		sendGCM(did);
		wait_str += did + '}';
	}
	if(did2 != ""){
		sendGCM(did2);
		wait_str += ',{"did":' + did2 + '}';
	}
	if(did3 != ""){
		sendGCM(did3);
		wait_str += ',{"did":' + did3 + '}';
	}
	wait_str += ']}';
	
	console.log(wait_str);
	window.location = 'http://127.0.0.1/car/' + wait_str;
}

function sendGCM(driver_id)
{
	var xmlhttp = new XMLHttpRequest();			
	url = server + 'gcm_server.php?data={"id":"' + id + '","tid":"' + driver_id + '","mode":"1"}';
	xmlhttp.onreadystatechange = function() 
	{
		if (xmlhttp.readyState == 4 && xmlhttp.status == 200) 
		{
			alert(xmlhttp.responseText);
		}
	}
	xmlhttp.open("GET", url, true);
	xmlhttp.send();
	
	window.location = local + 'waiting.html?data={"id":"' + id + '"}';
}

function cancel(){
	$('#dialog').css("display", "none");
	did = "";
	did2 = "";
	did3 = "";
}