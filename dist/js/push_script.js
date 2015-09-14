	//var pushNotification = "";

	//確認device ready
	function onDeviceReady() {

	    try {
	        var push = PushNotification.init({
	            "android": {
	                "senderID": "47580372845",
	                "image": "http://120.114.186.4/carpool/assets/logo.png"
	            },
	            "ios": {},
	            "windows": {}
	        });

	        //通知設定
	        push.on('notification', function(data) {
	            var additional = JSON.stringify(data.additionalData);
	            additional = JSON.parse(additional);
	            alert("tid: " + additional.tid);
	            document.getElementById("message").innerHTML += data.message;
	            if (additional.foreground) {
	                setName(additional.tid, 'name');
	                $('#dialog').css("display", "table");
	                $('.wrapperInside').attr('style', 'background-color: #666666;');
	            } else {
	                setName(additional.tid, 'name');
	                $('#dialog').css("display", "table");
	                $('.wrapperInside').attr('style', 'background-color: #666666;');
	            }

	        });

	        push.on('error', function(e) {
	            console.log("push error");
	        });

	    } catch (err) {
	        txt = "There was an error on this page.\n\n";
	        txt += "Error description: " + err.message + "\n\n";
	        alert(txt);
	    }
	}

	function setName(data, mode) {
	    var url = server + 'get_name.php?data={"id":"' + data + '"}';
	    var xmlhttp = new XMLHttpRequest();
	    xmlhttp.open("GET", url, true);
	    xmlhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
	    xmlhttp.onreadystatechange = function() {
	        if (xmlhttp.readyState == 4 && xmlhttp.status == 200) {
	            document.getElementById(mode).innerHTML = xmlhttp.responseText;
	        }
	    }
	    xmlhttp.send();
	}

	document.addEventListener('deviceready', onDeviceReady, true);
