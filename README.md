# Tuples Framework

Tuples is a lightweight framework, weighing in at just over 50KB, designed to provide essential features like an HTTP router, PSR-7 support, and a simplified dependency injector. 
It is crafted with the intention of being utilized seamlessly with RoadRunner while remaining compatible with traditional PHP implementations.

## Features

- **HTTP Router:** Efficient routing for handling web requests.
- **Request and Response Wrappers:** With PSR-7 Compatibility
- **Dependency Injection:** A simplified dependency injector to manage class dependencies.
- **Mutliples Database Connections:** A simple PDO Wrapper (for more complex use your can inject Doctrine or some similar packadge)
- **RoadRunner Worker:** In the skeleton app your have the worker and the basic configuration of RoadRunner HTTP Server

## Philosophy

Tuples follows the philosophy of being minimally invasive, providing only the fundamental functionalities needed for a web application. The goal is to keep the codebase lightweight, making it suitable for a variety of use cases. Whether you are working with RoadRunner (our favorite) or a standard PHP environment, Tuples aims to be versatile and easy to integrate.

## Create a Skeleton app with all the depedencies and RoadRunner Server

```bash
composer create-project eliasnoya/tuples-project myapp
cd myapp
# in Linux
./rr serve
# In Windows
./rr.exe serve
```
