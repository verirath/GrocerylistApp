# 🛒 GrocerylistApp  
Eine einfache Einkaufslisten-App mit **Symfony Backend**, **Vue 3 Frontend** und **MySQL-Datenbank**, vollständig lauffähig über **Docker**.

---

# 🚀 Projektstruktur

```
/
├── backend/        # Symfony API
├── frontend/       # Vue 3 SPA
├── docker-compose.yml
```

---

# 🐳 Docker Setup (empfohlen)

Mit Docker kannst du das komplette Projekt (Backend, Frontend & Database) mit **einem Befehl** starten.

## Voraussetzungen
- Docker  
- Docker Compose  

---

# ▶️ Projekt starten

Im Projekt-Root:

```bash
docker compose up --build
```

Docker startet drei Services:

| Service | Beschreibung | URL |
|--------|--------------|-----|
| **backend** | Symfony API | http://localhost:8000 |
| **frontend** | Vue 3 App | http://localhost:5173 |
| **db** | MySQL 8 Datenbank | Host: localhost, Port: 3306 |

---

# 🗄 Datenbank einrichten

Beim **ersten Start** muss die Datenbank erstellt und migriert werden:

```bash
docker compose exec backend php bin/console doctrine:database:create
docker compose exec backend php bin/console doctrine:migrations:migrate
```

✔ Die Datenbank wird automatisch in einem Docker Volume gespeichert (`db_data`).  
✔ Sie bleibt dauerhaft erhalten – selbst wenn du Docker neu startest.

---

# 🔧 Konfiguration

## Backend (Symfony)

Die Datenbankverbindung wird automatisch über Docker gesetzt:

```
DATABASE_URL="mysql://grocery:secret@db:3306/grocerylist?serverVersion=8.0"
```

Du brauchst **keine** `.env.local` für die DB-Konfiguration.

### Backend erreichen
```
http://localhost:8000
```

---

## Frontend (Vue 3)

Die API-URL wird über eine Docker-Umgebungsvariable gesetzt:

```
VITE_API_BASE_URL=http://localhost:8000
```

In der Datei `frontend/src/api/shoppingListApi.ts` wird die URL so gelesen:

```ts
const API_BASE =
  import.meta.env.VITE_API_BASE_URL ?? "http://localhost:8000";
```

### Frontend erreichen
```
http://localhost:5173
```

---

# ⛽ Entwicklung ohne Docker (optional)

Falls du lokal entwickeln möchtest:

### Backend
```bash
cd backend
composer install
symfony server:start -d
```

### Frontend
```bash
cd frontend
npm install
npm run dev
```

# 📱 App Features

### ✔ Listenübersicht
- Alle Listen anzeigen  
- Neue Liste erstellen  
- Liste öffnen

### ✔ Listen-Detailansicht
- **Einkaufsmodus** (abhaken, direkt speichern)
- **Bearbeitungsmodus** (Name ändern, Items bearbeiten, löschen, hinzufügen)
- Gesamte Liste löschen

# 📄 Lizenz

MIT
