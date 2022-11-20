# eLabFTW API v2 Openapi specification

## Description

This folder contains `openapi.yaml` file describing the REST API v2 for eLabFTW.

## Validating

To see if the specification file has errors, we can use the validator in debug mode:

~~~bash
curl --data-binary @apidoc/v2/openapi.yaml -H 'Content-Type:application/yaml' https://validator.swagger.io/validator/debug",
~~~

## Building the documentation

To generate the documentation from the yaml file, we can run `swagger-ui` in a container:

~~~bash
docker run --restart always --name swagger -e SWAGGER_JSON=/c/openapi.yaml -v $(pwd)/apidoc/v2/:/c -p 8085:8080 -d swaggerapi/swagger-ui
~~~

Access it on http://localhost:8085.
