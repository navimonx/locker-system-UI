import express from 'express';
import { getLockersByDepartment, getLockersByFloor, getDepartments, getLockerStats } from '../services/lockerService.js';

const router = express.Router();

router.get('/departments', async (req, res) => {
  try {
    const departments = await getDepartments();
    return res.json(departments);
  } catch (err) {
    console.error('Error fetching departments:', err);
    return res.status(500).json({ message: 'Failed to fetch departments', error: err.message });
  }
});

router.get('/stats', async (req, res) => {
  try {
    const stats = await getLockerStats();
    return res.json(stats);
  } catch (err) {
    console.error('Error fetching stats:', err);
    return res.status(500).json({ message: 'Failed to fetch stats', error: err.message });
  }
});

router.get('/:department', async (req, res) => {
  try {
    const { department } = req.params;
    const lockers = await getLockersByDepartment(department.toUpperCase());
    return res.json(lockers);
  } catch (err) {
    console.error('Error fetching lockers:', err);
    return res.status(500).json({ message: 'Failed to fetch lockers', error: err.message });
  }
});

router.get('/:department/:floor', async (req, res) => {
  try {
    const { department, floor } = req.params;
    const lockers = await getLockersByFloor(department.toUpperCase(), parseInt(floor, 10));
    return res.json(lockers);
  } catch (err) {
    console.error('Error fetching floor lockers:', err);
    return res.status(500).json({ message: 'Failed to fetch lockers', error: err.message });
  }
});

export default router;
