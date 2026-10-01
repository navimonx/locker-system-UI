import express from 'express';
import cors from 'cors';
import dotenv from 'dotenv';
import { testConnection } from './src/db.js';
import authRoutes from './src/routes/auth.js';
import lockerRoutes from './src/routes/lockers.js';
import reservationRoutes from './src/routes/reservations.js';
import adminRoutes from './src/routes/admin.js';

dotenv.config();

const app = express();
const PORT = process.env.PORT || 4000;

app.use(cors());
app.use(express.json());

app.get('/api/health', async (req, res) => {
  const dbOk = await testConnection();
  res.json({
    ok: true,
    message: 'Locker system API is running',
    stack: 'React + Express + MySQL',
    database: dbOk ? 'connected' : 'disconnected'
  });
});

app.use('/api/auth', authRoutes);
app.use('/api/lockers', lockerRoutes);
app.use('/api/reservations', reservationRoutes);
app.use('/api/admin', adminRoutes);

app.use((err, req, res, next) => {
  console.error('Unhandled error:', err);
  res.status(500).json({
    message: 'Internal server error',
    error: process.env.NODE_ENV === 'development' ? err.message : undefined
  });
});

app.listen(PORT, () => {
  console.log(`\n✅ Backend running on http://localhost:${PORT}`);
  console.log(`📚 API health check: http://localhost:${PORT}/api/health\n`);
});
