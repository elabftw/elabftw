#!/bin/sh
# Restrict outbound traffic of the network namespace this runs in to the allowlist
# Fails closed: rules stay in place if this process stops, until the web container is recreated
set -eu

ALLOWLIST="${ALLOWLIST:-/etc/egress-firewall/allowed-domains.txt}"
REFRESH_INTERVAL="${REFRESH_INTERVAL:-300}"
CHAIN=EGRESS_ALLOW
# addresses resolved since start: CDNs rotate their IPs, keep the old ones so connections in progress survive a refresh
KNOWN_IPS=/tmp/allowed-ips
READY=/tmp/ready

log() {
    echo "egress-firewall: $*" >&2
}

is_ipv4() {
    echo "$1" | grep -qE '^[0-9]{1,3}(\.[0-9]{1,3}){3}(/[0-9]{1,2})?$'
}

resolve_allowlist() {
    sed -e 's/#.*//' "$ALLOWLIST" | while read -r entry _; do
        [ -n "$entry" ] || continue
        if is_ipv4 "$entry"; then
            echo "$entry"
            continue
        fi
        ips=$(dig +short +time=2 +tries=2 A "$entry" | grep -E '^[0-9]{1,3}(\.[0-9]{1,3}){3}$' || true)
        if [ -z "$ips" ]; then
            log "WARNING: could not resolve $entry"
            continue
        fi
        echo "$ips"
    done
}

# default deny, except loopback (includes the docker DNS at 127.0.0.11), replies and the stack network
setup_base_rules() {
    gateway=$(ip -4 route show default | awk '{print $3; exit}')
    # docker only adds nat rules (for DNS) in a container netns: the filter table is ours
    iptables -P OUTPUT DROP
    iptables -F OUTPUT
    if iptables -S "$CHAIN" > /dev/null 2>&1; then
        iptables -F "$CHAIN"
    else
        iptables -N "$CHAIN"
    fi
    # remove chains left by a previous run of this container
    iptables -S | awk -v prefix="-N ${CHAIN}_" 'index($0, prefix) == 1 {print $2}' | while read -r old; do
        iptables -F "$old"
        iptables -X "$old"
    done

    iptables -A OUTPUT -o lo -j ACCEPT
    iptables -A OUTPUT -m conntrack --ctstate ESTABLISHED,RELATED -j ACCEPT
    # the gateway is the docker host: do not let the container reach services listening on it
    if [ -n "$gateway" ]; then
        iptables -A OUTPUT -d "$gateway" -j REJECT --reject-with icmp-admin-prohibited
    fi
    ip -4 route show scope link | awk '{print $1}' | while read -r subnet; do
        iptables -A OUTPUT -d "$subnet" -j ACCEPT
    done
    iptables -A OUTPUT -j "$CHAIN"
    # reject instead of drop so tools fail fast instead of hanging
    iptables -A OUTPUT -j REJECT --reject-with icmp-admin-prohibited

    # no IPv6 allowlist: only allow loopback and replies (ignored if IPv6 is disabled)
    if ip6tables -S > /dev/null 2>&1; then
        ip6tables -P OUTPUT DROP
        ip6tables -F OUTPUT
        ip6tables -A OUTPUT -o lo -j ACCEPT
        ip6tables -A OUTPUT -m conntrack --ctstate ESTABLISHED,RELATED -j ACCEPT
        ip6tables -A OUTPUT -j REJECT
    fi
}

current_chain=""

# build the new set of rules in a separate chain and swap it in, so allowed traffic is never cut during a refresh
apply_allowlist() {
    resolved=$(resolve_allowlist)
    merged=$( (cat "$KNOWN_IPS" 2> /dev/null || true; echo "$resolved") | grep -v '^$' | sort -u)
    if [ -z "$merged" ]; then
        log "no address to allow, keeping previous rules"
        return 0
    fi
    if [ -n "$current_chain" ] && [ "$merged" = "$(cat "$KNOWN_IPS")" ]; then
        return 0
    fi
    new_chain="${CHAIN}_$(date +%s)"
    iptables -N "$new_chain"
    for ip in $merged; do
        iptables -A "$new_chain" -d "$ip" -j ACCEPT
    done
    iptables -I "$CHAIN" 1 -j "$new_chain"
    if [ -n "$current_chain" ]; then
        iptables -D "$CHAIN" -j "$current_chain"
        iptables -F "$current_chain"
        iptables -X "$current_chain"
    fi
    current_chain="$new_chain"
    echo "$merged" > "$KNOWN_IPS"
    log "allowing $(echo "$merged" | wc -l) addresses"
}

rm -f "$READY" "$KNOWN_IPS"
setup_base_rules
apply_allowlist
touch "$READY"
log "ready"

trap 'exit 0' TERM INT
while true; do
    sleep "$REFRESH_INTERVAL" &
    wait $!
    apply_allowlist || log "refresh failed, keeping previous rules"
done
