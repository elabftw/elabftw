# eLabFTW API v2 Openapi specification

## Description

This folder contains the `openapi.yaml` file describing the REST API v2 for eLabFTW, using [Openapi v3 specification](https://swagger.io/specification/).

## Validating

To see if the specification file has errors, we can use the validator in debug mode:

~~~bash
curl --data-binary @apidoc/v2/openapi.yaml -H 'Content-Type:application/yaml' https://validator.swagger.io/validator/debug
~~~

## Building the documentation

To generate the documentation from the yaml file, we can run [swagger-ui](https://github.com/swagger-api/swagger-ui) in a container:

~~~bash
docker run --restart always --name swagger -e SWAGGER_JSON=/c/openapi.yaml -v $(pwd)/apidoc/v2/:/c -p 8085:8080 -d swaggerapi/swagger-ui
~~~

Access it on http://localhost:8085. Reload the page to see recent changes to the file.

## Using the UI to test things

In order to test the API through the documentation's web UI, you will need to allow CORS requests to the local dev server (assuming elab.local:3148). Edit your local configuration file to allow CORS (see https://github.com/elabftw/elabapi-javascript-example#readme), click the Authorize button on the top right of the documentation and enter a valid API key (`apiKey4Test` will work for userid:1 if you generated the users with the populate script).

That's it, now you can use the web UI to perform requests!
