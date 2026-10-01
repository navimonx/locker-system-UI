# Locker System — Modern Stack (React + Express + MySQL)

This is a modern JavaScript rewrite of the existing PHP locker management system.

## Project Structure

```
modern-stack/
├── frontend/          # React + Vite frontend app
│   ├── src/
│   │   ├── App.jsx    # Main routing and pages
│   │   ├── styles.css # Global styles (matching original design)
│   │   └── main.jsx   # React entry point
│   ├── vite.config.js
│   └── package.json
├── backend/           # Express API server
│   ├── server.js      # Main server entry
│   ├── src/
│   │   ├── db.js                      # MySQL connection pool
│   │   ├── services/
│   │   │   ├── userService.js         # User CRUD operations
│   │   │   ├── lockerService.js       # Locker queries
│   │   │   └── reservationService.js  # Reservation operations
│   │   ├── routes/
│   │   │   ├── auth.js                # /api/auth endpoints
│   │   │   ├── lockers.js             # /api/lockers endpoints
│   │   │   └── reservations.js        # /api/reservations endpoints
│   │   └── utils/
│   │       └── auth.js                # Password hashing, token generation
│   ├── .env.example
│   └── package.json
└── README.md
```

## Database

Uses the same MySQL schema as the original PHP system:
- `Users` table
- `Lockers` table
- `Reservations` table
- `AllowedStudentIds` table

## Getting Started

### Prerequisites
- Node.js 16+
- MySQL 5.7+
- The original `locker_system.sql` database imported

### Backend Setup

```bash
cd modern-stack/backend
npm install
cp .env.example .env
```

Edit `.env`:
```env
DB_HOST=localhost
DB_USER=root
DB_PASSWORD=your_password
DB_NAME=locker_system
PASSWORD_SALT=your-unique-salt-key
```

Start the server:
```bash
npm run dev
```

API runs on `http://localhost:4000`

### Frontend Setup

```bash
cd modern-stack/frontend
npm install
npm run dev
```

UI runs on `http://localhost:5173`

## API Endpoints

### Authentication
- `POST /api/auth/login` - Student login
- `POST /api/auth/signup` - Student registration

### Lockers
- `GET /api/lockers/departments` - List all departments
- `GET /api/lockers/:department` - Lockers in a department
- `GET /api/lockers/:department/:floor` - Lockers on a specific floor
- `GET /api/lockers/stats` - Locker availability stats

### Reservations
- `GET /api/reservations/user/:userId` - User's reservations
- `POST /api/reservations` - Create a reservation
- `POST /api/reservations/:id/cancel` - Cancel a pending reservation
- `POST /api/reservations/:id/approve` - Admin: approve reservation
- `POST /api/reservations/:id/release` - Admin: release a locker
- `GET /api/reservations/pending` - Admin: view all pending reservations

## Current Phase Status

✅ Phase 1: Project structure and placeholder pages  
✅ Phase 2: Auth pages and student/admin flows  
✅ Phase 3: Database integration and API routes

## Next Phases

- Phase 4: Admin records and locker management endpoints
- Phase 5: UI polish and final integration testing
- Phase 6: Deployment and migration guide
