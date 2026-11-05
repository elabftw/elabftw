# Sidecar that restricts outbound traffic of the elabftw dev container
# It shares the network namespace of the web container, so the rules it sets apply there
# https://hub.docker.com/layers/library/alpine/3.24.3/images/sha256-6708ca119efec5c3fdcad5dc95d8a28a529aad8debc95872b7e12b3941882f55
FROM alpine@sha256:be2656061b536c1883c4034790e21d0a09c5aaaf51968b89368a3c0371ea2c9a

RUN apk add --no-cache bind-tools iproute2 iptables ip6tables
COPY egress-firewall.sh /usr/local/bin/egress-firewall.sh
ENTRYPOINT ["/usr/local/bin/egress-firewall.sh"]
