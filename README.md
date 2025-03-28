# Sistema de Gestão de Servidores

Este é um sistema backend REST API construído em Laravel para gerenciamento de servidores públicos, incluindo servidores
efetivos e temporários, com recursos de gestão de unidades, lotações e documentação fotográfica.

## Requisitos

- Docker
- Docker Compose
- Git

## Configuração e Instalação

1. Clone o repositório
   ```bash
   git clone https://github.com/DianoDev/apiconcurso
   cd apiconcurso
   ```

2. Adicione a entrada para o MinIO no arquivo hosts do seu sistema:
   ```bash
   Para Windows:Abra o Bloco de Notas como administrador
   Abra o arquivo C:\Windows\System32\drivers\etc\hosts
   Adicione a linha: 127.0.0.1 minio
   
   Para Linux/macOS:
   Abra o terminal e execute: sudo nano /etc/hosts
   Adicione a linha: 127.0.0.1 minio
   ```

3. Copie o arquivo de configuração:
   ```bash
   cp .env.example .env
   ```

4. Inicie os containers Docker:
   ```bash
   docker compose -f compose-local.yml up -d
     ```
   ou
   ```bash
   docker-compose -f compose-local.yml up -d
     ```

5. Execute o container de composer para instalar as dependências:
   ```bash
   docker exec concurso-fpm php artisan key:generate
   ```

6. Execute as migrações e seeders:
   ```bash
   docker exec concurso-fpm php artisan migrate --seed
   ```

7. O sistema estará disponível em `http://localhost:8000` (ou na porta definida em APP_PORT no .env)

## Usuários de Teste

O seeder cria dois usuários por padrão:

- **Administrador**
    - Email: admin@example.com
    - Senha: password

- **Usuário Teste**
    - Email: teste@example.com
    - Senha: senha123

## Autenticação e Segurança

### Login

O sistema utiliza tokens JWT com expiração em 5 minutos.

**Endpoint**: `POST /api/login`

**Body**:

```json
{
  "email": "seu_email@exemplo.com",
  "password": "sua_senha"
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

Quando receber a mensagem "token expirado", você deve chamar este mesmo endpoint (/api/refresh) para obter um novo
token.

### Logout

**Endpoint**: `POST /api/logout`

## Endpoints da API

### Servidores Efetivos

#### Listar todos os servidores efetivos

**Endpoint**: `GET /api/servidores-efetivos`

**Parâmetros de consulta**:

- `search`: Termo para filtrar por nome
- `page`: Página atual para paginação

**Resposta**:

```json
{
  "current_page": 1,
  "data": [
    ...
  ],
  "first_page_url": "http://localhost:8000/api/servidores-efetivos?page=1",
  "from": 1,
  "last_page": 1,
  "last_page_url": "http://localhost:8000/api/servidores-efetivos?page=1",
  "links": [
  ],
  "next_page_url": null,
  "path": "http://localhost:8000/api/servidores-efetivos",
  "per_page": 10,
  "prev_page_url": null,
  "to": 3,
  "total": 3
}
```

#### Obter um servidor efetivo específico

**Endpoint**: `GET /api/servidores-efetivos/{id}`

**Resposta**:

```json
{
  "pes_id": 1,
  "se_matricula": "123456",
  "pessoa": {
    "pes_id": 1,
    "pes_nome": "Nome Completo",
    "pes_data_nascimento": "1980-01-01",
    "pes_sexo": "M",
    "pes_mae": "Nome da Mãe",
    "pes_pai": "Nome do Pai",
    "fotos": [
      ...
    ],
    "lotacoes": [
      ...
    ]
  }
}
```

#### Criar um servidor efetivo

**Endpoint**: `POST /api/servidores-efetivos`

**Body**:
**FormData**
```
  pes_nome: Nome Completo
  pes_data_nascimento: 1980-01-01
  pes_sexo: M
  pes_mae: Nome da Mãe
  pes_pai: Nome do Pai
  se_matricula: "123456
  unid_id: 1
```

**Resposta**:

```json
{
  "message": "Servidor efetivo cadastrado com sucesso",
  "servidor": {
    ...
  }
}
```

#### Atualizar um servidor efetivo

**Endpoint**: `PUT /api/servidores-efetivos/{id}`

**Body**:
**Json**
```json
{
  "pes_nome": "Nome Completo",
  "pes_data_nascimento": "1980-01-01",
  "pes_sexo": "M",
  "pes_mae": "Nome da Mãe",
  "pes_pai": "Nome do Pai",
  "se_matricula": "123456",
  "unid_id": 1
}
```

**Resposta**:

```json
{
  "message": "Servidor efetivo atualizado com sucesso",
  "servidor": {
    ...
  }
}
```

#### Excluir um servidor efetivo

**Endpoint**: `DELETE /api/servidores-efetivos/{id}`

**Resposta**:

```json
{
  "message": "Servidor efetivo excluído com sucesso"
}
```

#### Consultar servidores por unidade

**Endpoint**: `GET /api/servidores-efetivos/unidade/{unidadeId}`

**Resposta**:

```json
[
  {
    "nome": "Nome Completo",
    "idade": 43,
    "unidade_lotacao": "Nome da Unidade",
    "fotografia": "url_temporaria"
  },
  ...
]
```

#### Buscar servidores por nome

**Endpoint**: `POST /api/servidores-efetivos/buscar-por-nome`

**Body**:
**Json**
```json
{
  "nome": "termo_de_busca"
}
```

**Resposta**:

```json
[
  {
    "id": 1,
    "nome": "Nome Completo",
    "idade": 43,
    "unidade_lotacao": "Nome da Unidade",
    "endereco_funcional": "Endereço completo"
  },
  ...
]
```

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
    "data": [
      ...
    ],
    "first_page_url": "http://localhost:8000/api/servidores-temporarios?page=1",
    "from": 1,
    "last_page": 1,
    "last_page_url": "http://localhost:8000/api/servidores-temporarios?page=1",
    "links": [ ],
    "next_page_url": null,
    "path": "http://localhost:8000/api/servidores-temporarios",
    "per_page": 10,
    "prev_page_url": null,
    "to": 2,
    "total": 2
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
      "fotos": [
        ...
      ],
      "lotacoes": [
        ...
      ]
    }
  }
}
```

#### Criar um servidor temporário

**Endpoint**: `POST /api/servidores-temporarios`


**Body**:
**FormData**
```
  pes_nome: Nome Completo
  pes_data_nascimento: 1980-01-01
  pes_sexo: M
  pes_mae: Nome da Mãe
  pes_pai: Nome do Pai
  se_matricula: "123456
  unid_id: 1
```

**Resposta**:

```json
{
  "message": "Servidor temporário cadastrado com sucesso",
  "servidor": {
    ...
  }
}
```

#### Atualizar um servidor temporário

**Endpoint**: `PUT /api/servidores-temporarios/{id}`

**Body**:
**Json**

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
  "message": "Servidor temporário atualizado com sucesso",
  "servidor": {
    ...
  }
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

**Parâmetros de consulta**:

- `search`: Termo para filtrar por nome ou sigla
- `page`: Página atual para paginação

**Resposta**:

```json
{
  "current_page": 1,
  "data": [
    ...
  ],
  "total": 10
}
```

#### Obter uma unidade específica

**Endpoint**: `GET /api/unidades/{id}`

**Resposta**:

```json
{
  "unid_id": 1,
  "unid_nome": "Nome da Unidade",
  "unid_sigla": "SIGLA",
  "enderecos": [
    ...
  ]
}
```

#### Criar uma unidade

**Endpoint**: `POST /api/unidades`

**Body**:
**FormData**
```
  unid_nome: Nome Unidade
  unid_sigla: SIGLA
  end_tipo_logradouro: Avenida
  end_logradouro: Nome da Rua
  end_numero: 123
  end_bairro: Nome do Bairro
  unid_id: 1
```

**Resposta**:

```json
{
  "message": "Unidade cadastrada com sucesso",
  "unidade": {
    ...
  }
}
```

#### Atualizar uma unidade

**Endpoint**: `PUT /api/unidades/{id}`

**Body**:
**Json**:

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

#### Excluir uma unidade

**Endpoint**: `DELETE /api/unidades/{id}`

**Resposta**:

```json
{
  "message": "Unidade excluída com sucesso"
}
```

### Lotações

#### Listar todas as lotações

**Endpoint**: `GET /api/lotacoes`

**Parâmetros de consulta**:

- `search`: Termo para filtrar por nome da pessoa ou unidade
- `page`: Página atual para paginação

**Resposta**:

```json
{
  "current_page": 1,
  "data": [
    ...
  ],
  "total": 10
}
```

#### Obter uma lotação específica

**Endpoint**: `GET /api/lotacoes/{id}`

**Resposta**:

```json
{
  "lot_id": 1,
  "pes_id": 1,
  "unid_id": 1,
  "lot_data_lotacao": "2023-01-01",
  "lot_data_remocao": null,
  "lot_portaria": "Portaria nº 123/2023",
  "pessoa": {
    ...
  },
  "unidade": {
    ...
  }
}
```

#### Criar uma lotação

**Endpoint**: `POST /api/lotacoes`


**Body**:
**FormData**
```
  pes_id: 1
  unid_id: 1
  lot_data_lotacao: 2023-01-01
  lot_data_remocao: null
  lot_portaria: Portaria nº 123/2023
  end_bairro: Nome do Bairro
  unid_id: 1
```

**Resposta**:

```json
{
  "message": "Lotação cadastrada com sucesso",
  "lotacao": {
    ...
  }
}
```

#### Atualizar uma lotação

**Endpoint**: `PUT /api/lotacoes/{id}`

**Body**:
**Json**
```json
{
  "pes_id": 1,
  "unid_id": 1,
  "lot_data_lotacao": "2023-01-01",
  "lot_data_remocao": null,
  "lot_portaria": "Portaria nº 123/2023"
}
```
**Resposta**:

```json
{
  "message": "Lotação atualizada com sucesso",
  "lotacao": {
    ...
  }
}
```

#### Excluir uma lotação

**Endpoint**: `DELETE /api/lotacoes/{id}`

**Resposta**:

```json
{
  "message": "Lotação excluída com sucesso"
}
```

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
