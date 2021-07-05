# Getting started

Go to your user control panel and create an API key for your account. This key allows access to your data, keep it secret and secure!

# Select your tool

The API is an HTTP REST API and can be queried with different tools, as long as they speak HTTP.

## Use a shell script with cURL

The simplest way to get started is to use a shell script with `curl` to make the requests.

`curl`, is probably already installed on your computer. Otherwise, use your package manager to install it.

Note: if your installation has a self-signed certificate, add the `-k` flag for `curl`.

### A typical shell script

~~~bash
token="55cde...403157"
endpoint="https://elab.example.org/api/v1"
# get experiment with id 42
json=$(curl -s -H "Authorization: $token" ${endpoint}/experiments/42)
echo $json
~~~

You will find examples of `curl` usage in the documentation below.

## Use Python

A python library also exists to facilitate working in a python environment: [elabapy](https://pypi.org/project/elabapy/)

You can install `elabapy` using `pip`

~~~bash
pip install --user -U elabapy
~~~

or via `conda`:

~~~bash
conda skeleton pypi elabapy
conda-build elabapy
~~~

or via sources:

~~~bash
git clone https://github.com/elabftw/elabapy
cd elabapy
python setup.py install
~~~

### A typical python script

~~~python
import elabapy
import json
from requests.exceptions import HTTPError
# initialize the manager with an endpoint and your token
manager = elabapy.Manager(endpoint="https://elab.example.org/api/v1/", token="3148")
# get experiment with id 42
try:
    exp = manager.get_experiment(42)
    print(json.dumps(exp, indent=4, sort_keys=True))
# if something goes wrong, the corresponding HTTPError will be raised
except HTTPError as e:
    print(e)
~~~

Note: if your installation has a self-signed certificate, use `verify=False` when instantiating the Manager.

You will find examples of `elabapy` usage in the documentation below.
