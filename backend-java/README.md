# backend-java

Reactive backend for Resto-lite, being migrated progressively from the Laravel backend to
Java and Spring Boot.

This directory is the first migration phase. It contains only a minimal Spring Boot
skeleton: the Maven build, the application entry point, a health endpoint and a
Dockerfile. No domain modules, no Kafka and no persistence are included yet.

The Laravel backend remains the functional backend. This project does not replace it.

## Requirements

- JDK 21 or newer (JDK 21 LTS recommended).
- No Maven installation required: the Maven Wrapper (`mvnw`) is included.

## Run locally

```powershell
cd backend-java
.\mvnw.cmd spring-boot:run
```

On Linux or macOS:

```sh
cd backend-java
./mvnw spring-boot:run
```

The application starts on port 8081 to avoid clashing with the Laravel/Nginx setup,
which uses port 8080.

## Health endpoint

```text
GET http://localhost:8081/actuator/health
```

## Build and test

```powershell
.\mvnw.cmd clean verify
```

## Docker

```powershell
docker build -t resto-lite-backend-java ./backend-java
docker run --rm -p 8081:8081 resto-lite-backend-java
```

## Conventions

See the repository `AGENTS.md` for the migration rules, Java conventions and the
required task workflow.
