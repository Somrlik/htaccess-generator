HtaccessGenerator
-----------------

### Installation
 - Clone this repo
 - `composer install`
 - Run to generate example `credentials.yml`

### Usage
`php generate.php [path]`

 - path - fully qualified path of directory to be protected
  
  
If there is a `.htaccess` file in the remote directory _and it is readable_ the additions
will be appended and the whole file can still be copied.  

Since apache needs full path name in htaccess for htpasswd location, the `path` must be 
a full path, e.g. `/var/www/document-root/`

The files will be in `/generated`, so you can `cp -a generated/. /var/www/document-root` 

#### A one liner for current directory

``php generate.php `pwd` && cp -a generated/. `pwd` ``

### License
MIT or Beerware