this.addEventListener('install', function (event) {
    control_cache_version();
});
function control_cache_version(){
    fetch('../echo_version.php', {
	method: 'post'
    })
    .then(function (data) {
	data.text().then(console.log);
    })
    .catch(function (error) {
	console.log('Request failed', error);
    });
}






this.addEventListener('fetch', function (event) {
    event.respondWith(
	    caches.match(event.request).then(function (resp) {
	if (resp) {//use cached version
	    return resp;
	} else {
	    return fetch(event.request).then(function (response) {
		var resp2 = response.clone();



		console.log(event.request.url.indexOf('sw.js'));

		if (event.request.method === 'GET' && event.request.url.indexOf('sw.js') == -1) {
		    caches.open('v1').then(function (cache) {
			console.log("saved to cache: " + event.request.url);
			cache.put(event.request, resp2);
		    })
		}
		return response;
	    });
	}
    }).catch(function () {
	console.log("offline file not founddd!");
    })
	    );
});