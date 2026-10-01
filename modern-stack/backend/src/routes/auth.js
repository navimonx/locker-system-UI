import express from 'express';
import { authenticateUser, createUser, findUserByStudentId, findUserByEmail, findUserByContact } from '../services/userService.js';

const router = express.Router();

function validateStudentId(id) {
  return /^\d{2}-\d{4}$/.test(id);
}

router.post('/login', async (req, res) => {
  try {
    const { studentId, password } = req.body;

    if (!studentId || !password) {
      return res.status(400).json({ message: 'Student ID and password required' });
    }

    const user = await authenticateUser(studentId, password);

    if (!user) {
      return res.status(401).json({ message: 'Invalid ID or password' });
    }

    return res.json({
      ok: true,
      user
    });
  } catch (err) {
    console.error('Login error:', err);
    return res.status(500).json({ message: 'Login failed', error: err.message });
  }
});

router.post('/signup', async (req, res) => {
  try {
    const { studentId, firstName, lastName, email, contact, course, password, confirmPassword } = req.body;

    if (!studentId || !firstName || !lastName || !email || !contact || !password) {
      return res.status(400).json({ message: 'All fields required' });
    }

    if (!validateStudentId(studentId)) {
      return res.status(400).json({ message: 'Invalid student ID format (XX-XXXX)' });
    }

    if (password !== confirmPassword) {
      return res.status(400).json({ message: 'Passwords do not match' });
    }

    if (password.length < 6) {
      return res.status(400).json({ message: 'Password must be at least 6 characters' });
    }

    const existing = await findUserByStudentId(studentId);
    if (existing) {
      return res.status(409).json({ message: 'Student ID already has an account' });
    }

    const emailExists = await findUserByEmail(email);
    if (emailExists) {
      return res.status(409).json({ message: 'Email already in use' });
    }

    const contactExists = await findUserByContact(contact);
    if (contactExists) {
      return res.status(409).json({ message: 'Contact number already in use' });
    }

    const user = await createUser({
      studentId,
      firstName,
      lastName,
      email,
      contact,
      course,
      password,
      role: 'student'
    });

    return res.status(201).json({
      ok: true,
      user: {
        id: user.id,
        studentId: user.studentId,
        name: `${user.firstName} ${user.lastName}`.trim(),
        role: user.role
      }
    });
  } catch (err) {
    console.error('Signup error:', err);
    return res.status(500).json({ message: 'Signup failed', error: err.message });
  }
});

export default router;
