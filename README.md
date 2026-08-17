# Manipal Labs

Hands-on cybersecurity and networking labs. Each lab is a small, deliberately vulnerable
environment you build yourself, so you watch an attack or a defense work on live traffic
instead of reading about it.

> [!WARNING]
> Everything here is insecure on purpose. Plaintext passwords, hardcoded credentials,
> self-signed certificates. Keep it on an isolated lab network or inside Docker. Never
> expose any of it to the internet.

## Labs

| Lab | What it shows | Details |
|---|---|---|
| Wireshark, HTTP vs HTTPS | Sniff a login over HTTP and read the password in plaintext. Repeat over HTTPS and see only scrambled bytes. | [`labs/wireshark_lab/`](labs/wireshark_lab/) |

Each lab folder carries its own README with the run commands, credentials, and what to
look for.

## Running the labs

Everything runs from Kali Linux, and the labs themselves run in Docker on that Kali
machine. Get Kali one of two ways, whichever fits your hardware:

- Kali in a virtual machine, on top of the OS you already run.
- Kali as a native install, dual-booted alongside your OS.

Kali ships Wireshark and tcpdump, so the capture tools are already there. Once Kali is up
and Docker is installed, every lab is one `docker compose up`.

### Option 1: Kali in a virtual machine

Download a prebuilt Kali image from the [Kali downloads page](https://www.kali.org/get-kali/)
and import it. Kali publishes ready-made VirtualBox, VMware, and UTM/QEMU images, which
saves you an install from scratch. Which virtualization software you use depends on your
host OS:

| Host OS | Use |
|---|---|
| Windows | VirtualBox (free) or VMware Workstation Pro (free) |
| Linux | VirtualBox (free), VMware Workstation Pro, or KVM/virt-manager |
| macOS (Intel) | VirtualBox or VMware Fusion (free) |
| macOS (Apple Silicon, M1 to M4) | UTM or VMware Fusion, with the ARM64 Kali image |

### Option 2: Kali dual boot

Install Kali next to your current OS from the same [downloads page](https://www.kali.org/get-kali/).
It runs straight on the hardware with no virtualization overhead. Pick this for full
performance, or when a lab needs direct access to Wi-Fi or USB hardware.

### Install Docker on Kali

```bash
sudo apt update && sudo apt install -y docker.io docker-compose-v2
sudo systemctl enable --now docker
sudo usermod -aG docker $USER   # log out and back in so the group applies
```

Confirm it works:

```bash
docker run --rm hello-world
```

The root `Dockerfile` builds `mlabs-base:latest`, a shared base image of Ubuntu plus
common network tools. Individual labs build on top of it, so a new lab skips reinstalling
the basics.

## Networking

The lab captures on the Kali machine's own loopback, so a single VM needs no special
network setup. A fresh Kali VM already uses NAT on VirtualBox, NAT (VMnet8) on VMware, or
Shared Network on UTM, and that gives it internet for `apt` and Docker image pulls. Touch
the adapter only for the cases below.

### When you need to change it

- Reach the lab from your host or another device. Each lab publishes its own ports inside
  the VM, listed in that lab's `docker-compose.yml` and README. To open those ports from a
  browser on the host or a phone on your LAN, either add a NAT port-forward rule (a host
  port mapped to the guest's published port), or switch the VM to a Bridged or Host-Only
  adapter and browse to the VM's own IP.
- Run the container on one machine and capture from a second. Put both machines on the
  same Bridged or Host-Only network so they share a subnet, then point the browser and the
  capture at the server's IP.
- No internet inside the VM. Check that the adapter is set to NAT or Shared and enabled.

### The three modes

| Mode | Internet | Reachable from the host | Good for |
|---|---|---|---|
| NAT or Shared (default) | Yes | Only through a port-forward rule | Everyday internet access |
| Bridged | Yes | Yes, the VM gets an IP on your real LAN | Reaching the lab from other devices |
| Host-Only | No | Yes, over a private host-to-VM link | An isolated lab, safest for a vulnerable VM |

Bridged drops a deliberately vulnerable machine onto your real network, so reach for
Host-Only when you want reachability without that exposure. Whether VMs on a mode see each
other varies by tool, which the official guides below spell out.

### Set it per hypervisor

- VirtualBox. VM, Settings, Network, Adapter 1, "Attached to". Plain NAT keeps each VM
  isolated, so pick Bridged Adapter, Host-Only Adapter, or NAT Network when you want the
  VMs to see each other. Host-Only needs a network created first under File, Tools,
  Network Manager. Port forwarding sits under Advanced, Port Forwarding on the NAT adapter.
  Reference: [VirtualBox Manual, Virtual Networking](https://www.virtualbox.org/manual/topics/networkingdetails.html).
- VMware Workstation Pro and Fusion. VM, Settings, Network Adapter. Bridged is VMnet0, NAT
  is VMnet8, Host-only is VMnet1, and VMware's NAT already lets VMs on VMnet8 reach each
  other and the host. Edit subnets and forwarding in the Virtual Network Editor. Reference:
  [Understanding Common Networking Configurations](https://techdocs.broadcom.com/us/en/vmware-cis/desktop-hypervisors/workstation-pro/17-0/using-vmware-workstation-pro/configuring-network-connections/understanding-common-networking-configurations.html).
- UTM (macOS, Apple Silicon included). VM, Settings, Network, "Network Mode". Shared
  Network gives internet and host-to-guest access, Host Only drops internet, Bridged puts
  the guest on your LAN, and Emulated VLAN (QEMU backend) wires guests onto one virtual
  switch. Reference: [UTM Documentation, Network](https://docs.getutm.app/settings-qemu/devices/network/network/).

### Notes per host OS

- Windows and Linux. Bridged over Ethernet is reliable. Bridged over Wi-Fi works on most
  adapters, though a few drop it, so fall back to Host-Only if the VM gets no IP.
- macOS Intel. VirtualBox and VMware Fusion behave as above.
- macOS Apple Silicon. UTM Shared Network is the default and covers the lab. For a
  LAN-reachable VM, set Bridged to your active interface, usually `en0`.
- After any change, run `ip -brief addr` inside the VM to read its new IP, then `ping` it
  from the other machine to confirm the link.

### Official Kali VM guides

Kali ships ready-made images and setup docs, which beat installing from scratch:

- [Import a pre-made Kali VirtualBox VM](https://www.kali.org/docs/virtualization/import-premade-virtualbox/)
- [Import a pre-made Kali VMware VM](https://www.kali.org/docs/virtualization/import-premade-vmware/)
- [Installing VirtualBox Guest Additions](https://www.kali.org/docs/virtualization/install-virtualbox-guest-additions/)
- [Installing VMware Tools](https://www.kali.org/docs/virtualization/install-vmware-guest-tools/)
- [Kali downloads and every virtualization guide](https://www.kali.org/get-kali/)
