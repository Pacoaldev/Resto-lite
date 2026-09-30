# Resto-lite

## Project overview

Resto-lite is a restaurant management application.

The current repository contains:

- A Laravel/PHP backend.
- An Angular frontend.
- A mobile application.
- An OpenAPI contract.
- Docker Compose configuration.
- Restaurant, catalog, table and order management.

The project is being progressively migrated from Laravel to Java and Spring Boot.

## Current architecture

The Laravel backend remains the current functional backend.

The Angular frontend and mobile application must continue working during the migration.

The new Java backend will be created inside:

```text
backend-java/
```

The migration must be incremental. Do not remove or rewrite the Laravel backend unless explicitly requested.

## Target architecture

The Java backend should progressively use:

- Java.
- Spring Boot.
- Spring WebFlux.
- Maven.
- Hexagonal architecture.
- Spring Security.
- Kafka.
- PostgreSQL.
- MongoDB.
- Docker Compose.

The first implementation should be a modular monolith. Do not create independent microservices until the module boundaries are stable.

## Migration principles

- Analyse the existing Laravel implementation before writing Java code.
- Preserve current business behaviour.
- Do not invent business rules.
- Preserve OpenAPI compatibility when possible.
- Migrate one use case at a time.
- Keep Laravel and Java implementations working in parallel during migration.
- Do not modify the Angular or mobile applications unless explicitly requested.
- Do not delete existing code without explicit approval.
- Keep commits small and focused.

## Java conventions

- Use camelCase for variables, methods and files.
- Do not use accents, eñes or special characters in code names, comments or logs.
- Keep domain code independent of Spring, Kafka and database frameworks.
- Use hexagonal architecture.
- Separate domain, application, input adapters and output adapters.
- Do not use blocking calls inside WebFlux.
- Do not use block(), blockFirst() or blockLast().
- Do not introduce dependencies without explaining their purpose.

## Backend validation

Before considering a task complete:

1. Run the Java build.
2. Run the relevant tests.
3. Check the application starts locally.
4. Check the affected API endpoints.
5. Check Docker configuration if it was modified.
6. Check that the existing Laravel backend was not unintentionally changed.
7. Review the generated diff.

## Task workflow

Before modifying files:

1. Analyse the relevant existing code.
2. Identify the affected business rules.
3. List the files that will be created or modified.
4. Explain relevant design decisions.
5. Stop if required information is missing.

After modifying files:

1. Summarise the changes.
2. List the commands used for validation.
3. Report known limitations.
4. Identify pending migration work.