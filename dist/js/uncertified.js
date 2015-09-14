$(document).ready(function() {
    getUncertified();
});

var data = '';
var num = 0;

var server = "http://120.114.186.4/carpool/api/";
var local = "file:///android_asset/www/";

function initialization() {
    num = data.length;
    $('#number').attr('data-badge', num);

    var str = '';
    for (var i = 0; i < data.length; i++) {
        var background = 'http://graph.facebook.com/' + data[i]['aid'] + '/picture/?type=normal';

        str += '<div id="user' + i + '" class="mdl-cell--middle">';

        str += '<div class="mdl-cell mdl-cell--2-col">';
        str += '<img src="' + background + '" alt="..." class="avatar">';
        str += '</div>';

        str += '<div class="mdl-cell mdl-cell--4-col">';
        str += '<table class="mdl-data-table mdl-js-data-table mdl-shadow--2dp" style="width:100%">';
        str += '<thead>';
        str += '<tr>';
        str += '<th class="mdl-data-table__cell--non-numeric">Category</th>';
        str += '<th>Value</th>';
        str += '</tr>';
        str += '</thead>';
        str += '<tbody>';
        str += '<tr>';
        str += '<td class="mdl-data-table__cell--non-numeric">Ticket</td>';
        str += '<td>' + data[i]['ticket'] + '</td>';
        str += '</tr>';
        str += '<tr>';
        str += '<td class="mdl-data-table__cell--non-numeric">Facebook</td>';
        str += '<td>' + data[i]['aid'] + '</td>';
        str += '</tr>';
        str += '<td class="mdl-data-table__cell--non-numeric">Name</td>';
        str += '<td>' + data[i]['name'] + '</td>';
        str += '</tr>';
        str += '<tr>';
        str += '<td class="mdl-data-table__cell--non-numeric">Gender</td>';
        str += '<td>' + data[i]['gender'] + '</td>';
        str += '</tr>';
        str += '<td class="mdl-data-table__cell--non-numeric">Phone</td>';
        str += '<td>' + data[i]['phone'] + '</td>';
        str += '</tr>';
        str += '</tbody>';
        str += '</table>';
        str += '</div>';

        str += '<div class="mdl-cell mdl-cell--4-col">';
        str += '<input id="text' + i + '" type="text" class="form-control" aria-label="...">';
        str += '</div>';

        str += '<button class="mdl-button mdl-js-button mdl-button--raised mdl-js-ripple-effect mdl-button--accent" onclick="submit(' + i + ');">';
        str += 'Submit';
        str += '</button>';

        str += '</div>';
        str += '<br></br>';

        /*
		str += '<div id="user' + i + '" class="mdl-card mdl-shadow--2dp demo-card-wide">';

 		str += '<div class="mdl-card__title">';
 		str += '<img id="image" src="' + background + '" alt="..." class="img-circle">';
    	str += '<h2 class="mdl-card__title-text">' + data[i]['name'] + '</h2>';
  		str += '</div>';

  		str += '<div class="mdl-card__supporting-text">';
    	str += data[i]['aid'] + '<br></br>';
    	str += data[i]['phone'] + '<br></br>';
    	str += data[i]['gender'] + '<br></br>';
    	str += data[i]['regid'] + '<br></br>';
 		str += '</div>';

 		str += '<div>';
 		str += '<label class="mdl-checkbox mdl-js-checkbox mdl-js-ripple-effect" for="checkbox-1">';
		str += '<input type="checkbox" id="checkbox-1" class="mdl-checkbox__input" checked />';
		str += '<span class="mdl-checkbox__label">Checkbox</span>';
		str += '</label>';
		str += '</div>';

 		str += '<div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label textfield-demo">';
    	str += '<input class="mdl-textfield__input" type="text" id="sample3" />';
  		str += '</div>';

  		str += '	<div class="mdl-card__actions mdl-card--border">';
  		str += '		<button id="1" class="mdl-button mdl-js-button mdl-button--primary" onclick="accept(' + i + ',' + data[i]['aid'] + ')">';
  		str += '		  Accept';
		str += '		</button>';
		str += '		<button class="mdl-button mdl-js-button mdl-button--accent" onclick="decline(' + i + ',' + data[i]['aid'] + ')">';
  		str += '		  Decline';
		str += '		</button>';
    	str += '		</a>';
		str += '	</div>';
		str += '	</div>';
		str += '<br></br>';
		*/
    }

    $('#card').html(str);

    var url = window.location.toString();
    var str2 = url.substring(url.indexOf("{"), url.length);
    var json = JSON.parse(decodeURIComponent(str2));
    var id = json.id;
    var temp = '?data={"id":"' + id + '"}';

    $('#logo').attr('href', local + 'index.html' + temp);
    $('#dsgr').attr('href', local + 'index.html' + temp);
    $('#uncertify').attr('href', local + 'uncertified.html' + temp);
    $('#certify').attr('href', local + 'certified.html' + temp);
}

function getUncertified() {
    var xmlhttp = new XMLHttpRequest();
    var url = server + "getUncertified.php";
    xmlhttp.onreadystatechange = function() {
        if (xmlhttp.readyState == 4 && xmlhttp.status == 200) {
            data = JSON.parse(xmlhttp.responseText);
            console.log(data);
            initialization();
        }
    }
    xmlhttp.open("GET", url, true);
    xmlhttp.send();
}

function submit(i) {
    var xmlhttp = new XMLHttpRequest();

    var text = $('#text' + i).val();

    var arr = {
        aid: data[i]['aid'],
        description: text
    };

    var url = server + "updateCertified.php?data=" + JSON.stringify(arr);
    xmlhttp.onreadystatechange = function() {
        if (xmlhttp.readyState == 4 && xmlhttp.status == 200) {
            console.log(xmlhttp.responseText);
            $('#user' + i).css('display', 'none');

            delete data[i];
            num--;

            $('#number').attr('data-badge', num);
        }
    }
    xmlhttp.open("GET", url, true);
    xmlhttp.send();
}

function decline(i, aid) {
    var xmlhttp = new XMLHttpRequest();
    var url = server + "updateUncertified.php?data=" + aid;
    xmlhttp.onreadystatechange = function() {
        if (xmlhttp.readyState == 4 && xmlhttp.status == 200) {
            console.log(xmlhttp.responseText);
            $('#user' + i).css('display', 'none');

            delete data[i];
            num--;

            $('#number').attr('data-badge', num);
        }
    }
    xmlhttp.open("GET", url, true);
    xmlhttp.send();
}

/*
$('#table').on('click', 'tr', function() {
	sid = $(this).attr('value');
	if(status == 'progress')
 	{
 		getDialog();
		$('#modal').modal('show');
 	}
	else
 	{
 		redirectPage();
 	}
});
$("#table").on('click', 'td', function() {
 	status = $(this).attr('value');
});
$("#table").on('click', 'th', function() {
	status = 'redirect';
});
//globle variable
var table = '';
var sid ='';
var status = '';
function initilization()
{
	getDroplist('tw');
	getDroplist('us');
	getDroplist('kr');
	getDroplist('jp');
	var str = '';
	str += '<li><a href="admin/add_data.html">新增影片</a></li>';
	str += '<li><a href="admin/update_data.html">更新影片</a></li>';
	str += '<li class="divider"></li>'
	str += '<li><a href="admin/logout.html">登出</a></li>'
	$('#function').html(str);
	getTable();
}
function getDroplist(category)
{
	var xmlhttp = new XMLHttpRequest();
	var url = "api/get_droplist.php?category=" + category;
	xmlhttp.onreadystatechange = function()
	{
		if (xmlhttp.readyState == 4 && xmlhttp.status == 200)
		{
			setDroplist(category, xmlhttp.responseText);
		}
	}
	xmlhttp.open("GET", url, true);
	xmlhttp.send();
}
function setDroplist(category, responseText)
{
	var json = JSON.parse(responseText);
	var str = "";
	for(var i = 0 ; i < json.length ; i++)
	{
		str += '<li><a href="series/' + category + '/' + json[i]['url'] + '">' + json[i]['name'] +'</a></li>';
	}
	$('#' + category).html(str);
}
function getTable()
{
	var xmlhttp = new XMLHttpRequest();
	var url = "api/get_table.php";
	xmlhttp.onreadystatechange = function()
	{
		if (xmlhttp.readyState == 4 && xmlhttp.status == 200)
		{
			table = JSON.parse(xmlhttp.responseText);
			setTable();
		}
	}
	xmlhttp.open("GET", url, true);
	xmlhttp.send();
}
function setTable()
{
	var str = '';
	for(var i = 0 ; i < table.length ; i++)
	{
		str += '<tr value="' + table[i]['sid'] + '">';
		str += '<th scope="row" style="width:5%">' + table[i]['sid'] + '</th>';
		str += '<td style="width:30%">' + table[i]['name'] + '</td>';
		str += '<td value="progress">';
		str += '<div class="progress">';
		if(table[i]['progress'] == 0)
		{
			str += '<div class="progress-bar progress-bar-danger progress-bar-striped active" role="progressbar" aria-valuenow="60" aria-valuemin="0" aria-valuemax="100" ';
			str += 'style="min-width: 1em; width: 1%;">';
		}
		else if(table[i]['progress'] < 31)
		{
			str += '<div class="progress-bar progress-bar-warning progress-bar-striped active" role="progressbar" aria-valuenow="60" aria-valuemin="0" aria-valuemax="100" style="width:';
			str += table[i]['progress'] + '%;">';
		}
		else if(table[i]['progress'] < 61)
		{
			str += '<div class="progress-bar progress-bar-info progress-bar-striped active" role="progressbar" aria-valuenow="60" aria-valuemin="0" aria-valuemax="100" style="width:';
			str += table[i]['progress'] + '%;">';
		}
		else if(table[i]['progress'] < 91)
		{
			str += '<div class="progress-bar progress-bar-striped active" role="progressbar" aria-valuenow="60" aria-valuemin="0" aria-valuemax="100" style="width:';
			str += table[i]['progress'] + '%;">';
		}
		else
		{
			str += '<div class="progress-bar progress-bar-success progress-bar-striped active" role="progressbar" aria-valuenow="60" aria-valuemin="0" aria-valuemax="100" style="width:';
			str += table[i]['progress'] + '%;">';
		}
    	str += table[i]['progress'] + '%</div></div>';
    	str += '</td>';
		str += '<td style="width:10%">' + table[i]['last'] + '</td>';
		if(table[i]['end'] == '1')
			str += '<td style="width:10%">已完結</td>';
		else
			str += '<td style="width:10%">連載中</td>';
		str += '</tr>';
	}
	$('#table').html(str);
}
function redirectPage()
{
	var xmlhttp = new XMLHttpRequest();
	var url = "api/get_url.php?data=" + JSON.stringify(sid);
	xmlhttp.onreadystatechange = function()
	{
		if (xmlhttp.readyState == 4 && xmlhttp.status == 200)
		{
			var temp = JSON.parse(xmlhttp.responseText);
			window.location = 'series/' + temp[0]['category'] + '/' + temp[0]['url'];
		}
	}
	xmlhttp.open("GET", url, true);
	xmlhttp.send();
}
function getDialog()
{
	var xmlhttp = new XMLHttpRequest();
	var url = "api/get_dialog.php?data=" + sid;
	console.log(url);
	xmlhttp.onreadystatechange = function()
	{
		if (xmlhttp.readyState == 4 && xmlhttp.status == 200)
		{
			var temp = JSON.parse(xmlhttp.responseText);
			setDialog(temp);
			getName();
		}
	}
	xmlhttp.open("GET", url, true);
	xmlhttp.send();
}
function getName()
{
	var xmlhttp = new XMLHttpRequest();
	var url = "api/get_name.php?data=" + sid;
	xmlhttp.onreadystatechange = function()
	{
		if (xmlhttp.readyState == 4 && xmlhttp.status == 200)
		{
			$('#name').html(xmlhttp.responseText);
		}
	}
	xmlhttp.open("GET", url, true);
	xmlhttp.send();
}
function setDialog(response)
{
	var str = '<h1 id="go"><span id="name" class="label label-default"></span></h1>';
	for(var i = 0 ; i < response.length; i++)
	{
		str += '<caption><h3>Season ' + (i + 1) + '</h3></caption>';
		str += '<table class="table table-bordered" style="width:85%">';
		str += '<thread>';
		str += '<tr>';
		str += '<th>Episode</th>';
		str += '<th>Availiable</th>';
		str += '</tr>';
		str += '</thread>';
		str += '<tbody>';
		for(var j = 0 ; j < response[i].length; j++)
		{
			str += '<tr>';
			str += '<th scope="row">' + (j + 1) + '</th>';
			if(response[i][j]['availiable'] == '1')
				str += '<td><span class="glyphicon glyphicon-ok" aria-hidden="true"></span></td>';
			else
				str += '<td><span class="glyphicon glyphicon-remove" aria-hidden="true"></span></td>';
			str += '</tr>';
		}
		str += '<tbody>';
		str += '</table>';
	}
	$('#dialog').html(str);
}
*/
