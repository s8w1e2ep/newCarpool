$(document).ready(function() {
    getCertified();
});

var data = '';
var num = 0;
var success = 0;
var failed = 0;

var server = "http://120.114.186.4:8080/carpool/api/";
var local = "file:///android_asset/www/";

function initialization() {
    num = data.length;

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
        str += '<td class="mdl-data-table__cell--non-numeric">Description</td>';
        str += '<td>' + data[i]['description'] + '</td>';
        str += '</tr>';
        str += '<td class="mdl-data-table__cell--non-numeric">Result</td>';
        str += '<td>' + data[i]['result'] + '</td>';
        str += '</tr>';
        str += '<td class="mdl-data-table__cell--non-numeric">Created Time</td>';
        str += '<td>' + data[i]['created_time'] + '</td>';
        str += '</tr>';
        str += '<td class="mdl-data-table__cell--non-numeric">Edited Time</td>';
        str += '<td>' + data[i]['edited_time'] + '</td>';
        str += '</tr>';
        str += '</tbody>';
        str += '</table>';
        str += '</div>';

        str += '</div>';
        str += '<br></br>';

        if (data[i]['result'] == 1)
            success++;
    }

    $('#success').attr('data-badge', success);
    $('#failed').attr('data-badge', num - success);

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

function getCertified() {
    var xmlhttp = new XMLHttpRequest();
    var url = server + "getCertified.php";
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
