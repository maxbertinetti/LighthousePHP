# LighthousePHP

![LighthousePHP Logo](src/public/assets/img/Logo.png)

## Introduction

Lighthouse is a procedural PHP framework with zero OOP and no Composer dependency. It uses file-based routing and is designed to deliver optimal performance, security, and SEO out of the box, targeting 100/100 Lighthouse scores under correct usage.

## Install

Install the latest release globally:

```sh
curl -fsSL https://raw.githubusercontent.com/maxbertinetti/LighthousePHP/main/scripts/install.sh | sh -s -- maxbertinetti/LighthousePHP
```

Install a specific release tag or version:

```sh
curl -fsSL https://raw.githubusercontent.com/maxbertinetti/LighthousePHP/main/scripts/install.sh | sh -s -- maxbertinetti/LighthousePHP tag:0.1.0
```

Install a branch snapshot explicitly:
git
```sh
curl -fsSL https://raw.githubusercontent.com/maxbertinetti/LighthousePHP/main/scripts/install.sh | sh -s -- maxbertinetti/LighthousePHP branch:main
```

Create a new project after installing:

```sh
lighthouse new my-app
cd my-app
lighthouse serve
```

In generated applications, `core/` is framework-managed code and should not be modified directly.
