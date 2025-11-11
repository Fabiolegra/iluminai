# IluminAI - Gestão de Ocorrências de Iluminação Pública

> Uma plataforma web colaborativa para reportar e gerenciar ocorrências de iluminação pública de forma inteligente, segura e geolocalizada.

**[➡️ Acessar a Demonstração Online](https://iluminai.42web.io/iluminai/)**

## 📖 Sobre

O **IluminAI** é um sistema web desenvolvido para facilitar a comunicação entre os cidadãos e a administração municipal a respeito de problemas na iluminação pública. Usuários podem se cadastrar, fazer login e reportar ocorrências como postes com lâmpadas queimadas, falta de energia ou fios soltos, marcando a localização exata em um mapa interativo.

Administradores possuem um painel para visualizar todas as ocorrências, alterar seus status (de "pendente" para "em andamento" ou "resolvido") e, assim, gerenciar o fluxo de trabalho das equipes de manutenção. O objetivo é agilizar a resolução dos problemas, tornando a cidade mais segura e bem iluminada para todos.

### ✨ Funcionalidades

*   **Autenticação Completa:** Sistema seguro de cadastro, login e logout.
*   **Confirmação de E-mail:** Novos usuários precisam validar seu e-mail para ativar a conta.
*   **Recuperação de Senha:** Fluxo completo de "esqueci minha senha" com envio de link seguro por e-mail.
*   **Perfis de Usuário:** Distinção entre `usuário` (cidadão) e `admin` (gestor).
*   **Gerenciamento de Perfil:** Usuários podem alterar seu nome, senha e foto de perfil.
*   **Reporte Georreferenciado:** Formulário para criar ocorrências com tipo, descrição, até 3 fotos e localização precisa no mapa (clique ou geolocalização do navegador).
*   **Upload de Imagens para a Nuvem:** As fotos de ocorrências e de perfil são armazenadas de forma segura em um bucket **AWS S3**.
*   **Mapa Interativo (Mapbox):** Visualização de todas as ocorrências, com ícones e cores que representam o tipo e o status do problema.
*   **Painel do Usuário (`Minhas Ocorrências`):** Área onde os cidadãos podem acompanhar o status e interagir em suas ocorrências, com indicadores de mensagens não lidas.
*   **Painel Administrativo Avançado:**
    *   Dashboard com estatísticas (ocorrências pendentes, em andamento, resolvidas).
    *   Filtros por data, tipo e status.
    *   Gráfico de ocorrências por tipo (`Chart.js`).
    *   Tabela para gerenciamento rápido de status.
    *   **Traçar Rota:** Admins podem traçar uma rota de sua localização atual até uma ocorrência diretamente no mapa.
*   **Sistema de Chat por Ocorrência:** Área de comentários para comunicação entre o usuário e os administradores.
*   **Histórico de Status:** Log de todas as alterações de status de uma ocorrência para rastreabilidade.
*   **Segurança:** Validação de dados no servidor, senhas criptografadas (hash), proteção contra acesso indevido e uso de prepared statements para prevenir SQL Injection.

### 🛠️ Tecnologias Utilizadas

*   **Backend:** PHP
*   **Banco de Dados:** MySQL
*   **Frontend:** HTML, Tailwind CSS (via CDN), JavaScript
*   **Mapas:** Mapbox GL JS API
*   **Armazenamento de Arquivos:** AWS S3
*   **Envio de E-mails:** PHPMailer (via SMTP)
*   **Gráficos:** Chart.js
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

*   **Servidor Web com PHP:** XAMPP, WAMP, MAMP ou similar.
*   **Banco de Dados:** MySQL
*   **Navegador Web:** Chrome, Firefox, etc.
*   **Composer:** Para gerenciar as dependências do PHP.
*   **Token de API do Mapbox:** É necessário criar uma conta gratuita no Mapbox para obter um token de acesso.
*   **Credenciais AWS S3:** Uma conta na AWS com um bucket S3 configurado e credenciais de acesso (ID da chave de acesso e chave de acesso secreta).
*   **Servidor SMTP:** Credenciais de um servidor SMTP (como Gmail, SendGrid, etc.) para o envio de e-mails de confirmação e recuperação de senha.

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
    *   Abra o arquivo `.env` e preencha **todas** as credenciais: banco de dados, Mapbox, AWS S3 e SMTP.
    ```dotenv
    # .env
    # Banco de Dados
    DB_HOST=localhost
    DB_DATABASE=iluminai
    DB_USERNAME=root
    DB_PASSWORD=

    # Mapbox
    MAPBOX_TOKEN="seu_token_aqui"

    # AWS S3
    AWS_ACCESS_KEY_ID="sua_key_id_aqui"
    AWS_SECRET_ACCESS_KEY="sua_secret_key_aqui"
    AWS_REGION="sua_regiao_aqui" # ex: us-east-1
    AWS_BUCKET="nome_do_seu_bucket_aqui"

    # E-mail (SMTP)
    SMTP_HOST="smtp.example.com"
    SMTP_USER="seu_email@example.com"
    SMTP_PASS="sua_senha_de_app"
    SMTP_PORT=587
    SMTP_SECURE="tls"
    SMTP_FROM_EMAIL="no-reply@example.com"
    SMTP_FROM_NAME="IluminAI"
    ```

5.  **Crie o Usuário Administrador**
    *   Com o servidor web em execução, acesse o seguinte URL no seu navegador:
    *   `http://localhost/iluminai/src/actions/setup_admin.php`
    *   Este script criará o usuário administrador padrão com as seguintes credenciais:
        *   **E-mail:** `admin@iluminai.com`
        *   **Senha:** `admin123`
    *   **Aviso de Segurança:** É altamente recomendado remover ou proteger o arquivo `setup_admin.php` após o uso.

---

## ▶️ Uso

1.  **Acesse a Página Inicial**
    *   Abra `http://localhost/iluminai/` no seu navegador para ver a landing page.

2.  **Crie uma Conta ou Faça Login**
    *   Use a página de registro para criar uma conta de usuário comum.
    *   Use as credenciais do administrador (`admin@iluminai.com` / `admin123`) para acessar com privilégios de administrador.

3.  **Explore a Aplicação**
    *   Após o login, você será redirecionado para o mapa principal, onde poderá ver e criar ocorrências.
    *   Acesse "Minhas Ocorrências" para ver seu histórico.
    *   Acesse "Meu Perfil" para alterar seus dados.

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
