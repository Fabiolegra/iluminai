# IluminAI - Gestão de Ocorrências de Iluminação Pública

> Uma plataforma colaborativa para reportar e gerenciar problemas de iluminação pública de forma inteligente e geolocalizada.

**[➡️ Acessar a Demonstração Online](https://iluminai.42web.io/iluminai/)**

## 📖 Sobre o Projeto

O **IluminAI** é um sistema web desenvolvido para facilitar a comunicação entre os cidadãos e a administração municipal a respeito de problemas na iluminação pública. Usuários podem se cadastrar, fazer login e reportar ocorrências como postes com lâmpadas queimadas, falta de energia ou fios soltos, marcando a localização exata em um mapa interativo.

Administradores possuem um painel para visualizar todas as ocorrências, alterar seus status (de "pendente" para "em andamento" ou "resolvido") e, assim, gerenciar o fluxo de trabalho das equipes de manutenção. O objetivo é agilizar a resolução dos problemas, tornando a cidade mais segura e bem iluminada para todos.

### ✨ Funcionalidades

*   **Autenticação de Usuários:** Sistema completo de cadastro e login.
*   **Tipos de Usuário:** Perfis de `usuário` (cidadão) e `admin` (gestor).
*   **Reporte de Ocorrências:** Formulário para criar novas ocorrências com tipo, descrição, foto e localização.
*   **Mapa Interativo:** Visualização de todas as ocorrências em um mapa (usando Mapbox).
*   **Dashboard Pessoal:** Usuários podem ver e gerenciar suas próprias ocorrências.
*   **Painel Administrativo:** Admins podem visualizar e atualizar o status de qualquer ocorrência.
*   **Segurança:** Proteção de rotas e validação de permissões para ações críticas.

### 🛠️ Tecnologias Utilizadas

*   **Backend:** PHP
*   **Banco de Dados:** MySQL
*   **Frontend:** HTML, Tailwind CSS (via CDN), JavaScript
*   **Mapas:** Mapbox GL JS

---

## 🚀 Começando

Siga estas instruções para obter uma cópia do projeto em funcionamento na sua máquina local para fins de desenvolvimento e teste.

### ✅ Pré-requisitos

Para rodar este projeto, você precisará de um ambiente de desenvolvimento web com PHP e MySQL.

*   **Servidor Web com PHP:** XAMPP
*   **Banco de Dados:** MySQL
*   **Navegador Web:** Chrome, Firefox, etc.
*   **Token de API do Mapbox:** É necessário criar uma conta gratuita no Mapbox para obter um token de acesso.

### ⚙️ Instalação

1.  **Clone o repositório** para o diretório do seu servidor web (ex: `htdocs` no XAMPP).
    ```sh
    git clone https://github.com/Fabiolegra/iluminai.git
    cd iluminai
    ```

2.  **Crie o Banco de Dados**
    *   Acesse seu gerenciador de banco de dados (como o phpMyAdmin).
    *   Crie um novo banco de dados chamado `iluminai`.
    *   Importe o arquivo `esquema.sql` para criar as tabelas e suas estruturas.

3.  **Configure a Conexão com o Banco de Dados**
    *   Na raiz do projeto, renomeie o arquivo `.env.example` para `.env`.
    *   Abra o arquivo `.env` e preencha as credenciais do seu banco de dados local.
    ```dotenv
    # .env
    DB_HOST=localhost
    DB_DATABASE=iluminai
    DB_USERNAME=root
    DB_PASSWORD=
    ```

4.  **Configure o Token do Mapbox**
    *   No mesmo arquivo `.env`, insira seu token de acesso do Mapbox na variável `MAPBOX_TOKEN`.
    *   `MAPBOX_TOKEN="pk.eyJ1..."`

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
    *   Abra `http://localhost/iluminai/` no seu navegador.

2.  **Crie uma Conta ou Faça Login**
    *   Use a página de registro para criar uma conta de usuário comum.
    *   Use as credenciais do administrador (`admin@iluminai.com` / `admin123`) para acessar com privilégios de administrador.

3.  **Explore a Aplicação**
    *   Após o login, você será redirecionado para o mapa principal, onde poderá ver e criar ocorrências.
    *   Acesse "Minhas Ocorrências" para ver seu histórico.
    *   Se logado como admin, o link "Painel Admin" aparecerá no cabeçalho.

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
