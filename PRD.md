---

# 📄 Product Requirements Document (PRD)

## 0. Instrucciones para la IA

* **Rol de la IA:**
  Actúa como *Senior Software Engineer y Product Manager*.
  Usa este PRD como **única fuente de verdad**.
  **No inventes requisitos fuera del documento.**

* **Objetivo de esta sesión con la IA:**
  Diseñar la **arquitectura inicial del paquete**, estructura de carpetas, Service Provider y flujo de detección, **inspirado en `beyondcode/laravel-query-detector`**, y dividir el trabajo en pasos claros.

---

## 1. Metadatos del proyecto

* **Nombre del proyecto / feature:**
  Laravel Missing Index Detector

* **Versión del PRD:**
  v0.1

* **Tipo:**
  Nuevo producto (Composer Package)

* **Responsable de producto:**
  Osmell

* **Responsable técnico:**
  Osmell

* **Fecha objetivo de entrega (MVP):**
  2–3 dias

---

## 2. Contexto y problema

### Resumen ejecutivo (máx. 5 líneas)

Las consultas SQL sin índices adecuados son una de las principales causas de degradación de performance en aplicaciones Laravel.
Actualmente, Laravel cuenta con herramientas para detectar N+1 queries, pero **no existe una solución automática, ligera y orientada a DX para detectar índices faltantes en tiempo de desarrollo**.
Este paquete busca cubrir ese vacío.

### Problema actual

* Los desarrolladores ejecutan queries costosas sin saber que faltan índices.
* El problema se detecta tarde (producción o debugging manual).
* `EXPLAIN` rara vez se ejecuta de forma sistemática.
* Impacto: lentitud, alto consumo de CPU en DB, escalabilidad limitada.

### Evidencias / datos

* Experiencia directa en proyectos Laravel medianos/grandes.
* Issues frecuentes de performance relacionados con `WHERE`, `JOIN` y `ORDER BY` sin índices.
* Existencia de herramientas exitosas como `laravel-query-detector` demuestra el valor de los *performance guardrails*.

### Oportunidad

Crear una herramienta:

* Automática
* Dev-only
* No intrusiva
* Familiar para la comunidad Laravel
  que alerte sobre **missing indexes** antes de llegar a producción.

---

## 3. Objetivos y métricas

### Objetivo de negocio principal

Posicionarse como un contributor relevante en la comunidad Laravel mediante una herramienta DX-first.

### Objetivos secundarios

* Reducir bugs de performance tempranamente.
* Educar sobre buenas prácticas de base de datos.
* Sentar la base para futuros detectores de anti-patrones.

### KPIs

* Instalaciones del paquete.
* Stars en GitHub.
* Issues / PRs de la comunidad.
* Tiempo promedio de detección vs debugging manual.

---

## 4. Usuarios y casos de uso

### Usuarios

* Laravel developers (junior a senior)
* Equipos que usan MySQL / MariaDB en desarrollo
* Contexto: desarrollo local, staging
* Dispositivo: desktop

### Historias de usuario

1. Como desarrollador Laravel quiero ser advertido cuando una query usa columnas sin índice para evitar problemas de performance.
2. Como desarrollador quiero que las advertencias solo aparezcan en desarrollo.
3. Como desarrollador quiero mensajes claros y accionables sin romper la app.

---

## 5. Alcance

### In scope

* Detección automática de queries sin índices.
* Análisis basado en queries ejecutadas durante una request.
* Uso de `EXPLAIN` para análisis.
* Soporte inicial para MySQL / MariaDB.
* Output claro en consola/logs.
* Configuración vía archivo.
* Arquitectura inspirada en `beyondcode/laravel-query-detector`.

### Out of scope

* PostgreSQL (fase futura).
* Auto-creación de índices.
* Optimización automática de queries.
* Análisis en producción.
* UI gráfica o dashboard.

---

## 6. Requisitos funcionales (RF)

### Core

* **RF-1:** El sistema debe escuchar todas las queries ejecutadas durante una request.
* **RF-2:** El sistema debe analizar queries `SELECT` usando `EXPLAIN`.
* **RF-3:** El sistema debe detectar columnas usadas en `WHERE`, `JOIN`, `ORDER BY` sin índice.
* **RF-4:** El sistema debe reportar advertencias de forma no intrusiva.
* **RF-5:** El sistema debe poder activarse/desactivarse por entorno.

### Arquitectura (inspirada en Beyond Code)

* **RF-6:** El paquete debe usar un `ServiceProvider` con auto-discovery.
* **RF-7:** El sistema debe usar `DB::listen` para capturar queries.
* **RF-8:** El detector debe ejecutarse solo en `local` y `testing` por defecto.
* **RF-9:** El formato de reporte debe ser consistente con `laravel-query-detector`.

---

## 7. Requisitos no funcionales (RNF)

* **Performance:**
  El overhead debe ser mínimo y solo en desarrollo.

* **Seguridad:**
  No almacenar queries sensibles ni enviarlas externamente.

* **Disponibilidad:**
  El fallo del detector no debe afectar la ejecución de la app.

* **DX:**
  Mensajes claros, accionables y familiares para usuarios de Laravel.

* **Logging:**
  Logs estructurados y legibles.

---

## 8. UX / UI y flujo de usuario

### Flujo principal

1. Developer instala el paquete.
2. Ejecuta la app en entorno local.
3. Se ejecuta una query sin índice.
4. El paquete muestra una advertencia.

### Estados

* **Advertencia detectada:**
  Mensaje tipo warning con:

  * Tabla
  * Columna
  * Tipo de operación
  * Sugerencia clara

* **Silencioso:**
  Si no hay problemas, no muestra nada.

---

## 9. Datos, modelo y APIs

### Entidades internas

* **QueryReport**

  * sql
  * bindings
  * execution_time
  * table
  * column
  * operation
  * index_used (bool)

### Integraciones externas

* Base de datos (MySQL)
* Uso de `EXPLAIN`

### Reglas de negocio

* Solo analizar queries con tiempo > configurable threshold.
* Ignorar queries internas de Laravel.
* No duplicar advertencias por la misma query.

---

## 10. Criterios de aceptación (CA)

* **CA-1 (RF-1):**
  Dado una request con queries
  Cuando se ejecuta
  Entonces el sistema captura las queries.

* **CA-2 (RF-3):**
  Dado una query sin índice
  Cuando se analiza
  Entonces se genera una advertencia.

* **CA-3 (RF-5):**
  Dado entorno production
  Cuando se ejecuta la app
  Entonces el detector no corre.

---

## 11. Prioridades y roadmap

### Prioridades (MoSCoW)

* RF-1 → Must
* RF-2 → Must
* RF-3 → Must
* RF-4 → Must
* RF-5 → Must
* RF-9 → Should

### MVP

* MySQL only
* Advertencias básicas
* Configuración mínima
* DX comparable a `laravel-query-detector`

### Fases futuras

* PostgreSQL
* Grouping por request/route
* Integración con Laravel Pulse

---

## 12. Restricciones, riesgos y supuestos

### Restricciones técnicas

* Laravel 9+
* PHP 8+

### Riesgos

* Falsos positivos.
* Ruido excesivo si thresholds no están bien configurados.

### Supuestos

* Los developers prefieren warnings a errores.
* La arquitectura de Beyond Code es estable.

---

## 13. Notas para trabajar con AI coding assistants

### Stack objetivo

* PHP 8+
* Laravel
* Composer package

### Estándares

* PSR-12
* Código simple y legible
* Sin dependencias innecesarias

### Formato preferido de salida

* [x] Arquitectura inicial
* [x] Estructura de carpetas
* [x] Pasos / tareas
* [ ] Tests (fase siguiente)

### Instrucciones clave para la IA

* Siempre referencia RF-x y CA-x.
* Usa `beyondcode/laravel-query-detector` como **referencia arquitectónica**.
* No asumas features fuera del MVP.
* Propón arquitectura antes de escribir código.

---

## 📦 Estructura del paquete (inspirada en Beyond Code)

```text
src/
├── Detectors/
│   └── MissingIndexDetector.php
├── Listeners/
│   └── QueryListener.php
├── Reports/
│   └── MissingIndexReport.php
├── Support/
│   ├── QueryParser.php
│   └── ExplainAnalyzer.php
├── LaravelMissingIndexServiceProvider.php
config/
└── missing-index-detector.php
```
---

