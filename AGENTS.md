# Global Instructions

## 🗣️ Idioma y estilo

- Responde SIEMPRE en español (es-MX).
- Sé directo y técnico. Evita relleno.
- Si algo es ambiguo, haz SOLO 1 pregunta concreta.

## ⚠️ REGLAS OBLIGATORIAS, NO NEGOCIABLES

- ANTES DE DESARROLLAR O PROPONER CAMBIOS, REVISA SIEMPRE la carpeta `docs/*` y respeta los lineamientos y documentación definidos ahí.
- El documento `docs/DocttoTenancyArchitecture.md` es la FUENTE DE VERDAD de la funcionalidad del dominio Tenant (webhooks, integración con CRM, n8n, API y modelo de datos).  
  Siempre que hagas cambios, refactors o nuevas funcionalidades que afecten a Tenants o a estos flujos, DEBES actualizar ese documento para que siga describiendo el comportamiento real del sistema. No es una bitácora de cambios.
- LO QUE YA FUNCIONA NO SE TOCA!!!!! OBEDECE ESTA ORDEN.
- SOLO puedes implementar lo que esté explícitamente dentro de la tarea asignada (no inventes features nuevas).
- Modifica únicamente archivos que estén **directamente relacionados** con la funcionalidad solicitada. Si necesitas tocar algo adicional, explícalo en el plan antes de hacerlo.
- Respeta EXACTAMENTE la estructura de carpetas, namespaces y nombres existentes (módulos, repositorios, endpoints, etc.) según la documentación.
- Cambios en migraciones, modelos, repositorios o estructura de base de datos SOLO están permitidos cuando la funcionalidad lo requiera.
    - En esos casos: justifica el cambio en el plan, aplica el cambio mínimo indispensable y actualiza la documentación correspondiente en `docs/DocttoTenancyArchitecture.md`.
- Revisa minuciosamente el código proporcionado antes de dar una respuesta o codificar.

## 🔐 Seguridad y control de cambios

- NO hagas commits, NO hagas push, NO crees PRs, a menos que yo lo pida explícitamente.
- Antes de aplicar cambios, explica el plan en 2–5 bullets.
- Evita cambios cosméticos (reformateo, renombrados, reordenamiento) si no son estrictamente necesarios para la tarea.
- No ejecutes comandos destructivos (`rm -rf`, `reset --hard`, `clean -fd`, etc.) ni modifiques secretos/credenciales.
- No agregues ni cambies dependencias (composer/npm) sin que la tarea lo pida explícitamente o sin justificarlo claramente en el plan.

## ✅ Pruebas y verificación

- Si haces cambios, sugiere el comando mínimo para validar (lint/test/dev) y qué resultado esperas ver.
- Si no puedes ejecutar los comandos, dilo explícitamente y limita tu sugerencia a qué se debe correr y qué se debe revisar en la salida.
