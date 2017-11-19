this.addEventListener('install', function (event) {

});

this.addEventListener('message', function(event){
    var message=event.data;
    if( message==='clear_cache' ){
	caches.delete('mobisellCache').then(function(){
	    console.log('mobisellCache cleared!');
	});
    }
});

this.addEventListener('fetch', function (event) {
    event.respondWith(
	caches.match(event.request).then(function (resp) {
	    if (resp) {//use cached version
		return resp;
	    } else {
		return fetch(event.request).then(function (response) {
		    var resp2 = response.clone();
		    if (event.request.method === 'GET') {
			caches.open('mobisellCache').then(function (cache) {
			    //console.log(" saved to mobisellCache: " + event.request.url);
			    cache.put(event.request, resp2);
			});
		    }
		    return response;
		});
	    }
	}).catch(function () {
	    console.log("offline file not found!");
	})
    );
});