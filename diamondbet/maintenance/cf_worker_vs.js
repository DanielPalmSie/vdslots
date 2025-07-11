/**
 Script version 2.5:
 1. Changing maintenanceMode to true will enable manually maintenance.
 2. If maintenanceMode is false, it will do the request, if error 500 - 599 is returned, shows maintenance (needs to timeout)
 3. IPs in trusted list, will bypass maintenance in both cases
 4. Allow reqeuets to mts.videoslots.com
 5. Fetch the HTML resource from AZURE URL instead of static.
 6. Return code 503 with maintenance response to stop Google crawling / caching
 **/

async function callToMaintenancePage(modifiedHeaders) {
    // Return maintenance page
    const maintenance = await fetch("https://vspublic.blob.core.windows.net/images/videoslots.html");
    // Return 503 to stop Google crawling / caching
    return new Response(maintenance.body, {
        status: 503,
        statusText: 'Maintenance',
        headers: modifiedHeaders,
    });
}

async function fetchAndReplace(request) {

    // Default should be false,
    // if changed to true, will manually enable the maintenance
    let isMaintenanceMode = false;

    const white_list = [
        '194.204.105.66',
        '212.56.137.74',
        '195.158.94.218',
        '212.56.135.66',
    ];

    const allowedDomains = [
        'mts.videoslots.com'
    ];

    let isTrustedUser = false;
    // exclude certain domains
    let isDomainAllowed = false;

    let modifiedHeaders = new Headers()
    modifiedHeaders.set('Content-Type', 'text/html');
    modifiedHeaders.append('Cache-Control', 'no-cache, no-store');

    console.log(request.headers.get("cf-connecting-ip"));

    const url = new URL(request.url);
    console.log(url.host);

    // Allow requests to MTS for withdrawals on both brands
    isDomainAllowed = (allowedDomains.indexOf(url.host) >= 0);


    // If someone has trusted IP, allow to view page
    isTrustedUser = (white_list.indexOf(request.headers.get("cf-connecting-ip")) >= 0);

    // If maintenance is on, show for non trusted users only
    if (!isDomainAllowed && !isTrustedUser && isMaintenanceMode === true) {
        return await callToMaintenancePage(modifiedHeaders);
    }

    // Continue with the request
    const response = await fetch(request);
    console.log(response.status);

    // Server Errors from 500 to 599
    if (!isDomainAllowed && !isTrustedUser && response.status >= 500 && response.status <= 599) {
        return await callToMaintenancePage(modifiedHeaders);
    }

    // Default response if no Server Errors
    return response;
}

addEventListener("fetch", event => { event.respondWith(fetchAndReplace(event.request)) });