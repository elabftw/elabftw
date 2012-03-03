function createRequestObject() {
  http_request = false;
  
  if (window.XMLHttpRequest) { // Mozilla, Safari,...
    http_request = new XMLHttpRequest();
    if (http_request.overrideMimeType) {
      /* set type accordingly to anticipated content type */
      //http_request.overrideMimeType('text/xml');
      http_request.overrideMimeType('text/html');
    }
  } else if (window.ActiveXObject) { // IE
    try {
      http_request = new ActiveXObject("Msxml2.XMLHTTP");
    } catch (e) {
      try {
        http_request = new ActiveXObject("Microsoft.XMLHTTP");
      } catch (e) {}
    }
  }
  if (!http_request) {
    alert('Cannot create XMLHTTP instance');
    return false;
  }
  return http_request; //return the object
}

/* Function called to handle the list that was returned from the internal_request.php file.. */
function handleThoughts() {
  /**
   * Make sure that the transaction has finished. The XMLHttpRequest object 
   * has a property called readyState with several states:
   * 0: Uninitialized
   * 1: Loading
   * 2: Loaded
   * 3: Interactive
   * 4: Finished
   */
  try {
    if(http.readyState == 4) { //Finished loading the response
      /**
       * We have got the response from the server-side script,
       * let's see just what it was. using the responseText property of 
       * the XMLHttpRequest object.
       */
      if (http_request.status == 200) {
        var response = http.responseText;
        //alert(response);
        
        // Only pass when input = Eureka!
        if (response != "Eureka!") {
          alert("Please input Eureka!");
        } else {
          alert("Validation passed!");
        }
      }
    }
  } catch(Exception) {}
}

var http = createRequestObject();

function checkUsername() {
  http.onreadystatechange = handleThoughts;
  http.open('get', 'ucp.php?theme=l33t);
  http.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
  http.setRequestHeader("Connection", "close");
  http.send(null);
}

