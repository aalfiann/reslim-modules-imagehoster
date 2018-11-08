### Detail module information

1. Namespace >> **modules/imagehoster**
2. Zip Archive source >> 
    https://github.com/aalfiann/reSlim-modules-imagehoster/archive/master.zip

### How to Integrate this module into reSlim?

1. Download zip then upload to reSlim server to the **modules/**
2. Extract zip then you will get new folder like **reSlim-modules-imagehoster-master**
3. Rename foldername **reSlim-modules-imagehoster-master** to **imagehoster**
4. Done

### How to Integrate this module into reSlim with Packager?

1. Make AJAX GET request to >>
    http://**{yourdomain.com}**/api/packager/install/zip/safely/**{yourusername}**/**{yourtoken}**/?lang=en&source=**{zip archive source}**&namespace=**{modul namespace}**

### How to integrate this module into database?
This module is require integration to the current database.

1. Make AJAX GET request to >>
    http://**{yourdomain.com}**/api/imagehoster/install/**{yourusername}**/**{yourtoken}**

### Security Tips
After successful integration database, you must remove the **install** and **uninstall** router.  
Just make some edit in the **imagehoster.router.php** file manually.

---

### Description
Simple Host Your Images for free and unlimited by using 3rd party application. Save your time and avoid to maintenance the million of images from now on.

### Feature
- Upload Image
- History image uploaded
- You can improve this feature by read through original API Documentation of Imgur at [here](https://apidocs.imgur.com).

### Requirement
- This module is require [ProxyList](https://github.com/aalfiann/reslim-modules-proxylist) module installed on reSlim.
- You have to register to Imgur.com
- To get **Client-ID**, You need to register your application to https://api.imgur.com/oauth2/addclient

### Known limitations
 - Each application can allow approximately 1,250 uploads per day or approximately 12,500 requests per day. If the daily limit is hit five times in a month, then the app will be blocked for the rest of the month. The solution is you have to buy commercial version or create many application id from Imgur and use Rotate Proxy (Note: connection using proxy is not smooth and unstable).
 - Read the API documentation in **ImageHoster.postman_collection.json** for more detail how we did bypass the rate limit of Imgur.

## Disclaimer
This **ImageHoster** module is use **Imgur API** as Free user and we can not guarantee for your files will be saved forever.