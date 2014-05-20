-------------------------------------------------------------------------

BigUpload 

version 1.2    
Created by: Sean Thielen <sean@p27.us>    
[BigUpload: Uploading really big files in the browser](http://p27.us/2013/03/bigupload-uploading-really-big-files-in-the-browser/)

-------------------------------------------------------------------------

BigUpload is a tool for handling large file uploads (tested up to 2GB) through the browser.

![Screenshot](http://i.imgur.com/vESk5dp.png)

-------------------------------------------------------------------------

It uses the HTML5 FileReader library to split large files into manageable chunks,
and then sends these chunks to the server one at a time using an XmlHttpRequest.

The php script then pieces these chunks together into one large file.

Because the chunks are all the same size, it is easy to calculate an accurate progress bar
and a fairly accurate time remaining variable.

This tool is capable of handling file uploads of up to 2GB in size, without the need to tweak
the max_upload and timeout variables on your httpd.

This tool only works on Chrome and Firefox, but falls back to a normal file upload form on other browsers.

If you want to deploy this as-is, the variables you need to worry about are in the top of        
* js/bigUpload.js    
* inc/bigUpload.php    

And you need to be sure to make /BigUpload/files and /BigUpload/files/tmp writeable


Please feel free to contribute and use this in your projects!

-------------------------------------------------------------------------

v 1.2.1
* Option to pass additional parameters to the upload script
* Optional callback function on success
* Contributed by Matt (atomiku)

v 1.2    
* Cleaned up the code quite a lot    
* Added pause/resume functionality    
* Added fallback for unsupported browsers

v 1.0.1    
* Added time remaining calculator    
* Response from php script is now a json object, allowing for error processing    
* Minor script changes and bugfixes    
* Better comments

v 1.0.0    
* Initial version