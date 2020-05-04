# Getting started

Go to your user control panel and create an API key for your account. This key allows access to your data, keep it secret and secure!

# Select your tool

The API is an HTTP REST API and can be queried with different tools, as long as they speak HTTP.

## Use cURL

`curl`, is probably already installed on your computer. Otherwise, use your package manager to install it.

Note: if your installation has a self-signed certificate, add the `-k` flag for `curl`.

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

You will find examples of `elabapy` usage in the documentation below.
