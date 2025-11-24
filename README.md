# IluminAI - Plataforma de Gestão de Ocorrências de Iluminação Pública

![IluminAI](https://img.shields.io/badge/IluminAI-Gestão%20Inteligente-blue.svg)
![PHP](https://img.shields.io/badge/PHP-8.x-blueviolet.svg)
![MySQL](https://img.shields.io/badge/MySQL-8.0-orange.svg)
![TailwindCSS](https://img.shields.io/badge/TailwindCSS-3.x-cyan.svg)
![Mapbox](https://img.shields.io/badge/Mapbox-GL%20JS-green.svg)

O IluminAI é um sistema web completo projetado para o reporte, visualização e gerenciamento de ocorrências relacionadas à iluminação pública. A plataforma conecta cidadãos, operadores de campo e administradores, otimizando o fluxo de resolução de problemas como postes com luzes queimadas, fios soltos e outras eventualidades.

---

## ✨ Funcionalidades Principais

A plataforma é dividida em três níveis de acesso, cada um com ferramentas específicas:

### 👤 Painel do Usuário (Cidadão)
- **Autenticação Segura**: Cadastro com confirmação por e-mail, login e recuperação de senha.
- **Reporte de Ocorrências**: Formulário intuitivo para reportar problemas, incluindo tipo, descrição, upload de fotos e seleção da localização exata em um mapa interativo (Mapbox).
- **Mapa de Ocorrências**: Visualização de todas as ocorrências em um mapa, com marcadores coloridos de acordo com o status (Pendente, Em Andamento, Resolvido).
- **Minhas Ocorrências**: Página para acompanhar o status e o histórico de todas as suas ocorrências reportadas.
- **Comunicação Direta**: Um sistema de chat em cada ocorrência para interagir com administradores e operadores.
- **Gestão de Perfil**: Atualização de dados pessoais e foto de perfil.

### 🛠️ Painel do Operador
- **Visualização Otimizada**: Acesso ao mapa com filtros para visualizar todas as ocorrências ou apenas aquelas atribuídas a ele.
- **Ocorrências Atribuídas**: Uma lista clara com as tarefas sob sua responsabilidade.
- **Detalhes da Ocorrência**: Acesso completo aos detalhes, incluindo a capacidade de traçar uma rota até o local.
- **Atualização de Status**: Pode marcar uma ocorrência como "Resolvida" após a conclusão do serviço.
- **Interação via Chat**: Comunica-se com o cidadão e administradores para obter mais detalhes ou fornecer atualizações.

### 👑 Painel do Administrador
- **Dashboard Analítico**: Visão geral com estatísticas, gráficos de ocorrências por tipo e status, e filtros por período.
- **Gestão Completa de Ocorrências**:
  - Atribuição de ocorrências a operadores específicos.
  - Alteração manual de status.
  - Edição e exclusão de ocorrências.
- **Gerenciamento de Usuários**:
  - Visualização e filtragem de todos os usuários (cidadãos, operadores, admins).
  - Criação de novas contas de operadores.
  - Acesso ao perfil detalhado de cada usuário, com estatísticas de suas atividades.
  - Capacidade de bloquear ou reativar usuários.
- **Sistema de Avisos**: Ferramenta para enviar notificações para usuários específicos, por tipo (todos os operadores, por exemplo) ou para todos os usuários do sistema.

---

## 🚀 Tecnologias Utilizadas

- **Backend**:
  - **PHP 8+**: Linguagem principal da aplicação.
  - **MySQL**: Banco de dados para armazenamento de todas as informações.
  

- **Frontend**:
  - **HTML5** e **JavaScript**: Estrutura e interatividade do lado do cliente.
  - **Tailwind CSS**: Framework CSS utilitário para uma estilização moderna e responsiva.
  - **Mapbox GL JS**: API para a renderização de mapas interativos, geocodificação e cálculo de rotas.
  - **Chart.js**: Biblioteca para a criação de gráficos dinâmicos no dashboard do administrador.

- **Ferramentas e Dependências**:
  - **Composer**: Gerenciador de dependências PHP.
  - **PHPMailer**: Biblioteca para envio de e-mails (confirmação de conta, recuperação de senha, etc.).
  - **DotEnv**: Para gerenciamento de variáveis de ambiente.

---

## 🚀 Guia de Instalação Detalhado (Passo a Passo com XAMPP)

Este guia detalha todo o processo para configurar e executar o projeto IluminAI em seu computador local usando o XAMPP.

### Pré-requisitos

- **XAMPP**: Garante um ambiente com Apache, MySQL e PHP. Baixe em [apachefriends.org](https://www.apachefriends.org/index.html).
- **Composer**: Gerenciador de dependências para PHP. Instruções de instalação em [getcomposer.org](https://getcomposer.org/download/).
- **Git**: Sistema de controle de versão para baixar o código. Baixe em git-scm.com.
- **Contas de Serviço**:
  - **Conta na AWS**: Para o serviço S3 (armazenamento de imagens).
  - **Conta na Mapbox**: Para a API de mapas.
  - **Conta de E-mail (Gmail)**: Para enviar e-mails de confirmação e recuperação de senha.

---

### Passo 1: Download do Projeto e Dependências

1.  **Abra o Terminal do XAMPP**: No painel de controle do XAMPP, clique no botão "Shell".
2.  **Navegue até a pasta `htdocs`**: Este é o diretório onde o XAMPP armazena os sites.
    ```bash
    cd C:/xampp/htdocs
    ```
3.  **Clone o repositório do projeto**:
    ```bash
    git clone https://github.com/seu-usuario/iluminai.git
    ```
4.  **Acesse a pasta do projeto**:
    ```bash
    cd iluminai
    ```
5.  **Instale as dependências do PHP**: O Composer irá ler o arquivo `composer.json` e baixar as bibliotecas necessárias (AWS SDK, PHPMailer, etc.).
    ```bash
    composer install
    ```

---

### Passo 2: Configuração do Banco de Dados

1.  **Inicie o Apache e o MySQL** no painel de controle do XAMPP.
2.  **Abra o phpMyAdmin**: Navegue até `http://localhost/phpmyadmin` em seu navegador.
3.  **Crie o Banco de Dados**:
    - Clique na aba **"Bancos de dados"**.
    - No campo "Criar banco de dados", digite `iluminai`.
    - Selecione o agrupamento `utf8mb4_unicode_ci` e clique em **"Criar"**.
4.  **Importe a Estrutura**:
    - Clique no banco de dados `iluminai` que acabou de criar (na lista à esquerda).
    - Clique na aba **"Importar"**.
    - Clique em **"Escolher arquivo"** e selecione o arquivo `esquema.sql` que está na pasta do projeto (`C:\xampp\htdocs\iluminai\esquema.sql`).
    - Role para baixo e clique em **"Executar"**. As tabelas do projeto serão criadas.

---

### Passo 3: Configuração das Variáveis de Ambiente (`.env`)

Este é o passo mais importante. Ele conecta sua aplicação com todos os serviços externos.

1.  **Crie o arquivo `.env`**: Na pasta do projeto (`C:\xampp\htdocs\iluminai`), renomeie o arquivo `.env.example` para `.env`.
2.  **Abra o arquivo `.env`** em um editor de código e preencha as informações conforme os sub-passos abaixo.

#### 3.1) Configuração do Banco de Dados e App

Estas são as configurações padrão para o XAMPP.

```env
# URL base da sua aplicação. Aponte para a pasta 'public' do seu projeto.
APP_URL=http://localhost/iluminai/public

# Configuração do Banco de Dados (padrão XAMPP)
DB_HOST=localhost
DB_USERNAME=root
DB_PASSWORD=
DB_DATABASE=iluminai
```

#### 3.2) Configuração da AWS para o S3 (Upload de Fotos)

1.  **Faça login no Console AWS**.
2.  **Crie um usuário IAM**:
    - No campo de busca, procure por **"IAM"** e acesse o serviço.
    - No menu lateral, clique em **"Users"** e depois em **"Create user"**.
    - Dê um nome ao usuário (ex: `iluminai-s3-user`) e clique em **"Next"**.
    - Selecione **"Attach policies directly"**, procure por `AmazonS3FullAccess` e marque a caixa. Clique em **"Next"**.
    - Revise e clique em **"Create user"**.
3.  **Crie as chaves de acesso**:
    - Clique no usuário que você acabou de criar.
    - Vá para a aba **"Security credentials"**.
    - Role para baixo até **"Access keys"** e clique em **"Create access key"**.
    - Selecione **"Command Line Interface (CLI)"**, marque a confirmação e clique em **"Next"**.
    - Clique em **"Create access key"**.
    - **Copie e guarde a "Access key ID" e a "Secret access key"**. Cole-as no seu arquivo `.env`.
4.  **Crie um Bucket S3**:
    - No campo de busca do console, procure por **"S3"** e acesse o serviço.
    - Clique em **"Create bucket"**.
    - Dê um **nome único global** para o bucket (ex: `iluminai-fotos-seu-nome-123`).
    - Escolha a **Região da AWS** (ex: `us-east-1`).
    - Role para baixo até a seção **"Block Public Access settings for this bucket"** e **desmarque** a opção **"Block all public access"**. Marque a caixa de confirmação que aparecerá. Isso é **essencial** para que as imagens possam ser visualizadas no site.
    - Clique em **"Create bucket"**.
5.  **Preencha o `.env` com os dados da AWS**:

```env
# Configuração da AWS S3 para upload de fotos
AWS_ACCESS_KEY_ID="SUA_ACCESS_KEY_ID_COPIADA_AQUI"
AWS_SECRET_ACCESS_KEY="SUA_SECRET_ACCESS_KEY_COPIADA_AQUI"
AWS_DEFAULT_REGION="us-east-1" # Região que você escolheu
AWS_BUCKET="nome-do-seu-bucket-criado-aqui"
```

#### 3.3) Configuração do Mapbox (Mapas)

1.  **Faça login na sua conta Mapbox**.
2.  Você será redirecionado para o seu Dashboard. A chave de acesso padrão (**Default public token**) estará visível.
3.  Copie essa chave e cole no seu arquivo `.env`.

```env
# Chave da API do Mapbox
MAPBOX_TOKEN="SUA_CHAVE_PUBLICA_DO_MAPBOX_AQUI"
```

#### 3.4) Configuração de E-mail (com Gmail)

Para usar o Gmail, você precisa de uma "Senha de App".

1.  **Ative a Verificação em 2 Etapas**: Se ainda não estiver ativa, acesse sua Conta Google, vá em **"Segurança"** e ative a **"Verificação em 2 etapas"**.
2.  **Crie uma Senha de App**:
    - Na mesma página de **"Segurança"**, clique em **"Senhas de app"**.
    - Em "Selecionar app", escolha **"E-mail"**.
    - Em "Selecionar dispositivo", escolha **"Computador Windows"**.
    - Clique em **"Gerar"**.
    - Uma senha de 16 letras será gerada. **Copie esta senha** (sem os espaços).
3.  **Preencha o `.env` com os dados do Gmail**:

```env
# Configuração do Servidor de E-mail (PHPMailer com Gmail)
SMTP_HOST=smtp.gmail.com
SMTP_USER="seu_endereco@gmail.com"
SMTP_PASS="A_SENHA_DE_APP_DE_16_LETRAS_AQUI"
SMTP_PORT=587
SMTP_SECURE=tls
```

---

### Passo 4: Finalização

1.  **Reinicie o Apache**: No painel de controle do XAMPP, pare (`Stop`) e inicie (`Start`) o módulo do Apache para garantir que todas as configurações sejam carregadas.
2.  **Acesse a Aplicação**: Abra seu navegador e acesse a pasta `public` do projeto:
    ```
    http://localhost/iluminai/public/
    ```
    Você deverá ser redirecionado para a página de login.

Pronto! Se todos os passos foram seguidos corretamente, o site IluminAI estará funcionando em seu ambiente local.

---

## 🤝 Contribuição

Contribuições são bem-vindas! Se você deseja melhorar o projeto, sinta-se à vontade para abrir uma *Pull Request* ou reportar um bug através das *Issues*.

## 📄 Licença

Este projeto é distribuído sob a licença MIT. Veja o arquivo `LICENSE` para mais detalhes.