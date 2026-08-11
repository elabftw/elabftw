---
sidebar_position: 10
title: Using the API from a browser
---

# Using eLabFTW's API from JavaScript

It is possible to use a web application to interact with eLabFTW's API, but eLabFTW needs to be configured to accept connections from that external application. See [documentation on Cross-Origin Resource Sharing](https://developer.mozilla.org/en-US/docs/Web/HTTP/Guides/CORS).

## Server configuration

On the server-side, there are some `ENV` variables that need to be set:

~~~yaml
- environment:
    # the value must be the origin of the page making the request
    - ALLOW_ORIGIN=https://app.example.com
    # comma separated list of methods (use GET, POST, PATCH, DELETE for full api access)
    - ALLOW_METHODS=GET,POST,PATCH
    - ALLOW_HEADERS=Content-Type, Authorization
~~~

This needs to be configured by an instance Sysadmin with access to the container deployment configuration, and the container must be restarted for the changes to take effect.

## Making a query from a browser

This is an example page showing how to interact with the API from the browser. It is storing the API key in `localStorage`, not great, not terrible.

~~~html
<!doctype html>
<html lang='en'>
<head>
  <meta charset='utf-8'>
  <title>eLabFTW API demo</title>
</head>

<body>
  <h1>Accessing the eLabFTW API through JavaScript</h1>

  <h2>Experiments</h2>

  <button id='getExp' type='button'>Read experiments</button>
  <button id='forgetKey' type='button'>Forget API key</button>

  <pre id='output'></pre>

  <script>
    const elabftwServerUrl = 'https://elab.example.org/api/v2';
    const apiKeyStorageKey = 'elabftwApiKey';

    const output = document.getElementById('output');

    function getApiKey() {
      let apiKey = localStorage.getItem(apiKeyStorageKey);

      if (!apiKey) {
        apiKey = prompt('Enter your eLabFTW API key:');

        if (!apiKey) {
          throw new Error('An API key is required.');
        }

        apiKey = apiKey.trim();
        localStorage.setItem(apiKeyStorageKey, apiKey);
      }

      return apiKey;
    }

    async function getExperiments() {
      output.textContent = 'Loading...';

      try {
        const response = await fetch(`${elabftwServerUrl}/experiments`, {
          method: 'GET',
          headers: {
            'Accept': 'application/json',
            'Authorization': getApiKey(),
          },
        });

        if (!response.ok) {
          if (response.status === 401) {
            localStorage.removeItem(apiKeyStorageKey);
          }

          throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }

        const experiments = await response.json();

        output.textContent = JSON.stringify(experiments, null, 2);
      } catch (error) {
        output.textContent = `Request failed: ${error.message}`;
      }
    }

    document.getElementById('getExp').addEventListener('click', getExperiments);

    document.getElementById('forgetKey').addEventListener('click', () => {
      localStorage.removeItem(apiKeyStorageKey);
      output.textContent = 'API key removed.';
    });
  </script>
</body>
</html>
~~~

This should be enough to get you started!
