# IluminAI - Gestão de Ocorrências de Iluminação Pública

> Uma plataforma web colaborativa para reportar e gerenciar problemas de iluminação pública de forma inteligente e geolocalizada.

**[➡️ Acessar a Demonstração Online](https://iluminai.42web.io/iluminai/)**

## 📖 Sobre

O **IluminAI** é um sistema web desenvolvido para facilitar a comunicação entre os cidadãos e a administração municipal a respeito de problemas na iluminação pública. Usuários podem se cadastrar, fazer login e reportar ocorrências como postes com lâmpadas queimadas, falta de energia ou fios soltos, marcando a localização exata em um mapa interativo.

Administradores possuem um painel para visualizar todas as ocorrências, alterar seus status (de "pendente" para "em andamento" ou "resolvido") e, assim, gerenciar o fluxo de trabalho das equipes de manutenção. O objetivo é agilizar a resolução dos problemas, tornando a cidade mais segura e bem iluminada para todos.

### ✨ Funcionalidades

*   **Autenticação Completa:** Sistema seguro de cadastro, login e logout.
*   **Perfis de Usuário:** Distinção entre `usuário` (cidadão) e `admin` (gestor), cada um com suas permissões.
*   **Reporte Georreferenciado:** Formulário para criar ocorrências com tipo, descrição, foto opcional e localização precisa no mapa.
*   **Mapa Interativo (Mapbox):** Visualização de todas as ocorrências, com ícones e cores que representam o tipo e o status do problema.
*   **Painel do Usuário:** Área onde os cidadãos podem acompanhar o status e interagir nas suas ocorrências.
*   **Painel Administrativo:** Interface para gestores visualizarem todas as ocorrências, atualizarem seus status e acompanharem o trabalho.
*   **Sistema de Chat:** Cada ocorrência possui uma área de comentários para comunicação entre o usuário e os administradores.
*   **Histórico de Status:** Log de todas as alterações de status de uma ocorrência.
*   **Segurança:** Validação de dados no servidor, proteção contra acesso indevido e senhas criptografadas.

### 🛠️ Tecnologias Utilizadas

*   **Backend:** PHP
*   **Banco de Dados:** MySQL
*   **Frontend:** HTML, Tailwind CSS (via CDN), JavaScript
*   **Mapas:** API Mapbox GL JS
*   **Gerenciador de Dependências:** Composer

---

## 📂 Estrutura do Projeto

```
iluminai/
├── config/               # Conexão com o banco de dados
├── public/               # Arquivos acessíveis pelo navegador (páginas, CSS, JS)
│   ├── uploads/          # Imagens enviadas pelos usuários
│   └── templates/        # Partes reutilizáveis de HTML (ex: header)
├── src/                  # Lógica principal da aplicação
│   └── actions/          # Scripts que processam formulários (login, registro, etc.)
├── vendor/               # Dependências do Composer
├── .env                  # Arquivo para variáveis de ambiente (NÃO versionar)
├── .env.example          # Exemplo de arquivo .env
├── bootstrap.php         # Inicializa a aplicação (sessão, autoload, .env)
├── composer.json         # Define as dependências do projeto
├── esquema.sql           # Estrutura do banco de dados
└── README.md             # Este arquivo
```

---

## � Começando

Siga estas instruções para obter uma cópia do projeto em funcionamento na sua máquina local para fins de desenvolvimento e teste.

### ✅ Pré-requisitos

Para rodar este projeto, você precisará de um ambiente de desenvolvimento web com PHP e MySQL.

*   **Servidor Web com PHP:** XAMPP
*   **Banco de Dados:** MySQL
*   **Navegador Web:** Chrome, Firefox, etc.
*   **Composer:** Para gerenciar as dependências do PHP.
*   **Token de API do Mapbox:** É necessário criar uma conta gratuita no Mapbox para obter um token de acesso.

### ⚙️ Instalação

1.  **Clone o repositório** para o diretório do seu servidor web (ex: `htdocs` no XAMPP).
    ```sh
    git clone https://github.com/Fabiolegra/iluminai.git
    cd iluminai
    ```

2.  **Instale as Dependências**
    *   Execute o Composer para baixar as bibliotecas necessárias (como o `dotenv`).
    ```sh
    composer install
    ```

3.  **Crie o Banco de Dados**
    *   Acesse seu gerenciador de banco de dados (como o phpMyAdmin).
    *   Crie um novo banco de dados chamado `iluminai`.
    *   Importe o arquivo `esquema.sql` para criar as tabelas e suas estruturas.

4.  **Configure as Variáveis de Ambiente**
    *   Na raiz do projeto, copie o arquivo `.env.example` e renomeie a cópia para `.env`.
    *   Abra o arquivo `.env` e preencha as credenciais do seu banco de dados local.
    ```dotenv
    # .env
    DB_HOST=localhost
    DB_DATABASE=iluminai
    DB_USERNAME=root
    DB_PASSWORD=
    ```

5.  **Configure o Token do Mapbox**
    *   No mesmo arquivo `.env`, insira seu token de acesso público do Mapbox na variável `MAPBOX_TOKEN`.

6.  **Crie o Usuário Administrador**
    *   Com o servidor web em execução, acesse o seguinte URL no seu navegador:
    *   `http://localhost/iluminai/src/actions/setup_admin.php`
    *   Este script criará o usuário administrador padrão com as seguintes credenciais:
        *   **E-mail:** `admin@iluminai.com`
        *   **Senha:** `admin123`
    *   **Aviso de Segurança:** É altamente recomendado remover ou proteger o arquivo `setup_admin.php` após o uso.

---

## ▶️ Uso

1.  **Acesse a Página Inicial**
    *   Abra `http://localhost/iluminai/` no seu navegador.

2.  **Crie uma Conta ou Faça Login**
    *   Use a página de registro para criar uma conta de usuário comum.
    *   Use as credenciais do administrador (`admin@iluminai.com` / `admin123`) para acessar com privilégios de administrador.

3.  **Explore a Aplicação**
    *   Após o login, você será redirecionado para o mapa principal, onde poderá ver e criar ocorrências.
    *   Acesse "Minhas Ocorrências" para ver seu histórico.
    *   Se logado como admin, o link "Painel Admin" aparecerá no cabeçalho.

---

## 🗃️ Banco de Dados

O banco de dados é composto por 5 tabelas principais:

*   `users`: Armazena os dados dos usuários (comuns e administradores).
*   `ocorrencias`: Tabela central que guarda todas as ocorrências reportadas, incluindo tipo, descrição, localização e status.
*   `ocorrencias_log`: Registra o histórico de mudanças de status de cada ocorrência, garantindo rastreabilidade.
*   `comentarios`: Armazena as mensagens trocadas dentro de uma ocorrência, formando o sistema de chat.
*   `comentarios_visualizacao`: Controla quais comentários já foram lidos por cada usuário em cada ocorrência.

---

## 📡 Endpoints da API

A aplicação possui um endpoint principal para alimentar o mapa com os dados das ocorrências.

#### `GET /public/occurrences.php`

Retorna um JSON com a lista de todas as ocorrências cadastradas.

*   **Proteção:** Requer que o usuário esteja autenticado.
*   **Resposta (Sucesso):**
    ```json
    [
      { "id": 1, "user_id": 2, "tipo": "iluminacao apagada", "descricao": "Poste em frente ao número 123.", "latitude": "-2.4400", "longitude": "-54.7100", "status": "pendente" },
      { ... }
    ]
    ```
---

## 🤝 Contribuição

Contribuições são o que tornam a comunidade de código aberto um lugar incrível para aprender, inspirar e criar. Qualquer contribuição que você fizer será **muito apreciada**.

1.  Faça um **Fork** do projeto.
2.  Crie uma nova Branch (`git checkout -b feature/AmazingFeature`).
3.  Faça o **Commit** de suas alterações (`git commit -m 'Add some AmazingFeature'`).
4.  Faça o **Push** da Branch (`git push origin feature/AmazingFeature`).
5.  Abra um **Pull Request**.

Não se esqueça de dar uma estrela ao projeto! Obrigado!

---

## 📜 Licença

Distribuído sob a licença MIT. Veja `LICENSE.md` para mais informações.
