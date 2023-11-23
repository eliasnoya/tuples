# Tuples Framework

Tuples is a lightweight framework, weighing in at just over 100KB, designed to provide essential features like an HTTP router, PSR-7 support, and a simplified dependency injector. It is crafted with the intention of being utilized seamlessly with RoadRunner while remaining compatible with traditional PHP implementations.

## Features

- **HTTP Router:** Efficient routing for handling web requests.
- **PSR-7 Compatibility:** Conforms to the PSR-7 standard for HTTP message interfaces.
- **Dependency Injection:** A simplified dependency injector to manage class dependencies.

## Philosophy

Tuples follows the philosophy of being minimally invasive, providing only the fundamental functionalities needed for a web application. The goal is to keep the codebase lightweight, making it suitable for a variety of use cases. Whether you are working with RoadRunner (our favorite) or a standard PHP environment, Tuples aims to be versatile and easy to integrate.

## Installation

```bash
composer create-project eliasnoya/tuples-framework
# Accept create a .rr.yaml and then
# in Linux
./rr serve
# or in Windows
./rr.exe serve
```
