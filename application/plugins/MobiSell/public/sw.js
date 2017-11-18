this.addEventListener('install', function(event) {
//  event.waitUntil(
//    caches.open('v1').then(function(cache) {
//      return cache.addAll([
//        '../js/Semantic-UI/semantic.min.css',
//        '../js/picker/themes/default.css'
//      ]);
//    })
//  );
});
this.addEventListener('fetch', function (event) {
    event.respondWith(
	caches.match(event.request).then(function (resp) {
	    if( resp ){//use cached version
		return resp;
	    } else {
		return fetch(event.request).then(function (response) {
		    var resp2=response.clone();
		    if( event.request.method==='GET' ){
			caches.open('v1').then(function (cache) {
			    console.log("saved to cache: "+event.request.url);
			    cache.put(event.request, resp2);
			})
		    }
		    return response;
		});
	    }
	}).catch(function () {
	    return caches.match('/sw-test/gallery/myLittleVader.jpg');
	})
    );
});