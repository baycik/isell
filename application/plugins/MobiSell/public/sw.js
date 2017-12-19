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
function postClientMessage( msg ){
    clients.matchAll().then(function(clients){
	clients.forEach(function(client){
	    client.postMessage(msg);
	});
    });
}
this.addEventListener('fetch', function (event) {
    event.respondWith(
	caches.match(event.request).then(function (resp) {
	    if (resp) {//use cached version
		return resp;
	    } else {
		return fetch(event.request).then(function (response) {
		    if( response.status!=200 ){
			postClientMessage( {
			    url: event.request.url,
			    status: response.status
			});
		    } else 
		    if (event.request.method === 'GET') {
			var resp2 = response.clone();
			caches.open('mobisellCache').then(function (cache) {
			    //console.log(" saved to mobisellCache: " + event.request.url);
			    cache.put(event.request, resp2);
			});
		    }
		    return response;
		}).catch(function (e) {
		    postClientMessage( {
			url: event.request.url,
			status: 408
		    });
		    console.log('isell-error',e);
		    return new Response('');
		});
	    }
	})
    );
});