import express from 'express';
import { getUserReservations, createReservation, cancelReservation, approveReservation, releaseReservation, getPendingReservations, getReservationById } from '../services/reservationService.js';

const router = express.Router();

// Middleware to validate JWT (simplified)
function authMiddleware(req, res, next) {
  const userId = req.headers['x-user-id'];
  if (!userId) {
    return res.status(401).json({ message: 'Unauthorized' });
  }
  req.userId = parseInt(userId, 10);
  next();
}

router.get('/user/:userId', async (req, res) => {
  try {
    const { userId } = req.params;
    const reservations = await getUserReservations(parseInt(userId, 10));
    return res.json(reservations);
  } catch (err) {
    console.error('Error fetching reservations:', err);
    return res.status(500).json({ message: 'Failed to fetch reservations', error: err.message });
  }
});

router.post('/', authMiddleware, async (req, res) => {
  try {
    const { lockerId, duration } = req.body;
    const { userId } = req;

    if (!lockerId) {
      return res.status(400).json({ message: 'Locker ID required' });
    }

    const reservation = await createReservation(userId, lockerId, duration || '1 Semester');
    return res.status(201).json({
      ok: true,
      reservation
    });
  } catch (err) {
    console.error('Error creating reservation:', err);
    return res.status(400).json({ message: err.message || 'Failed to create reservation' });
  }
});

router.post('/:reservationId/cancel', authMiddleware, async (req, res) => {
  try {
    const { reservationId } = req.params;
    const { userId } = req;

    await cancelReservation(parseInt(reservationId, 10), userId);
    return res.json({
      ok: true,
      message: 'Reservation cancelled'
    });
  } catch (err) {
    console.error('Error cancelling reservation:', err);
    return res.status(400).json({ message: err.message || 'Failed to cancel reservation' });
  }
});

router.post('/:reservationId/approve', authMiddleware, async (req, res) => {
  try {
    const { reservationId } = req.params;
    const reservation = await approveReservation(parseInt(reservationId, 10));
    return res.json({
      ok: true,
      reservation
    });
  } catch (err) {
    console.error('Error approving reservation:', err);
    return res.status(400).json({ message: err.message || 'Failed to approve reservation' });
  }
});

router.post('/:reservationId/release', authMiddleware, async (req, res) => {
  try {
    const { reservationId } = req.params;
    const reservation = await releaseReservation(parseInt(reservationId, 10));
    return res.json({
      ok: true,
      reservation
    });
  } catch (err) {
    console.error('Error releasing reservation:', err);
    return res.status(400).json({ message: err.message || 'Failed to release reservation' });
  }
});

router.get('/pending', async (req, res) => {
  try {
    const reservations = await getPendingReservations();
    return res.json(reservations);
  } catch (err) {
    console.error('Error fetching pending reservations:', err);
    return res.status(500).json({ message: 'Failed to fetch pending reservations', error: err.message });
  }
});

export default router;
