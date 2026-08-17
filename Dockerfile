FROM ubuntu:22.04

# Prevent interactive prompts during package installation
ENV DEBIAN_FRONTEND=noninteractive

# Install essential network tools for penetration testing
RUN apt-get update && \
    apt-get install -y curl wget net-tools iproute2 && \
    apt-get clean
