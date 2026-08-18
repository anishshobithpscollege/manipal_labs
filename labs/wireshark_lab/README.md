# Wireshark Lab: HTTP vs HTTPS Password Sniffing

A deliberately vulnerable "Employee Portal" login page, served over both HTTP and HTTPS
from one container. Log in from a Kali attacker VM while you capture the traffic, then
compare what the sniffer sees.

Over HTTP (port 80) the username and password cross the network as plaintext, and you read
them straight out of the packet. Over HTTPS (port 443) the same login gets encrypted before
it leaves the host, so the sniffer gets scrambled bytes and nothing else.

> [!WARNING]
> This site keeps a hardcoded password in its source and hands a flag to anyone who logs
> in. It is insecure on purpose. Run it on an isolated lab network or on your own machine,
> never on the public internet.

## Architecture

Two machines on the same private network:

- **Attacker** — Kali Linux in a VM (UTM, VirtualBox, or VMware), running Wireshark and curl.
- **Target** — this Docker container on your host (Mac, Windows, or Linux), serving the login
  page on ports 80 and 443.

The container binds both ports on `0.0.0.0`, so the Kali VM reaches it at the host's IP on the
shared network. Give the VM a network mode that puts it on a subnet with the host:

- **Shared Network** (simplest on UTM / Apple Silicon) — the host shows up at `192.168.64.1`
  and the VM gets a `192.168.64.x` address. This guide uses `192.168.64.1` throughout.
- **Bridged** — works too, but both machines then sit on your physical LAN, so the target is
  the host's LAN address (something like `10.55.2.161`), not `192.168.64.1`. Swap the IP
  accordingly.

No VM handy? You can run everything on one box over loopback instead. See
[Single-machine alternative](#single-machine-alternative) at the end.

## What's in this folder

| File | Purpose |
|---|---|
| `Dockerfile` | Apache, PHP, and OpenSSL on top of the shared `mlabs-base` image. Turns on the SSL module and generates a self-signed certificate. |
| `docker-compose.yml` | Builds the image, runs it, maps ports `80` and `443`, and mounts the two PHP pages. |
| `index.php` | The login form. Handles the `POST` and sets the session on a correct password. |
| `dashboard.php` | Loads after a successful login. Holds the flag. |

Compose mounts the PHP pages as volumes, so you edit `index.php` or `dashboard.php` and
refresh. No rebuild.

## Prerequisites

- A host with Docker running and the Compose v2 plugin (`docker compose ...`). On Mac or
  Windows that means Docker Desktop open before you start.
- A Kali Linux VM with Wireshark, on a bridged or shared network adapter.

Kali ships Wireshark and curl. The [main README](../../README.md#running-the-labs) covers
installing Docker if it is missing.

## Quick start (on the host)

The image builds on a shared base image (`mlabs-base`). Clone the repo, build the base once,
then bring the lab up.

```bash
# 1. Clone the labs and build the shared base image.
#    The tag has to be exactly mlabs-base:latest. The lab Dockerfile depends on it.
git clone https://github.com/anishshobithpscollege/manipal_labs.git
cd manipal_labs
docker build -t mlabs-base:latest .

# 2. Start the lab from its folder.
cd labs/wireshark_lab
docker compose up -d --build
```

A warning that the compose `version` attribute is obsolete is harmless. Delete the `version:`
line from `docker-compose.yml` to silence it.

Confirm it runs and both ports are published:

```bash
docker compose ps
```

Find the host's IP on the network the VM shares. That address is the target:

```bash
ifconfig | grep "inet "         # macOS (no `ip` command there)
ip -4 addr show | grep inet     # Linux
ipconfig                        # Windows
```

Ignore `127.0.0.1` (loopback) and your physical-LAN address (something like `10.55.2.161`).
On a Mac with UTM, the shared-network address is usually `192.168.64.1`.

### Lab details

| | |
|---|---|
| Target (HTTP) | `http://<HOST_IP>/`, e.g. `http://192.168.64.1/` |
| Target (HTTPS) | `https://<HOST_IP>/`, accept the self-signed certificate |
| Login | `admin` / `supersecret123` |
| Flag | shown on the dashboard once you log in |

## Running the exercise (from Kali)

The container runs on the host, and Kali reaches it across the shared network. Everything the
login puts on the wire crosses Kali's interface, so you capture there.

### Confirm reachability

```bash
ping -c 3 192.168.64.1     # replace with your host IP
```

No reply means the network adapter mode is wrong or a host firewall is blocking. Fix that
before going on.

### Set up Wireshark

Kali installs Wireshark already. Let your own user capture without root:

```bash
sudo dpkg-reconfigure wireshark-common   # answer Yes to non-superuser capture
sudo usermod -aG wireshark $USER
newgrp wireshark
```

Launch it and start capturing on the VM's network interface (usually `eth0`):

```bash
wireshark &
```

A busy interface fills with noise. To keep only the lab traffic, put this in the capture
filter box before you start: `host 192.168.64.1 and (tcp port 80 or tcp port 443)`. Packets
appear live while capturing. Stop with the red square when you are done, or read them as they
arrive.

### Capture the HTTP login

1. Start a capture on `eth0`.
2. Send the login from a terminal:
   ```bash
   curl -d 'username=admin&password=supersecret123' -X POST http://192.168.64.1/index.php
   ```
3. In the display filter bar, type `http.request.method == "POST"` and press Enter. The POST
   appears, from your Kali IP to the host.
4. Select it and expand the HTML form fields in the detail pane, or right-click, then Follow,
   then HTTP Stream. The body reads `username=admin&password=supersecret123`. Nothing got
   cracked. It crossed the network that way.

To see the flag too, log in through Firefox in Kali over `http://192.168.64.1/`. The browser
follows the redirect to the dashboard, so the whole page comes back as plaintext. Filter with
`frame contains "FLAG"` and it turns up.

### Capture the HTTPS login

1. Keep capturing on `eth0`.
2. Send the same login over HTTPS. `-k` accepts the self-signed certificate, whose name is
   `localhost` and does not match the host IP:
   ```bash
   curl -k -d 'username=admin&password=supersecret123' -X POST https://192.168.64.1/index.php
   ```
3. Apply `http.request.method == "POST"`. Zero packets. No HTTP text left the host.
4. Clear the filter and type `tls`. The traffic shows up as TLS records, TLSv1.3 in this
   setup, labelled `Client Hello`, `Server Hello`, and `Application Data`.
5. Click an `Application Data` packet and read the ASCII column in the bytes pane. Random.
   Search `frame contains "supersecret123"` and you get nothing back.

### The same capture from the terminal

tcpdump reads the identical traffic without the GUI. Run one line on Kali, then fire the
matching curl from another terminal:

```bash
sudo tcpdump -i eth0 -A -s0 'host 192.168.64.1 and port 80'    # HTTP, password in the clear
sudo tcpdump -i eth0 -A -s0 'host 192.168.64.1 and port 443'   # HTTPS, only ciphertext
```

## Troubleshooting

| Symptom | Cause and fix |
|---|---|
| `curl: (60) SSL certificate problem` on the HTTPS request | You left off `-k`. The cert is self-signed and named `localhost`, so curl refuses it. Add `-k`. |
| `ping` to the host times out | The VM adapter is not on a subnet with the host, or a host firewall blocks it. Check the adapter mode, and confirm `docker compose ps` shows `0.0.0.0:80` and `0.0.0.0:443`. |
| `docker compose` cannot find `mlabs-base` | You skipped step 1. Build the base image with the exact tag `mlabs-base:latest` from the repo root. |
| No packets appear while capturing | Wrong interface. Capture the one whose subnet holds the host IP (`eth0` for the shared network), not `lo`. |
| `the attribute version is obsolete` warning | Harmless. Remove the `version:` line from `docker-compose.yml`. |

## Single-machine alternative

Skip the VM and run curl and Wireshark on the same box as Docker. This assumes Docker running
natively on that machine, for example Docker inside Kali itself. It does not apply to Docker
Desktop on macOS or Windows, where the container runs in a hidden VM and the host has no `lo`
that sees its traffic.

Capture the loopback interface `lo` instead of `eth0`, and target `localhost`:

```bash
curl -d 'username=admin&password=supersecret123' -X POST http://localhost/index.php
curl -k -d 'username=admin&password=supersecret123' -X POST https://localhost/index.php
```

Same filters, same result. The traffic never leaves the machine, so there is no `eth0` to
watch, only `lo`.

## Teardown

From `labs/wireshark_lab`:

```bash
docker compose down
```

Drop the built lab image too:

```bash
docker compose down --rmi local
```
