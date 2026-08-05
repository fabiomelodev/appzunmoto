# GiroMoto

Plataforma de **reserva de vagas (turnos/diárias) para motoboys e entregadores**: restaurantes e comércios publicam turnos, entregadores demonstram interesse, as duas partes confirmam a parceria e conversam pelo chat.

Reimplementação fiel do app original (React + TanStack + Supabase) em **Laravel + Livewire + AlpineJS + Tailwind**. Todo o código (variáveis, métodos, colunas) está em **inglês**; a interface permanece em **português**.

## Stack

- **Laravel 13** · **Livewire 4** · **AlpineJS** · **Tailwind v4** (Vite; fontes Inter/Sora servidas localmente)
- **MySQL** · **Leaflet** (mapa de vagas) · autenticação nativa do Laravel

## Requisitos

- PHP 8.3+ · Composer · Node 18+ · MySQL 8+

## Instalação

```bash
composer install
npm install

cp .env.example .env
php artisan key:generate

# configure DB_* no .env (MySQL), depois:
php artisan migrate
php artisan storage:link      # avatares/uploads públicos
php artisan db:seed           # dados de demonstração (opcional)

npm run build                 # ou: npm run dev
```

Servir: `php artisan serve` (ou via Laragon em `http://appgiromoto.test`).

## Contas de demonstração

Após `php artisan db:seed` (senha de todas: `secret123`):

| Papel | E-mail |
|------|--------|
| Restaurante | `bella@demo.test`, `yama@demo.test`, `burger@demo.test` |
| Motoboy | `carlos@demo.test`, `ana@demo.test` |

Ou use o **Modo teste** na tela de login para entrar instantaneamente.

## Funcionalidades

- **Vagas**: listagem com busca, filtros avançados, chips de região, filtro/selo "meu interesse" e destaque das vagas próprias.
- **Publicar vaga**: escolher/cadastrar endereço (CEP via ViaCEP + geocoding), formulário completo, clonar, **editar/pausar/excluir** (com marca "Atualizado em").
- **Detalhe**: candidatar-se, ver perfil do entregador, avaliar, compartilhar no WhatsApp.
- **Parcerias**: aceitar/recusar candidatos, confirmar parceria (dos dois lados) → vaga "preenchida"; chat por conversa.
- **Notificações** in-app (nova candidatura, mensagem, parceria/turno).
- **Conta**: perfil (avatar, dados, avaliações), veículo + documentos (privados), endereços, configurações (tema dark/light/urbano, notificações, e-mail/senha), histórico e **mapa** das vagas.

## Arquitetura

- **Models/Migrations** (inglês): `profiles`, `shifts`, `applications`, `chats`, `messages`, `reviews`, `notifications`, `user_addresses`, `user_settings`, `shift_contacts`, `documents`.
- **Observers** substituem os triggers do Postgres original: provisionar perfil no cadastro, recalcular média de avaliações, e notificações (candidatura, mensagem, aceite).
- **Regras de negócio** centralizadas em `App\Support\Partnerships` (interesse → aceite → confirmação → preenchida).
- **Componentes Blade** reutilizáveis em `resources/views/components` (`ui.*`, cards, modais, etc.); telas em `resources/views/livewire`.

## Testes

```bash
php artisan test
```

Suíte de feature (PHPUnit) cobrindo autenticação, vagas (CRUD/filtros/candidatura/avaliação/edição/pausa), parcerias/chat, notificações, conta, veículo/documentos, endereços, histórico, mapa e as regras de autorização.

## Notas

- Documentos (RG/CNH) ficam em disco **privado**, servidos apenas ao dono via rota autorizada.
- O mapa exibe vagas **geolocalizadas**; novos endereços são geocodificados (Nominatim/OpenStreetMap) ao salvar.
