import { NextResponse } from 'next/server';

export function middleware(request) {
  const response = NextResponse.next();
  response.headers.set('X-Content-Type-Options', 'nosniff');
  response.headers.set('X-Frame-Options', 'DENY');
  response.headers.set('X-XSS-Protection', '1; mode=block');
  response.headers.set('Referrer-Policy', 'strict-origin-when-cross-origin');

  const pathname = request.nextUrl.pathname;
  if (/\.(css|js|woff|woff2|ttf|eot)$/.test(pathname)) {
    response.headers.set('Cache-Control', 'public, max-age=31536000, immutable');
  } 
  else if (/\.(jpg|jpeg|png|gif|webp|svg|ico)$/.test(pathname)) {
    response.headers.set('Cache-Control', 'public, max-age=2592000');
  } 
  else if (request.headers.get('Accept')?.includes('text/html')) {
    response.headers.set('Cache-Control', 'no-cache, no-store, must-revalidate');
    response.headers.set('Pragma', 'no-cache');
    response.headers.set('Expires', '0');
  }

  return response;
}
