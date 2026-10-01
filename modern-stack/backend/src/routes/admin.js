import express from 'express';
import { getAllReservations, getAllStudents, getAdminUsers, getPendingReservations, getLockerInventory, approveReservationByAdmin, rejectReservationByAdmin, releaseLockerByAdmin } from '../services/adminService.js';

const router = express.Router();

function adminOnly(req, res, next) {
  const role = req.headers['x-user-role'];
  if (role !== 'admin' && role !== 'superadmin' && role !== 'registrar') {
    return res.status(403).json({ message: 'Admin access required' });
  }
  next();
}

router.get('/records', adminOnly, async (req, res) => {
  try {
    const reservations = await getAllReservations();
    return res.json({ reservations });
  } catch (err) {
    return res.status(500).json({ message: 'Failed to fetch reservation records', error: err.message });
  }
});

router.get('/students', adminOnly, async (req, res) => {
  try {
    const students = await getAllStudents();
    return res.json({ students });
  } catch (err) {
    return res.status(500).json({ message: 'Failed to fetch students', error: err.message });
  }
});

router.get('/admins', adminOnly, async (req, res) => {
  try {
    const admins = await getAdminUsers();
    return res.json({ admins });
  } catch (err) {
    return res.status(500).json({ message: 'Failed to fetch admin users', error: err.message });
  }
});

router.get('/pending-reservations', adminOnly, async (req, res) => {
  try {
    const pending = await getPendingReservations();
    return res.json({ pending });
  } catch (err) {
    return res.status(500).json({ message: 'Failed to fetch pending reservations', error: err.message });
  }
});

router.get('/lockers', adminOnly, async (req, res) => {
  try {
    const inventory = await getLockerInventory();
    return res.json({ inventory });
  } catch (err) {
    return res.status(500).json({ message: 'Failed to fetch locker inventory', error: err.message });
  }
});

router.post('/reservations/:id/approve', adminOnly, async (req, res) => {
  try {
    const reservation = await approveReservationByAdmin(parseInt(req.params.id, 10));
    return res.json({ ok: true, reservation });
  } catch (err) {
    return res.status(400).json({ message: err.message || 'Failed to approve reservation' });
  }
});

router.post('/reservations/:id/reject', adminOnly, async (req, res) => {
  try {
    const reservation = await rejectReservationByAdmin(parseInt(req.params.id, 10));
    return res.json({ ok: true, reservation });
  } catch (err) {
    return res.status(400).json({ message: err.message || 'Failed to reject reservation' });
  }
});

router.post('/lockers/:id/release', adminOnly, async (req, res) => {
  try {
    const locker = await releaseLockerByAdmin(parseInt(req.params.id, 10));
    return res.json({ ok: true, locker });
  } catch (err) {
    return res.status(400).json({ message: err.message || 'Failed to release locker' });
  }
});

export default router;
