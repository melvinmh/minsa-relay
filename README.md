# Relay MINSA — Render.com

Servicio PHP que actúa como proxy entre el frontend (mendman.eu.org)
y el API de MINSA (websalud.minsa.gob.pe).

## Archivos
- `paciente.php` — consulta datos del paciente por DNI
- `vacunas.php`  — consulta historial de vacunas por idpaciente
- `Dockerfile`   — imagen PHP 8.2 + Apache para Render

## Variable de entorno requerida
| Variable      | Descripción                                      |
|---------------|--------------------------------------------------|
| `MINSA_COOKIE`| Cookie completa copiada desde el navegador MINSA |

## Endpoints disponibles
- `GET /paciente.php?dni=12345678`
- `GET /vacunas.php?idpaciente=99999`
