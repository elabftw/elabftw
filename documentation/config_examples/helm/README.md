# Helm

The eLabFTW Helm chart is maintained in the
[tugraz-rdm/elabftw-helm](https://github.com/tugraz-rdm/elabftw-helm) repository.

> ⚠️ **Beta Software**
>
> The Helm chart is an independent deployment project maintained outside the core eLabFTW repository.
> It is currently in **Beta status** and may contain breaking changes between releases.
>
> Users should evaluate the chart carefully before using it in production environments.
> Support for the Helm chart is provided through the
> [tugraz-rdm/elabftw-helm](https://github.com/tugraz-rdm/elabftw-helm) repository.
> For issues related to the eLabFTW application itself, please use the appropriate eLabFTW support channels.

This documentation provides examples for deploying eLabFTW using the external Helm chart.

For detailed configuration options, refer to the Helm chart documentation:

- [Configuration guide](https://github.com/tugraz-rdm/elabftw-helm#elabftw-configuration)
- [Default `values.yaml`](https://github.com/tugraz-rdm/elabftw-helm/blob/main/charts/elabftw/values.yaml)

Contributions to the Helm chart are welcome. If you encounter an issue or would like to propose an improvement, please open an issue or submit a pull request in the Helm chart repository.

---

## Basic Installation with Default Configuration

Before installing the chart, create the required Kubernetes Secrets.

Create the namespace:

```bash
kubectl create namespace elabftw
```

Create the eLabFTW application Secret:

```bash
kubectl create secret generic elabftw-secret \
  --namespace elabftw \
  --from-literal=secret-key='your-elabftw-secret-value'
```

Create the MySQL credentials Secret:

```bash
kubectl create secret generic elabftw-mysql-secret \
  --namespace elabftw \
  --from-literal=mysql-root-password='your-root-password' \
  --from-literal=mysql-replication-password='your-replication-password' \
  --from-literal=mysql-password='your-database-password'
```

Install the eLabFTW Helm chart using the existing Secrets:

```bash
helm install elabftw \
  oci://ghcr.io/tugraz-rdm/elabftw \
  --namespace elabftw \
  --set elabftw.secrets.existingSecret=elabftw-secret \
  --set elabftw.secrets.secretKey=secret-key \
  --set mysql.auth.existingSecret=elabftw-mysql-secret
```

## Custom Values Installation

Create a custom `values.yaml` file:

```yaml
elabftw:
  siteUrl: https://elabftw.example.com
  serverName: elabftw.example.com
  features:
    autoDbInit: true
    autoDbUpdate: true

  addons:
    chemPlugin:
      enabled: true
    opencloning:
      enabled: true

  secrets:
    secretKey: "REPLACE_WITH_RANDOM_SECRET_KEY"

mysql:
  enabled: true
  auth:
    rootPassword: "REPLACE_WITH_MYSQL_ROOT_PASSWORD"
    password: "REPLACE_WITH_MYSQL_PASSWORD"
    database: "elabftw"
    username: "elabftw"

redis:
  enabled: false
  auth:
    enabled: true
    password: "REPLACE_WITH_REDIS_PASSWORD"
```

> Replace all `REPLACE_WITH_*` values before installing. Do not deploy with the example values.
> The chart will create the required Kubernetes Secrets during installation using the values provided above.
> For production environments, consider using pre-created Kubernetes Secrets and reference them instead of storing credentials in `values.yaml`.
>
> The example above enables the **ChemPlugin** and **OpenCloning** addons. Redis authentication is configured, but Redis is disabled by default. Set `redis.enabled: true` if Redis should be deployed.

Install the chart with the custom configuration:

```bash
helm install elabftw \
  oci://ghcr.io/tugraz-rdm/elabftw \
  --namespace elabftw \
  --create-namespace \
  --values values.yaml
```
