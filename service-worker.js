// Cache
//


const CACHE_VERSION = 1;
const CURRENT_CACHES = {
  offline: `offline-cache-v${CACHE_VERSION}`,
};

self.addEventListener("activate", (event) => {
  // Delete all caches that aren't named in CURRENT_CACHES.
  // While there is only one cache in this example, the same logic
  // will handle the case where there are multiple versioned caches.
  const expectedCacheNamesSet = new Set(Object.values(CURRENT_CACHES));
  event.waitUntil(
    caches.keys().then((cacheNames) =>
      Promise.all(
        cacheNames.map((cacheName) => {
          if (!expectedCacheNamesSet.has(cacheName)) {
            // If this cache name isn't present in the set of
            // "expected" cache names, then delete it.
            console.log("Deleting out of date cache:", cacheName);
            return caches.delete(cacheName);
          }
        }),
      ),
    ),
  );
});

self.addEventListener("fetch", (event) => {
  console.log("Handling fetch event for", event.request.url);

  event.respondWith(( async () => {

    // We call .clone() on the request since we might use it
    // in a call to cache.put() later on.
    // Both fetch() and cache.put() "consume" the request,
    // so we need to make a copy.
    // (see https://developer.mozilla.org/en-US/docs/Web/API/Request/clone)
    let response;
    try {
      response = await fetch(event.request.clone());
    } catch (e) {
      let cache = await caches.open(CURRENT_CACHES.offline);
      response = await cache.match(event.request)
      if (response) {
        console.log(" Found response in cache:", response);
        return response;
      }
    }
    console.log(
      "  Response for %s from network is: %O",
      event.request.url,
      response,
    );
    
    if (response.status < 400) {
      // This avoids caching responses that we know are errors
      // (i.e. HTTP status code of 4xx or 5xx).
      console.log("  Caching the response to", event.request.url);
      // We call .clone() on the response to save a copy of it
      // to the cache. By doing so, we get to keep the original
      // response object which we will return back to the controlled
      // page.
      // https://developer.mozilla.org/en-US/docs/Web/API/Request/clone
      cache.put(event.request, response.clone());
    } else {
      console.log("  Not caching the response to", event.request.url);
    }
    
    // Return the original response object, which will be used to
    // fulfill the resource request.
    return response;
  })());
});
