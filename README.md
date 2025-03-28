# Sistema de Gestão de Servidores

Este é um sistema backend REST API construído em Laravel para gerenciamento de servidores públicos, incluindo servidores efetivos e temporários, com recursos de gestão de unidades, lotações e documentação fotográfica.

## Requisitos

- PHP 8.2+
- PostgreSQL 16+
- Composer
- Docker (recomendado para ambiente de desenvolvimento)
- MinIO (para armazenamento de arquivos)

## Configuração

1. Clone o repositório
2. Execute `composer install`
3. Configure o arquivo `.env` baseado em `.env.example`
4. Execute o Docker Compose:
   ```
   docker-compose -f compose-local.yml up -d
   ```
5. Execute as migrações e seeders:
   ```
   php artisan migrate --seed
   ```

## Autenticação e Segurança

### Login
O sistema utiliza tokens JWT com expiração em 5 minutos.

**Endpoint**: `POST /api/login`

**Body**:
```json
{
  "email": "seu_email@exemplo.com",
  "password": "sua_senha",
  "device_name": "dispositivo_opcional"
}
```

**Resposta**:
```json
{
  "token": "seu_token_jwt",
  "expires_at": "2025-03-28T10:30:00Z",
  "user": {
    "id": 1,
    "name": "Nome do Usuário",
    "email": "seu_email@exemplo.com"
  }
}
```

### Refresh Token

Para renovar o token antes ou após a expiração (5 minutos), utilize:

**Endpoint**: `POST /api/refresh`

**Body**:
```json
{
  "token": "seu_token_atual"
}
```

**Resposta em caso de sucesso**:
```json
{
  "token": "novo_token_jwt",
  "expires_at": "2025-03-28T10:35:00Z",
  "user": {
    "id": 1,
    "name": "Nome do Usuário",
    "email": "seu_email@exemplo.com"
  }
}
```

**Resposta em caso de token expirado**:
```json
{
  "message": "token expirado"
}
```

Quando receber a mensagem "token expirado", você deve chamar este mesmo endpoint (/api/refresh) para obter um novo token.

### Logout
**Endpoint**: `POST /api/logout`

## Endpoints da API

### Servidores Temporários

#### Listar todos os servidores temporários
**Endpoint**: `GET /api/servidores-temporarios`

**Parâmetros de consulta**:
- `search`: Termo para filtrar por nome
- `page`: Página atual para paginação

**Resposta**:
```json
{
  "message": "Servidores temporários listados com sucesso",
  "servidores": {
    "current_page": 1,
    "data": [...],
    "total": 10
  }
}
```

#### Obter um servidor temporário específico
**Endpoint**: `GET /api/servidores-temporarios/{id}`

**Resposta**:
```json
{
  "message": "Servidor temporário encontrado",
  "servidor": {
    "pes_id": 1,
    "st_data_admissao": "2023-01-01",
    "st_data_demissao": "2023-12-31",
    "pessoa": {
      "pes_id": 1,
      "pes_nome": "Nome Completo",
      "pes_data_nascimento": "1990-01-01",
      "pes_sexo": "M",
      "pes_mae": "Nome da Mãe",
      "pes_pai": "Nome do Pai",
      "fotos": [...],
      "lotacoes": [...]
    }
  }
}
```

#### Criar um servidor temporário
**Endpoint**: `POST /api/servidores-temporarios`

**Body**:
```json
{
  "pes_nome": "Nome Completo",
  "pes_data_nascimento": "1990-01-01",
  "pes_sexo": "M",
  "pes_mae": "Nome da Mãe",
  "pes_pai": "Nome do Pai",
  "st_data_admissao": "2023-01-01",
  "st_data_demissao": "2023-12-31",
  "unid_id": 1
}
```

**Resposta**:
```json
{
  "message": "Servidor temporário cadastrado com sucesso",
  "servidor": {...}
}
```

#### Atualizar um servidor temporário
**Endpoint**: `PUT /api/servidores-temporarios/{id}`

**Body**: (mesmo formato da criação)

**Resposta**:
```json
{
  "message": "Servidor temporário atualizado com sucesso",
  "servidor": {...}
}
```

#### Excluir um servidor temporário
**Endpoint**: `DELETE /api/servidores-temporarios/{id}`

**Resposta**:
```json
{
  "message": "Servidor temporário excluído com sucesso"
}
```

### Unidades

#### Listar todas as unidades
**Endpoint**: `GET /api/unidades`

**Resposta**:
```json
{
  "message": "Unidades listadas com sucesso",
  "unidades": {...}
}
```

#### Obter uma unidade específica
**Endpoint**: `GET /api/unidades/{id}`

#### Criar uma unidade
**Endpoint**: `POST /api/unidades`

**Body**:
```json
{
  "unid_nome": "Nome da Unidade",
  "unid_sigla": "SIGLA",
  "end_tipo_logradouro": "Avenida",
  "end_logradouro": "Nome da Rua",
  "end_numero": 123,
  "end_bairro": "Nome do Bairro",
  "cid_id": 1
}
```

#### Atualizar uma unidade
**Endpoint**: `PUT /api/unidades/{id}`

#### Excluir uma unidade
**Endpoint**: `DELETE /api/unidades/{id}`

### Lotações

#### Listar todas as lotações
**Endpoint**: `GET /api/lotacoes`

#### Obter uma lotação específica
**Endpoint**: `GET /api/lotacoes/{id}`

#### Criar uma lotação
**Endpoint**: `POST /api/lotacoes`

**Body**:
```json
{
  "pes_id": 1,
  "unid_id": 1,
  "lot_data_lotacao": "2023-01-01",
  "lot_data_remocao": null,
  "lot_portaria": "Portaria nº 123/2023"
}
```

#### Atualizar uma lotação
**Endpoint**: `PUT /api/lotacoes/{id}`

#### Excluir uma lotação
**Endpoint**: `DELETE /api/lotacoes/{id}`

### Upload de Fotos

#### Upload de Fotos de Pessoa

**IMPORTANTE**: Existem dois modos de upload:
1. **Upload de arquivo único**: Use o campo `file`
2. **Upload de múltiplos arquivos**: Use o campo `files` (observe que não é "files[]")

**Endpoint**: `POST /api/pessoas/{pessoaId}/fotos`

**Requisição para um único arquivo**:
- Método: `POST`
- Headers:
  - `Authorization: Bearer {seu_token}`
  - `Content-Type: multipart/form-data`
- Body (form-data):
  - `file`: arquivo da imagem (JPG, PNG, GIF)

**Requisição para múltiplos arquivos**:
- Método: `POST`
- Headers:
  - `Authorization: Bearer {seu_token}`
  - `Content-Type: multipart/form-data`
- Body (form-data):
  - `files`: múltiplos arquivos de imagem (JPG, PNG, GIF)

**Resposta para um único arquivo**:
```json
{
  "message": "Foto cadastrada com sucesso",
  "foto": {
    "id": 1,
    "data": "2025-03-28",
    "url": "url_temporaria_com_validade_de_5_minutos"
  }
}
```

**Resposta para múltiplos arquivos**:
```json
{
  "message": "3 foto(s) cadastrada(s) com sucesso",
  "fotos": [
    {
      "id": 1,
      "data": "2025-03-28",
      "url": "url_temporaria_com_validade_de_5_minutos"
    },
    {
      "id": 2,
      "data": "2025-03-28",
      "url": "url_temporaria_com_validade_de_5_minutos"
    },
    {
      "id": 3,
      "data": "2025-03-28",
      "url": "url_temporaria_com_validade_de_5_minutos"
    }
  ]
}
```

#### Visualizar Foto
**Endpoint**: `GET /api/fotos/{id}`

**Resposta**:
```json
{
  "id": 1,
  "data": "2025-03-28",
  "url": "url_temporaria_com_validade_de_5_minutos"
}
```

#### Listar Fotos de uma Pessoa
**Endpoint**: `GET /api/pessoas/{pessoaId}/fotos`

**Resposta**:
```json
[
  {
    "id": 1,
    "data": "2025-03-28",
    "url": "url_temporaria_com_validade_de_5_minutos"
  },
  {
    "id": 2,
    "data": "2025-03-28",
    "url": "url_temporaria_com_validade_de_5_minutos"
  }
]
```

#### Excluir Foto
**Endpoint**: `DELETE /api/fotos/{id}`

**Resposta**:
```json
{
  "message": "Foto excluída com sucesso"
}
```


