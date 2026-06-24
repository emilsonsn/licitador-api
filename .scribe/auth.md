# Authenticating requests

To authenticate requests, include an **`Authorization`** header with the value **`"Bearer {YOUR_JWT_TOKEN}"`**.

All authenticated endpoints are marked with a `requires authentication` badge in the documentation below.

Envie o token JWT retornado pelo endpoint de login no cabeçalho <code>Authorization: Bearer {YOUR_JWT_TOKEN}</code>.
