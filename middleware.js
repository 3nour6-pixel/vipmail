export async function middleware(request) {

  const originResponse = await fetch(request);


  const newResponse = new Response(originResponse.body, originResponse);


  const pathname = request.nextUrl.pathname;


  newResponse.headers.set('X-Content-Type-Options', 'nosniff');
  newResponse.headers.set('X-Frame-Options', 'DENY');
  newResponse.headers.set('X-XSS-Protection', '1; mode=block');
  newResponse.headers.set('Referrer-Policy', 'strict-origin-when-cross-origin');




  if (/\.(css|js|woff|woff2|ttf|eot)$/.test(pathname)) {
    newResponse.headers.set('Cache-Control', 'public, max-age=31536000, immutable');
  } 

  else if (/\.(jpg|jpeg|png|gif|webp|svg|ico)$/.test(pathname)) {
    newResponse.headers.set('Cache-Control', 'public, max-age=2592000');
  }

  else if (request.headers.get('Accept')?.includes('text/html') && !pathname.startsWith('/api/')) {
    newResponse.headers.set('Cache-Control', 'no-cache, no-store, must-revalidate');
    newResponse.headers.set('Pragma', 'no-cache');
    newResponse.headers.set('Expires', '0');
  }

  return newResponse;
}
