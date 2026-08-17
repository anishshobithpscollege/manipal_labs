# Wireshark Lab: HTTP vs HTTPS Password Sniffing

A deliberately vulnerable "Employee Portal" login page, served over both HTTP and HTTPS
from one container. Log in while you capture the traffic, then compare what a network
sniffer sees.

Over HTTP (port 80) the username and password cross the wire as plaintext, and you read
them straight out of the packet. Over HTTPS (port 443) the same login gets encrypted
before it leaves the machine, so the sniffer gets scrambled bytes and nothing else.

> [!WARNING]
> This site keeps a hardcoded password in its source and hands a flag to anyone who logs
> in. It is insecure on purpose. Run it on an isolated lab network or on your own
> machine, never on the public internet.

## What's in this folder

| File | Purpose |
|---|---|
| `Dockerfile` | Apache, PHP, and OpenSSL on top of the shared `mlabs-base` image. Turns on the SSL module and generates a self-signed certificate. |
| `docker-compose.yml` | Builds the image, runs it, maps ports `80` and `443`, and mounts the two PHP pages. |
| `index.php` | The login form. Handles the `POST` and sets the session on a correct password. |
| `dashboard.php` | Loads after a successful login. Holds the flag. |

Compose mounts the PHP pages as volumes, so you edit `index.php` or `dashboard.php` and
refresh the browser. No rebuild.

## Prerequisites

- Docker with the Compose v2 plugin (`docker compose ...`).
- Wireshark, or `tcpdump`, to capture the traffic.

Kali ships all of these. The [main README](../../README.md#running-the-labs) covers
installing Docker if it is missing.

## Quick start

The image builds on a shared base image (`mlabs-base`). Build the base once, then bring
the lab up.

```bash
# 1. From the repository root, build the shared base image.
#    The tag has to be exactly mlabs-base:latest. The lab Dockerfile depends on it.
docker build -t mlabs-base:latest .

# 2. Start the lab from this folder.
cd labs/wireshark_lab
docker compose up -d
```

Confirm it runs and answers:

```bash
docker compose ps
curl -s  http://localhost/  | head -3   # HTTP
curl -sk https://localhost/ | head -3   # HTTPS, -k accepts the self-signed cert
```

Both return HTML.

### Lab details

| | |
|---|---|
| URL (HTTP) | `http://localhost/` |
| URL (HTTPS) | `https://localhost/`, accept the self-signed certificate warning |
| Login | `admin` / `supersecret123` |
| Flag | shown on the dashboard once you log in |

## Running the exercise

The site and curl both sit on the same Kali machine, so you capture the loopback
interface `lo` while you send the login, and every byte curl puts on the wire crosses
right in front of Wireshark.

### Set up Wireshark

Kali installs Wireshark already. Let your own user capture without root:

```bash
sudo dpkg-reconfigure wireshark-common   # answer Yes to non-superuser capture
sudo usermod -aG wireshark $USER
newgrp wireshark
```

Launch it:

```bash
wireshark &
```

### Capture the HTTP login

1. In the interface list, type `tcp port 80 or tcp port 443` into the capture filter box,
   then double-click `Loopback: lo` to start capturing.
2. In a terminal, post the login, then pull the dashboard with the session cookie curl
   saved:
   ```bash
   curl -c cookies.txt -d 'username=admin&password=supersecret123' http://localhost/index.php
   curl -b cookies.txt http://localhost/dashboard.php
   ```
3. Back in Wireshark, click the red square to stop the capture.
4. In the display filter bar at the top, type `http.request.method == "POST"` and press
   Enter. One packet appears.
5. Right-click that packet, then Follow, then HTTP Stream. The request body reads
   `username=admin&password=supersecret123`. Nothing got cracked. It crossed the wire
   that way.

Try `frame contains "FLAG"` next. The dashboard flag turns up too, since the server sent
the whole page back as plaintext.

### Capture the HTTPS login

1. Start a fresh capture on `lo` with the same capture filter.
2. Run the same two requests over HTTPS. The `-k` flag accepts the self-signed
   certificate:
   ```bash
   curl -k -c cookies.txt -d 'username=admin&password=supersecret123' https://localhost/index.php
   curl -k -b cookies.txt https://localhost/dashboard.php
   ```
3. Stop the capture and apply `http.request.method == "POST"` again. Zero packets. No
   HTTP text ever left the machine.
4. Clear that filter and type `tls`. The traffic shows up now, labelled `Client Hello`,
   `Server Hello`, and `Application Data`.
5. Click an `Application Data` packet and read the ASCII column in the bytes pane. Random.
   Search `frame contains "supersecret123"` and you get nothing back.

### The same capture from the terminal

tcpdump reads the identical traffic without the GUI. Run one line, then fire the matching
curl request from another terminal:

```bash
sudo tcpdump -i lo -A -s0 'port 80'    # over HTTP, the password prints in the clear
sudo tcpdump -i lo -A -s0 'port 443'   # over HTTPS, only ciphertext
```

## Teardown

```bash
docker compose down
```

Drop the built lab image too:

```bash
docker compose down --rmi local
```
